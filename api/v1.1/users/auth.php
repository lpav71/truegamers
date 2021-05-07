<?php
	//error_reporting(0);
	/**************************************************************************************************
	****************Аутентификация пользователя********************************************************
	**************************************************************************************************/
	//define('WORK', true, true);
	//require_once ('../utils/requires.php');
	//print_r($_REQUEST);
	//exit();
	$username = pg_escape_string($_REQUEST["username"]);
	$password = $_REQUEST["password"];
	if (empty($username)){
		die(format_result("ERROR_EMPTY_USERNAME"));
	}
	if (empty($password)){
		die(format_result("ERROR_EMPTY_PASSWORD"));
	}
	$query = "SELECT * FROM tg_users WHERE (LOWER(username) = LOWER('$username') OR LOWER(email) = LOWER('$username') OR LOWER(phone) = LOWER('$username')) AND (deleted = 0)";
	$queries[] = $query;
	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	if (pg_num_rows($result) < 1) die(format_result('ERROR_USER_NOT_EXISTS'));
	if (pg_num_rows($result) > 1) die(format_result('ERROR_MULTIPLE_USERNAME'));
	//Проверяем пароль
	$user = pg_fetch_array($result);
	// Очистка результата
  pg_free_result($result);
	$user_password = md5($password . ':' . $user["salt"]);
	//echo $user_password.'|'.$user["password"];
	//print_r($user);
	if (trim($user["password"]) != trim($user_password)){
		die(format_result('ERROR_INVALID_PASSWORD'));
	}
	if ($user["banned"] != 0){
		die(format_array(array("Result"=>"ERROR_USER_BANNED", "Ban_reason"=>$user["ban_reason"], "Ban_end"=>$user["ban_end"])));
		//die('ERROR_USER_BANNED: ' . $user["ban_reason"] . ' : ' . $user["ban_end"]);
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
	//Проверяем, не авторизовался ли пользователь в новом филиале
	/*$ip = $_SERVER["REMOTE_ADDR"];
	$query = "SELECT * FROM tg_branches WHERE ip = '$ip'";
	$queries[] = $query;
	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	$branch = pg_fetch_array($result);
	$queries[] = $branch;
	if (!empty($branch)){
		$branch_id = $branch["ID"];
		$query = "SELECT COUNT(*) AS count FROM tg_users_to_branches WHERE user_id = ".$user["ID"]." AND branch_id = $branch_id";
		$queries[] = $query;
		$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
		$row = pg_fetch_array($result);
		$queries[] = $row;
		if (intval($row["count"]) < 1){
			$query = "INSERT INTO tg_users_to_branches (user_id, branch_id) VALUES(".$user["ID"].", $branch_id)";
			$queries[] = $query;
			pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
		}
	}*/
	//Пересчитываем ранг пользователя
	/*$query = "SELECT * FROM tg_rang WHERE branch_id = $branch_id AND duration >= ".$user["game_time"]."ORDER BY duration ASC LIMIT 1";
	$queries[] = $query;
	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	$row = pg_fetch_array($result);
	$queries[] = $row;
	$rang = intval($row["ID"]);
	$user["rang", = $rang;
	$query = "UPDATE tg_users SET rang = $rang WHERE \"ID\" = ".$user["ID"];
	$queries[] = $query;
	pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));*/
	//Получаем филиалы, в которых зарегистрировн пользователь
	$query = "SELECT * FROM tg_users_to_branches WHERE user_id = ".$user["ID"];
	$queries[] = $query;
	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	$user["branches"] = array();
	while ($branch = pg_fetch_array($result)){
		$query = "SELECT * FROM tg_branches WHERE \"ID\" = ".$branch["branch_id"];
		$queries[] = $query;
		$r = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
		$br = pg_fetch_array($r);
		pg_free_result($r);
		$user["branches"][] = array("id"=>intval($branch["branch_id"]), "name"=>$br["name"]);
	}
	$_SESSION["user_id"] = $user["ID"];
	if ($user["is_superhost"] == 1){
		$user["is_superhost"] = true;
	}
	else{
		$user["is_superhost"] = false;
	}
	//print_r($user);
	$user["rang_id"] = $user["rang"];
	$query = "SELECT * FROM tg_rang WHERE branch_id = ".$user["branches"][0]["id"]." AND num = ".$user["rang"];
	$queries[] = $query;
	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	$rang = pg_fetch_array($result);
	$user["rang"] = array("id"=>intval($user["rang"]), "name"=>trim($rang["name"]), "num"=>intval($rang["num"]), "duration"=>intval($rang["duration"]));
	//Вычисляем следующий ранг
	$query = "SELECT * FROM tg_rang WHERE branch_id = ".$user["branches"][0]["id"]." AND num > ".$user["rang"]["id"]." ORDER BY num LIMIT 1";
	$queries[] = $query;
	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	$next_rang = pg_fetch_array($result);
	$required_duration = $next_rang["duration"] - $rang["duration"];
	$current_duration = $user["game_time"] - $rang["duration"];
	if ($required_duration > 0){
			$rang_progress = round($current_duration / $required_duration * 100);
	}
	else {
		$rang_progress = 0;
	}
	$user["rang"]["progress"] = intval($rang_progress);
	$user["rang"]["next_id"] = intval($next_rang["ID"]);
	$user["rang"]["next_num"] = intval($next_rang["num"]);
	$user["rang"]["next_name"] = $next_rang["name"];
	$user["rang"]["next_duration"] = intval($next_rang["duration"]);
	$result = array();
	/*print_r($user);
	exit();*/
	$user["balance"] = str_replace('$', '', $user["balance"]);
	$user["balance"] = str_replace(',', '', $user["balance"]);
	$user["bonus"] = str_replace('$', '', $user["bonus"]);
	$user["bonus"] = str_replace(',', '', $user["bonus"]);
	if ($_SERVER["HTTP_USER_AGENT"] == 'Mozilla/3.0 (compatible) / Truegamers Admin Web Application'){
		$result["id"] = intval($user["ID"]);
		$result["username"] = $user["username"];
		$result["email"] = $user["email"];
		$result["phone"] = $user["phone"];
		$result["status"] = $user["status"];
		$result["comment"] = $user["comment"];
		$result["is_superhost"] = $user["is_superhost"];
		$result["name"] = $user["name"];
		$result["surname"] = $user["surname"];
		$result["birthdate"] = date('Y-m-d H:i:s', strtotime($user["birthdate"]));
		$result["reg_date"] = date('Y-m-d H:i:s', strtotime($user["reg_date"])).$user["time_zone"];
		$result["last_visit"] = date('Y-m-d H:i:s', strtotime($user["last_visit"])).$user["time_zone"];
		$result["game_time_minutes"] = intval($user["game_time"]);
		$result["rang"] = $user["rang"];
		$result["balance"] = (float)$user["balance"];
		$result["bonus_balance"] = (float)$user["bonus"];
		$result["branches"] = $user["branches"];
	}
	else {
		$result["id"] = intval($user["ID"]);
		$result["username"] = $user["username"];
		$result["email"] = $user["email"];
		$result["phone"] = $user["phone"];
		$result["status"] = $user["status"];
		$result["comment"] = $user["comment"];
		$result["is_superhost"] = $user["is_superhost"];
		$result["name"] = $user["name"];
		$result["surname"] = $user["surname"];
		$result["birthdate"] = date('Y-m-d H:i:s', strtotime($user["birthdate"]));
		$result["reg_date"] = date('Y-m-d H:i:s', strtotime($user["reg_date"])).$user["time_zone"];
		$result["last_visit"] = date('Y-m-d H:i:s', strtotime($user["last_visit"])).$user["time_zone"];
		$result["game_time_minutes"] = intval($user["game_time"]);
		$result["rang"] = $user["rang"];
		$result["balance"] = (float)$user["balance"];
		$result["bonus_balance"] = (float)$user["bonus"];
		$result["branches"] = $user["branches"];
	}
	if ($user["is_superhost"] == 1){
		$allowed_branches_subquery = "SELECT \"ID\" FROM tg_branches";
	}
	else{
		$allowed_branches_subquery = "SELECT \"branch_id\" FROM tg_users_to_branches WHERE user_id = ".$user["ID"];
	}
	if (!empty($_REQUEST["promo"])){
		define("AUTHORIZATION", true, true);
		require_once('promo/check.php');
	}
	$res["result"] = "RESULT_SUCCESS";
	$res["payload"] = $result;
	$out =  format_array($res);
	//Пишем в базу данных информацию о запросе
  $query = "INSERT INTO tg_request (\"time\", uri, post_data, server_data, request_data, ip, response, session_data, session_id, end_time, queries) ";
  $query .= "VALUES(CURRENT_TIMESTAMP, '".$_SERVER["REQUEST_URI"]."', '".json_encode($_POST)."', '".json_encode($_SERVER)."', '";
  $query .= json_encode($_REQUEST)."', '".$_SERVER["REMOTE_ADDR"]."', '$out', '', '', CURRENT_TIMESTAMP, '".pg_escape_string(json_encode($queries))."') RETURNING \"ID\" AS \"id\"";
  $r = @pg_query($query);
	exit($out);
?>
