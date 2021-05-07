<?php
    /**************************************************************************************************
     ********************************Извлечение настроек клуба*****************************************
     **************************************************************************************************/
    if (! defined('WORK')) die('HIERARСHY_ERROR');
    define('CURRENT_FOLDER', dirname(__FILE__).'/', true);
    $query = "SELECT * FROM tg_settings WHERE filial_id = 0 OR filial_id = ".intval($filial_id);
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $settings = array();
    while ($row = pg_fetch_array($result)){
        $name = $row["name"];
        $settings[$name] = $row;
    }
?>