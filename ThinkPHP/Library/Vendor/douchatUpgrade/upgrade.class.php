<?php 

// +----------------------------------------------------------------------
// | online_upgrade [ 在线升级类 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://idoubi.sinaapp.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艾逗笔 <765532665@qq.com> <http://idoubi.sinaapp.com>
// +----------------------------------------------------------------------	

class upgrade {

	/**
	 * 构造函数
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function __construct() {

	}

	/**
	 * 将二进制文件转换成zip文件
	 * @param $bin_path String 二进制文件路径
	 * @param $zip_path String zip文件存放路径
	 * @return $zip_path String zip文件存放路径
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function bin2zip($bin_path, $zip_path) {
		$bin_contents = file_get_contents($bin_path);
		$bytes = file_put_contents($zip_path, $bin_contents);
		if ($bytes !== false) {
			return $zip_path;
		} else {
			return false;
		}
	}

	/**
	 * 解压缩zip文件
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function unzip($zip_path, $unzip_path) {
		$zip = new ZipArchive();
		if ($zip->open($zip_path) === true) {
			$zip->extractTo($unzip_path);
			$zip->close();
			return $unzip_path;
		}
	}

	/**
	 * 读取压缩包内容
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function read_zip($zip_path) {
		$zip = zip_open($zip_path);
		$list = array();
		if ($zip) {
			while ($zip_entry = zip_read($zip)) {
				// var_dump(zip_entry_name($zip_entry));
				if (!preg_match('/.*\/$/', zip_entry_name($zip_entry))) {
					// if (preg_match('/douchat/', zip_entry_name($zip_entry))) {
					// 	$list[] = str_replace('douchat', '', zip_entry_name($zip_entry));
					// } else {
					// 	$list[] = '/' . zip_entry_name($zip_entry);
					// }
					$list[] = zip_entry_name($zip_entry);
				}
				// preg_match('/(.*)Addon\.class\.php/', zip_entry_name($zip_entry), $m);
				
				// if ($m && !strpos($m[1], '/')) {
				// 	$addon_name = $m[1];
				// 	if (zip_entry_open($zip, $zip_entry, "r")) {
				// 		$addon_file_contents = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

				// 		preg_match('/\'name\'=>\'(.*)\'/', $addon_file_contents, $bzname);
				// 		preg_match('/\'title\'=>\'(.*)\'/', $addon_file_contents, $name);
				// 		preg_match('/\'description\'=>\'(.*)\'/', $addon_file_contents, $desc);
				// 		preg_match('/\'version\'=>\'(.*)\'/', $addon_file_contents, $version);

				// 	}
				// 	break;
				// }

			}
			return $list;
		} else {
			return false;
		}
	}

	/**
	 * 生成map信息
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function get_dir_map($dir_list) {
		
		$item = array();
		$map = array();
		$prefix_path = '';
		$m = array();
		foreach ($dir_list as $k => $v) {
			preg_match('/(.*)\/Addons/', $v, $m);
			if ($m && $m[1]) {
				$prefix_path = $m[1];
				break;
			}
		}

		foreach ($dir_list as $k => $v) {
			$item['path'] = str_replace($prefix_path, '', $v);
			$item['checksum'] = $this->get_file_md5($v);
			$map[] = $item;
		}

		return json_encode($map);
	}

	/**
	 * 读取文件夹的目录层次
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function list_dir($dir){
		$list = array();
		if (is_dir($dir)){
			$file_dir = scandir($dir);
			foreach($file_dir as $file){
				if ($file == '.' || $file == '..'){
					continue;
				}
				elseif (is_dir($dir.$file)){
					$list = array_merge($list, $this->list_dir($dir.$file.'/'));
				}
				else{
					array_push($list, $dir.$file);
				}
			}
		}
		return $list;
	}

	/**
	 * 获取文件的校验码
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function get_file_md5($file) {
		return md5_file($file);
	}

	/**
	 * 删除文件
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function delete_file($file) {
		if (@unlink($file) == false) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * 删除文件夹
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function delete_dir($dir) {
	    //先删除目录下的文件：
	    $dh = opendir($dir);
	    while ($file = readdir($dh)) {
	      if ($file != "." && $file != "..") {
	        $fullpath = $dir . "/" . $file;
	        if (!is_dir($fullpath)) {
	          unlink($fullpath);
	        } else {
	          $this->delete_dir($fullpath);
	        }
	      }
	    }
	 
	    closedir($dh);
	    //删除当前文件夹：
	    if (rmdir($dir)) {
	      return true;
	    } else {
	      return false;
	    }
	}

	/**
	 * 检测文件
	 * @author 艾逗笔<765532665@qq.com>
	 */
	public function check_file($path, $md5, $check_path) {
		if (!is_file($check_path)) {
			return 'A';
		}

		if ($this->get_file_md5($check_path) != $md5) {
			return 'M';
		}

		return false;
	}

	/**
	 * 复制文件
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

	public function test() {
		var_dump(111);
	}
}

// $test = new online_upgrade();
//$zip_path = $test->bin2zip('http://douchat.cc/uploads/shop/20160312/b6c852a782701834e5ee82c5b6fa8571', '../douchat.back/Data/Test.zip');
// $zip_path = '../douchat/douchat.zip';

// $temp_unzip_path = $test->unzip($zip_path, '../douchat.back/Data/Test/');
// $dir_list = $test->list_dir($temp_unzip_path);
// $map = $test->get_dir_map($dir_list, '../douchat.back/Data/Test');
// file_put_contents('map.json', $map);
// var_dump($map);die;
// $upgrade = array();
// foreach ($dir_list as $k => $v) {
// 	$check_result = $test->check_file($v, $test->get_file_md5($v), str_replace('/Data/', '/Addons/', $v));
// 	if ($check_result) {
// 		$file_arr['name'] = basename($v);
// 		$file_arr['oprate'] = $check_result;
// 		$file_arr['origin_path'] = $v;
// 		$file_arr['path'] = str_replace('/Data/', '/Addons/', $v);	
// 		$upgrade[] = $file_arr;	
// 	}
// }
// var_dump($upgrade);die;
// foreach ($upgrade as $k => $v) {
// 	$result = $test->copy_file($v['origin_path'], $v['path']);
// 	if ($result) {
// 		echo '更新文件' . $v['path'] . '成功';
// 	} else {
// 		echo '更新文件' . $v['path'] . '失败';
// 		break;
// 	}
// }

?>