<?php
    error_reporting(0);
    /**************************************************************************************************
     ****************Аутентификация пользователя********************************************************
     **************************************************************************************************/
    $username = pg_escape_string($_REQUEST["username"]);
    $password = $_REQUEST["password"];
    if (empty($username)){
        die(format_result("ERROR_EMPTY_USERNAME"));
    }
    if (empty($password)){
        die(format_result("ERROR_EMPTY_PASSWORD"));
    }
    $query = "SELECT * FROM tg_users WHERE LOWER(username) = LOWER('$username') OR LOWER(email) = LOWER('$username') OR LOWER(phone) = LOWER('$username') AND deleted = 0";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    if (pg_num_rows($result) < 1) die(format_result('ERROR_USER_NOT_EXISTS'));
    if (pg_num_rows($result) > 1) die(format_result('ERROR_MULTIPLE_USERNAME'));
    //Проверяем пароль
    $user = pg_fetch_array($result);
    // Очистка результата
    pg_free_result($result);
    $user_password = md5($password . ':' . $user["salt"]);
    if (trim($user["password"]) != trim($user_password)){
        die(format_result('ERROR_INVALID_PASSWORD'));
    }
    if ($user["banned"] != 0){
        die(format_array(array("Result"=>"ERROR_USER_BANNED", "Ban_reason"=>$user["ban_reason"], "Ban_end"=>$user["ban_end"])));
        //die('ERROR_USER_BANNED: ' . $user["ban_reason"] . ' : ' . $user["ban_end"]);
    }
    if ($user["status"] != 1 && $user["status"] != 3){
        die(format_result('ERROR_PERMISSION_DENIED'));
    }
    //Записываемся в историю авторизаций
    $ip = $_SERVER["REMOTE_ADDR"];
    $user_agent = pg_escape_string($_SERVER["HTTP_USER_AGENT"]);
  	$query = "SELECT * FROM tg_branches WHERE \"ip\" = '$ip'";
    $queries[] = $query;
  	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  	$row = pg_fetch_array($result);
  	$filial_id = intval($row["ID"]);
    $usr_id = $user["ID"];
    $query = "INSERT INTO tg_auth_history (\"date\", \"ip\", \"user_agent\", \"branch_id\", \"user_id\") VALUES (CURRENT_TIMESTAMP, '$ip', '$user_agent', $filial_id, $usr_id)";
    $queries[] = $query;
    pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    //Пишем в сессию логин с паролем и выводим инфу о юзере
    session_start();
    $_SESSION["username"] = $username;
    $_SESSION["password"] = $password;
    $_SESSION["filial_id"] = $user["filial_id"];
    // заставляем браузер показать окно сохранения файла
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=cdb.png');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize("files/db_conn/cdb.png"));
    // читаем файл и отправляем его пользователю
    readfile("files/db_conn/cdb.png");
?>
