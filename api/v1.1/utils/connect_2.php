<?php
	if (! defined('WORK')) die('HIERARСHY_ERROR');
	/**************************************************************************************************
	****************Соединение с базой данных**********************************************************
	**************************************************************************************************/
	//$dbconn = pg_pconnect("host=188.246.224.156 port=5432 dbname=truegamers user=truegamers password=12801024qwE")
    //$dbconn = pg_pconnect("host=46.148.235.107 port=5432 dbname=truegamers user=truegamers password=12801024qwE")
    $dbconn = pg_pconnect("host=127.0.0.1 port=5432 dbname=tgamers user=postgres")
        or die('DATABASE_CONNECTION_ERROR');
?>