<?php
    error_reporting(E_ERROR);
    /**************************************************************************************************
     ***************************Изменение данных о  компьютере*****************************************
     **************************************************************************************************/
    /**************************************************************************************************
     * Обязательные параметры:
     *     comp_id - ID компьютера
     *     filial_id - ID филиала
     *     user_id - ID пользователя
     *     real_balance - Реальный баланс пользователя
     *     bonus_balance - Бонусный баланс пользователя
     *     minutes - Остаток минут пользователя
     *     packet_id = ID пакета или 0 для поминутки
     *     status - 1, если свободен; 2, если занят
     *     ip - IP-адрес компьютера
     *************************************************************************************************/
    define('WORK', true, true);
    require_once ('../utils/requires.php');
    //Проверяем, авторизован ли пользователь
    $allowed_users = array(0, 1, 2, 3, 5);
    require_once("../users/authorise.php");
    $comp_id = intval($_REQUEST["comp_id"]);
    if ($comp_id < 1){
        die(format_result("ERROR_EMPTY_COMP_ID"));
    }
    $filial_id = intval($_REQUEST["filial_id"]);
    if ($filial_id < 1){
        die(format_result("ERROR_EMPTY_FILIAL_ID"));
    }
    $user_id = intval($_REQUEST["user_id"]);
    if ($user_id < 1){
        die(format_result("ERROR_EMPTY_USER_ID"));
    }
    $filial_id = intval($_REQUEST["filial_id"]);
    if ($filial_id < 1){
        die(format_result("ERROR_EMPTY_FILIAL_ID"));
    }
    $real_balance = intval($_REQUEST["real_balance"]);
    if ($real_balance < 1){
        die(format_result("ERROR_EMPTY_REAL_BALANCE"));
    }
    $bonus_balance = intval($_REQUEST["bonus_balance"]);
    if ($bonus_balance < 1){
        die(format_result("ERROR_EMPTY_BONUS_BALANCE"));
    }
    $minutes = intval($_REQUEST["minutes"]);
    if ($minutes < 1){
        die(format_result("ERROR_EMPTY_MINUTES"));
    }
    $packet_id = intval($_REQUEST["packet_id"]);
    if (!isset($_REQUEST["packet_id"])){
        die(format_result("ERROR_EMPTY_PACKET_ID"));
    }
    $status = intval($_REQUEST["status"]);
    if ($status < 1){
        die(format_result("ERROR_EMPTY_MINUTES"));
    }
    $ip = pg_escape_string($_REQUEST["ip"]);
    if (empty($ip)){
        die(format_result("ERROR_EMPTY_IP"));
    }
    $query = "UPDATE tg_pc_info SET user_id = $user_id, ".
                                   "filial_id = $filial_id, ".
                                   "money = '$real_balance', ".
                                   "bonus = '$bonus_balance', ".
                                   "minutes = $minutes, ".
                                   "packet_id = $packet_id, ".
                                   "status = $status WHERE \"ID\" = $comp_id";
    pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $query = "SELECT * FROM tg_pc_info WHERE \"ID\" = $comp_id";
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $comp = pg_fetch_array($result);
    $comp = exclude_indexes($comp);
    $query = "UPDATE tg_club_map SET \"ip\" = '$ip', filial_id = $filial_id WHERE \"ID\" = ". $comp["comp_id"];
    pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $query = "SELECT * FROM tg_club_map WHERE \"ID\" = ". $comp["comp_id"];
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $map_item = pg_fetch_array($result);
    $map_item = exclude_indexes($map_item);
    $res = array($comp, $map_item);
    $res = output_format_table($res);
    die($res);
?>