<?php
    /**************************************************************************************************
     *************************************Список тарифов***********************************************
     **************************************************************************************************/
    /**************************************************************************************************
     * Фильтр полей:
     *     fields (название поля или список через запятую)(ID, filial_id - любое поле из таблицы tg_prices). Пример: list.php?fields=ID,filial_id...
     * Ограничение количества элементов в выборке:
     *     limit={количество в виде числа}
     * Пропустить некоторое количество записей в начале:
     *     offset={сколько пропустить в виде числа}
     *************************************************************************************************/
    define('WORK', true, true);
    require_once ('../utils/requires.php');
    //Проверяем, авторизован ли пользователь
    //require_once("../users/authorise.php");
    if (intval($_GET["filial_id"]) < 1){
        exit(format_result("ERROR_EMPTY_FILIAL_ID"));
    }
    $filial_id = intval($_GET["filial_id"]);
    $where = "WHERE filial_id = $filial_id AND \"active\" = 1";
    if (!empty($_GET["time"])){
      $time = pg_escape_string($_GET["time"]);
      $time = str_replace('|', ' ', $time);
      $where .= " AND '$time' BETWEEN \"time_start\" AND \"time_end\"";
    }
    if (intval($_GET["zone"]) > 0){
      $zone = intval($_GET["zone"]);
      $where .= " AND \"zone\" = $zone";
    }
    $holiday = intval($_GET["holiday"]);
    if ($holiday > 0){
      $where .= " AND \"holiday\" = 1";
    }
    else{
      $where .= " AND \"holiday\" = 0";
    }


    if (!empty($_GET["fields"])){
        $fields = pg_escape_string($_GET["fields"]);
        $fields = '"'.str_replace(',', '","', $fields).'"';
    }
    else {
        $fields = "*";
    }
    $query = "SELECT $fields FROM tg_prices $where";
    //echo $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $out = array();
    while($row = pg_fetch_array($result)){
        $out[] = $row;
    }
    pg_free_result($result);
    $res = output_format_table($out);
    exit($res);
?>
