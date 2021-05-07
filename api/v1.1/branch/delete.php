<?php
    /**************************************************************************************************
     *************************************Удаление филиала*********************************************
     **************************************************************************************************/
    /**************************************************************************************************
     * Принимаемые параметры:
     *     id (число) - ID удаляемого филиала
     *************************************************************************************************/
    define('WORK', true, true);
    require_once ('../utils/requires.php');
    //Проверяем, авторизован ли пользователь
    require_once("../users/authorise.php");
    $id = intval($_GET["id"]);
    if ($id < 1){
        die(format_result("ERROR_EMPTY_ID"));
    }
    //Удаляем данные, относящиеся к удаляемому филиалу
    $query = "SELECT table_name FROM information_schema.tables  where table_schema='public' ORDER BY table_name";
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    while ($tbl = pg_fetch_array($result)){
        if ($tbl["table_name"] == "tg_filial" || $tbl["table_name"] == "tg_files"){
            continue;
        }
        else{
            $query = "DELETE FROM ".$tbl["table_name"]." WHERE filial_id = $id";
            pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
        }
    }
    //Удаляем все файлы, относящиеся к указанному филиалу
    $query = "SELECT * FROM tg_files WHERE filial_id = $id";
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    while ($file = pg_fetch_array($result)) {
        if (file_exists($_SERVER["DOCUMENT_ROOT"]."/files/".$file["filename"])){
            @unlink($_SERVER["DOCUMENT_ROOT"]."/files/".$file["filename"]);
        }
    }
    $query = "DELETE FROM tg_files WHERE filial_id = $id";
    pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    //Удаляем запись о филиале
    $query = "DELETE FROM tg_filial WHERE \"ID\" = $id";
    pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    exit(format_result("RESULT_SUCCESS"));
?>