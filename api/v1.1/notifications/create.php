<?php
  function create_notification($branch_id, $text, $sum = 0, $kassa_type = 0, $transaction_type = 0, $sender = array()){
    global $user, $queries;
    if (empty($sender['ip'])) $sender['ip'] = '0.0.0.0';
    $admin_id_query = "SELECT admin_id FROM tg_kassa_smena WHERE branch_id = $branch_id AND status = 0";
    //Извлекаем ID резервации у текущего юзера
    $query = "SELECT * FROM tg_reservation WHERE user_id = {$user['ID']} AND branch_id = $branch_id AND status = 1";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $reservation = pg_fetch_array($result);
    $reservation_id = intval($reservation_id);
    $sender["ID"] = intval($sender['ID']);
    $sender['pc'] = intval($sender['pc']);
    $query = "INSERT INTO tg_notification (\"date\", user_id, admin_id, description, transaction_type, \"money\", transaction, reservation_id, branch_id, sender, sender_pc, sender_ip) VALUES(";
    $query .= "CURRENT_TIMESTAMP, {$user['ID']}, ($admin_id_query), '$text', $kassa_type, '$sum', $transaction_type, $reservation_id, $branch_id, {$sender['ID']}, {$sender['pc']}, '{$sender['ip']}') RETURNING \"ID\" AS \"id\"";
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $notification = pg_fetch_array($result);
    $notification = normalize_array($notification);
    return $notification;
  }
?>
