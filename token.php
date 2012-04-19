<?php

require_once './source/class/class_core.php';
require_once './source/class/class_ulogin.php';

$discuz = & discuz_core::instance();
$discuz->init();

if (!$_G['uid'] && isset($_POST['token'])){
    
    $ulogin = new ulogin($_POST['token']);
    $ulogin->receiveUserData($_SERVER['HTTP_HOST']);
    if (!$ulogin->getError()){
        $result = $ulogin->checkUser();
        if ($result != 'exist'){
            $ulogin->addUser();
        }
    }
 }
header("Location:".$_G['siteurl']);

?>
