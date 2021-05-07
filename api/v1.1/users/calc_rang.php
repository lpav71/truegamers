<?php
  //$branch_id = intval($_GET["branch_id"]);
  //if ($branch_id < 1) exit("ERROR_EMPTY_BRANCH_ID");
  if (!isset($_REQUEST["id"])){
    $query = "SELECT * FROM tg_users WHERE \"ID\" NOT IN(5, 6) AND \"ID\" IN (SELECT user_id FROM tg_users_to_branches WHERE branch_id = $branch_id) AND game_time > 0 ORDER BY \"ID\"";
    $query = "SELECT * FROM tg_users WHERE \"ID\" NOT IN(5, 6) AND game_time > 0 AND rang = 0 ORDER BY \"ID\"";
    $res = pg_query($query);
    if (!$res){
      die("PGSQL query error: File: ".__FILE__."; Line: ".__LINE__);
    }
    $users = array();
    while ($usr = pg_fetch_array($res)){
      $users[] = $usr["ID"];
    }
    exit(implode("|", $users));
  }
  else {
    $id = intval($_REQUEST["id"]);
    $query = "SELECT * FROM tg_users WHERE \"ID\" = $id";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $usr = pg_fetch_array($result);
    $queries[] = $usr;
    $game_time = intval($usr["game_time"]);
    $current_rang = $usr["rang"];
    if ($game_time == 0) $game_time = 1;
    $query = "SELECT * FROM tg_rang WHERE branch_id = $branch_id AND duration <= $game_time ORDER BY duration DESC LIMIT 1";
    $query = "SELECT * FROM tg_rang WHERE branch_id = (SELECT branch_id FROM tg_users_to_branches WHERE user_id = {$id}) AND duration <= $game_time ORDER BY duration DESC LIMIT 1";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $rang = pg_fetch_array($result);
    $queries[] = $rang;
    //print_r($queries);
    $query = "UPDATE tg_users SET rang = {$rang['num']} WHERE \"ID\" = $id";
    $queries[] = $query;
    pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    exit($current_rang."|".$rang["num"]."|".$rang["name"]);
  }
?>
