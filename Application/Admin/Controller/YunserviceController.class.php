<?php

namespace Admin\Controller;

/**
 * 云服务控制器
 * @author 艾逗笔<765532665@qq.com>
 */
class YunserviceController extends AdminController {
	var $web_info = array ();
	protected function _initialize() {
		$this->assign ( '__MENU__', $this->getMenus () );
		
		C ( 'SESSION_PREFIX', 'douchat_home' );
		C ( 'COOKIE_PREFIX', 'douchat_home_' );
		
		$host_url = urldecode ( $_GET ['callback'] );
		if ($host_url) {
			$this->assign ( 'host_url', $host_url );
			session ( 'host_url', $host_url );
		}
	}

	/**
	 * 检测站点是否绑定
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function check_bind() {
		$site_info_path = realpath('./') . '/Data/site.info';
		if (!is_file($site_info_path)) {
			$this->error('你的站点暂未注册云服务，请先去绑定站点！', U('siteinfo'));
		} else {
			$site_info = file_get_contents($site_info_path);
			$site_info = json_decode($site_info, true);
			if ($site_info['admin_uid'] && $site_info['site_token']) {
				preg_match('/(.*)\/index\.php/', U('index'), $m);
				$site_info['site_url'] = $m[1];
				$site_info['site_ip'] = $_SERVER["SERVER_ADDR"];
				return $site_info;
			} else {
				$this->error("站点绑定信息不完整，请重新绑定站点");
			}
		}
	}

	/**
	 * 云服务首页
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function index() {
		$this->display ('notice');
	}

	/**
	 * 系统公告
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function notice() {
		$this->display();
	}

	/**
	 * 在线更新
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function upgrade() {
		$site_info = $this->check_bind();
		$this->assign('site_info', $site_info);
		
		$fversion = file_get_contents('http://douchat.cc/?/interface/last_fversion/site_url-'.$site_info['site_url'].'__site_token-'.$site_info['site_token'].'__uid-'.$site_info['admin_uid']);
	
		$fversion = substr($fversion, 1,strlen($fversion)-2);
		$fversion = json_decode($fversion, true);
		if ($fversion['errcode'] != 1111) {
			$this->error($fversion['errmsg'], U('siteinfo'));
		}
		$json = file_get_contents($fversion['data']['map_url']);
		
		$file_list = json_decode($json,true);
		$upgrade_file = array();
		foreach ($file_list as $file) {
			if ($file['path'] == '/Application/Common/Conf/config.php') {
				continue;
			}

			$realpath = realpath('./').$file['path'];
			$temp = array();
			if (!is_file($realpath)) {
				$temp['method'] = 'A';
				$temp['path'] = $file['path'];
				$upgrade_file[] = $temp;
			}
			else {
				if ($file['checksum'] != md5_file($realpath)) {
					$temp['method'] = 'M';
					$temp['path'] = $file['path'];
					$upgrade_file[] = $temp;
				}
			}
		}
		
		$upgrade_info['version_id'] = $fversion['data']['id'];
		$upgrade_info['map'] = $upgrade_file;
		S('upgrade_info', json_encode($upgrade_info), 300);
		$this->assign('upgrade_file',$upgrade_file);

		$this->assign('version',$fversion['data']['version']);
		$this->assign('add_time',date('Y-m-d',$fversion['data']['add_time']));
		$this->assign('code_url',$fversion['data']['code_url']);
		$this->display();
	}

	public function siteinfo() {
		$site_info_path = realpath('./') . '/Data/site.info';
		if (IS_AJAX) {
			$data['site_name'] = I('site_name');
			$data['site_token'] = I('site_token');
			$data['admin_uid'] = I('admin_uid');
			$data['add_time'] = I('add_time');
			$site_info = json_encode($data);
			
			if (!is_file($site_info_path)) {
				touch($site_info_path, 0777);
			}
			$res = file_put_contents($site_info_path, $site_info);
			if ($res) {
				$result['errcode'] = 1;
				$result['errmsg'] = '保存站点信息成功';
			} else {
				$result['errcode'] = 0;
				$result['errmsg'] = '保存站点信息失败';
			}

			$this->ajaxReturn($result);
		}

		if (is_file($site_info_path)) {
			$site_info = file_get_contents($site_info_path);
			$site_info = json_decode($site_info, true);
			if ($site_info['admin_uid'] && $site_info['site_token']) {
				$this->assign('is_site_bind', 1);
				$this->assign('site_info', $site_info);
			} else {
				$this->assign('is_site_bind', 0);
			}
		}

		preg_match('/(.*)\/index\.php/', U('index'), $m);
		$site_url = $m[1];
		$this->assign('site_url',$site_url);
		$this->assign('site_ip', $_SERVER["SERVER_ADDR"]);
		$this->display();
	}

	public function do_upgrade() {
		if (IS_AJAX) {
			$local_path = realpath('./') . I('file_path');
			$file_path = 'http://douchat.cc/?/interface/framework_file_download/version_id-'.I('version_id').'__file_path-'.base64_encode(I('file_path'));
			if (!is_file($local_path)) {
				touch($local_path, 0777);
			}

			
			chmod($local_path, 0777);
			


			$res = copy($file_path, $local_path);
			if ($res) {
				$result['errcode'] = 1111;
				$result['errmsg'] = 'success';
			} else {
				$result['errcode'] = 1000;
				$result['errmsg'] = 'fail';
			}
			
			$this->ajaxReturn($result);
		}

		$upgrade_info = S('upgrade_info');
		$upgrade_arr = json_decode($upgrade_info, true);
		$version_id = $upgrade_arr['version_id'];
		
		foreach ($upgrade_arr['map'] as $k => $v) {
			$files[] = $v['path'];
		}
		$this->assign('version_id', $version_id);
		$this->assign('files', implode(',', $files));
		$this->display();
	}

	/**
	 * 版本更新成功后，记录版本日志
	 */
	public function upgrade_version_log() {
		if (IS_AJAX) {
			$data['version_id'] = I('version_id');
			$data['upgrade_time'] = time();
			if (file_put_contents(realpath('./') . '/Data/version.info', json_encode($data))) {
				$data['errcode'] = 1111;
				$data['errmsg'] = '更新成功';
			} else {
				$data['errcode'] = 0000;
				$data['errmsg'] = '更新失败';
			}
			$this->ajaxReturn($data);
		}
		
	}

