<?php
    /**************************************************************************************************
     ************************Выведение баланса пользователя********************************************
     **************************************************************************************************/
    /**************************************************************************************************
     * Принимаемые параметры:
     *     id (число) - ID пользователя
     *     balance (число с плавающей точкой, например 230.16) - баланс пользователя
     *************************************************************************************************/
    //Проверяем, авторизован ли пользователь
    $id = intval($_REQUEST["id"]);
    if ($id > 1){
        //проверяем права доступа у текущего пользователя - он не должен быть простым пользователем
    }
    else {
      $id = $user["ID"];
    }
    if (empty($_REQUEST["sum"])){
      die(format_result("ERROR_EMPTY_SUM"));
    }
    $sum = pg_escape_string($_REQUEST["sum"]);
    //Проверяем сумму на корректность
    $sum = check_money($sum);
    if (!$sum){
      die(format_result("ERROR_INVALID_SUM"));
    }
    $action = $_REQUEST["action"];
    if (empty($action)){
      die(format_result("ERROR_EMPTY_ACTION"));
    }
    if ($action != 'decrement_balance' && $action != 'decrement_bonus'){
      die(format_result("ERROR_INVALID_ACTION"));
    }
    if ($action == 'decrement_balance'){
      //Проверяем, можем ли мы списать с баланса
      $query = "SELECT \"balance\" FROM tg_users WHERE \"ID\" = $id";
      $queries[] = $query;
      $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      $usr = pg_fetch_array($result);
      $balance = $usr["balance"];
      $balance = (float)str_replace(array(",", '$'), "", $balance);
      if ($balance < $sum){
        die(format_result("ERROR_BALANCE_NOT_ENOUGH"));
      }
      //Проверяем, нужно ли увеличивать потраченные деньги в tg_game_time
      $reservation_id = intval($_REQUEST["reservation_id"]);
      if ($reservation_id > 0){
        $query = "SELECT * FROM tg_reservation WHERE \"ID\" = $reservation_id";
        $queries[] = $query;
        $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
        $reservation = pg_fetch_array($result);
        if ($reservation["packet_id"] == 0){
          $query = "UPDATE tg_users SET \"balance\" = \"balance\" - '$sum'::money WHERE \"ID\" = $id";
          $queries[] = $query;
          pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
          $query = "UPDATE tg_game_time SET \"money\" = \"money\" + '$sum'::money, duration = duration + 1, date_end = date_end + INTERVAL '1 MINUTE' WHERE reservation_id = $reservation_id";
          $queries[] = $query;
          pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
          $query = "UPDATE tg_users SET game_time = game_time + 1 WHERE \"ID\" = $id";
          $queries[] = $query;
          pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
          if (session_status() == PHP_SESSION_NONE) session_start();
          $notification_id = intval($_SESSION["notification_id"]);
          $query = "UPDATE tg_notification SET \"money\" = \"money\" + '$sum'::money WHERE \"ID\" = $notification_id";
          $queries[] = $query;
          pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
        }
        else{
          $query = "UPDATE tg_game_time SET duration = duration + 1, date_end = date_end + INTERVAL '1 MINUTE' WHERE reservation_id = $reservation_id";
          $queries[] = $query;
          pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
          $query = "UPDATE tg_users SET game_time = game_time + 1 WHERE \"ID\" = $id";
          $queries[] = $query;
          pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
        }
      }
      else{
        $query = "UPDATE tg_users SET \"balance\" = \"balance\" - '$sum'::money WHERE \"ID\" = $id";
        $queries[] = $query;
        pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      }
    }
    else {
      //Проверяем, можем ли мы списать с баланса
      $query = "SELECT \"bonus\" FROM tg_users WHERE \"ID\" = $id";
      $queries[] = $query;
      $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      $usr = pg_fetch_array($result);
      $bonus = $usr["bonus"];
      $bonus = (float)str_replace(array(",", '$'), "", $bonus);
      if ($bonus < $sum){
        die(format_result("ERROR_BONUSES_NOT_ENOUGH"));
      }
      //Проверяем, нужно ли увеличивать потраченные деньги в tg_game_time
      $reservation_id = intval($_REQUEST["reservation_id"]);
      if ($reservation_id > 0){
        $query = "SELECT * FROM tg_reservation WHERE \"ID\" = $reservation_id";
        $queries[] = $query;
        $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
        $reservation = pg_fetch_array($result);
        if ($reservation["packet_id"] == 0){
          $query = "UPDATE tg_game_time SET \"bonus\" = \"bonus\" + '$sum'::money, duration = duration + 1, date_end = date_end + INTERVAL '1 MINUTE' WHERE reservation_id = $reservation_id";
          $queries[] = $query;
          pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
          $query = "UPDATE tg_users SET \"bonus\" = \"bonus\" - '$sum'::money WHERE \"ID\" = $id";
          $queries[] = $query;
          pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
          $query = "UPDATE tg_users SET game_time = game_time + 1 WHERE \"ID\" = $id";
          $queries[] = $query;
          pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
          if (session_status() == PHP_SESSION_NONE) session_start();
          $notification_id = intval($_SESSION["notification_id"]);
          $query = "UPDATE tg_notification SET \"money\" = \"money\" + '$sum'::money WHERE \"ID\" = $notification_id";
          $queries[] = $query;
          pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
        }
        else{
          $query = "UPDATE tg_game_time SET duration = duration + 1, date_end = date_end + INTERVAL '1 MINUTE' WHERE reservation_id = $reservation_id";
          $queries[] = $query;
          pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
          $query = "UPDATE tg_users SET game_time = game_time + 1 WHERE \"ID\" = $id";
          $queries[] = $query;
          pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
        }
      }
      else{
        $query = "UPDATE tg_users SET \"bonus\" = \"bonus\" - '$sum'::money WHERE \"ID\" = $id";
        $queries[] = $query;
        pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      }
    }
    $query = "SELECT * FROM tg_users WHERE \"ID\" = $id";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $usr = pg_fetch_array($result);
    $queries[] = $usr;
    pg_free_result($result);
    //Проверяем ранг пользователя
    $game_time = intval($usr["game_time"]);
    $query = "SELECT * FROM tg_rang WHERE duration >= $game_time AND branch_id = (SELECT branch_id FROM tg_users_to_branches WHERE user_id = $id ORDER BY \"ID\" LIMIT 1) ORDER BY num ASC LIMIT 1";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $rang = pg_fetch_array($result);
    $queries[] = $rang;
    $rang_updated = false;
    if ($game_time == $rang["duration"] && $rang["num"] != $usr["rang"]){
      $new_rang = $rang["num"];
      $bonus = money_to_float($rang["bonus"]);
      //У пользователя новый ранг - записываем его и начисляем бонус
      $query = "UPDATE tg_users SET rang = $new_rang, bonus = bonus + '$bonus'::money WHERE \"ID\" = $id";
      $queries[] = $query;
      pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      $rang_updated = true;
    }
    $result = array();
    $result["id"] = $user["ID"];
    $result["balance"] = (float)str_replace(array(",", '$'), "", $usr["balance"]);
		$result["bonus_balance"] = (float)str_replace(array(",", '$'), "", $usr["bonus"]);
    $result["new_rang"] = array("updated"=>$rang_updated, "id"=>$new_rang, "name"=>$rang["name"]);
    $res["result"] = "RESULT_SUCCESS";
  	$res["payload"] = $result;
    //print_r($queries);
    //$query = "UPDATE tg_request SET queries = '".serialize($queries)."' WHERE \"ID\" = $request_insert_id";
    //@pg_query($query);
  	echo format_array($res);
    exit();
?>
