<?php
  $branch_id = intval($_REQUEST["branch_id"]);
  if ($branch_id < 1){
    die(format_result("ERROR_EMPTY_BRANCH_ID"));
  }
  $comp_id = intval($_REQUEST["comp_id"]);
  if ($comp_id < 1){
    die(format_result("ERROR_EMPTY_COMP_ID"));
  }
  $query = "SELECT * FROM tg_games WHERE branch_id = $branch_id AND comp_id = $comp_id ORDER BY num";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $count = pg_num_rows($result);
  $out = array();
  while ($row = pg_fetch_array($result)){
    $out_row = array();
    $out_row["id"] = intval($row["ID"]);
    $out_row["game"] = trim($row["game"]);
    $out_row["icon_link"] = trim($row["icon_link"]);
    $out_row["exe_link"] = trim($row["exe_link"]);
    $out_row["type_soft"] = intval($row["type_soft"]);
    $out_row["param"] = trim($row["param"]);
    $out_row["num"] = intval($row["num"]);
    $out_row["comp_id"] = intval($row["comp_id"]);
    $out_row["comp_categor"] = intval($row["comp_categor"]);
    $out_row["branch_id"] = intval($row["branch_id"]);
    $out_row["status_id"] = intval($row["status_id"]);
    $out_row["steam_id"] = intval($row["steam_id"]);
    $out_row["handle"] = trim($row["handle"]);
    $out_row["caption_game"] = trim($row["caption_game"]);
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
