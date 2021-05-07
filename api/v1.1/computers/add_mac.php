<?php
  $branch_id = intval($_REQUEST["branch_id"]);
  if ($branch_id < 1){
    die(format_result("ERROR_EMPTY_BRANCH_ID"));
  }
  $comp_id = intval($_REQUEST["comp_id"]);
  if ($comp_id < 1){
    die(format_result("ERROR_EMPTY_COMP_ID"));
  }
  $mac = pg_escape_string($_REQUEST["mac"]);
  if (empty($mac)){
    die(format_result("ERROR_EMPTY_MAC_ADDRESS"));
  }
  else {
    $pattern = "#^[0-9ABCDEF]{2}\-[0-9ABCDEF]{2}\-[0-9ABCDEF]{2}\-[0-9ABCDEF]{2}\-[0-9ABCDEF]{2}\-[0-9ABCDEF]{2}$#isU";
    if (!preg_match($pattern, $mac)){
      die(format_result("ERROR_INVALID_MAC_ADDRESS"));
    }
    else{
      $query = "SELECT * FROM tg_pc_info WHERE branch_id = $branch_id AND \"ID\" = $comp_id";
      $queries[] = $query;
      $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      $row = pg_fetch_array($result);
      if (!empty($row["mac"])){
        die(format_result("ERROR_MAC_ADDRESS_ALREADY_EXISTS"));
      }
      $query = "SELECT * FROM tg_pc_info WHERE mac = '$mac'";
      $queries[] = $query;
      $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      if (pg_num_rows($result) > 0){
        die(format_result("ERROR_MAC_ADDRESS_OF_ANOTHER_COMPUTER"));
      }
    }
  }
  $ip = $_REQUEST["ip"];
  if (empty($ip)){
    die(format_result("ERROR_EMPTY_IP"));
  }
  elseif (!check_ip($ip)){
    die(format_result("ERROR_INVALID_IP"));
  }
  $version = pg_escape_string($_REQUEST["version"]);
  if (empty($version)){
    die(format_result("ERROR_EMPTY_VERSION"));
  }
  $query = "UPDATE tg_pc_info SET mac = '$mac', version = '$version', ip = '$ip' WHERE comp_id = $comp_id AND branch_id = $branch_id";
  $queries[] = $query;
  pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $query = "SELECT * FROM tg_pc_info WHERE comp_id = $comp_id AND branch_id = $branch_id";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $info = pg_fetch_array($result);
  $output = array("result"=>"RESULT_SUCCESS", "payload"=>array());
  $info = exclude_indexes($info);
  $output["payload"]["id"] = intval($info["ID"]);
  $output["payload"]["comp_id"] = intval($info["comp_id"]);
  $output["payload"]["category"] = intval($info["category"]);
  $output["payload"]["branch_id"] = intval($info["branch_id"]);
  $output["payload"]["mac"] = $info["mac"];
  $output["payload"]["ip"] = $info["ip"];
  $output["payload"]["version"] = $info["version"];
  echo format_array($output);
?>
