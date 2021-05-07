 <?php
  if (!defined("AUTHORIZATION")){
    require_once('users/authorise.php');
  }
  if (!empty($_REQUEST["promo"])){
    if (!isset($user)){
      die (format_result("ERROR_USER_NOT_AUTHORIZED"));
    }
    else{
      $promo = pg_escape_string($_REQUEST["promo"]);
    }
  }
  else {
    die (format_result("ERROR_EMPTY_PROMOCODE"));
  }
  $user = normalize_array($user);
  //Проверяем наличие промокода в БД
  $query = "SELECT * FROM tg_promo WHERE status = 0 AND LOWER('$promo') = LOWER(\"code\") AND max_activations > (SELECT COUNT(*) FROM tg_promo_activations WHERE tg_promo_activations.promo_id = tg_promo.\"ID\") AND branch_id IN ($allowed_branches_subquery)";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  if (pg_num_rows($result) < 1){
    die (format_result("ERROR_INVALID_PROMOCODE"));
  }
  else {
    $promocode = pg_fetch_array($result);
    $br_id - $promocode["branch_id"];
    $promocode = normalize_array($promocode, array(), $user["time_zone"]);
  }
  //Проверяем, не использовал ли этот пользователь этот промокод раньше
  $query = "SELECT * FROM tg_promo_activations WHERE user_id = {$user['ID']} AND promo_id = {$promocode['ID']}";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  if (pg_num_rows($result) > 0){
    $queries[] = pg_fetch_array($result);
    die (format_result("ERROR_PROMOCODE_ALREADY_USED"));
  }
  //Пишем об активации промокода в БД
  $query = "INSERT INTO tg_promo_activations (promo_id, \"date\", user_id, admin_id, \"sum\") VALUES(";
  $query .= "{$promocode['ID']}, CURRENT_TIMESTAMP, {$user['ID']}, (SELECT admin_id FROM tg_kassa_smena WHERE status = 0 AND branch_id IN ($allowed_branches_subquery) LIMIT 1), {$promocode['bonus']})";
  $queries[] = $query;
  pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  //Изменяем данные о промокоде в tg_promo
  $promocode["prev_used"] = $promocode["used"];
  $promocode["prev_status"] = $promocode["status"];
  $promocode["prev_total_sum"] = $promocode["total_sum"];
  $promocode["used"]++;
  if ($promocode["used"] < $promocode["max_activations"]){
    $promocode["status"] = 0;
  }
  else{
    $promocode["status"] = 1;
  }
  $promocode["total_sum"] += money_to_float($promocode["bonus"]);
  $query = "UPDATE tg_promo SET used = {$promocode['used']}, \"status\" = {$promocode['status']}, total_sum = {$promocode['total_sum']} WHERE \"ID\" = {$promocode['ID']}";
  $queries[] = $query;
  pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  //Обновляем профиль пользователя
  $query = "UPDATE tg_users SET bonus = bonus + '{$promocode['bonus']}' WHERE \"ID\" = {$user['ID']}";
  $queries[] = $query;
  pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  //Изменяем текущее состояние переменных
  $result = array();
  $user["bonus"] += $promocode["bonus"];
  $query = "SELECT * FROM tg_promo WHERE \"ID\" = {$promocode['ID']}";
  $queries[] = $query;
  $r = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $row = pg_fetch_array($r);
  $queries = $row;
  $branch_id = $row["branch_id"];
  require_once('notifications/create.php');
  create_notification($branch_id, "Бонус за использование промокода {$promocode['promo_name']}.", $promocode["bonus"], 0, 3, array());
  if (defined("AUTHORIZATION")){
    $result["bonus_balance"] += $promocode["bonus"];
    $result["promocode"] = $promocode;
  }
  else {
    $result['id'] = intval($user["id"]);
    $result['bonus_balance'] = $user["bonus"];
    $result['promocode'] = $promocode;
    $res["result"] = "RESULT_SUCCESS";
  	$res["payload"] = $result;
    exit(format_array($res));
  }
?>
