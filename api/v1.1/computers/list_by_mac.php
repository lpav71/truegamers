<?php
    /**************************************************************************************************
     *************************************Список компьютеров*******************************************
     **************************************************************************************************/
    /**************************************************************************************************
     * Фильтрующие параметры:
     *     mac (строка) - по MAC-адресу компьютера(ов)
     *************************************************************************************************/
    define('WORK', true, true);
    require_once ('../utils/requires.php');
    //Проверяем, авторизован ли пользователь
    //require_once("../users/authorise.php");
    if (empty($_REQUEST["mac"])) {
        exit(format_result("ERROR_EMPTY_MAC_ADDRESS"));
    }
    $mac = trim(pg_escape_string($_REQUEST["mac"]));
    $query = "SELECT comp_id, category FROM tg_club_map WHERE \"mac\" = '$mac'";
    //echo $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    if (pg_num_rows($result) < 1){
        exit(format_result("ERROR_PC_NOT_FOUND"));
    }
    $pc = pg_fetch_array($result);
    $pc = exclude_indexes($pc);
    echo format_array($pc);
?>