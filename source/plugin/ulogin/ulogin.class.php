<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class plugin_ulogin{
       
    function plugin_ulogin(){
    }
    
    function global_login_extra(){
        global $_G;
        $redirect_url = $_G['siteurl'].'token.php';
        return '<script src="http://ulogin.ru/js/ulogin.js?stop"></script>
                <a style="float:right;margin-top:8px;" href="#" id="uLoginWin" x-ulogin-params="display=window;fields=nickname,first_name,last_name,email,bdate,sex,photo;optional=phone;redirect_uri='.$redirect_url.'"><img src="http://ulogin.ru/img/feat1.png" width=45px height=45px alt="МультиВход"/></a>
                    <script>if (typeof uLogin != "undefined") uLogin.initWidget("uLoginWin");</script>';
    }

}

class plugin_ulogin_member extends plugin_ulogin{
    function logging_method(){
        global $_G;
        $redirect_url = $_G['siteurl'].'token.php';
        return '<div><a href="#" id="uLoginLogging" x-ulogin-params="display=window;fields=nickname,first_name,last_name,email,bdate,sex,photo;optional=phone;redirect_uri='.$redirect_url.'"><img src="http://ulogin.ru/img/button.png" width=187 height=30 alt="МультиВход"/></a></div>
                <script>if (typeof uLogin != "undefined") uLogin.initWidget("uLoginLogging");</script>';
    }
}

?>
