<?php
// Core

// Запускаем сессию
session_start();

// Часовой пояс
date_default_timezone_set('Europe/Moscow');

// Системные константы
defined('__DIR__') or define('__DIR__', dirname(__FILE__));
define('ROOTPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('BASEURL','/' . basename(__DIR__) . '/');

include __DIR__ . '/include/System.php';
$data = new System;
$data->title = 'Главная';

if(!$action = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS)) {
	$c = 'index';
}
else {
	$c = $action;
}

$file = __DIR__ . "/layouts/{$c}.php";

if(!is_readable($file)) {
	$file = __DIR__ . "/layouts/index.php";
}

if($data->ajax)
{
	require $file;
	exit;
}

ob_start();
require $file;
$content = ob_get_clean();
require __DIR__ . '/layouts/template.php';