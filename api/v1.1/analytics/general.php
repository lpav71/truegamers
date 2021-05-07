<?php
  $filter = $_REQUEST["filter"];
  if (empty($filter)){
    die(format_result("ERROR_EMPTY_FILTER"));
  }
  $filter = json_decode($filter, true);
  $branch_id = $filter["branch_id"];
  if (!is_array($branch_id)){
    die(format_result("ERROR_INVALID_BRANCH_ID"));
  }
  if (!empty($branch_id)){
    $branch_filter = "WHERE \"ID\" IN(".implode(',', $branch_id).")";
  }
  else {
    $branch_filter = "WHERE \"ID\" IN (".$allowed_branches_subquery.")";
  }
  if (!empty($filter["date_start"]) && !empty($filter["date_end"])){
    $date_start = pg_escape_string($filter["date_start"]);
    $date_end = pg_escape_string($filter["date_end"]);
    $interval = strtotime($date_end) - strtotime($date_start);
    $hours = round($interval / 3600, 5);
    $date_filter = "AND (\"date_start\" BETWEEN TO_TIMESTAMP('$date_start', 'YYYY-MM-DD HH24:MI:SS') AND TO_TIMESTAMP($date_end, 'YYYY-MM-DD HH24:MI:SS'))";
  }
  elseif (!empty($filter["date_start"])){
    $date_start = pg_escape_string($filter["date_start"]);
    $interval = time() - strtotime($date_start);
    $hours = round($interval / 3600, 5);
    $date_filter = " AND \"date_start\" >= TO_TIMESTAMP('$date_start', 'YYYY-MM-DD HH24:MI:SS')";
  }
  elseif (!empty($filter["date_end"])){
    die(format_result("ERROR_DATE_START_REQUIRED"));
    $date_end = pg_escape_string($filter["date_end"]);
    $date_filter = " AND \"date_start\" <= TO_TIMESTAMP('$date_end', 'YYYY-MM-DD HH24:MI:SS')";
  }
  elseif (empty($filter["date_start"]) && empty($filter["date_end"])){
    $month_start = date("Y-m")."-01 00:00:00";
    $interval = time() - strtotime($month_start);
    $hours = round($interval / 3600, 5);
    $date_filter = " AND \"date_start\" >= TO_TIMESTAMP('$month_start', 'YYYY-MM-DD HH24:MI:SS')";
  }
  if (!empty($_REQUEST["sort"])){
    $sort = $_REQUEST["sort"];
    $sort = json_decode($sort, true);
    if (is_array($sort)){
      $field = $sort["field"];
      $direction = $sort["order"];
    }
    else {
      $field = "city";
      $direction = "ASC";
    }
  }
  else {
    $field = "city";
    $direction = "ASC";
  }
  //Создаем временную таблицу
  $query = "DROP TABLE IF EXISTS analytics_general_temp";
  $queries[] = $query;
  pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $query = "CREATE TEMPORARY TABLE analytics_general_temp AS (SELECT *, '0' AS \"total\", '0' AS \"bar_total\", '0' AS \"receipt_count\", '0' AS \"average_receipt\", '0' AS \"average_occupancy\", '0' AS \"clients_total\", '0' AS \"new_clients\" FROM tg_branches $branch_filter)";
  $queries[] = $query;
  pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  //получаем список валют
  $query = "SELECT DISTINCT(currency_code) AS currency_code, currency FROM analytics_general_temp ORDER BY currency_code";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  while ($currency = pg_fetch_array($result)){
    $currencies[$currency["currency_code"]]["currency_code"] = $currency["currency_code"];
    $currencies[$currency["currency_code"]]["currency"] = $currency["currency"];
    $currencies[$currency["currency_code"]]["total"] = 0;
    $currencies[$currency["currency_code"]]["bar_total"] = 0;
    $currencies[$currency["currency_code"]]["receipt_count"] = 0;
    $currencies[$currency["currency_code"]]["average_receipt"] = 0;
  }
  $query = "SELECT * FROM analytics_general_temp";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  while ($branch = pg_fetch_array($result)){
    //Вычисляем выручку клуба
    $query = "SELECT SUM(kassa_total + bar_total + acquiring) AS \"total\", SUM(cash_coll + card_coll + acquiring_coll) AS receipt_count FROM tg_kassa_smena WHERE branch_id = ".$branch["ID"].$date_filter;
    $queries[] = $query;
    $res = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $row = pg_fetch_array($res);
    $queries[] = $row;
    $total = money_to_float($row["total"]);
    $bar_total = money_to_float($row["bar_total"]);
    $receipt_count = $row["receipt_count"];
    if ($receipt_count > 0){
      $average_receipt = round(($total - $bar_total) / $receipt_count, 2);
    }
    else {
      $average_receipt = 0;
    }
    //получаем количество компьютеров в клубе
    $query = "SELECT COUNT(*) AS comp_count FROM tg_pc_info WHERE branch_id = ".$branch["ID"];
    $queries[] = $query;
    $res = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $row = pg_fetch_array($res);
    $queries[] = $row;
    $comp_count = $row["comp_count"];
    //вычисляем время, наигранное за указанный интервал
    $query = "SELECT SUM(duration) AS \"duration\" FROM tg_game_time WHERE branch_id = ".$branch["ID"].$date_filter;
    $queries[] = $query;
    $res = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $row = pg_fetch_array($res);
    $queries[] = $row;
    if ($comp_count > 0){
      $duration = round($row["duration"] / 60, 5);
      $occupancy = round($duration / ($hours * $comp_count) * 100, 3);
    }
    else {
      $occupancy = 0;
    }
    //вычисляем количество клиентов в клубе
    $query = "SELECT COUNT(*) AS \"count\" FROM tg_users WHERE deleted = 0 AND banned = 0 AND \"ID\" IN (SELECT user_id FROM tg_users_to_branches WHERE branch_id = {$branch['ID']})";
    $queries[] = $query;
    $res = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $row = pg_fetch_array($res);
    $queries[] = $row;
    $users_count = $row["count"];
    //вычисляем количество новых пользователей
    $d_filter = str_replace('date_start', 'reg_date', $date_filter);
    $query = "SELECT COUNT(*) AS users_count FROM tg_users WHERE deleted = 0 AND banned = 0 $d_filter AND \"ID\" IN (SELECT user_id FROM tg_users_to_branches WHERE branch_id = {$branch['ID']})";
    $queries[] = $query;
    $res = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $row = pg_fetch_array($res);
    $queries[] = $row;
    $new_users_count = $row["users_count"];
    //записываем полученные данные во временную таблицу
    $query = "UPDATE analytics_general_temp SET total = '$total', bar_total = '$bar_total', receipt_count = '$receipt_count', average_receipt = '$average_receipt', ";
    $query .= "average_occupancy = '$occupancy', clients_total = '$users_count', new_clients = '$new_users_count' WHERE \"ID\" = ".$branch["ID"];
    $queries[] = $query;
    pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $branches[] = $branch;
  }
  $queries[] = $branches;
  //Извлекаем данные из временной таблицы
  $query = "SELECT \"ID\" AS \"id\", * FROM analytics_general_temp ORDER BY $field $direction";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $branches = array();
  $occupancy_sum = 0;
  $total_data = array("average_occupancy"=>0);
  while ($branch = pg_fetch_array($result)){
    unset($branch["ID"]);
    $rec_count = $branch['receipt_count'];
    $branch = normalize_array($branch, array("active", "country_code", "time_zone", "local_ip", "local_port", 'receipt_count'));
    $currencies[$branch["currency_code"]]["total"] += $branch["total"];
    $currencies[$branch["currency_code"]]["bar_total"] += $branch["bar_total"];
    $currencies[$branch["currency_code"]]["receipt_count"] += intval($rec_count);
    if ( $currencies[$branch["currency_code"]]["receipt_count"] > 0){
      $currencies[$branch["currency_code"]]["average_receipt"] = round(($currencies[$branch["currency_code"]]["total"] - $currencies[$branch["currency_code"]]["bar_total"]) / $currencies[$branch["currency_code"]]["receipt_count"], 2);
    }
    else{
      $currencies[$branch["currency_code"]]["average_receipt"] = 0;
    }
    $occupancy_sum += $branch["average_occupancy"];
    $total_data["clients_count"] += $branch["clients_total"];
    $total_data["new_clients_count"] += $branch["new_clients"];
    $branches[] = $branch;
  }
  if (count($branches) > 0){
    $total_data["average_occupancy"] = $occupancy_sum / count($branches);
  }
  $queries[] = $branches;
  $result = array("result"=>"RESULT_SUCCESS");
  $result["payload"] = array();
  $result["payload"]["items"] = $branches;
  $result["payload"]["currencies_total"] = $currencies;
  $result["payload"]["total"] = $total_data;
  $result["payload"]["count"] = count($branches);
  //Уничтожаем временную таблицу
  $query = "DROP TABLE IF EXISTS analytics_general_temp";
  $queries[] = $query;
  pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  exit(format_array($result));
