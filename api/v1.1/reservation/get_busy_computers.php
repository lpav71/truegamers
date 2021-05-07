<?php
  $start = pg_escape_string($_REQUEST["start"]);
  if (empty($start)){
    die (format_result("ERROR_EMPTY_START"));
  }
  $end = pg_escape_string($_REQUEST["end"]);
  if (empty($end)){
    die (format_result("ERROR_EMPTY_END"));
  }
  $ip = $_SERVER["REMOTE_ADDR"];
  $query = "SELECT * FROM tg_branches WHERE ip = '$ip'";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $branch = pg_fetch_array($result);
  $queries[] = $branch;
  $branch_id = $branch["ID"];
  $tm_filter = "AND (('$start' BETWEEN \"start\" AND \"end\" OR '$end' BETWEEN \"start\" AND \"end\") OR (\"start\" <= '$start' AND \"end\" >= '$end') OR ('$start' <= \"start\" AND '$end' >= \"end\"))";
  $query = "SELECT * FROM tg_pc_info WHERE branch_id = $branch_id ORDER BY comp_id";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $computers = array();
  while ($computer = pg_fetch_array($result)){
    $computer = normalize_array($computer);
    $query = "SELECT * FROM tg_reservation WHERE branch_id = $branch_id AND status < 2 AND comp_id = {$computer['comp_id']} $tm_filter";
    $queries[] = $query;
    $res = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    if (pg_num_rows($res) > 0){
      $computer["busy"] = true;
      $reservation = pg_fetch_array($res);
      $reservation = normalize_array($reservation);
      $computer["reservation"] = $reservation;
    }
    else {
      $computer["busy"] = false;
    }
    $computers[] = $computer;
  }
  $queries[] = $computers;
  $output = array("result"=>"RESULT_SUCCESS");
  $output["payload"] = array("items"=>$computers, "total"=>count($computers));
  exit(format_array($output));
?>
