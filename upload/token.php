<?php

require_once './source/class/class_core.php';
require_once './source/class/class_ulogin.php';

$discuz = & discuz_core::instance();
$discuz->init();

if (isset($_POST['identity']))
{
    try
    {
        DB::delete("ulogin_member", "identity='".$_POST['identity']."'");
        echo json_encode(array(
            'answerType' => 'ok',
            'msg' => "Удаление привязки аккаунта ".$_POST['network']." успешно выполнено"
        ));
        exit;
    } catch (Exception $e) {
        echo json_encode(array(
            'answerType' => 'error',
            'msg' => "Ошибка при удалении аккаунта \n Exception: " . $e->getMessage()
        ));
        exit;
    }
}

uloginParseRequest();


/**
 * Обработка ответа сервера авторизации
 */
function uloginParseRequest()
{

    if (!isset($_POST['token'])) return;  // не был получен токен uLogin
    $s = ulogin::uloginGetUserFromToken($_POST['token']);
    if (!$s)
    {
        showmessage('Ошибка работы uLogin:Не удалось получить данные о пользователе с помощью токена.');
    }
    $u_user = json_decode($s, true);
    $u_user['nickname'] = isset($u_user['nickname']) ? $u_user['nickname'] : $u_user['nickname'] = '';

    $check = ulogin::uloginCheckTokenError($u_user);
    if (!$check)
    {
        return false;
    }

    $user_id = ulogin::getUserIdByIdentity($u_user['identity']);

    if (isset($user_id) && !empty($user_id))
    {
        $d = getuserbyuid($user_id);
        if ($user_id > 0 && ( $d['uid'] > 0)) {
            ulogin::uloginCheckUserId($user_id);
        }
        else{
            $user_id = ulogin::uloginRegistrationUser($u_user, 1);
        }
    }
    else $user_id = ulogin::uloginRegistrationUser($u_user);
    if ($user_id > 0) ulogin::loginCustomer($u_user, $user_id);
        else return false;
    return true;
}

header("Location:".$_GET['backurl']);
?>
