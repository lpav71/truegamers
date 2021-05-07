<?php
  $usr_id = intval($arr[1]);
  if ($usr_id < 1){
    die(format_result("ERROR_INVALID_USER_ID"));
  }
  $query = "SELECT * FROM \"tg_users\" WHERE \"ID\" = $usr_id";
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  if (pg_num_rows($result) < 1){
    die(format_result("ERROR_USER_DOES_NOT_EXISTS"));
  }
  $usr = pg_fetch_array($result);
  $queries[] = $usr;
  $sum = $_REQUEST["sum"];
  if (empty($sum)){
    die(format_result("ERROR_EMPTY_SUM"));
  }
  else{
    if (!check_money($sum)){
      die(format_result("ERROR_INVALID_SUM"));
    }
  }
  $field = $_REQUEST["field"];
  if (empty($field)){
    die(format_result("ERROR_EMPTY_FIELD"));
  }
  elseif ($field != "balance" && $field != "bonus"){
    die(format_result("ERROR_INVALID_FIELD"));
  }
  $action = $_REQUEST["action"];
  if (empty($action)){
    die(format_result("ERROR_EMPTY_ACTION"));
  }
  elseif ($action != "increment" && $action != "decrement"){
    die(format_result("ERROR_INVALID_ACTION"));
  }
  if ($action == "decrement"){
    //проверяем хватает ли денег на балансе / бонусах
    $query = "SELECT * FROM tg_users WHERE \"ID\" = $usr_id";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $usr = pg_fetch_array($result);
    $queries[] = $usr;
    if ($field == "balance"){
      $balance = money_to_float($usr["balance"]);
      if ($balance < $sum){
        die(format_result("ERROR_BALANCE_NOT_ENOUGH"));
      }
      $query = "UPDATE tg_users SET balance = balance - '$sum'::money WHERE \"ID\" = $usr_id";
    }
    else{
      $bonus = money_to_float($usr["bonus"]);
      if ($bonus < $sum){
        die(format_result("ERROR_BONUS_BALANCE_NOT_ENOUGH"));
      }
      $query = "UPDATE tg_users SET bonus = bonus - '$sum'::money WHERE \"ID\" = $usr_id";
    }
  }
  else {
    if ($field == "balance"){
      $query = "UPDATE tg_users SET balance = balance + '$sum'::money WHERE \"ID\" = $usr_id";
    }
    else{
      $query = "UPDATE tg_users SET bonus = bonus + '$sum'::money WHERE \"ID\" = $usr_id";
    }
  }
  $queries[] = $query;
  pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $query = "SELECT * FROM \"tg_users\" WHERE \"ID\" = $usr_id";
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $usr = pg_fetch_array($result);
  //Получаем филиалы, в которых зарегистрировн пользователь
	$query = "SELECT * FROM tg_users_to_branches WHERE user_id = ".$usr["ID"];
  $queries[] = $query;
	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	$usr["branches"] = array();
	while ($branch = pg_fetch_array($result)){
    $query = "SELECT * FROM tg_branches WHERE \"ID\" = ".$branch["branch_id"];
    $queries[] = $query;
		$r = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
		$br = pg_fetch_array($r);
		pg_free_result($r);
		$usr["branches"][] = array("id"=>intval($branch["branch_id"]), "name"=>$br["name"]);
	}
	$_SESSION["user_id"] = $usr["ID"];
	if ($usr["is_superhost"] == 1){
		$usr["is_superhost"] = true;
	}
	else{
		$usr["is_superhost"] = false;
	}
  $usr["rang_id"] = $usr["rang"];
	$query = "SELECT * FROM tg_rang WHERE branch_id = ".$usr["branches"][0]["id"]." AND num = ".$user["rang"];
	$queries[] = $query;
	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	$rang = pg_fetch_array($result);
	$usr["rang"] = array("id"=>intval($usr["rang"]), "name"=>trim($rang["name"]), "num"=>intval($rang["num"]), "duration"=>intval($rang["duration"]));
	//Вычисляем следующий ранг
	$query = "SELECT * FROM tg_rang WHERE branch_id = ".$usr["branches"][0]["id"]." AND num > ".$usr["rang"]["id"]." ORDER BY num LIMIT 1";
	$queries[] = $query;
	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
	$next_rang = pg_fetch_array($result);
	$required_duration = $next_rang["duration"] - $rang["duration"];
	$current_duration = $usr["game_time"] - $rang["duration"];
  if ($required_duration > 0){
			$rang_progress = round($current_duration / $required_duration * 100);
	}
	else {
		$rang_progress = 0;
	}
	$usr["rang"]["progress"] = intval($rang_progress);
	$usr["rang"]["next_id"] = intval($next_rang["ID"]);
	$usr["rang"]["next_num"] = intval($next_rang["num"]);
	$usr["rang"]["next_name"] = $next_rang["name"];
	$usr["rang"]["next_duration"] = intval($next_rang["duration"]);
	$result = array();
	/*print_r($usr);
	exit();*/
	$usr["balance"] = str_replace('$', '', $usr["balance"]);
	$usr["balance"] = str_replace(',', '', $usr["balance"]);
	$usr["bonus"] = str_replace('$', '', $usr["bonus"]);
	$usr["bonus"] = str_replace(',', '', $usr["bonus"]);
	if ($_SERVER["HTTP_USER_AGENT"] == 'Mozilla/3.0 (compatible) / Truegamers Admin Web Application'){
		$result["id"] = intval($usr["ID"]);
		$result["username"] = $usr["username"];
		$result["email"] = $usr["email"];
		$result["phone"] = $usr["phone"];
		$result["status"] = $usr["status"];
		$result["is_superhost"] = $usr["is_superhost"];
		$result["name"] = $usr["name"];
		$result["surname"] = $usr["surname"];
		$result["birthdate"] = date('Y-m-d H:i:s', strtotime($usr["birthdate"]));
		$result["reg_date"] = date('Y-m-d H:i:s', strtotime($usr["reg_date"])).$usr["time_zone"];
		$result["last_visit"] = date('Y-m-d H:i:s', strtotime($usr["last_visit"])).$usr["time_zone"];
		$result["game_time_minutes"] = intval($usr["game_time"]);
		$result["rang"] = $usr["rang"];
		$result["balance"] = (float)$usr["balance"];
		$result["bonus_balance"] = (float)$usr["bonus"];
		$result["branches"] = $usr["branches"];
	}
	else {
		$result["id"] = intval($usr["ID"]);
		$result["username"] = $usr["username"];
		$result["email"] = $usr["email"];
		$result["phone"] = $usr["phone"];
		$result["status"] = $usr["status"];
		$result["is_superhost"] = $usr["is_superhost"];
		$result["name"] = $usr["name"];
		$result["surname"] = $usr["surname"];
		$result["birthdate"] = date('Y-m-d H:i:s', strtotime($usr["birthdate"]));
		$result["reg_date"] = date('Y-m-d H:i:s', strtotime($usr["reg_date"])).$usr["time_zone"];
		$result["last_visit"] = date('Y-m-d H:i:s', strtotime($usr["last_visit"])).$usr["time_zone"];
		$result["game_time_minutes"] = intval($usr["game_time"]);
		$result["rang"] = $usr["rang"];
		$result["balance"] = (float)$usr["balance"];
		$result["bonus_balance"] = (float)$usr["bonus"];
		$result["branches"] = $usr["branches"];
	}
	$res["result"] = "RESULT_SUCCESS";
	$res["payload"] = $result;
	echo format_array($res);
?>
