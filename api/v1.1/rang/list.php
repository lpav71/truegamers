<?php
  $branch_id = intval($_REQUEST["branch_id"]);
  if ($branch_id < 1){
    $ip = $_SERVER["REMOTE_ADDR"];
    $query = "SELECT * FROM tg_branches WHERE ip = '$ip'";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $branch = pg_fetch_array($result);
    $queries[] = $branch;
    if (empty($branch)){
      die(format_result("ERROR_EMPTY_BRANCH_ID"));
    }
    $branch_id = intval($branch["ID"]);
    //$branch = exclude_indexes
  }
  $query = "SELECT * FROM tg_rang WHERE branch_id = $branch_id ORDER BY num";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $rang_list = array();
  while ($rang = pg_fetch_array($result)){
    $row = array();
    $row["id"] = intval($rang["ID"]);
    $row["name"] = trim($rang["name"]);
    $row["duration"] = intval($rang["duration"]);
    $row["bonus"] = money_to_float($rang["bonus"]);
    $row["num"] = intval($rang["num"]);
    $row["branch_id"] = intval($rang["branch_id"]);
    $rang_list[] = $row;
  }
  $queries[] = $rang_list;
  $output = array("result"=>"RESULT_SUCCESS", "count"=>count($rang_list));
  $output["payload"] = $rang_list;
  exit(format_array($output));
?>
