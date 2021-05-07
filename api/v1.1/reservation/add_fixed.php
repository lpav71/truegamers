<?php
  $comp_id = intval($_REQUEST["comp_id"]);
  if ($comp_id < 1){
    die(format_result("ERROR_EMPTY_COMP_ID"));
  }
  $user_id = intval($_REQUEST["user_id"]);
  if ($user_id < 1){
    $user_id = $user["ID"];
  }
  $branch_id = intval($_REQUEST["branch_id"]);
  if ($branch_id < 1){
    //Пытаемся получить филиал по IP
    $ip = $_SERVER["REMOTE_ADDR"];
    $query = "SELECT * FROM tg_branches WHERE ip = '$ip'";
    //echo $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    if (pg_num_rows($result) < 1){
      die(format_result("ERROR_REQUIRED_BRANCH_ID"));
    }
    else{
      $branch = pg_fetch_array($result);
      $branch_id = intval($branch["ID"]);
    }
  }
  $packet_id = intval($_REQUEST["packet_id"]);
  if (!isset($_REQUEST["packet_id"])){
    die(format_result("ERROR_EMPTY_PACKET_ID"));
  }
  $start = pg_escape_string($_REQUEST["start"]);
  if (empty($start)){
    die(format_result("ERROR_EMPTY_START"));
  }
  $start = str_replace('%20', ' ', $start);
  $end = pg_escape_string($_REQUEST["end"]);
  if (empty($end)){
    die(format_result("ERROR_EMPTY_END"));
  }
  $end = str_replace('%20', ' ', $end);
  $sum = pg_escape_string($_REQUEST["sum"]);
  if (empty($sum)){
    die(format_result("ERROR_EMPTY_SUM"));
  }
  else {
    $sum = check_money($sum);
    if (!$sum){
      die(format_result("ERROR_INVALID_SUM"));
    }
  }
  $duration = strtotime($end) - strtotime($start);
  $duration = round($duration / 60);
  if ($packet_id > 0){
    $query = "SELECT * FROM tg_prices WHERE \"ID\" = $packet_id";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    if (pg_num_rows($result) < 1){
      die(format_result("ERROR_INVALID_PACKET"));
    }
    $tarif = pg_fetch_array($result);
    //$duration = intval($tarif["duration"]);
    if ($duration < 1){
      $duration = 720;
    }
  }
  else{
    //$duration = 720;
    //Проверяем наличие будущей резервации на этом компе
    $query = "SELECT * FROM tg_reservation WHERE \"start\" > CURRENT_TIMESTAMP AND comp_id = $comp_id AND branch_id = $branch_id";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    if (pg_num_rows($result) > 0){
      //Будущая резервация есть, вычисляем duration
      $future_reservation = pg_fetch_array($result);
      if (intval($future_reservation["user_id"]) == intval($user_id)){
        //Будущая резервация принадлежит текущему пользователю
        $real_duration = $duration;
      }
      $query = "SELECT EXTRACT(EPOCH FROM (SELECT \"start\" FROM tg_reservation WHERE \"start\" > CURRENT_TIMESTAMP AND comp_id = $comp_id AND branch_id = $branch_id ORDER BY \"start\" ASC LIMIT 1) - (CURRENT_TIMESTAMP)) / 60 AS duration";
      $queries[] = $query;
      $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      $row = pg_fetch_array($result);
      $duration = floor($row["duration"]) - 1;
    }
  }
  //Проверяем баланс у пользователя и списываем нужную сумму
  $query = "SELECT * FROM tg_prices WHERE \"ID\" = $packet_id";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $tarif = pg_fetch_array($result);
  if ($tarif["tarif_category"] == 1){
    $transaction = 8;
    $query = "UPDATE tg_reservation SET \"status\" = 2 WHERE comp_id = $comp_id AND branch_id = $branch_id AND packet_id = 0 AND \"status\" = 1 AND \"start\" < CURRENT_TIMESTAMP";
    $queries[] = $query;
    pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  }
  else {
    $transaction = 7;
  }
  $query = "UPDATE tg_reservation SET \"status\" = 2 WHERE comp_id = $comp_id AND branch_id = $branch_id AND packet_id = 0 AND \"status\" = 0 AND \"start\" < CURRENT_TIMESTAMP";
  $queries[] = $query;
  pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  //Проверяем, не попадаем ли мы на чужую резервацию
	$query = "SELECT * FROM tg_reservation WHERE \"start\" <= TO_TIMESTAMP('$start', 'YYYY-MM-DD HH24:MI:SS') AND \"end\" > TO_TIMESTAMP('$start', 'YYYY-MM-DD HH24:MI:SS') AND (comp_id = $comp_id AND branch_id= $branch_id) AND \"status\" = 0 UNION ALL ";//попадаем концовкой в чужую резервацию
	$query .= "SELECT * FROM tg_reservation WHERE \"start\" < TO_TIMESTAMP('$start', 'YYYY-MM-DD HH24:MI:SS') + INTERVAL '$duration MINUTE' AND \"end\"";//Попадаем началом в чужую резервацию
	$query .= " >= TO_TIMESTAMP('$start', 'YYYY-MM-DD HH24:MI:SS') + INTERVAL '$duration MINUTE' AND (comp_id = $comp_id  AND branch_id= $branch_id) AND \"status\" = 0 ";
	$query .= "UNION ALL SELECT * FROM tg_reservation WHERE \"start\" <= TO_TIMESTAMP('$start', 'YYYY-MM-DD HH24:MI:SS') AND \"end\" >= TO_TIMESTAMP('$start', 'YYYY-MM-DD HH24:MI:SS') + INTERVAL '$duration MINUTE' AND (comp_id = $comp_id AND branch_id= $branch_id) AND \"status\" = 0 UNION ALL ";//Попадаем целиком в чужую резервацию
	$query .= "SELECT * FROM tg_reservation WHERE \"start\" >= TO_TIMESTAMP('$start', 'YYYY-MM-DD HH24:MI:SS') AND \"end\" <= TO_TIMESTAMP('$start', 'YYYY-MM-DD HH24:MI:SS') + INTERVAL '$duration MINUTE' AND (comp_id = $comp_id AND branch_id= $branch_id) AND \"status\" = 0 ";//Чужая резервация целиком попадает в нашу
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  if (pg_num_rows($result) > 0) die(format_result('ERROR_CROSSING_RESERVATIONS'));
  //Отправляем старые поминутки в аут
  $query = "UPDATE tg_reservation SET status = 2 WHERE comp_id = $comp_id AND packet_id = 0 AND branch_id = $branch_id AND \"start\" < CURRENT_TIMESTAMP";
  pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  if ($real_duration > 0){
    //$duration = $real_duration;
  }
  if (intval($tarif["tarif_category"]) == 1){
    //Покупка пакета, проверяем баланс пользователя и списываем деньги
    $query = "SELECT * FROM tg_users WHERE \"ID\" = $user_id";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $row = pg_fetch_array($result);
    if (money_to_float($row["balance"]) >= money_to_float($tarif["price"])){
      $price = money_to_float($tarif["price"]);
      $price = $sum;
      $query = "UPDATE tg_users SET balance = balance - '$price' WHERE \"ID\" = $user_id";
      $queries[] = $query;
      pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      //Пишем уведомление для пользователя
      $tarif_name = trim($tarif["name"]);
      $query = "INSERT INTO tg_notification (description, transaction, pay_type, transaction_type, \"money\", user_id, branch_id, \"date\", reservation_id) VALUES (";
      $query .= "'Покупка тарифа: $tarif_name на компьютере № $comp_id', $transaction, 0, 1, '$price', $user_id, $branch_id, CURRENT_TIMESTAMP, 0)";
      $queries[] = $query;
      pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      $query = "SELECT MAX(\"ID\") AS \"id\" FROM tg_notification LIMIT 1";
      $queries[] = $query;
      $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      $row = pg_fetch_array($result);
      $notification_id = intval($row["id"]);
      if (session_status() == PHP_SESSION_NONE) session_start();
      $_SESSION["notification_id"] = $notification_id;
      //$duration = $tarif["duration"];
      $subscription = 2;
    }
    else {
      die(format_result('ERROR_BALANCE_NOT_ENOUGH'));
    }
  }
  else{
    $price = 0;
    $subscription = 0;
    $query = "INSERT INTO tg_notification (description, transaction, pay_type, transaction_type, \"money\", user_id, branch_id, \"date\", reservation_id) VALUES (";
    $query .= "'Покупка поминутного тарифа на компьютере № $comp_id', $transaction, 0, 1, '$price', $user_id, $branch_id, CURRENT_TIMESTAMP, 0)";
    $queries[] = $query;
    pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $query = "SELECT MAX(\"ID\") AS \"id\" FROM tg_notification LIMIT 1";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $row = pg_fetch_array($result);
    $notification_id = intval($row["id"]);
    if (session_status() == PHP_SESSION_NONE) session_start();
    $_SESSION["notification_id"] = $notification_id;
  }
  $query = "INSERT INTO tg_reservation (comp_id, \"user_id\", packet_id, \"start\", \"end\", \"status\", created, creator_id, email_token, branch_id) VALUES (
		$comp_id,
		$user_id,
		$packet_id,
		TO_TIMESTAMP('$start', 'YYYY-MM-DD HH24:MI:SS'),
		TO_TIMESTAMP('$start', 'YYYY-MM-DD HH24:MI:SS') + INTERVAL '$duration MINUTE',
		0,
		CURRENT_TIMESTAMP,
		$user_id,
		'',
		$branch_id
	)";
  $queries[] = $query;
	pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $query = "SELECT * FROM tg_reservation ORDER BY \"ID\" DESC LIMIT 1";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $reservation = pg_fetch_array($result);
  $queries[] = $reservation;
  $fixed = $tarif["time_fix_end"];
  if (!empty($fixed)){
    $time_start = strtotime($reservation["start"]);
    $time_end = strtotime($reservation["end"]);
    $date_end = date("Y-m-d", $time_end);
    $fixed_time = strtotime($date_end." ".$fixed);
    if ($fixed_time > $time_start && $fixed_time < $time_end){
      $time_end = date("Y-m-d H:i:s", $fixed_time);
      $query = "UPDATE tg_reservation SET \"end\" = '$time_end' WHERE \"ID\" = ".$reservation["ID"];
      $queries[] = $query;
    	pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      $query = "SELECT * FROM tg_reservation ORDER BY \"ID\" DESC LIMIT 1";
      $queries[] = $query;
      $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      $reservation = pg_fetch_array($result);
      $queries[] = $reservation;
    }
  }
  if ($reservation["packet_id"] > 0){
    //проверяем интервал времени до следующей резервации этого компа (для создания повторной поминутки)
    $query = "SELECT *, \"start\" - (SELECT \"end\" FROM tg_reservation WHERE \"ID\" = ".$reservation["ID"].") AS \"interval\" FROM tg_reservation WHERE branch_id = $branch_id AND comp_id = $comp_id AND \"start\" > ";
    $query .= "(SELECT \"end\" FROM tg_reservation WHERE \"ID\" = ".$reservation["ID"].") ORDER BY \"start\" LIMIT 1";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $next_reservation = pg_fetch_array($result);
    if (!empty($next_reservation)){
      $arr = explode(":", $next_reservation["interval"]);
      $interval = intval($arr[0]) * 60 + $arr[1];
      if ($interval > 720) $interval = 720;
      else $interval--;
     }
    else{
      $interval = 720;
    }
    /*if ($interval >= 5){
      $query = "INSERT INTO tg_reservation (comp_id, \"user_id\", packet_id, \"start\", \"end\", \"status\", created, creator_id, email_token, branch_id) VALUES (
    		$comp_id,
    		$user_id,
    		0,
    		(SELECT \"end\" FROM tg_reservation WHERE \"ID\" = ".$reservation["ID"].") - INTERVAL '1 MINUTE',
    		(SELECT \"end\" FROM tg_reservation WHERE \"ID\" = ".$reservation["ID"].") + INTERVAL '$interval MINUTE',
    		0,
    		CURRENT_TIMESTAMP,
    		$user_id,
    		'',
    		$branch_id
    	)";
      pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      pg_free_result($result);
      $query = "SELECT * FROM tg_reservation WHERE \"ID\" = (SELECT MAX(\"ID\") FROM tg_reservation WHERE branch_id = $branch_id AND comp_id = $comp_id)";
      $queries[] = $query;
      $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      $nextreservation = pg_fetch_array($result);
      $queries[] = $nextreservation;
    }*/
  }
  //$queries[] = $next_reservation;
  $res_id = $reservation["ID"];
  $query = "UPDATE tg_notification SET reservation_id = $res_id WHERE \"ID\" = $notification_id";
  $queries[] = $query;
  pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $query = "INSERT INTO tg_game_time(user_id, date_start, date_end, duration, comp_id, reservation_id, \"money\", bonus, subscription, branch_id)";
  $query .= "VALUES($user_id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 0, $comp_id, $res_id, '$price', 0, $subscription, $branch_id)";
  $queries[] = $query;
  pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $output = array("result"=>"RESULT_SUCCESS", "payload"=>array());
  $output["payload"] = exclude_indexes($reservation);
  if (!empty($nextreservation)){
    //$output["future_reservation"] = exclude_indexes($nextreservation);
  }
  $query = "SELECT * FROM tg_users WHERE \"ID\" = $user_id";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $usr = pg_fetch_array($result);
  $output["payload"]["user"]["id"] = intval($usr["ID"]);
  $output["payload"]["user"]["balance"] = money_to_float($usr["balance"]);
  $output["payload"]["user"]["bonus_balance"] = money_to_float($usr["bonus"]);
  exit(format_array($output));
?>
