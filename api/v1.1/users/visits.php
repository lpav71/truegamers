<?php
  if (!empty($_REQUEST["filter"])){
    $filter = $_REQUEST["filter"];
    $filter = json_decode($filter, true);
  }
  $usr_id = intval($filter["user_id"]);
  if ($usr_id < 1){
    die(format_result("ERROR_EMPTY_USER_ID"));
  }
  else{
    $query = "SELECT * FROM tg_users WHERE \"ID\" = $usr_id";
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    if (pg_num_rows($result) < 1){
      die(format_result("ERROR_USER_DOES_NOT_EXISTS"));
    }
    $usr = pg_fetch_array($result);
    $queries[] = $usr;
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
    $res["id"] = intval($usr["ID"]);
		$res["username"] = $usr["username"];
		$res["email"] = $usr["email"];
		$res["phone"] = $usr["phone"];
		$res["status"] = $usr["status"];
		$res["is_superhost"] = $usr["is_superhost"];
		$res["name"] = $usr["name"];
		$res["surname"] = $usr["surname"];
		$res["birthdate"] = date('Y-m-d H:i:s', strtotime($usr["birthdate"]));
		$res["reg_date"] = date('Y-m-d H:i:s', strtotime($usr["reg_date"])).$usr["time_zone"];
		$res["last_visit"] = date('Y-m-d H:i:s', strtotime($usr["last_visit"])).$usr["time_zone"];
		$res["game_time_minutes"] = intval($usr["game_time"]);
		$res["rang"] = $usr["rang"];
		$res["balance"] = (float)$usr["balance"];
		$res["bonus_balance"] = (float)$usr["bonus"];
		$res["branches"] = $usr["branches"];
  }
  if (!empty($_REQUEST["sort"])){
    $sort = $_REQUEST["sort"];
    $sort = json_decode($sort, true);
    if (is_array($sort)){
      $field = $sort["field"];
      $direction = $sort["order"];
    }
    else {
      $field = "date_start";
      $direction = "DESC";
    }
  }
  else {
    $field = "date_start";
    $direction = "DESC";
  }
  $query = "SELECT * FROM tg_game_time WHERE user_id = $usr_id ORDER BY \"$field\" $direction";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $visits = array();
  while ($visit = pg_fetch_array($result)){
    $visit = exclude_indexes($visit);
    $row["id"] = intval($visit["ID"]);
    unset($visit["ID"]);
    unset($visit[0]);
    $visit = normalize_array($visit, array("ID", "subscription", "0"), $usr["time_zone"]);
    foreach ($visit as $key=>$value){
      $row[$key] = $value;
    }
    unset($row["ID"]);
    unset($row[0]);
    $visits[] = $row;
  }
  $output = array("result"=>"RESULT_SUCCESS", "payload"=>array("items"=>$visits, "client"=>$res,"total"=>count($visits)));
  exit(format_array($output));
?>
