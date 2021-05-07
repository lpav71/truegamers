<?php
  $ip = $_SERVER["REMOTE_ADDR"];
  $query = "SELECT * FROM tg_branches WHERE ip = '$ip'";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $branch = pg_fetch_array($result);
  $queries[] = $branch;
  $branch_id = intval($branch["ID"]);
  $query = "SELECT * FROM tg_reservation WHERE branch_id = $branch_id AND packet_id > 0 AND \"start\" >= CURRENT_TIMESTAMP ORDER BY comp_id";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $reservations = array();
  while ($reservation = pg_fetch_array($result)){
    $row = array();
    $row["id"] = intval($reservation["ID"]);
    $row["comp_id"] = intval($reservation["comp_id"]);
    $row["start"] = $reservation["start"];
    $row["end"] = $reservation["end"];
    $row["packet_id"] = intval($reservation["packet_id"]);
    $reservations[] = $row;
  }
  $queries[] = $reservations;
  $output = array();
  $output["result"] = "RESULT_SUCCESS";
  $output["count"] = count($reservations);
  $output["payload"] = $reservations;
  exit(format_array($output));
?>
