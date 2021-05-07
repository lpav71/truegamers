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
    $branch_filter = "SELECT user_id FROM tg_users_to_branches WHERE \"branch_id\" IN(".implode(',', $branch_id).")";
  }
  else {
    $branch_filter = "SELECT user_id FROM tg_users_to_branches WHERE \"branch_id\" IN (".$allowed_branches_subquery.")";
  }
  if (!empty($filter["date_start"]) && !empty($filter["date_end"])){
    $date_start = pg_escape_string($filter["date_start"]);
    $date_end = pg_escape_string($filter["date_end"]);
    $interval = strtotime($date_end) - strtotime($date_start);
    $hours = round($interval / 3600, 5);
    $date_filter = "AND (\"date\" BETWEEN TO_TIMESTAMP('$date_start', 'YYYY-MM-DD HH24:MI:SS') AND TO_TIMESTAMP($date_end, 'YYYY-MM-DD HH24:MI:SS'))";
  }
  elseif (!empty($filter["date_start"])){
    $date_start = pg_escape_string($filter["date_start"]);
    $interval = time() - strtotime($date_start);
    $hours = round($interval / 3600, 5);
    $date_filter = " AND \"date\" >= TO_TIMESTAMP('$date_start', 'YYYY-MM-DD HH24:MI:SS')";
  }
  elseif (!empty($filter["date_end"])){
    die(format_result("ERROR_DATE_START_REQUIRED"));
  }
  elseif (empty($filter["date_start"]) && empty($filter["date_end"])){
    $month_start = date("Y-m")."-01 00:00:00";
    $interval = time() - strtotime($month_start);
    $hours = round($interval / 3600, 5);
    $date_filter = " AND \"date\" >= TO_TIMESTAMP('$month_start', 'YYYY-MM-DD HH24:MI:SS')";
  }
  if (!empty($_REQUEST["sort"])){
    $sort = $_REQUEST["sort"];
    $sort = json_decode($sort, true);
    if (is_array($sort)){
      $field = $sort["field"];
      $direction = $sort["order"];
    }
    else {
      $field = "revenue";
      $direction = "DESC";
    }
  }
  else {
    $field = "revenue";
    $direction = "DESC";
  }
$limitStart = $_GET['offset'];
$limitCount = $_GET['limit'];

