<?php
  $branch_id = intval($_REQUEST["branch_id"]);
  if ($branch_id < 1){
    die(format_result("ERROR_EMPTY_BRANCH_ID"));
  }
  $query = "SELECT * FROM tg_software_setting WHERE branch_id = $branch_id";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $row = pg_fetch_array($result);
  $queries[] = $row;
  $output = array("result"=>"RESULT_SUCCESS","payload"=>array("version"=>$row["ver"], "hash"=>$row["hash"]));
  exit(format_array($output));
?>
