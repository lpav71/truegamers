<?php
  $dbconn = pg_pconnect("host=188.246.224.156 port=5432 dbname=truegamers user=truegamers_temp password=12801024qwE")
    or die('DATABASE_CONNECTION_ERROR');
  if (!isset($_REQUEST["id"])){
    $query = "SELECT * FROM tg_users WHERE user_id > 350 ORDER BY \"ID\"";
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
    $phone = $usr["phone"];
    //Проверяем наличие логина
    $db = mysqli_connect("localhost", "web_bunker10_usr", "Q3Ar1a1w2oVt9IC8");
    if (!$db){
      die("MySQL connection error");
    }
    if (!mysqli_select_db($db, "web_bunker101_db")){
      die("MySQL DB connection error");
    }
    $q = "SELECT * FROM tb_users WHERE id = {$usr['user_id']}";
    //echo $q."\r\n";
    $r = mysqli_query($db, $q);
    if (!$r){
      die("MySQL query error: File: ".__FILE__."; Line: ".__LINE__."; Query: ".$q);
    }
    $u = mysqli_fetch_array($r);
    if ($u['u_login'] != ''){
      $login = $u['u_login'];
    }
    if (empty($phone)){
      $phone = '';
    }
    $phone = str_replace(array('-', ' ', '+'), '', $phone);
    $pattern = '#^(7|8|9|38)([0-9]+)#is';
    //print_r($usr);
    if (preg_match($pattern, $phone, $out)){
      $old_phone = $out[1].$out[2];
      if ($out[1] == '8'){
        $out[1] = '7';
      }
      elseif ($out[1] == 9){
        $out[1] = '79';
      }
      if (strlen($out[2]) > 10){
        $out[2] = substr($out[2], 0, 10);
      }
      $new_phone = $out[1].$out[2];
      if (empty($login)){
        $login = $new_phone;
      }
      $query = "UPDATE tg_users SET phone = '$new_phone', username = '$login' WHERE \"ID\" = $id";
      $queries[] = $query;
      pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      $query = "SELECT * FROM tg_users WHERE \"ID\" = $id";
      $queries[] = $query;
      $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      $usr = pg_fetch_array($result);
      exit($old_phone."|".$new_phone."|".$usr["username"]);
    }
    else {
      exit($phone);
    }
  }
  exit($phone);
?>
