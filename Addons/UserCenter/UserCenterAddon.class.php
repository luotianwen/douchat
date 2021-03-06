<?php

namespace Addons\UserCenter;

use Common\Controller\Addon;

/**
 * 用户中心插件
 *
 * @author 艾逗笔
 */
class UserCenterAddon extends Addon {
	public $info = array (
			'name' => 'UserCenter',
			'title' => '用户中心',
			'description' => '实现3G首页、微信登录，微信用户绑定，微信用户信息初始化等基本功能',
			'status' => 1,
			'author' => '艾逗笔',
			'has_adminlist' => 1,
			'version' => '1.0' 
	);
	public $admin_list = array ();
	public function install() {
		return true;
	}
	public function uninstall() {
		return true;
	}
	
	// 实现的weixin钩子方法
	public function weixin($param) {
	}
}