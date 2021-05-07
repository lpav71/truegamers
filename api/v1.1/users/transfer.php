<?php
  $db = mysqli_connect("localhost", "web_bunker10_usr", "Q3Ar1a1w2oVt9IC8");
  if (!$db){
    die("MySQL connection error");
  }
  if (!mysqli_select_db($db, "web_bunker101_db")){
    die("MySQL DB connection error");
  }
  if (!isset($_REQUEST["id"])){
    $my_query = "SELECT * FROM tb_users ORDER BY id";
    $res = mysqli_query($db, $my_query);
    if (!$res){
      die("MySQL query error: File: ".__FILE__."; Line: ".__LINE__);
    }
    $users = array();
    while ($usr = mysqli_fetch_array($res)){
      $users[] = $usr["id"];
    }
    exit(implode("|", $users));
  }
  else {
    $id = intval($_REQUEST["id"]);
    //Проверяем, не перенесен ли уже пользователь
    $pg_query = "SELECT * FROM tg_users WHERE user_id = '$id'";
    $result = pg_query($pg_query) or die(database_query_error($query, __FILE__, __LINE__));
    if (pg_num_rows($result) > 0){
      $usr = pg_fetch_array($result);
      exit($usr["username"]."|".$usr["email"]."|".trim($usr["phone"])."|".$usr["balance"]."|".$usr["bonus"]."|".$usr["game_time"]);
    }
    else {
      $my_query = "SELECT * FROM tb_users tu LEFT JOIN tb_accounts ta ON tu.id = ta.id_user LEFT JOIN tb_user_setting tus ON tu.id = tus.id_user WHERE tu.id = $id";
      $res = mysqli_query($db, $my_query);
      if (!$res){
        die("MySQL query error: File: ".__FILE__."; Line: ".__LINE__);
      }
      $usr = mysqli_fetch_array($res);
      if ($usr['prachy'] < 0) $usr["prachy"] = 0;
      if ($usr['bonus'] < 0) $usr["bonus"] = 0;
      $birthdate = format_datetime($usr['s_date']);
      $reg_date = format_datetime($usr['date_reg']);
      $last_visit = format_datetime($usr['last_login_datetime']);
      $name = $usr["name1"];
      $surname = $usr["name2"];
      $usr["s_total_game_time"] = intval($usr["s_total_game_time"]);
      $query = "INSERT INTO tg_users(username, password, salt, email, phone, \"status\", name, surname, birthdate, ";
      $query .= "reg_date, last_visit, phone_code, email_token, balance, bonus, ref_user, rang, game_time, user_id) VALUES('{$usr['u_name']}', '{$usr['u_passw']}', ";
      $query .= "'{$usr['secretkey']}', '{$usr['email']}', '{$usr['teleph']}', 5, '$name', '$surname', TO_TIMESTAMP('$birthdate', 'YYYY-MM-DD HH24:MI:SS'), TO_TIMESTAMP('$reg_date', 'YYYY-MM-DD HH24:MI:SS'), ";
      $query .= "TO_TIMESTAMP('$last_visit', 'YYYY-MM-DD HH24:MI:SS'), '', '', '{$usr["prachy"]}', '{$usr['bonus']}', 0, '{$usr['rang']}', '{$usr["s_total_game_time"]}', '$id') RETURNING \"ID\" AS \"id\"";
      $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      $row = pg_fetch_array($result);
      $usr_id = $row["id"];
      $query = "INSERT INTO tg_users_to_branches (branch_id, user_id) VALUES(1, $usr_id)";
      pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      if ($usr['prachy'] < 0) $usr["prachy"] = 0;
      if ($usr["prachy"] > 0){
        $description = "Перенос баланса";
        $query = "INSERT INTO tg_notification(\"date\", user_id, description, transaction_type, money, transaction, reservation_id, branch_id) VALUES(";
        $query .= "CURRENT_TIMESTAMP, $usr_id, '$description', 0, '{$usr['prachy']}', 11, 0, 1)";
        $queries[] = $query;
        pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      }
      if ($usr["bonus"] > 0){
        $description = "Перенос бонусного баланса";
        $query = "INSERT INTO tg_notification(\"date\", user_id, description, transaction_type, money, transaction, reservation_id, branch_id) VALUES(";
        $query .= "CURRENT_TIMESTAMP, $usr_id, '$description', 0, '{$usr['bonus']}', 14, 0, 1)";
        pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      }
      $pg_query = "SELECT * FROM tg_users WHERE \"ID\" = '$usr_id'";
      $result = pg_query($pg_query) or die(database_query_error($query, __FILE__, __LINE__));
      $usr = pg_fetch_array($result);
      exit($usr["username"]."|".$usr["email"]."|".trim($usr["phone"])."|".$usr["balance"]."|".$usr["bonus"]."|".$usr["game_time"]);
    }
  }
  function format_datetime($date){
    $date = str_replace(' ', '', $date);
    //echo $date."\r\n";
    $date_pattern = "#^([0-9]{4})([0-9]{2})([0-9]{2})$#is";
    $short_datetime_pattern = "#^([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})$#is";
    $long_datetime_pattern = "#^([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})$#is";
    if (preg_match($date_pattern, $date, $out)){
      //echo "1.".strtotime($out[1]."-".$out[2]."-".$out[3])."\r\n";
      return $out[1]."-".$out[2]."-".$out[3]."00:00:00";
    }
    elseif (preg_match($short_datetime_pattern, $date, $out)){
      return $out[1]."-".$out[2]."-".$out[3]." ".$out[4].":".$out[5].":00";
    }
    elseif (preg_match($long_datetime_pattern, $date, $out)){
      //echo "3.".strtotime($out[1]."-".$out[2]."-".$out[3]." ".$out[4].":".$out[5].":".$out[6])."\r\n";
      return $out[1]."-".$out[2]."-".$out[3]." ".$out[4].":".$out[5].":".$out[6];
    }
    else{
      //echo "4.\r\n";
      return "1970-01-01 00:00:00";
    }
  }
?>
