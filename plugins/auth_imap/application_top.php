<?php

if (!($app_module === 'users' && $app_action === 'login')) {
    return;
}

if (empty($_POST)) {
    return;
}

$module = $app_module.'/'.$app_action;

//check form token
app_check_form_token($module);

if(app_recaptcha::is_enabled())
{
    if(!app_recaptcha::verify())
    {
        $alerts->add(TEXT_RECAPTCHA_VERIFY_ROBOT, 'error');
        var_dump('REDIRECT on line '.__FILE__.':'.__LINE__);
        redirect_to($module);
    }
}

//login attempt
if(!login_attempt::verify())
{
    $alerts->add(TEXT_LOGIN_ATTEMPT_VERIFY_ERROR, 'error');
    redirect_to($module);
}

$username = $_POST['username'] ?? null;
$password = $_POST['password'] ?? null;

if (!($username && $password)) {
    return;
}

$username = db_prepare_input($username);
$password = db_prepare_input($password);
$mailHost = AUTH_IMAP_HOST; // get from config/server.php
$group_id = AUTH_IMAP_USER_GROUP; // get from config/server.php
$mailHostParts = explode('.', $mailHost);
$mailDomain = count($mailHostParts) > 2 ? implode('.', [
    $mailHostParts[count($mailHostParts) - 2],
    $mailHostParts[count($mailHostParts) - 1],
]) : $mailHost;

if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
    $email = $username;
    $username = substr($username, 0, strpos($username, '@'));
} else {
    $email = $username.'@'.$mailDomain;
}

require_once __DIR__.'/vendor/autoload.php';

use Webklex\PHPIMAP\ClientManager;

$cm = new ClientManager();

$client = $cm->make([
    'host'          => $mailHost,
    'port'          => AUTH_IMAP_PORT, // get from config/server.php
    'encryption'    => AUTH_IMAP_ENCRYPTION, // get from config/server.php
    'validate_cert' => AUTH_IMAP_VALIDATE_CERT, // get from config/server.php
    'username'      => $username,
    'password'      => $password,
    'protocol'      => 'imap'
]);

try {
    $client->connect();
} catch (\Exception $e) {
    return;
}

$check_query = db_query("select id, field_6, multiple_access_groups from app_entity_1 where field_9='" . db_input($email) . "' ");

$isFirstLogin = false;
if(!$check = db_fetch_array($check_query)) {
    $hasher = new PasswordHash(11, false);

    $sql_data = [
        'password' => $hasher->HashPassword($password),
        'field_12' => $username,
        'field_5' => 1,
        'field_6' => $group_id,
        'field_7' => $username,
        'field_8' => $username,
        'field_9' => $email,
        'date_added' => time(),
    ];

    db_perform('app_entity_1', $sql_data);
    $userId = db_insert_id();
    $isFirstLogin = true;
} else {
    $userId = $check['id'] ?? 0;
}

app_session_register('app_logged_users_id', $userId);
users_login_log::success($username, $userId);

if ($isFirstLogin) {
    redirect_to('users/account');
}

if(isset($_COOKIE['app_login_redirect_to'])) {
    setcookie('app_login_redirect_to', '', time() - 3600, '/');
    redirect_to(str_replace('module=', '', $_COOKIE['app_login_redirect_to']));
}

redirect_to('dashboard/');
