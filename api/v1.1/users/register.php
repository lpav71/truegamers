<?php
  $branch_id = intval($_POST["branch_id"]);
  if ($branch_id < 1){
    die(format_result("ERROR_EMPTY_BRANCH_ID"));
  }
  $username = pg_escape_string($_POST["username"]);
  if (empty($username)){
    die(format_result("ERROR_EMPTY_USERNAME"));
  }
  $password = pg_escape_string($_POST["password"]);
  if (empty($password)){
    die(format_result("ERROR_EMPTY_PASSWORD"));
  }
  $email = pg_escape_string($_POST["email"]);
  if (empty($email)){
    die(format_result("ERROR_EMPTY_EMAIL"));
  }
  else {
    $pattern = "#^[-_0-9a-zа-я\.]+@[-_0-9a-zа-я\.]{2,}\.[0-9a-zа-я]{2,}$#isU";
    if (!preg_match($pattern, $email)){
      die(format_result("ERROR_INVALID_EMAIL"));
    }
  }
  $phone = pg_escape_string($_POST["phone"]);
  if (empty($phone)){
    die(format_result("ERROR_EMPTY_PHONE"));
  }
  else {
    $pattern = "#^[0-9]{10,16}$#isU";
    if (!preg_match($pattern, str_replace('+', '', $phone))){
      die(format_result("ERROR_INVALID_PHONE"));
    }
  }
  $query = "SELECT * FROM tg_branches WHERE \"ID\" = $branch_id";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	if (pg_num_rows($result) < 1) die(format_result('ERROR_BRANCH_DOES_NOT_EXISTS'));
  $query = "SELECT * FROM tg_users WHERE username = '$username'";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	if (pg_num_rows($result) > 0) die(format_result('ERROR_USERNAME_IS_BUSY'));
  $query = "SELECT * FROM tg_users WHERE email = '$email'";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	if (pg_num_rows($result) > 0) die(format_result('ERROR_EMAIL_IS_BUSY'));
  $query = "SELECT * FROM tg_users WHERE phone = '$phone'";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	if (pg_num_rows($result) > 0) die(format_result('ERROR_PHONE_IS_BUSY'));
  $name = pg_escape_string($_POST["name"]);
  $surname = pg_escape_string($_POST["surname"]);
  $birthdate = pg_escape_string($_POST["birthdate"]);
  $ref_id = intval($_REQUEST["truid"]);
  if (!empty($birthdate)){
    $pattern = "#^[0-9]{4}-[0-9]{2}-[0-9]{2}$#isU";
    if (!preg_match($pattern, $birthdate)){
      die(format_result("ERROR_INVALID_BIRTHDATE"));
    }
  }
  $referal = pg_escape_string($_POST["referal"]);
  $promo = pg_escape_string($_POST["promo"]);
  $salt = generate_password(20);
  $email_token = generate_password(5, false, false, true, false);
  $phone_code = generate_password(5, false, false, true, false);
  $user_pass = md5($password.":".$salt);
  $query = "INSERT INTO tg_users(username, password, salt, email, phone, \"status\", name, surname, birthdate, ";
  $query .= "reg_date, last_visit, phone_code, email_token, balance, bonus, ref_user) VALUES('$username', '$user_pass', ";
  $query .= "'$salt', '$email', '$phone', 5, '$name', '$surname', '$birthdate 00:00:00', CURRENT_TIMESTAMP, ";
  $query .= "CURRENT_TIMESTAMP, '$phone_code', '$email_token', '0.00', '0.00', $ref_id)";
  $queries[] = $query;
  pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  if ($ref_id > 0){
    $query = "SELECT COUNT(*) FROM tg_users WHERE ref_user = $ref_id";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $row = pg_fetch_array($result);
    $count = intval($row["count"]);
    if ($count > 0 && $count < 51){
      $query = "UPDATE tg_users SET ref_percent = 5 WHERE \"ID\" = $ref_id";
    }
    elseif ($count >= 51){
      $query = "UPDATE tg_users SET ref_percent = 10 WHERE \"ID\" = $ref_id";
    }
    $queries[] = $query;
    pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  }
  $query = "SELECT * FROM tg_users ORDER BY \"ID\" DESC LIMIT 1";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $user = pg_fetch_array($result);
  $user_id  = $user["ID"];
  send_sms($phone, "Код для подтвержения вашего номера телефона: $phone_code");
  $email_content = file_get_contents('templates/reg_user_email_template.txt');
  $email_content = str_replace("{code}", $email_token, $email_content);
  send_email($email, 'admin@truegamers.pro', 'Регистрация в системе Truegamers', $email_content);
  $query = "INSERT INTO tg_users_to_branches (user_id, branch_id) VALUES ($user_id, $branch_id)";
  $queries[] = $query;
  pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  //Получаем филиалы, в которых зарегистрировн пользователь
	$query = "SELECT * FROM tg_users_to_branches WHERE user_id = ".$user["ID"];
  $queries[] = $query;
	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	$user["branches"] = array();
	while ($branch = pg_fetch_array($result)){
		$user["branches"][] = $branch["branch_id"];
	}
	if ($user["is_superhost"] == 1){
		$user["is_superhost"] = true;
	}
	else{
		$user["is_superhost"] = false;
	}
	$user["rang_id"] = $user["rang"];
	$query = "SELECT * FROM tg_rang WHERE \"ID\" = ".$user["rang"];
  $queries[] = $query;
	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	$rang = pg_fetch_array($result);
	$user["rang"] = trim($rang["name"]);
	$result = array();
	$user["balance"] = str_replace('$', '', $user["balance"]);
	$user["balance"] = str_replace(',', '', $user["balance"]);
	$user["bonus"] = str_replace('$', '', $user["bonus"]);
	$user["bonus"] = str_replace(',', '', $user["bonus"]);
	if ($_SERVER["HTTP_USER_AGENT"] == 'Mozilla/3.0 (compatible) / Truegamers Admin Web Application'){
		$result["id"] = $user["ID"];
		$result["username"] = $user["username"];
		$result["email"] = $user["email"];
		$result["phone"] = $user["phone"];
		$result["status"] = $user["status"];
		$result["is_superhost"] = $user["is_superhost"];
		$result["name"] = $user["name"];
		$result["surname"] = $user["surname"];
		$result["birthdate"] = date('Y-m-d H:i:s', strtotime($user["birthdate"]));
		$result["reg_date"] = date('Y-m-d H:i:s', strtotime($user["reg_date"])).$user["time_zone"];
		$result["last_visit"] = date('Y-m-d H:i:s', strtotime($user["last_visit"])).$user["time_zone"];
		$result["game_time_minutes"] = intval($user["game_time"]);
		$result["rang"] = $user["rang"];
		$result["rang_id"] = intval($user["rang_id"]);
		$result["balance"] = (float)$user["balance"];
		$result["bonus_balance"] = (float)$user["bonus"];
		$result["branches"] = $user["branches"];
    $result["ref_user"] = $ref_id;
	}
	else {
		$result["id"] = $user["ID"];
		$result["username"] = $user["username"];
		$result["email"] = $user["email"];
		$result["phone"] = $user["phone"];
		$result["status"] = $user["status"];
		$result["is_superhost"] = $user["is_superhost"];
		$result["name"] = $user["name"];
		$result["surname"] = $user["surname"];
		$result["birthdate"] = date('Y-m-d H:i:s', strtotime($user["birthdate"]));
		$result["reg_date"] = date('Y-m-d H:i:s', strtotime($user["reg_date"])).$user["time_zone"];
		$result["last_visit"] = date('Y-m-d H:i:s', strtotime($user["last_visit"])).$user["time_zone"];
		$result["game_time_minutes"] = intval($user["game_time"]);
		$result["rang"] = $user["rang"];
		$result["rang_id"] = intval($user["rang_id"]);
		$result["balance"] = (float)$user["balance"];
		$result["bonus_balance"] = (float)$user["bonus"];
		$result["branches"] = $user["branches"];
    $result["ref_user"] = $ref_id;
	}
	$res["result"] = "RESULT_SUCCESS";
	$res["payload"] = $result;
	echo format_array($res);
?>
