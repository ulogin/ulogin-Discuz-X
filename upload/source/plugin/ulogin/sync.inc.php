<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once './source/class/class_core.php';
require_once './source/plugin/ulogin/class_ulogin.php';

$discuz = & discuz_core::instance();
$discuz->init();

global $_G;
if(!$_G['uid'])
{
	showmessage('not_loggedin', null, array(), array('login' => 1));
}

$panel = ulogin::getPanelCode(1);
$syncpanel = ulogin::getuloginUserAccountsPanel();

?>