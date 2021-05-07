<?php
  $branch_id = intval($_REQUEST["branch_id"]);
  if ($branch_id < 1){
    die(format_result("ERROR_EMPTY_BRANCH_ID"));
  }
  $category = intval($_REQUEST["category"]);
  if ($category < 1){
    //die(format_result("ERROR_EMPTY_CATEGORY"));
    $category = "SELECT DISTINCT(number) FROM tg_pc_categories WHERE branch_id = $branch_id";
  }
  $sort = $_REQUEST["sort"];
  if (empty($sort)){
    $field = "zone";
    $direction = "ASC";
  }
  else{
    $sort = json_decode($sort, true);
    if (is_array($sort)){
      $field = $sort["field"];
      $direction = $sort["order"];
    }
    else{
      $field = "zone";
      $direction = "ASC";
    }
  }
  $query = "SELECT * FROM tg_prices WHERE \"branch_id\" = $branch_id AND \"zone\" IN($category) AND \"active\" = 1 ORDER BY $field $direction";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $count = pg_num_rows($result);
  $out = array();
  while ($row = pg_fetch_array($result)){
    $out_row = array();
    $query = "SELECT * FROM tg_pc_categories WHERE branch_id = $branch_id AND number = {$row['zone']}";
    $queries[] = $query;
    $res = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $zone = pg_fetch_array($res);
    $out_row["id"] = intval($row["ID"]);
    $out_row["name"] = trim($row["name"]);
    $out_row["zone"] = intval($zone["number"]);
    $out_row["zone_name"] = trim($zone["name"]);
    $out_row["time_start"] = $row["time_start"];
    $out_row["time_end"] = $row["time_end"];
    $out_row["price"] = (float)str_replace(array("$", ","), "", $row["price"]);
    $out_row["duration"] = intval($row["duration"]);
    $out_row["weekday"] = intval($row["weekday"]);
    $out_row["branch_id"] = intval($row["branch_id"]);
    $out_row["packet_id"] = intval($row["packet_id"]);
    $out_row["tarif_category"] = intval($row["tarif_category"]);
    $out_row["time_fix_end"] = $row["time_fix_end"];
    $out[] = $out_row;
  }
  pg_free_result($result);
  $res = array();
  $res["result"] = "RESULT_SUCCESS";
  $res["count"] = intval($count);
  $res["payload"] = $out;
  echo format_array($res);
  exit();
?>
