<?php

require_once './source/function/function_member.php';
require_once './source/function/function_profile.php';

class ulogin{
    
    private $_token_url = 'http://ulogin.ru/token.php?token={t}&host={h}';
    
    private $_token;
    
    private $_uid;
    
    private $_identity;
    
    private $_userdata;
    
    private $_host;
    
    private $_error;
    
    private $_member;
    
    function __construct($token) {
        if (strlen($token) == 32){
            $this->_token = $token;
            $this->_userdata = array();
        }
    }
    
    function receiveUserData($host = ''){
        if (isset($host) && !empty ($host)){
            $this->_host = $host;
        }else{
            $this->_host = $_SERVER['HTTP_HOST'];
        }
        
        if (function_exists('file_get_contents')){
            $rawdata = file_get_contents($this->_setApiUrl());
            if ($rawdata){
                $rawdata = json_decode($rawdata, true);
                if (isset($rawdata['error']))
                    $this->_error = $rawdata['error'];
                else{
                    $this->_userdata = $rawdata;
                    $this->_identity = $rawdata['identity'];
                }
            }else{
                $this->_error = 'Unreachable location.';
            }
        }else{
            $this->_error = 'Function "file_get_contents" required';
        }
    }

    function checkUser(){
        $this->_uid = DB::result_first("SELECT uid FROM ".DB::table('ulogin_member')." where identity = '".$this->_identity."'");
        if ($this->_uid){
            $this->_member = DB::fetch_first("SELECT * FROM ".DB::table('common_member')." WHERE uid = ".$this->_uid);
            if ($this->_member){
                $this->_addUserProfile();
                setloginstatus($this->_member, time());
                return 'exist';
            }
            return 'deleted';
        }else 
            return 'not found';
    }
    
    function addUser(){
        $identity_parts = parse_url($this->_userdata['identity']);
        $User['username'] = isset($this->_userdata['nickname']) ? $this->_userdata['nickname'] : $this->_userdata['first_name'].'_'.$this->_userdata['last_name'];
        $User['password'] = md5($this->_userdata['email'].'Yx'.$identity_parts['path'].'Qul'.$this->_userdata['network'].'1a'.$this->_userdata['uid']);
        $User['email'] = $this->_userdata['email'];
        while ($result = DB::result_first("SELECT uid FROM ".DB::table('common_member')." WHERE username='".$User['username']."'")){
            $User['username'] = isset($this->_userdata['nickname']) ? $this->_userdata['nickname'].'_'.time() : $this->_userdata['first_name'].'_'.time();
        }
   
        if (DB::result_first("SELECT uid FROM ".DB::table('common_member')." WHERE email='".$User['email']."'")){
            $email_parts = explode('@', $this->_userdata['email']);
            $User['email'] = $email_parts[0].'_ul_'.$this->_userdata['uid'].'@'.$email_parts[1];
        }

        $uid = DB::insert('common_member', $User,  true);
        
        if ($uid){
            if ($this->_uid)
                DB::update('ulogin_member', array('uid'=> $uid), "identity='".$this->_identity."'");
            else{
                DB::insert('ulogin_member', array('uid'=>$uid,'identity'=>$this->_identity));
            }
            $this->_uid = $uid;
            $this->_addUserProfile();
            $this->_member = DB::fetch_first("SELECT * FROM ".DB::table('common_member')." WHERE uid=".$uid);
            setloginstatus($this->_member, time());
        }
    }
    
    function getError(){
        return $this->_error;
    }
    
    private function _setApiUrl(){
        $result = str_replace('{t}', $this->_token, $this->_token_url);
        return str_replace('{h}', urlencode($this->_host), $result);
    }
    
    private function _addUserProfile(){
        
        $bdate = explode('.',$this->_userdata['bdate']);
        $profile = array();
        $profile['uid']             =   $this->_uid;   
        $profile['birthday']        =   $bdate[0];
        $profile['birthmonth']      =   $bdate[1];
        $profile['birthyear']       =   $bdate[2];
        $profile['gender']          =   $this->_userdata['sex'] == '2' ? 1 : 2;
        $profile['realname']        =   $this->_userdata['first_name'].' '.$this->_userdata['last_name'];
        $profile['zodiac']          =   get_zodiac($bdate[2]);
        $profile['constellation']   =   get_constellation($bdate[1],$bdate[0]);
        $exist = DB::result_first("SELECT gender FROM ".DB::table('common_member_profile')." WHERE uid=".$this->_uid);
        if (!$exist){
            DB::insert('common_member_profile', $profile);   
        }
    }
    
}
?>
