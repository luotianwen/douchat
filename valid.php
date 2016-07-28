<?php 
if (!isset($_GET['site_url']) || !isset($_GET['user_name']) || !isset($_GET['timestamp'])) {
	die('请勿直接访问本地址');
}
$site_url = $_GET['site_url'];
$username = $_GET['user_name'];
$timestamp = $_GET['timestamp'];
$tempArr = array($site_url,$username,$timestamp);
sort($tempArr,SORT_STRING);
$tempStr = sha1(implode($tempArr));
echo $tempStr;

?>