<?php
    //error_reporting(E_ALL);
    /**************************************************************************************************
     ***************************Редактирование филиала*************************************************
     **************************************************************************************************/
    /**************************************************************************************************
     * Обязательные параметры:
     *     id - ID филиала
     *     name - название филиала
     *     city - город
     *     comp_count - количество компьютеров
     *     ip - IP-адрес сети клуба (внешний IP, предоставленный провайдером)
     *     owner_id - ID хоста в таблице tg_users
     *************************************************************************************************/
    define('WORK', true, true);
    require_once ('../utils/requires.php');
    //Проверяем, авторизован ли пользователь
    $allowed_user_statuses = array(0, 1, 2);
    require_once("../users/authorise.php");
    $id = intval($_REQUEST["id"]);
    if ($id < 1){
        die(format_result('ERROR_EMPTY_ID'));
    }
    $query = "SELECT * FROM tg_filial WHERE \"ID\" = $id";
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    if (pg_num_rows($result) < 1){
        die(format_result('ERROR_FILIAL_NOT_FOUND'));
    }
    $filial = pg_fetch_array($result);
    $filial["filial_id"] = $filial["ID"];
    //Отфутболиваем всех, кто не имеет прав редактирования
    if (!check_user_access_rights($filial)){
        die(format_result('ERROR_PERMISSION_DENIED'));
    }
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
        if ($usr["filial_id"] != 0 && $usr["filial_id"] != $filial["ID"]){
            //Необходим пользователь с filial_id = 0 или из текущего филиала
            die(format_result("ERROR_USER_FROM_ANOTHER_FILIAL"));
        }
    }
    $query = "UPDATE tg_filial SET \"name\" = '$name', \"comp-count\" = $comp_count, \"city\" = '$city', \"ip\" = '$ip', \"owner_id\" = $owner_id WHERE \"ID\" = $id";
    pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    //Меняем владельца филиала
    $query = "UPDATE tg_users SET \"status\" = 5 WHERE \"ID\" = ".$filial["owner_id"];
    pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $query = "UPDATE tg_users SET \"status\" = 1, \"filial_id\" = $id WHERE \"ID\" = $owner_id";
    pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $query = "SELECT * FROM tg_filial WHERE \"ID\" = $id";
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $filial = pg_fetch_array($result);
    $filial = exclude_indexes($filial);
    pg_free_result($result);
    $filial["result"] = "RESULT_SUCCESS";
    echo format_array($filial);
?>