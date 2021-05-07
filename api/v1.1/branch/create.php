<?php
    //error_reporting(E_ALL);
    ini_set("display_errors",1);
    error_reporting(E_ERROR);
	/**************************************************************************************************
	***************************Добавление филиала******************************************************
	**************************************************************************************************/
	/**************************************************************************************************
	 * Обязательные параметры:
	 *     name - название филиала
	 *     city - город
	 *     comp_count - количество компьютеров
	 *     ip - IP-адрес сети клуба (внешний IP, предоставленный провайдером)
	 *     owner_id - ID хоста в таблице tg_users
	 *************************************************************************************************/
	define('WORK', true, true);
	require_once ('../utils/requires.php');
	//Проверяем, авторизован ли пользователь
	$allowed_statuses = array(1, 3);
	require_once("../users/authorise.php");
	$name = pg_escape_string($_REQUEST["name"]);
	if (empty($name)){
	    die(format_result("ERROR_EMPTY_NAME"));
	}
	$city = pg_escape_string($_REQUEST["city"]);
	if (empty($city)){
	    die(format_result("ERROR_EMPTY_CITY"));
	}
	$comp_count = intval($_REQUEST["comp_count"]);
	if (empty($comp_count)){
	    die(format_result("ERROR_EMPTY_COMP_COUNT"));
	}
	$ip = pg_escape_string($_POST["ip"]);
	if (empty($ip)){
	    $ip = $_SERVER["REMOTE_ADDR"];
	}
	elseif (!check_ip($ip)){
	    die(format_result("ERROR_INVALID_IP"));
	}
	$owner_id = intval($_REQUEST["owner_id"]);
	if ($owner_id < 1){
	    die(format_result("ERROR_EMPTY_OWNER_ID"));
	}
	else{
	    $query = "SELECT * FROM tg_users WHERE \"ID\" = $owner_id";
	    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	    $usr = pg_fetch_array($result);
	    if ($usr["filial_id"] > 0){
	        //Необходим пользователь с filial_id = 0
	        die(format_result("ERROR_USER_FROM_ANOTHER_FILIAL"));
	    }
	}
	$query = "INSERT INTO tg_filial (\"name\", \"comp_count\", \"city\", \"ip\", \"owner_id\") VALUES(";
	$query .= "'$name', $comp_count, '$city', '$ip', $owner_id)";
	pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	//Получаем ID только что созданного филиала
	$query = "SELECT MAX(\"ID\") AS \"ID\" FROM tg_filial LIMIT 1";
	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	$fil = pg_fetch_array($result);
	$last_insert_id = $fil["ID"];
	pg_free_result($result);
	if (intval($_GET["test"]) != 1){
	   $query = "UPDATE tg_users SET status = 1, filial_id = $last_insert_id WHERE \"ID\" = $owner_id";
	   pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	}
	$query = "SELECT * FROM tg_filial WHERE \"ID\" = $last_insert_id";
	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	$filial = pg_fetch_array($result);
	$filial = exclude_indexes($filial);
	pg_free_result($result);
	$filial["result"] = "RESULT_SUCCESS";
	echo format_array($filial);
	if (intval($_GET["test"]) == 1){
	    $query = "DELETE FROM tg_filial WHERE \"ID\" = $last_insert_id";
	    pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	}
?>