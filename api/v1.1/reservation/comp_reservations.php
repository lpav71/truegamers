<?php
  //Текущая и будушая резервация компьютера
  $comp_id = intval($_REQUEST["comp_id"]);
  if ($comp_id < 1){
    die(format_result("ERROR_EMPTY_COMP_ID"));
  }
  $user_id = intval($_REQUEST["user_id"]);
  if ($user_id < 1){
    $user_id = $user["ID"];
  }
  $branch_id = intval($_REQUEST["branch_id"]);
  if ($branch_id < 1){
    //Пытаемся получить филиал по IP
    $ip = $_SERVER["REMOTE_ADDR"];
    $query = "SELECT * FROM tg_branches WHERE ip = '$ip'";
    $queries[] = $query;
    //echo $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    if (pg_num_rows($result) < 1){
      die(format_result("ERROR_REQUIRED_BRANCH_ID"));
    }
    else{
      $branch = pg_fetch_array($result);
      $branch_id = intval($branch["ID"]);
    }
  }
  //Получаем филиал
  /*$query = "SELECT * FROM tg_pc_info WHERE \"ID\" = $comp_id";
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $pc_info = pg_fetch_array($result);
  $branch_id = intval($pc_info["branch_id"]);*/
  //Извлекаем текущую резервацию текущего пользователя
  $query = "SELECT * FROM tg_reservation WHERE comp_id = $comp_id AND user_id = $user_id ";
  $query .= "AND branch_id = $branch_id AND \"status\" < 2 AND packet_id > 0 AND CURRENT_TIMESTAMP BETWEEN \"start\" AND \"end\" LIMIT 1";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  if (pg_num_rows($result) < 1){
    $current_user_current_reservation = null;
  }
  else{
    $row = pg_fetch_array($result);
    $current_user_current_reservation = array("id"=>$row["ID"], "start"=>$row["start"], "end"=>$row["end"]);
  }
  pg_free_result($result);
  //Извлекаем будущую резервацию текущего пользователя
  $query = "SELECT * FROM tg_reservation WHERE comp_id = $comp_id AND \"status\" = 0 AND user_id = $user_id ";
  $query .= "AND branch_id = $branch_id AND packet_id > 0 AND CURRENT_TIMESTAMP < \"start\"  ORDER BY \"start\" LIMIT 1";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  if (pg_num_rows($result) < 1){
    $current_user_next_reservation = null;
  }
  else{
    $row = pg_fetch_array($result);
    $current_user_next_reservation = array("id"=>$row["ID"], "start"=>$row["start"], "end"=>$row["end"]);
  }
  pg_free_result($result);
  //Извлекаем текущую резервацию другого пользователя
  $query = "SELECT * FROM tg_reservation WHERE comp_id = $comp_id AND user_id <> $user_id ";
  $query .= "AND branch_id = $branch_id AND \"status\" < 2 AND packet_id > 0 AND CURRENT_TIMESTAMP BETWEEN \"start\" AND \"end\" LIMIT 1";
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $queries[] = $query;
  if (pg_num_rows($result) < 1){
    $another_user_current_reservation = null;
  }
  else{
    $row = pg_fetch_array($result);
    $another_user_current_reservation = array("start"=>$row["start"], "end"=>$row["end"]);
  }
  pg_free_result($result);
  //Извлекаем будущую резервацию другого пользователя
  $query = "SELECT * FROM tg_reservation WHERE comp_id = $comp_id AND \"status\" = 0 AND user_id <> $user_id ";
  $query .= "AND branch_id = $branch_id AND packet_id > 0 AND CURRENT_TIMESTAMP < \"start\" ORDER BY \"start\" LIMIT 1";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  if (pg_num_rows($result) < 1){
    $another_user_next_reservation = null;
  }
  else{
    $row = pg_fetch_array($result);
    $another_user_next_reservation = array("start"=>$row["start"], "end"=>$row["end"]);
  }
  pg_free_result($result);
  $out = array("result"=>"RESULT_SUCCESS");
  $out["user_id"] = intval($user_id);
  $out["comp_id"] = intval($comp_id);
  $out["branch_id"] = intval($branch_id);
  $out["payload"] = array();
  $out["payload"]["current_reservation"] = array("current_user"=>$current_user_current_reservation,"another_user"=>$another_user_current_reservation);
  $out["payload"]["next_reservation"] = array("current_user"=>$current_user_next_reservation,"another_user"=>$another_user_next_reservation);
  echo format_array($out);
  exit();
?>
