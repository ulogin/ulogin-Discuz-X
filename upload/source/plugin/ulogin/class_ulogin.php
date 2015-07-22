<?php

require_once './source/function/function_member.php';
require_once './source/function/function_profile.php';

class ulogin {

	/**
	 * Обменивает токен на пользовательские данные
	 *
	 * @param bool $token
	 *
	 * @return bool|mixed|string
	 */
	public static function uloginGetUserFromToken($token = false)
	{
		global $_G;
		$response = false;
		if ($token)
		{
			$data = array('cms' => 'discuz', 'version' => $_G['setting']['version']);
			$request = 'http://ulogin.ru/token.php?token='.$token.'&host='.$_SERVER['HTTP_HOST'].'&data='.base64_encode(json_encode($data));
			if (in_array('curl', get_loaded_extensions()))
			{
				$c = curl_init($request);
				curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
				$response = curl_exec($c);
				curl_close($c);
			}
			elseif (function_exists('file_get_contents') && ini_get('allow_url_fopen')) $response = file_get_contents($request);
		}
		return $response;
	}

	/**
	 * Возвращает текущий url
	 */
	public static function ulogin_get_current_page_url()
	{
		$pageURL = 'http';
		if (isset($_SERVER["HTTPS"]))
		{
			if ($_SERVER["HTTPS"] == "on")
			{
				$pageURL .= "s";
			}
		}
		$pageURL .= "://";
		if ($_SERVER["SERVER_PORT"] != "80")
		{
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		}
		else
		{
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		return $pageURL;
	}

	/**
	 * Проверка пользовательских данных, полученных по токену
	 *
	 * @param $u_user - пользовательские данные
	 *
	 * @return bool
	 */
	public function uloginCheckTokenError($u_user)
	{
		if (!is_array($u_user))
		{
			showmessage('Ошибка работы uLogin. Данные о пользователе содержат неверный формат');
			exit;
		}
		if (isset($u_user['error']))
		{
			$strpos = strpos($u_user['error'], 'host is not');
			if ($strpos)
			{
				showmessage('Ошибка работы uLogin. Адрес хоста не совпадает с оригиналом');
			}
			switch ($u_user['error'])
			{
				case 'token expired':
					showmessage('Ошибка работы uLogin. Время жизни токена истекло');
					exit;
				case 'invalid token':
					showmessage('Ошибка работы uLogin. Неверный токен');
					exit;
				default:
					showmessage('Ошибка работы uLogin. '.$u_user['error']);
					exit;
			}
		}
		if (!isset($u_user['identity']))
		{
			showmessage('Ошибка работы uLogin. В возвращаемых данных отсутствует переменная
			 "identity"');
			exit;
		}
		return true;
	}

	public static function getUserIdByIdentity($identity)
	{
		$exist = DB::result_first("SELECT uid FROM ".DB::table('ulogin_member')." WHERE identity = '".$identity."'");
		return $exist;
	}

	public static function getUserByEmail($email)
	{
		$exist = DB::result_first("SELECT uid FROM ".DB::table('common_member')." WHERE email = '".$email."'");
		return $exist;
	}

	/**
	 * @param $user_id
	 *
	 * @return bool
	 */
	public static function uloginCheckUserId($user_id)
	{
		global $_G;
		$current_user = $_G['uid'];
		if (($current_user > 0) && ($user_id > 0) && ($current_user != $user_id))
		{
			showmessage('Данный аккаунт привязан к другому пользователю. Вы не можете использовать этот аккаунт');
			exit;
		}
		return true;
	}

	/**
	 * Регистрация на сайте и в таблице uLogin
	 *
	 * @param Array $u_user - данные о пользователе, полученные от uLogin
	 * @param int $in_db - при значении 1 необходимо переписать данные в таблице uLogin
	 *
	 * @return bool|int|Error
	 */
	public static function uloginRegistrationUser($u_user, $in_db = 0)
	{
		if (!isset($u_user['email']))
		{
			showmessage('Через данную форму выполнить регистрацию невозможно. Сообщите администратору сайта о следующей ошибке:
            Необходимо указать "email" в возвращаемых полях uLogin');
			exit;
		}
		$u_user['network'] = isset($u_user['network']) ? $u_user['network'] : '';
		// данные о пользователе есть в ulogin_member, но отсутствуют в common_member
		if ($in_db == 1) DB::delete("ulogin_member", "identity='".$u_user['identity']."'");
		$user_id = ulogin::getUserByEmail($u_user['email']);
		// $check_m_user == true -> есть пользователь с таким email
		$check_m_user = $user_id > 0 ? true : false;
		global $_G;
		$current_user = $_G['uid'];
		// $is_logged_in == true -> ползователь онлайн
		$is_logged_in = $current_user > 0 ? true : false;
		if (($check_m_user == false) && !$is_logged_in)
		{
			$user_login = ulogin::ulogin_generateNickname($u_user['first_name'], $u_user['last_name'], $u_user['nickname'], $u_user['bdate']);
			$UserFields['username'] = $user_login;
			$UserFields['email'] = $u_user['email'];
			$UserFields['password'] = random(16);
			$UserFields['regdate'] = time();
			$uid = DB::insert('common_member', $UserFields, true);
			if ($uid)
			{
				DB::insert("ulogin_member", array("uid" => $uid, "identity" => $u_user['identity'], "network" => $u_user['network']));
				$profile = array();
				$profile['uid'] = $uid;
				if (isset($u_user['bdate']) && !empty($u_user['bdate']))
				{
					$dob = explode('.', $u_user['bdate']);
					$profile['birthday'] = $dob[0];
					$profile['birthmonth'] = $dob[1];
					$profile['birthyear'] = $dob[2];
					$profile['zodiac'] = get_zodiac($dob[2]);
					$profile['constellation'] = get_constellation($dob[1], $dob[0]);
				}
				else $u_user['bdate'] = '';
				$profile['gender'] = $u_user['sex'] == '2' ? 1 : 2;
				$profile['realname'] = $u_user['first_name'].' '.$u_user['last_name'];
				if (isset($u_user['country']))
				{
					$profile['birthprovince'] = $u_user['country'];
					$profile['resideprovince'] = $u_user['country'];
				}
				if (isset($u_user['city']))
				{
					$profile['birthcity'] = $u_user['city'];
					$profile['residecity'] = $u_user['city'];
				}
				loadcache('plugin');
				$set = $_G['cache']['plugin']['ulogin'];
				if ($set['soclink'] == '1') $profile['site'] = $u_user['profile'];
				else $profile['site'] = '';
				DB::insert('common_member_profile', $profile);
				if ($set['notify'] == '1')
				{
					if (!function_exists('sendmail'))
					{
						include libfile('function/mail');
					}
					$add_member_subject = lang('email', 'add_member_subject');
					$add_member_message = lang('email', 'add_member_message', array('newusername' => $UserFields['username'], 'bbname' => $_G['setting']['bbname'], 'adminusername' => $_G['member']['username'], 'siteurl' => $_G['siteurl'], 'newpassword' => $UserFields['password'],));
					//Отправка письма авторизованному пользователю
					if (!sendmail($UserFields['email'], $add_member_subject, $add_member_message))
					{
						runlog('sendmail', '"'.$UserFields['email']." sendmail failed.");
					}
				}
				return $uid;
			}
		}
		else
		{// существует пользователь с таким email или это текущий пользователь
			if (!isset($u_user["verified_email"]) || intval($u_user["verified_email"]) != 1)
			{
				$token = $_POST['token'];
				$uLogin_message = "Электронный адрес данного аккаунта совпадает с электронным адресом существующего пользователя
					.<br>Требуется подтверждение на владение указанным email.".ulogin::_get_back_url()."<script src='//ulogin.ru/js/ulogin.js'  type='text/javascript'></script><script type='text/javascript'>uLogin.mergeAccounts('$token')</script>";
				die($uLogin_message);
			}
			if (intval($u_user["verified_email"]) == 1)
			{
				$user_id = $is_logged_in ? $current_user : $user_id;
				$other_u = DB::result_first("SELECT identity FROM ".DB::table('ulogin_member')." WHERE uid = '".$user_id."'");
				if ($other_u)
				{
					if (!$is_logged_in && !isset($u_user['merge_account']))
					{
						$token = urlencode($_POST['token']);
						$uLogin_message = "С данным аккаунтом уже связаны данные из другой социальной сети
							.<br/>Требуется привязка новой учётной записи социальной сети к этому аккаунту.".ulogin::_get_back_url()."<script src='//ulogin.ru/js/ulogin.js'  type='text/javascript'></script><script type='text/javascript'>uLogin.mergeAccounts('$token','".$other_u."')</script>";
						die($uLogin_message);
					}
				}
				DB::insert("ulogin_member", array("uid" => $user_id, "identity" => $u_user['identity'], "network" => $u_user['network']));
				return $user_id;
			}
		}
		return false;
	}

	/**
	 * Обновление данных о пользователе и вход
	 *
	 * @param $u_user - данные о пользователе, полученные от uLogin
	 * @param $id_customer - идентификатор пользователя
	 *
	 * @return string
	 */
	public static function loginCustomer($u_user, $uid)
	{
		$member = getuserbyuid($uid);
		if (isset($member) && !empty($member))
		{
			//обновление полей member_profile
			global $_G;
			$profile = array();
			$profile['uid'] = $uid;
			if (isset($u_user['bdate']) && !empty($u_user['bdate']))
			{
				$dob = explode('.', $u_user['bdate']);
				$profile['birthday'] = $dob[0];
				$profile['birthmonth'] = $dob[1];
				$profile['birthyear'] = $dob[2];
				$profile['zodiac'] = get_zodiac($dob[2]);
				$profile['constellation'] = get_constellation($dob[1], $dob[0]);
			}
			else $u_user['bdate'] = '';
			$profile['gender'] = $u_user['sex'] == '2' ? 2 : $u_user['sex'] == '1' ? 1 : 0;
			$profile['realname'] = $u_user['first_name'].' '.$u_user['last_name'];
			if (isset($u_user['country']))
			{
				$profile['birthprovince'] = $u_user['country'];
				$profile['resideprovince'] = $u_user['country'];
			}
			if (isset($u_user['city']))
			{
				$profile['birthcity'] = $u_user['city'];
				$profile['residecity'] = $u_user['city'];
			}
			loadcache('plugin');
			$set = $_G['cache']['plugin']['ulogin'];
			if ($set['soclink'] == '1') $profile['site'] = $u_user['profile'];
			else $profile['site'] = '';
			DB::update('common_member_profile', $profile, 'uid='.$uid);
			$avatar_exist = DB::result_first("SELECT avatarstatus FROM ".DB::table('common_member')." WHERE uid = $uid");
			if(empty($avatar_exist)) {
				if (isset($u_user['photo'])) $u_user['photo'] = $u_user['photo'] === "https://ulogin.ru/img/photo.png" ? '' : $u_user['photo'];
				if (isset($u_user['photo_big'])) $u_user['photo_big'] = $u_user['photo_big'] === "https://ulogin.ru/img/photo_big.png" ? '' : $u_user['photo_big'];
				ulogin::_uploadAvatar((isset($u_user['photo_big']) and !empty($u_user['photo_big'])) ? $u_user['photo_big'] : ((isset($u_user['photo']) and !empty($u_user['photo'])) ? $u_user['photo'] : ''), $uid);
				DB::update('common_member', array('avatarstatus'=>1), 'uid='.$uid);
			}
			setloginstatus($member, time());
			return true;
		}
		else return false;
	}

	/**
	 * Гнерация логина пользователя
	 * в случае успешного выполнения возвращает уникальный логин пользователя
	 *
	 * @param $first_name
	 * @param string $last_name
	 * @param string $nickname
	 * @param string $bdate
	 * @param array $delimiters
	 *
	 * @return string
	 */
	public static function ulogin_generateNickname($first_name, $last_name = "", $nickname = "", $bdate = "", $delimiters = array('.', '_'))
	{
		$delim = array_shift($delimiters);
		$first_name = ulogin::ulogin_translitIt($first_name);
		$first_name_s = substr($first_name, 0, 1);
		$variants = array();
		if (!empty($nickname)) $variants[] = $nickname;
		$variants[] = $first_name;
		if (!empty($last_name))
		{
			$last_name = ulogin::ulogin_translitIt($last_name);
			$variants[] = $first_name.$delim.$last_name;
			$variants[] = $last_name.$delim.$first_name;
			$variants[] = $first_name_s.$delim.$last_name;
			$variants[] = $first_name_s.$last_name;
			$variants[] = $last_name.$delim.$first_name_s;
			$variants[] = $last_name.$first_name_s;
		}
		if (!empty($bdate))
		{
			$date = explode('.', $bdate);
			$variants[] = $first_name.$date[2];
			$variants[] = $first_name.$delim.$date[2];
			$variants[] = $first_name.$date[0].$date[1];
			$variants[] = $first_name.$delim.$date[0].$date[1];
			$variants[] = $first_name.$delim.$last_name.$date[2];
			$variants[] = $first_name.$delim.$last_name.$delim.$date[2];
			$variants[] = $first_name.$delim.$last_name.$date[0].$date[1];
			$variants[] = $first_name.$delim.$last_name.$delim.$date[0].$date[1];
			$variants[] = $last_name.$delim.$first_name.$date[2];
			$variants[] = $last_name.$delim.$first_name.$delim.$date[2];
			$variants[] = $last_name.$delim.$first_name.$date[0].$date[1];
			$variants[] = $last_name.$delim.$first_name.$delim.$date[0].$date[1];
			$variants[] = $first_name_s.$delim.$last_name.$date[2];
			$variants[] = $first_name_s.$delim.$last_name.$delim.$date[2];
			$variants[] = $first_name_s.$delim.$last_name.$date[0].$date[1];
			$variants[] = $first_name_s.$delim.$last_name.$delim.$date[0].$date[1];
			$variants[] = $last_name.$delim.$first_name_s.$date[2];
			$variants[] = $last_name.$delim.$first_name_s.$delim.$date[2];
			$variants[] = $last_name.$delim.$first_name_s.$date[0].$date[1];
			$variants[] = $last_name.$delim.$first_name_s.$delim.$date[0].$date[1];
			$variants[] = $first_name_s.$last_name.$date[2];
			$variants[] = $first_name_s.$last_name.$delim.$date[2];
			$variants[] = $first_name_s.$last_name.$date[0].$date[1];
			$variants[] = $first_name_s.$last_name.$delim.$date[0].$date[1];
			$variants[] = $last_name.$first_name_s.$date[2];
			$variants[] = $last_name.$first_name_s.$delim.$date[2];
			$variants[] = $last_name.$first_name_s.$date[0].$date[1];
			$variants[] = $last_name.$first_name_s.$delim.$date[0].$date[1];
		}
		$i = 0;
		$exist = true;
		while (true)
		{
			if ($exist = ulogin::ulogin_userExist($variants[$i]))
			{
				foreach ($delimiters as $del)
				{
					$replaced = str_replace($delim, $del, $variants[$i]);
					if ($replaced !== $variants[$i])
					{
						$variants[$i] = $replaced;
						if (!$exist = ulogin::ulogin_userExist($variants[$i])) break;
					}
				}
			}
			if ($i >= count($variants) - 1 || !$exist) break;
			$i++;
		}
		if ($exist)
		{
			while ($exist)
			{
				$nickname = $first_name.mt_rand(1, 100000);
				$exist = ulogin::ulogin_userExist($nickname);
			}
			return $nickname;
		}
		else
			return $variants[$i];
	}

	/**
	 * Транслит
	 */
	public static function ulogin_translitIt($str)
	{
		$tr = array("А" => "a", "Б" => "b", "В" => "v", "Г" => "g", "Д" => "d", "Е" => "e", "Ж" => "j", "З" => "z", "И" => "i", "Й" => "y", "К" => "k", "Л" => "l", "М" => "m", "Н" => "n", "О" => "o", "П" => "p", "Р" => "r", "С" => "s", "Т" => "t", "У" => "u", "Ф" => "f", "Х" => "h", "Ц" => "ts", "Ч" => "ch", "Ш" => "sh", "Щ" => "sch", "Ъ" => "", "Ы" => "yi", "Ь" => "", "Э" => "e", "Ю" => "yu", "Я" => "ya", "а" => "a", "б" => "b", "в" => "v", "г" => "g", "д" => "d", "е" => "e", "ж" => "j", "з" => "z", "и" => "i", "й" => "y", "к" => "k", "л" => "l", "м" => "m", "н" => "n", "о" => "o", "п" => "p", "р" => "r", "с" => "s", "т" => "t", "у" => "u", "ф" => "f", "х" => "h", "ц" => "ts", "ч" => "ch", "ш" => "sh", "щ" => "sch", "ъ" => "y", "ы" => "y", "ь" => "", "э" => "e", "ю" => "yu", "я" => "ya");
		if (preg_match('/[^A-Za-z0-9\_\-]/', $str))
		{
			$str = strtr($str, $tr);
			$str = preg_replace('/[^A-Za-z0-9\_\-\.]/', '', $str);
		}
		return $str;
	}

	/**
	 * Проверка существует ли пользователь с заданным логином
	 */
	public static function ulogin_userExist($login)
	{
		$exist = DB::result_first("SELECT uid FROM ".DB::table('common_member')." WHERE username = '".$login."'");
		if ($exist == false)
		{
			return false;
		}
		return true;
	}

	/**
	 * @param int $place - указывает, какую форму виджета необходимо выводить (0 - форма входа, 1 - форма синхронизации). Значение по умолчанию = 0
	 *
	 * @return string(html)
	 */
	public static function getPanelCode($place = 0)
	{
		/*
		 * Выводит в форму html для генерации виджета
		 */
		global $_G;
		$set = $_G['cache']['plugin']['ulogin'];
		$redirect_uri = urlencode($_G['siteurl'].'token.php?backurl='.urlencode(ulogin::ulogin_get_current_page_url()));
		$ulogin_default_options = array();
		$ulogin_default_options['display'] = 'small';
		$ulogin_default_options['providers'] = 'vkontakte,odnoklassniki,mailru,facebook';
		$ulogin_default_options['fields'] = 'first_name,last_name,email,photo,photo_big';
		$ulogin_default_options['optional'] = 'sex,bdate,country,city';
		$ulogin_default_options['hidden'] = 'other';
		$ulogin_options = array();
		$ulogin_options['ulogin_id1'] = $set['ulogin_id1'];
		$ulogin_options['ulogin_id2'] = $set['ulogin_id2'];
		$groups = unserialize($set['groups']);
		$ulogin_options['group'] = $groups;
		$default_panel = false;
		switch ($place)
		{
			case 0:
				$ulogin_id = $ulogin_options['ulogin_id1'];
				break;
			case 1:
				$ulogin_id = $ulogin_options['ulogin_id2'];
				break;
			default:
				$ulogin_id = $ulogin_options['ulogin_id1'];
		}
		if (empty($ulogin_id))
		{
			$ul_options = $ulogin_default_options;
			$default_panel = true;
		}
		$panel = '';
		$panel .= '<div class="ulogin_panel"';
		if ($default_panel)
		{
			$ul_options['redirect_uri'] = $redirect_uri;
			$x_ulogin_params = '';
			foreach ($ul_options as $key => $value) $x_ulogin_params .= $key.'='.$value.';';
			if ($ul_options['display'] != 'window') $panel .= ' data-ulogin="'.$x_ulogin_params.'"></div>';
			else
				$panel .= ' data-ulogin="'.$x_ulogin_params.'" href="#"><img src="https://ulogin.ru/img/button.png" width=187 height=30 alt="МультиВход"/></div>';
		}
		else
			$panel .= ' data-uloginid="'.$ulogin_id.'" data-ulogin="redirect_uri='.$redirect_uri.'"></div>';
		$panel = '<div class="ulogin_block place'.$place.'">'.$panel.'</div><div style="clear:both"></div>';
		return $panel;
	}

	/**
	 * Вывод списка аккаунтов пользователя
	 *
	 * @param int $user_id - ID пользователя (значение по умолчанию = текущий пользователь)
	 *
	 * @return string
	 */
	static public function getuloginUserAccountsPanel($user_id = 0)
	{
		global $_G;
		$current_user = $_G['uid'];
		$user_id = empty($user_id) ? $current_user : $user_id;
		if (empty($user_id)) return '';
		$networks = DB::fetch_all("SELECT * FROM ".DB::table('ulogin_member')." WHERE  uid= '".$user_id."'");
		$output = '';
		if ($networks)
		{
			$output .= '<div id="ulogin_accounts">';
			foreach ($networks as $network)
			{
				if ($network['uid'] = $user_id) $output .= "<div data-ulogin-network='{$network['network']}'  data-ulogin-identity='{$network['identity']}' class='ulogin_network big_provider {$network['network']}_big'></div>";
			}
			$output .= '</div>';
			return $output;
		}
		return '';
	}

	/**
	 * Возвращает Back url в html формате
	 */
	function _get_back_url()
	{
		return '<br/><a href="'.(isset($_GET['backurl']) ? $_GET['backurl'] : 'forum.php').'">'.'Назад'.'</a>';
	}

	public static function _uploadAvatar($url, $uid)
	{
		if (!empty($url))
		{
			$uid = abs(intval($uid));
			$uid = sprintf("%09d", $uid);
			$dir1 = substr($uid, 0, 3);
			$dir2 = substr($uid, 3, 2);
			$dir3 = substr($uid, 5, 2);
			$dir = DISCUZ_ROOT.'uc_server/data/avatar/'.$dir1.'/'.$dir2.'/'.$dir3.'/';
			if (!file_exists($dir))
			{
				mkdir($dir, 0777);
			}
			$sc = stream_context_create();
			$imagedump = file_get_contents($url, null, $sc);
			$tmpfname = $dir.substr($uid, -2).'_avatar_small.jpg';
			$tmpfname2 = $dir.substr($uid, -2).'_avatar_big.jpg';
			$tmpfname3 = $dir.substr($uid, -2).'_avatar_middle.jpg';
			$fh = fopen($tmpfname, "w");
			fwrite($fh, $imagedump);
			if (file_exists($tmpfname))
			{
				copy($url, $tmpfname);
				copy($url, $tmpfname2);
				copy($url, $tmpfname3);
			}
			fclose($fh);
		}
		return false;
	}
}

?>