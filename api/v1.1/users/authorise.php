<?php
	//error_reporting(0);
	/**************************************************************************************************
	****************Аутентификация пользователя********************************************************
	**************************************************************************************************/
	define('WORK', true, true);
	session_start();
	if (!empty($_REQUEST["username"])){
		$_SESSION["username"] = pg_escape_string($_REQUEST["username"]);
	}
	$username = pg_escape_string($_SESSION["username"]);
	if (!empty($_REQUEST["password"])){
		$_SESSION["password"] = pg_escape_string($_REQUEST["password"]);
	}
	$password = $_SESSION["password"];
	if (empty($username)){
		die(format_result("ERROR_USER_NOT_AUTHORISED"/*.session_id()*/));
	}
	if (empty($password)){
		die(format_result("ERROR_USER_NOT_AUTHORISED"/*.session_id()*/));
	}
	$query = "SELECT * FROM tg_users WHERE (LOWER(username) = LOWER('$username') OR LOWER(email) = LOWER('$username') OR LOWER(phone) = LOWER('$username')) AND (deleted = 0) AND (banned = 0)";
	$queries[] = $query;
	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	if (pg_num_rows($result) < 1) die(format_result('ERROR_USER_NOT_EXISTS'));
	//if (pg_num_rows($result) > 1) die(format_result('ERROR_MULTIPLE_USERNAME'));
	//Проверяем пароль
	$user = pg_fetch_array($result);
	// Очистка результата
  pg_free_result($result);
	$user_password = md5($password . ':' . $user["salt"]);
	if (trim($user["password"]) != trim($user_password)){
		//die(format_result('ERROR_INVALID_PASS'));
	}
	if ($user["banned"] <> 0){
		die(format_array(array("Result"=>"ERROR_USER_BANNED", "Ban_reason"=>$user["ban_reason"], "Ban_end"=>$user["ban_end"])));
		//die('ERROR_USER_BANNED: ' . $user["ban_reason"] . ' : ' . $user["ban_end"]);
	}
	if ($user["is_superhost"] == 1){
		$allowed_branches_subquery = "SELECT \"ID\" FROM tg_branches";
	}
	else{
		$allowed_branches_subquery = "SELECT \"branch_id\" FROM tg_users_to_branches WHERE user_id = ".$user["ID"];
	}
	//Проверяем, имеет ли пользователь права доступа к скрипту
	if (!array_search($user["status"], $allowed_user_statuses)){
	    //die(format_result('ERROR_PERMISSION_DENIED'));
	}
	//Функция проверки наличия у пользователя прав редактирования текущей записи
	function check_user_access_rights($record){
	    global $user;
	    if ($user["status"] == 0 || $user["status"] == 2){
	        return true;
	    }
	    elseif ($user["status"] == 1 || $user["status"] == 3){
	        if ($record["filial_id"] == $user["filial_id"]){
	            return true;
	        }
	        else {
	            return false;
	        }
	    }
	    else {
	        return false;
	    }
	}
?>
