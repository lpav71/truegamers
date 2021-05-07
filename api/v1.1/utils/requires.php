<?php
	/**************************************************************************************************
	****************Подключение всех обязательных скриптов*********************************************
	**************************************************************************************************/
  if (! defined('WORK')) die('HIERARСHY_ERROR');
	define('CURRENT_FOLDER', dirname(__FILE__).'/', true);
	require_once(CURRENT_FOLDER.'connect.php');
	//Подключаем нужный файл для форматирования отображаемых данных
	if ($_REQUEST["format"] == 'ini'){
		require_once('output/ini.php');
	}
	elseif ($_REQUEST["format"] == 'xml'){
		require_once('output/xml.php');
	}
	else {
		require_once('output/json.php');
	}
	//Подключаем вспомогательные функции
	require_once(CURRENT_FOLDER.'functions.php');
	require_once('output/functions.php');
	$allowed_user_statuses = array(0,1,2,3,4,5);
	header('Content-Type: text/plain; charset=utf-8');
?>
