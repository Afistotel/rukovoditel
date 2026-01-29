<?php
// Отключаем уведомления, оставляем остальные ошибки
error_reporting(E_ALL & ~E_NOTICE);

// Версия проекта
define('PROJECT_VERSION', '3.6.3');

require_once 'lib/database.php';
require_once 'lib/html.php';

// Редирект, если задан step, но нет lng
if (isset($_GET['step']) && !isset($_GET['lng'])) {
    header('Location: index.php');
    exit();
}

// Массив доступных языков и соответствующих файлов
$availableLanguages = [
    'russian'  => 'languages/russian.php',
    'chinese'  => 'languages/chinese.php',
    'italian'  => 'languages/italian.php',
    'english'  => 'languages/english.php',
    'german'   => 'languages/german.php'
];

// Подключаем языковой файл
$language = $_GET['lng'] ?? 'english';
$languageFile = $availableLanguages[$language] ?? $availableLanguages['english'];
require_once $languageFile;

// Формируем заголовок приложения
$app_title = isset($_GET['lng'])
    ? sprintf(TEXT_INSTALLATION_HEADING, PROJECT_VERSION)
    : sprintf('Rukovoditel %s Installation', PROJECT_VERSION);

// Получаем параметры запроса
$step   = $_GET['step'] ?? '';
$action = $_GET['action'] ?? '';

// Проверка установки приложения
$configPath = substr(__DIR__, 0, -7) . 'config/database.php';
if (is_file($configPath) && $step !== 'success') {
    header('Location: ../index.php');
    exit();
}

// Подключение действий по шагам
$actions = [
    'rukovoditel_config' => 'actions/check_db_settings.php',
    'install_rukovoditel' => 'actions/install_rukovoditel.php'
];

foreach ($actions as $key => $file) {
    if ($step === $key || $action === $key) {
        require_once $file;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="utf-8"/>
    <title><?= $app_title ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="MobileOptimized" content="320">

    <!-- Стили -->
    <link href="../template/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css"/>
    <link href="../template/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
    <link href="../template/plugins/uniform/css/uniform.default.css" rel="stylesheet" type="text/css"/>
    <link rel="stylesheet" type="text/css" href="../template/plugins/select2/select2_conquer.css"/>
    <link href="../template/css/style-conquer.css" rel="stylesheet" type="text/css"/>
    <link href="../template/css/style.css" rel="stylesheet" type="text/css"/>
    <link href="../template/css/style-responsive.css" rel="stylesheet" type="text/css"/>
    <link href="../template/css/plugins.css" rel="stylesheet" type="text/css"/>
    <link href="../css/skins/default/default.css" rel="stylesheet" type="text/css"/>
    <link rel="stylesheet" type="text/css" href="../css/default.css"/>

    <style>
        .login .content {
            width: auto;
            max-width: 750px;
        }
    </style>

    <!-- Скрипты -->
    <script src="js/jquery/3.6.4/jquery-3.6.4.min.js" type="text/javascript"></script>
    <script type="text/javascript" src="js/validation/1.9.5/jquery.validate.min.js"></script>
    <script type="text/javascript" src="js/validation/1.9.5/additional-methods.min.js"></script>
    <script type="text/javascript" src="../js/main.js"></script>

    <script type="text/javascript">
        $.extend($.validator.messages, {
            required: '<?= TEXT_FIELD_IS_REQURED ?>',
            email: '<?= TEXT_FIELD_IS_REQURED_EMAIL ?>'
        });
    </script>

    <link rel="shortcut icon" href="../favicon.ico"/>
</head>
<body class="login">
    <!-- LOGO -->
    <div class="login-page-logo"><?= $app_title ?></div>

    <!-- LOGIN -->
    <div class="content">
        <?php
        $modules = [
            'checking_environment' => 'modules/checking_environment.php',
            'database_config'      => 'modules/database_config.php',
            'rukovoditel_confi'    => 'modules/rukovoditel_config.php',
            'success'              => 'modules/success.php'
        ];

        $moduleFile = $modules[$step] ?? 'modules/language.php';
        require_once $moduleFile;
        ?>
    </div>

    <!-- COPYRIGHT -->
    <div class="copyright">
        <a href="https://www.rukovoditel.net" target="_blank">Rukovoditel <?= PROJECT_VERSION ?></a><br>
        Copyright &copy; <?= date('Y') ?> <a target="_blank" href="https://www.rukovoditel.net">www.rukovoditel.net</a>
    </div>

    <!-- JAVASCRIPTS -->
    <!--[if lt IE 9]>
    <script src="../template/plugins/respond.min.js"></script>
    <script src="../template/plugins/excanvas.min.js"></script> 
    <![endif]-->
    <script src="../template/plugins/jquery-migrate-1.2.1.min.js" type="text/javascript"></script>
    <script src="../template/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="../template/plugins/bootstrap-hover-dropdown/twitter-bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
    <script src="../template/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
    <script src="../template/plugins/jquery.blockui.min.js" type="text/javascript"></script>
    <script src="../template/plugins/jquery.cokie.min.js" type="text/javascript"></script>
    <script src="../template/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
    <script type="text/javascript" src="../template/plugins/select2/select2.min.js"></script>
    <script src="../template/scripts/app.js" type="text/javascript"></script>

    <script>
        jQuery(document).ready(function() {
            App.init();
        });
    </script>
</body>
</html>