//Создаем временную таблицу
  $query = "DROP TABLE IF EXISTS analytics_clients_temp";
  $queries[] = $query;
  pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $additional_fields = array("revenue", "game_time_total", "visits_total", "average_session_length", "receipt_count", "average_receipt", "bonuses_count");
  $fields = "";
  foreach ($additional_fields as $value){
    $fields .= "0 AS \"$value\", ";
  }
  $fields = trim($fields);
  $fields = trim($fields, ',');
  $query = "CREATE TEMPORARY TABLE analytics_clients_temp AS (SELECT *, $fields FROM tg_users WHERE \"ID\" IN ($branch_filter))";
  $queries[] = $query;
  pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  //получаем список валют
  $query = "SELECT DISTINCT(currency_code) AS currency_code, currency FROM tg_branches ORDER BY currency_code";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  while ($currency = pg_fetch_array($result)){
    $currencies[$currency["currency_code"]]["currency_code"] = $currency["currency_code"];
    $currencies[$currency["currency_code"]]["currency"] = $currency["currency"];
    $currencies[$currency["currency_code"]]["total"] = 0;
    $currencies[$currency["currency_code"]]["receipt_count"] = 0;
    $currencies[$currency["currency_code"]]["average_receipt"] = 0;
  }
  //получаем список валют с привязкой к филиалу
  $query = "SELECT \"ID\", currency_code, currency FROM tg_branches ORDER BY \"ID\"";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $cur_codes = array();
  while ($cur = pg_fetch_array($result)){
    $cur_codes[$cur["ID"]] = $cur["currency_code"];
  }
  $query = "SELECT * FROM analytics_clients_temp limit $limitCount offset $limitStart";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $client_data = array();
  while ($client = pg_fetch_array($result)){
    $client_data = array();
    //получаем выручку по бару
    $query = "SELECT SUM(\"money\") AS \"sum\", COUNT(*) AS receipt_count FROM tg_kassa_check WHERE user_id = {$client['ID']} AND transaction_type IN (0, 1)$date_filter";
    $queries[] = $query;
    $res = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $row = pg_fetch_array($res);
    $queries[] = $row;
    $client_data[] = money_to_float($row["sum"]);
    $receipt_count = intval($row["receipt_count"]);
    $d_filter = str_replace('date', 'date_start', $date_filter);
    //Вычисляем наигранное юзером время
    $query = "SELECT SUM(duration) AS duration FROM tg_game_time WHERE user_id = {$client['ID']}$d_filter";
    $queries[] = $query;
    $res = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $row = pg_fetch_array($res);
    $queries[] = $row;
    $client_data[] = intval($row["duration"]);
    //Количество визитов юзера
    $query = "SELECT COUNT(*) AS cnt FROM tg_game_time WHERE user_id = {$client['ID']}$d_filter";
    $queries[] = $query;
    $res = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $row = pg_fetch_array($res);
    $queries[] = $row;
    $client_data[] = intval($row["cnt"]);
    //Среднее время сеанса
    if (intval($client_data[2]) > 0){
      $average_session_duration = round($client_data[1] / $client_data[2]);
    }
    else {
      $average_session_duration = 0;
    }
    $client_data[] = $average_session_duration;
    //средний чек
    if (intval($receipt_count) > 0){
      $average_receipt = round($client_data[0] / $receipt_count);
    }
    else {
      $average_receipt = 0;
    }
    $client_data[] = $receipt_count;
    $client_data[] = $average_receipt;
    //Считаем бонусы
    $query = "SELECT  COUNT(*) AS bonuses_count FROM tg_notification WHERE user_id = {$client['ID']} AND transaction IN (2, 3, 4, 5, 14)$date_filter";
    $queries[] = $query;
    $res = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $row = pg_fetch_array($res);
    $queries[] = $row;
    $client_data[] = $row["bonuses_count"];
    $update_data = array_combine($additional_fields, $client_data);
    $substr = "";
    foreach ($update_data as $key => $value) {
      $substr .= "\"$key\" = '$value', ";
    }
    $substr = trim($substr);
    $substr = trim($substr, ',');
    $query = "UPDATE analytics_clients_temp SET $substr WHERE \"ID\" = {$client['ID']}";
    $queries[] = $query;
    pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $clients[] = $client;
  }
  $queries[] = $clients;
  //Извлекаем данные из временной таблицы
  $query = "SELECT \"ID\" AS \"id\", * FROM analytics_clients_temp ORDER BY $field $direction limit $limitCount offset $limitStart";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $clients = array();
  $total_data = array("game_time"=>0, "visits"=>0, "average_session_length"=>0, "bonuses_count"=>0);
  while ($client = pg_fetch_array($result)){
    //Получаем валюту пользователя
    $query = "SELECT currency_code FROM tg_branches WHERE \"ID\" = (SELECT branch_id FROM tg_users_to_branches WHERE user_id = {$client['ID']} LIMIT 1)";
    $queries[] = $query;
    $res = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $branch = pg_fetch_array($res);
    $queries[] = $branch;
    $currency_code = $branch["currency_code"];
    $currencies[$currency_code]["total"] += (float)$client["revenue"];
    $currencies[$currency_code]["receipt_count"] += intval($client["receipt_count"]);
    if (intval($currencies[$currency_code]["receipt_count"]) > 0){
      $currencies[$currency_code]["average_receipt"] = round((float)$currencies[$currency_code]["total"] / intval($currencies[$currency_code]["receipt_count"]), 2);
    }
    else{
      $currencies[$currency_code]["average_receipt"] = 0;
    }
    $total_data["game_time"] += intval($client["game_time_total"]);
    $total_data["visits"] += intval($client["visits"]);
    if ($total_data["visits"] > 0){
      $total_data["average_session_length"] = round($total_data["game_time"] / $total_data["visits"]);
    }
    else {
      $total_data["average_session_length"] = 0;
    }
    $total_data["bonuses_count"] += intval($client["bonuses_count"]);
    $exclude = explode(',', 'password,salt,phone_code,banned,deleted,send_sms,send_push,is_superhost,rang,email_token,ban_reason,ban_reason_admin,ban_start,ban_end,temp,abonement,user_id');
    $client = normalize_array($client, $exclude, $client["time_zone"]);
    unset($client["ID"]);
    $clients[] = $client;
  }
  $queries[] = $clients;
  $result = array("result"=>"RESULT_SUCCESS");
  $result["payload"] = array();
  $result["payload"]["items"] = $clients;
  $result["payload"]["currencies_total"] = $currencies;
  $result["payload"]["total"] = $total_data;
  $result["payload"]["count"] = count($clients);
  //Уничтожаем временную таблицу
  $query = "DROP TABLE IF EXISTS analytics_client_temp";
  $queries[] = $query;
  pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  exit(format_array($result));
?>