	/**
	 * 已购买应用
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function buyed() {
		$site_info = $this->check_bind();
		$this->assign('site_info', $site_info);
		$this->display();
	}

	/**
	 * 应用购买记录
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function record() {
		$site_info = $this->check_bind();
		$this->assign('site_info', $site_info);
		$this->display();
	}

	/**
	 * 我的账户
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function account() {
		$site_info = $this->check_bind();
		$this->assign('site_info', $site_info);
		$this->display();
	}

	/**
	 * 查看更多应用
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function moreapps() {
		$this->display();
	}

	/**
	 * 安装插件
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function install_addon() {
		if (IS_AJAX) {
			$content = file_get_contents('http://douchat.cc/?/interface/get_shop_info/?shop_id='.I('shop_id'));
			$content = substr($content, 1,strlen($content)-2);
			$shop_info_arr = json_decode($content,true);		// 请求接口获取需要安装的应用信息

			$content = file_get_contents('http://douchat.cc/?/interface/get_shop_version_map/shop_id-'.I('shop_id').'__branch_id-'.I('branch_id'));
			$content = substr($content, 1,strlen($content)-2);

			$file_list = json_decode($content,true);			// 请求接口获取需要安装的版本的文件信息
			$file_list = json_decode($file_list['map'], true);
			
			$install_file = array();
			foreach ($file_list as $file) {
				$realpath = realpath('./') . '/Addons/' . $shop_info_arr['bzname'] . $file['path'];
				$temp = array();
				if (!is_file($realpath)) {
					$temp['method'] = 'A';
					$temp['path'] = $file['path'];
					$install_file[] = $temp;					// 找出需要新增的文件
				} else {
					if ($file['checksum'] != md5_file($realpath)) {
						$temp['method'] = 'M';
						$temp['path'] = $file['path'];
						$install_file[] = $temp;				// 找出需要替换的文件
					}
				}
			}
			
			$install_info['shop_id'] = I('shop_id');
			$install_info['branch_id'] = I('branch_id');
			$install_info['map'] = $install_file;
			$install_info['url'] = U('install_addon', array('bzname'=>$shop_info_arr['bzname']));
			S($shop_info_arr['bzname'] . '_install_info', json_encode($install_info), 300);
			$this->ajaxReturn($install_info);
		}
		
		if (@$_GET['bzname']) {
			$bzname = $_GET['bzname'];
			$install_info = S($bzname . '_install_info');
			$install_arr = json_decode($install_info, true);
			$shop_id = $install_arr['shop_id'];
			$branch_id = $install_arr['branch_id'];

			foreach ($install_arr['map'] as $k => $v) {
				$files[] = $v['path'];
			}

			if (!$files) {
				redirect(U('Addons/install', array('addon_name'=>$bzname,'install_type'=>1)));
			}
			$this->assign('shop_id', $shop_id);
			$this->assign('bzname', $bzname);
			$this->assign('branch_id', $branch_id);
			$this->assign('files', implode(',', $files));
			$this->display();
		}

		
	}

	/**
	 * 逐个下载插件安装文件
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function do_install() {
		if (IS_AJAX) {

			$file_path = 'http://douchat.cc/?/interface/addon_file_download/shop_id-' . I('shop_id') . '__branch_id-'.I('branch_id').'__file_path-'.base64_encode(I('file_path'));
			$local_path = realpath('./') . '/Addons/' . I('bzname') . I('file_path');

			if (file_get_contents($file_path)) {
				$res = $this->copy_file($file_path, realpath('./') . '/Addons/' . I('bzname') . I('file_path'));
			} else {
				$res = false;
			}
			
			if ($res) {
				$result['errcode'] = 1111;
				$result['errmsg'] = '下载文件成功';
			} else {
				$result['errcode'] = 1000;
				$result['errmsg'] = '下载文件失败';
			}
			
			$this->ajaxReturn($result);
		}
	}

	/**
	 * 复制文件通用函数
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function copy_file($origin_path, $path) {
		if (!file_exists($path)) {
			$dirs = explode('/', $path);
			$dir = $dirs[0] . '/';
			for ($i=1, $j=count($dirs)-1; $i<$j; $i++) {
				$dir .= $dirs[$i] . '/';
				if (!is_dir($dir)) {
					mkdir($dir, 0777);
				}
			}
		}

		return copy($origin_path, $path);
	}

}
