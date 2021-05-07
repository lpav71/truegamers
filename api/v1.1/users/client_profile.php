<?php
  $usr_id = intval($arr[1]);
  if ($usr_id < 1){
    die(format_result("ERROR_INVALID_USER_ID"));
  }
  $query = "SELECT * FROM \"tg_users\" WHERE \"ID\" = $usr_id";
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $usr = pg_fetch_array($result);
  if (empty($usr)){
    die(format_result("ERROR_USER_DOES_NOT_EXISTS"));
  }
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
  //получаем любимый тариф пользователя
  $query = "SELECT  COUNT(*) AS cnt, packet_id FROM tg_reservation WHERE user_id = {$usr['ID']} GROUP BY packet_id ORDER BY cnt DESC LIMIT 1";
  $queries[] = $query;
	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $tarif = pg_fetch_array($result);
  $queries[] = $tarif;
  if ($tarif["packet_id"] > 0){
    $query = "SELECT * FROM tg_prices WHERE \"ID\" = ".$tarif["packet_id"];
    $queries[] = $query;
  	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $tarif = pg_fetch_array($result);
    $queries[] = $tarif;
  }
  else {
    $tarif["name"] = "Поминутный";
  }
  $favorite_tarif = trim($tarif["name"]);
  pg_free_result($result);
	if ($_SERVER["HTTP_USER_AGENT"] == 'Mozilla/3.0 (compatible) / Truegamers Admin Web Application'){
    $result = array();
		$result["id"] = intval($usr["ID"]);
		$result["username"] = $usr["username"];
		$result["email"] = $usr["email"];
		$result["phone"] = $usr["phone"];
		$result["status"] = $usr["status"];
    $result["comment"] = $user["comment"];
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
    $result = array();
		$result["id"] = intval($usr["ID"]);
		$result["username"] = $usr["username"];
		$result["email"] = $usr["email"];
		$result["phone"] = $usr["phone"];
		$result["status"] = $usr["status"];
    $result["comment"] = $user["comment"];
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
  if ($usr["deleted"] == 1){
    $result["active"] = "deleted";
  }
  else {
    if ($usr["banned"] == 1){
      $result["active"] = "banned";
    }
    else {
      $result["active"] = "active";
    }
  }
  $result["favorite_tarif"] = $favorite_tarif;
	$res["result"] = "RESULT_SUCCESS";
	$res["payload"] = $result;
	echo format_array($res);
?>
