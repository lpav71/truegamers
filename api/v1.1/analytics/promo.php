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
    $branch_filter = "WHERE \"branch_id\" IN(".implode(',', $branch_id).")";//print_r($branch_filter); die();
  }
  else {
    $branch_filter = "WHERE \"branch_id\" IN (".$allowed_branches_subquery.")";
  }
  if (!empty($filter["date_start"]) && !empty($filter["date_end"])){
    $date_start = pg_escape_string($filter["date_start"]);
    $date_end = pg_escape_string($filter["date_end"]);
    $interval = strtotime($date_end) - strtotime($date_start);
    $hours = round($interval / 3600, 5);
    $date_filter = "AND (\"create_date\" BETWEEN TO_TIMESTAMP('$date_start', 'YYYY-MM-DD HH24:MI:SS') AND TO_TIMESTAMP($date_end, 'YYYY-MM-DD HH24:MI:SS'))";
  }
  elseif (!empty($filter["date_start"])){
    $date_start = pg_escape_string($filter["date_start"]);
    $interval = time() - strtotime($date_start);
    $hours = round($interval / 3600, 5);
    $date_filter = " AND \"create_date\" >= TO_TIMESTAMP('$date_start', 'YYYY-MM-DD HH24:MI:SS')";
  }
  elseif (!empty($filter["date_end"])){
    die(format_result("ERROR_DATE_START_REQUIRED"));
  }
  elseif (empty($filter["date_start"]) && empty($filter["date_end"])){
    $month_start = date("Y-m")."-01 00:00:00";
    $interval = time() - strtotime($month_start);
    $hours = round($interval / 3600, 5);
    $date_filter = " AND \"create_date\" >= TO_TIMESTAMP('$month_start', 'YYYY-MM-DD HH24:MI:SS')";
  }
  if (!empty($_REQUEST["sort"])){
    $sort = $_REQUEST["sort"];
    $sort = json_decode($sort, true);
    if (is_array($sort)){
      $field = '"'.$sort["field"].'"';
      $direction = $sort["order"];
    }
    else {
      $field = '"ID"';
      $direction = "DESC";
    }
  }
  else {
    $field = '"ID"';
    $direction = "DESC";
  }
  //получаем список валют
  $query = "SELECT DISTINCT(currency_code) AS currency_code, currency FROM tg_branches ORDER BY currency_code";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  while ($currency = pg_fetch_array($result)){
    $currencies[$currency["currency_code"]]["currency_code"] = $currency["currency_code"];
    $currencies[$currency["currency_code"]]["currency"] = $currency["currency"];
    $currencies[$currency["currency_code"]]["total_sum"] = 0;
  }
  $total = array("promo_count"=>0,"generated"=>0,"activation_count"=>0);
  //получаем список валют с привязкой к филиалу
  $query = "SELECT \"ID\", currency_code, currency, time_zone FROM tg_branches ORDER BY \"ID\"";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $cur_codes = array();
  while ($cur = pg_fetch_array($result)){
    $cur_codes[$cur["ID"]] = array("currency_code"=>$cur["currency_code"],"currency"=>$cur["currency"], "time_zone"=>$cur["time_zone"]);
  }
  $query = "SELECT \"ID\" AS \"id\", * FROM tg_promo $branch_filter $date_filter ORDER BY $field $direction";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $promos = array();
  $items = array();
  while ($promo = pg_fetch_array($result)){
    $row = array();
    $promo["currency"] = $cur_codes[$promo["branch_id"]]["currency"];
    $promo["currency_code"] = $cur_codes[$promo["branch_id"]]["currency_code"];
    $promo_start = strtotime($promo["create_date"]);
    $promo_end = strtotime($promo["end_date"]);
    $row["id"] = intval($promo["ID"]);
    $row["code"] = $promo["code"];
    $row["name"] = $promo["promo_name"];
    $row["description"] = $promo["description"];
    $row["bonus"] = money_to_float($promo["bonus"]);
    $row["generated"] = intval($promo["max_activations"]);
    $hours = ceil(($promo_end - $promo_start) / 3600);
    $row["hours"] = intval($hours);
    $row["date_created"] = $promo["create_date"].$cur_codes[$promo["branch_id"]]["time_zone"];
    $row["activation_count"] = intval($promo["used"]);
    $row["total_sum"] = money_to_float($promo["total_sum"]);
    $currencies[$promo["currency_code"]]["total_sum"] += $row["total_sum"];
    $total_data["promo_count"]++;
    $total_data["generated"] += $row["generated"];
    $total_data["activated_count"] += $row["activation_count"];
    $promos[] = $promo;
    $items[] = $row;
  }
  $queries[] = $promos;
  $result = array("result"=>"RESULT_SUCCESS");
  $result["payload"] = array();
  $result["payload"]["items"] = $items;
  $result["payload"]["currencies_total"] = $currencies;
  $result["payload"]["total"] = $total_data;
  $result["payload"]["count"] = count($items);
  header("Content-type:application/json");
  exit(format_array($result));
?>
