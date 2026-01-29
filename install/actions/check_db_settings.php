<?php
// Получение и очистка входных данных
$server   = trim($_POST['db_host'] ?? '');
$port     = trim($_POST['db_port'] ?? '');
$username = trim($_POST['db_username'] ?? '');
$password = trim($_POST['db_password'] ?? '');
$database = trim($_POST['db_name'] ?? '');

// Формирование параметров для передачи
$params = [
    'db_host'     => $server,
    'db_port'     => $port,
    'db_username' => $username,
    'db_password' => $password,
    'db_name'     => $database,
    'lng'         => $_GET['lng'] ?? ''
];

// Подключение к БД
db_connect($server, $username, $password, $database, $port, 'db_link', $params);

// Проверка привилегий пользователя
$userPrivilegesList = [];
$result = db_query('SHOW PRIVILEGES');

while ($row = db_fetch_array($result)) {
    $userPrivilegesList[] = $row['Privilege'];
}

// Требуемые привилегии
$requiredPrivileges = ['Select', 'Insert', 'Update', 'Delete', 'Create', 'Drop', 'Alter'];

// Поиск отсутствующих привилегий
$missedPrivileges = array_filter($requiredPrivileges, function ($privilege) use ($userPrivilegesList) {
    return !in_array($privilege, $userPrivilegesList, true);
});

// Обработка ошибок
if (!empty($missedPrivileges)) {
    $error = 'Next privileges: "' . implode(', ', $missedPrivileges) . '" are required for MySQL user.';
    
    $queryParams = [
        'step'      => 'database_config',
        'db_error'  => $error,
        'lng'       => $params['lng'],
        'params'    => base64_encode(json_encode($params))
    ];
    
    $queryString = http_build_query($queryParams);
    header('Location: index.php?' . $queryString);
    exit();
}
