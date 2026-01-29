<?php
require('../includes/libs/PasswordHash.php');

$hasher = new PasswordHash(11, false);

// Получение параметров из запроса
$language = $_GET['lng'] ?? 'en';
$sqlFile = 'sql/' . $language . '.sql';

$dbParams = [
    'server'   => $_POST['db_host'] ?? '',
    'port'     => $_POST['db_port'] ?? '',
    'username' => $_POST['db_username'] ?? '',
    'password' => $_POST['db_password'] ?? '',
    'database' => $_POST['db_name'] ?? ''
];

// Подключение к БД
db_connect(
    $dbParams['server'],
    $dbParams['username'],
    $dbParams['password'],
    $dbParams['database'],
    $dbParams['port']
);

// Чтение SQL-файла
if (!file_exists($sqlFile)) {
    http_response_code(500);
    echo 'SQL file does not exist: ' . htmlspecialchars($sqlFile);
    exit();
}

$installQuery = file_get_contents($sqlFile);
if ($installQuery === false) {
    http_response_code(500);
    echo 'Failed to read SQL file: ' . htmlspecialchars($sqlFile);
    exit();
}

// Настройка кодировки БД
db_query("ALTER DATABASE `" . db_input($dbParams['database']) . "` CHARACTER SET utf8mb4");

// Формирование INSERT-запросов
$appConfigValues = [
    ['11', 'CFG_APP_LOGO', ''],
    ['10', 'CFG_APP_SHORT_NAME', db_input($_POST['app_short_name'] ?? '')],
    ['9', 'CFG_APP_NAME', db_input($_POST['app_name'] ?? '')],
    ['12', 'CFG_EMAIL_USE_NOTIFICATION', '1'],
    ['13', 'CFG_EMAIL_SUBJECT_LABEL', ''],
    ['14', 'CFG_EMAIL_AMOUNT_PREVIOUS_COMMENTS', '2'],
    ['15', 'CFG_EMAIL_COPY_SENDER', '0'],
    ['16', 'CFG_EMAIL_SEND_FROM_SINGLE', '0'],
    ['17', 'CFG_EMAIL_ADDRESS_FROM', db_input($_POST['email_address_from'] ?? '')],
    ['18', 'CFG_EMAIL_NAME_FROM', db_input($_POST['email_name_from'] ?? '')],
    ['19', 'CFG_EMAIL_USE_SMTP', '0'],
    ['20', 'CFG_EMAIL_SMTP_SERVER', ''],
    ['21', 'CFG_EMAIL_SMTP_PORT', ''],
    ['22', 'CFG_EMAIL_SMTP_ENCRYPTION', ''],
    ['23', 'CFG_EMAIL_SMTP_LOGIN', ''],
    ['24', 'CFG_EMAIL_SMTP_PASSWORD', ''],
    ['25', 'CFG_LDAP_USE', '0'],
    ['26', 'CFG_LDAP_SERVER_NAME', ''],
    ['27', 'CFG_LDAP_SERVER_PORT', ''],
    ['28', 'CFG_LDAP_BASE_DN', ''],
    ['29', 'CFG_LDAP_UID', ''],
    ['30', 'CFG_LDAP_USER', ''],
    ['31', 'CFG_LDAP_EMAIL_ATTRIBUTE', ''],
    ['32', 'CFG_LDAP_USER_DN', ''],
    ['33', 'CFG_LDAP_PASSWORD', ''],
    ['34', 'CFG_LOGIN_PAGE_HEADING', ''],
    ['35', 'CFG_LOGIN_PAGE_CONTENT', ''],
    ['36', 'CFG_APP_TIMEZONE', db_input($_POST['app_time_zone'] ?? '')],
    ['37', 'CFG_APP_DATE_FORMAT', 'm/d/Y'],
    ['38', 'CFG_APP_DATETIME_FORMAT', 'm/d/Y H:i'],
    ['39', 'CFG_APP_ROWS_PER_PAGE', '10'],
    ['40', 'CFG_REGISTRATION_EMAIL_SUBJECT', ''],
    ['41', 'CFG_REGISTRATION_EMAIL_BODY', ''],
    ['42', 'CFG_PASSWORD_MIN_LENGTH', '5'],
    ['43', 'CFG_APP_LANGUAGE', $language . '.php'],
    ['44', 'CFG_APP_SKIN', ''],
    ['45', 'CFG_PUBLIC_USER_PROFILE_FIELDS', '']
];

$insertAppConfig = "INSERT INTO app_configuration VALUES\n" .
    implode(",\n", array_map(function ($row) {
        return "('" . implode("','", array_map('db_input', $row)) . "')";
    }, $appConfigValues));

$userPasswordHash = $hasher->HashPassword($_POST['user_password'] ?? '');
$insertAppEntity1 = "INSERT INTO app_entity_1 VALUES\n" .
    "('1',0,'0','0','0','" . time() . "','0',NULL,'0','" .
    db_input($userPasswordHash) . "','',1,'1','0','" .
    db_input($_POST['fields'][7] ?? '') . "','" .
    db_input($_POST['fields'][8] ?? '') . "','" .
    db_input($_POST['fields'][9] ?? '') . "','','" .
    db_input($_POST['fields'][12] ?? '') . "','" .
    $language . ".php','blue'," . time() . ")";

$installQuery .= ";\n" . $insertAppConfig . ";\n" . $insertAppEntity1;

// Выполнение запросов
$queryArray = array_filter(
    array_map('trim', explode(';', $installQuery)),
    function ($query) {
        return $query !== '';
    }
);

foreach ($queryArray as $query) {
    try {
        db_query($query);
    } catch (Exception $e) {
        http_response_code(500);
        echo 'Query execution failed: ' . htmlspecialchars($query) . '<br>';
        echo 'Error: ' . htmlspecialchars($e->getMessage());
        exit();
    }
}

// Генерация конфигурационного файла БД
$dbConfig = <<<PHP
<?php

// Define database connection
define('DB_SERVER', '" . $dbParams['server'] . ($dbParams['port'] ? ':' . $dbParams['port'] : '') . "');
define('DB_SERVER_USERNAME', '" . $dbParams['username'] . "');
define('DB_SERVER_PASSWORD', '" . $dbParams['password'] . "');
define('DB_SERVER_PORT', '" . $dbParams['port'] . "');
define('DB_DATABASE', '" . $dbParams['database'] . "');

PHP;

// Запись конфигурации
if (file_exists('../config/database.php')) {
    unlink('../config/database.php');
}

if (!file_put_contents('../config/database.php', $dbConfig)) {
    http_response_code(500);
    echo 'Failed to write database configuration file.';
    exit();
}

// Редирект на страницу успеха
header('Location: index.php?step=success&lng=' . urlencode($language));
exit();
