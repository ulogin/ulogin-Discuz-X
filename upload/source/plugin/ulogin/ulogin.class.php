<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class plugin_ulogin{
       
    function plugin_ulogin(){}
    //Авторизация в шапке
    public static function global_login_extra(){
        return '<div style="float: right;margin-top: 5px">'.ulogin::getPanelCode().'</div><script type="text/javascript" charset="utf-8" src="//ulogin.ru/js/ulogin.js"></script>';
    }
}

class plugin_ulogin_member extends plugin_ulogin{
    //Экспресс вход
    public static function logging_method(){
        return '<div style="padding-top: 5px;">'.ulogin::getPanelCode().'</div><script type="text/javascript" charset="utf-8" src="//ulogin.ru/js/ulogin.js"></script>';
    }
    //Регистрация
    public static function register_logging_method(){
        return '<div style="padding-top: 5px;">'.ulogin::getPanelCode().'</div><script type="text/javascript" charset="utf-8" src="//ulogin.ru/js/ulogin.js"></script>';
    }
}

?>
