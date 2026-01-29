<?php
/**
 * Этот файл является частью программы "CRM Руководитель" - конструктор CRM систем для бизнеса
 * https://www.rukovoditel.net.ru/
 * 
 * CRM Руководитель - это свободное программное обеспечение, 
 * распространяемое на условиях GNU GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * 
 * Автор и правообладатель программы: Харчишина Ольга Александровна (RU), Харчишин Сергей Васильевич (RU).
 * Государственная регистрация программы для ЭВМ: 2023664624
 * https://fips.ru/EGD/3b18c104-1db7-4f2d-83fb-2d38e1474ca3
 */

require_once __DIR__ . '/includes/application_top.php';
require_once __DIR__ . '/includes/plugins.php';
require_once __DIR__ . '/includes/plugins_menu.php';


$modulePath = $app_plugin_path . 'modules/' . $app_module . '/';

// Подключение модуля
if (is_file($modulePath . 'module_top.php')) {
    require_once $modulePath . 'module_top.php';
}

// Подключение действия модуля
$actionPath = $modulePath . 'actions/' . $app_action . '.php';
if (is_file($actionPath)) {
    require_once $actionPath;
}

if (IS_AJAX) {
    $viewPath = $modulePath . 'views/' . $app_action . '.php';
    if (is_file($viewPath)) {
        require_once $viewPath;
    }
} else {
    // Подключение шаблона
    if (str_starts_with($app_layout, 'plugins/')) {
        require_once $app_layout;
    } else {
        require_once __DIR__ . '/template/' . $app_layout;
    }
}

require_once __DIR__ . '/includes/application_bottom.php';

