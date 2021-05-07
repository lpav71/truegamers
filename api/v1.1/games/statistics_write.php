<?php
  $reservation_id = intval($_REQUEST["reservation_id"]);
  if ($reservation_id < 1){
    die (format_result("ERROR_EMPTY_RESERVATION_ID"));
  }
  $ip = $_SERVER["REMOTE_ADDR"];
  $query = "SELECT * FROM tg_branches WHERE ip = '$ip'";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $branch = pg_fetch_array($result);
  $branch_id = intval($branch["ID"]);
  $queries[] = $branch;
  $user_id = intval($_SESSION["user_id"]);
  $game_ids = explode(',', $_REQUEST["games"]);
  if (!isset($_REQUEST["games"])){
    die (format_result("ERROR_EMPTY_GAMES"));
  }
  $duration = explode(',', $_REQUEST["duration"]);
  if (!isset($_REQUEST["duration"])){
    die (format_result("ERROR_EMPTY_DURATION"));
  }
  //Получаем номер компьютера
  $query = "SELECT * FROM tg_reservation WHERE \"ID\" = $reservation_id";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $reservation = pg_fetch_array($result);
  $queries[] = $reservation;
  $comp_id = intval($reservation["comp_id"]);
  $i = 0;
  $out = array();
  foreach ($game_ids as $value){
    $durat = $duration[$i];
    //Получаем название игры
    $query = "SELECT * FROM tg_soft_param WHERE \"ID\" = $value LIMIT 1";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $game = pg_fetch_array($result);
    $queries[] = $game;
    $game_name = trim($game["game"]);
    //Проверяем, есть ли запись об этой игре в tg_game_stat
    $query = "SELECT COUNT(*) AS count FROM tg_game_stat WHERE reservation_id = $reservation_id and game_id = '$value'";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $stat = pg_fetch_array($result);
    $queries[] = $stat;
    if (intval($stat["count"]) < 1){
      $query = "INSERT INTO tg_game_stat (reservation_id, branch_id, game_id, game_name, game_time, time_start, time_end) VALUES(";
      $query .= "$reservation_id, $branch_id, $value, '$game_name', $durat, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
      $queries[] = $query;
      pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    }
    else {
      $query = "UPDATE tg_game_stat SET game_time = $durat, time_end = time_start + INTERVAL '$durat MINUTE' ";
      $query .= " WHERE reservation_id = $reservation_id AND game_id = '$value'";
      $queries[] = $query;
      pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    }
    //Извлекаем запись
    $query = "SELECT * FROM tg_game_stat WHERE reservation_id = $reservation_id AND game_id = '$value'";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $game = pg_fetch_array($result);
    $queries[] = $game;
    $row = array();
    $row["id"] = intval($game["ID"]);
    $row["reservation_id"] = intval($game["reservation_id"]);
    $row["branch_id"] = intval($game["branch_id"]);
    $row["game_id"] = intval($game["game_id"]);
    $row["game_name"] = $game["game_name"];
    $row["game_time"] = intval($game["game_time"]);
    $row["time_start"] = $game["time_start"];
    $row["time_end"] = $game["time_end"];
    $out[] = $row;
    $i++;
  }
  $output = array("result"=>"RESULT_SUCCESS", "count"=>count($out));
  $output["payload"] = $out;
  exit(format_array($output));
?>
