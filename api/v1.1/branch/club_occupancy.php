<?php
    /**************************************************************************************************
     *************************************Загрузка клуба***********************************************
     **************************************************************************************************/
    define('WORK', true, true);
    require_once ('../utils/requires.php');
    //Проверяем, авторизован ли пользователь
    //require_once("../users/authorise.php");
    $date = $_GET["date"];
    $date = str_replace('|', ' ', $date);
    $filial_id = intval($_GET["filial_id"]);
    $query = "SELECT SUM(duration) AS duration FROM tg_game_time WHERE '$date'::date = date_start::date AND filial_id = $filial_id AND date_end <= '$date'";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $res = pg_fetch_array($result);
    $now = strtotime($date);
    $diff = ($now - strtotime('today')) / 60;
    //echo $diff;
    $query = "SELECT COUNT(*) as cnt FROM tg_club_map WHERE filial_id = $filial_id";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $computers = pg_fetch_array($result);
    $cnt = $computers["cnt"];
    $diff = $cnt * $diff;
    exit(round(($res["duration"] / $diff) * 100));
?>
