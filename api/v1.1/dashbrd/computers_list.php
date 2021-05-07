<?php
  if (!empty($_REQUEST["filter"])){
    $filter = $_REQUEST["filter"];
    $filter = json_decode($filter, true);
  }
  if (!empty($_REQUEST["sort"])){
    $sort = $_REQUEST["sort"];
    $sort = json_decode($sort, true);
  }
  $computer_statuses = array("off", "free", "busy");
  $branch_id = $filter["branch_id"];
  $order_field = pg_escape_string($sort["field"]);
  $order_direction = pg_escape_string($sort["order"]);
  if (empty($order_field)) $order_field = "ID";
  if (empty($order_direction)) $order_direction = "ASC";
  if (is_array($branch_id) && count($branch_id) > 0){
    $query = "SELECT * FROM tg_branches WHERE \"ID\" IN(".implode(',', $branch_id).")";
  }
  elseif (intvaL($branch_id) > 0){
    $query = "SELECT * FROM tg_branches WHERE \"ID\" = $branch_id";
  }
  else {
    if (empty($branch_id)) {
      $query = "SELECT * FROM tg_branches";
    }else $query = "SELECT * FROM tg_branches WHERE \"ID\" IN ($allowed_branches_subquery)";
  }
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  if (pg_num_rows($result) < 1){
    die (format_result("ERROR_INVALID_BRANCH_ID"));
  }
  $rows = pg_num_rows($result);
  if ($rows == 1){
    $branch = pg_fetch_array($result);
    $queries[] = $branch;
    $branch = exclude_indexes($branch);
    $br_id = $branch["ID"];
    $br = array();
    $br[$br_id]["id"] = intval($branch["ID"]);
    $br[$br_id]["currency"] = "РУБ";
    $br[$br_id]["currency"] = $branch["currency"];
    $br[$br_id]["currency_code"] = "RUB";
    $br[$br_id]["currency_code"] = $branch["currency_code"];
  }
  else {
    $branches = array();
    $br = array();
    while($branch = pg_fetch_array($result)){
      //$br["id"] = intval($branch["ID"]);
      $br_id = $branch["ID"];
      $br[$br_id]["currency"] = "РУБ";
      $br[$br_id]["currency"] = $branch["currency"];
      $br[$br_id]["currency_code"] = "RUB";
      $br[$br_id]["currency_code"] = $branch["currency_code"];
    }
    //$br = $branches;
  }
$branches = $br;
  if (is_array($branch_id) && count($branch_id) > 0){
    $query = "SELECT * FROM tg_pc_info WHERE branch_id IN(".implode(',', $branch_id).") ORDER BY \"$order_field\" $order_direction";
    $queries[] = $query;
  }
  elseif (intvaL($branch_id) > 0){
    $query = "SELECT * FROM tg_pc_info WHERE branch_id = $branch_id ORDER BY \"$order_field\" $order_direction";
    $queries[] = $query;
  }
  else {
    if (empty($branch_id))
    {
      $query = "SELECT * FROM tg_pc_info ORDER BY \"$order_field\" $order_direction";
    }
    else{
      $query = "SELECT * FROM tg_pc_info WHERE branch_id IN ($allowed_branches_subquery) ORDER BY \"$order_field\" $order_direction";
    }
    $queries[] = $query;
  }
  $r = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $queries[] = "Results count: ".pg_num_rows($r);
  $computers = array();
  $i = 0;
  while ($computer = pg_fetch_array($r)){
    $comp = array();
    $comp["id"] = intval($computer["ID"]);
    //Получаем данные пользователя, находящегося за компьютером
    if (intval($computer["user_id"]) > 0){
      $query = "SELECT * FROM tg_users WHERE \"ID\" = ".$computer["user_id"];
      $queries[] = $query;
      $res = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      $usr = pg_fetch_array($res);
      $comp["user"]["id"] = intval($usr["ID"]);
      $comp["user"]["username"] = $usr["username"];
    }
    else{
      $comp["user"] = null;
    }
    $comp["number"] = intval($computer["comp_id"]);
    //Зал компьютера
    $query = "SELECT * FROM tg_pc_categories WHERE branch_id = ".$computer["branch_id"]." AND number = ".$computer["category"];
    $queries[] = $query;
    $res = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $category = pg_fetch_array($res);
    $comp["category"]["id"] = intval($category["number"]);
    $comp["category"]["name"] = $category["name"];
    $comp["status"] = $computer_statuses[intval($computer["status"])];
    $comp["user_balance"] = money_to_float($computer["money"]);
    $comp["bonus_balance"] = money_to_float($computer["bonus"]);
    $comp["currency"] = $branches[$computer["branch_id"]]["currency"];
    $comp["currency_code"] = $branches[$computer["branch_id"]]["currency_code"];
    if (intval($computer["packet_id"]) > 0){
      $query = "SELECT * FROM tg_prices WHERE \"ID\" = ".$computer["packet_id"];
      $queries[] = $query;
      $res = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      $packet = pg_fetch_array($res);
      $comp["packet"] = array("id"=>intval($packet["ID"]), "name"=>trim($packet["name"]));
    }
    else {
      $comp["packet"] = null;
    }
    $comp["remaining_minutes"] = intval($computer["minutes"]);
    if (intval($computer["user_id"]) > 0){
      $minutes = intval($comp["minutes"]);
      $query = "SELECT \"start\",\"end\" FROM tg_reservation WHERE branch_id = ".$computer["branch_id"]." AND \"status\" = 1 AND comp_id = ".$computer["comp_id"];
      $queries[] = $query;
      $res = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      $reservation = pg_fetch_array($res);
      $queries[] = $reservation;
      $comp["session_start"] = $reservation["start"].$branch["time_zone"];
      $comp["session_end"] = $reservation["end"].$branch["time_zone"];
    }
    else{
      $comp["session_start"] = null;
      $comp["session_end"] = null;
    }
    $query = "SELECT * FROM tg_branches WHERE \"ID\" = ".$computer["branch_id"];
    $queries[] = $query;
    $res = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $b = pg_fetch_array($res);
    $comp["currency"] = $b["currency"];
    $comp["currency_code"] = $b["currency_code"];
    $comp["pos_x"] = intval($computer["pos_x"]);
    $comp["pos_y"] = intval($computer["pos_y"]);
    $comp["mac"] = $computer["mac"];
    $comp["ip"] = $computer["ip"];
    $comp["software_version"] = $computer["version"];
    $computers[] = $comp;
  }
  $queries[] = $computers;
  $output = array("result"=>"RESULT_SUCCESS", "payload"=>array("items"=>array(), "totalCount"=>count($computers)));
  $output["payload"]["branch"] = $branches;
  $output["payload"]["items"] = $computers;
  header('content-type:application/json');
  exit(format_array($output));
?>
