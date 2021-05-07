<?php
$filter = $_REQUEST["filter"];
if (empty($filter)) {
    die(format_result("ERROR_EMPTY_FILTER"));
}
$filter = json_decode($filter, true);
$branch_id = $filter["branch_id"];
if (!is_array($branch_id)) {
    die(format_result("ERROR_INVALID_BRANCH_ID"));
}
if (!empty($branch_id)) {
    $branch_filter = "WHERE branch_id IN(" . implode(',', $branch_id) . ")";
} else {
    $branch_filter = "WHERE branch_id IN ($allowed_branches_subquery)";
}
if (!empty($filter['date']['from']) && !empty($filter['date']['to'])) {
    $date_start = pg_escape_string($filter['date']['from']);
    $date_start = str_replace('T',' ',$date_start);
    $date_start = str_replace('.000Z',' ',$date_start);
    $date_end = pg_escape_string($filter['date']['to']);
    $date_end = str_replace('T',' ',$date_end);
    $date_end = str_replace('.000Z',' ',$date_end);
    $date_filter = "AND (\"date\" BETWEEN TO_TIMESTAMP('$date_start', 'YYYY-MM-DD HH24:MI:SS') AND TO_TIMESTAMP('$date_end', 'YYYY-MM-DD HH24:MI:SS'))";
} elseif (!empty($filter['date']['from'])) {
    $date_start = pg_escape_string($filter['date']['from']);
    $date_start = str_replace('T',' ',$date_start);
    $date_start = str_replace('.000Z',' ',$date_start);
    $date_filter = " AND \"date\" >= TO_TIMESTAMP('$date_start', 'YYYY-MM-DD HH24:MI:SS')";
} elseif (!empty($filter['date']['to'])) {
    $date_end = pg_escape_string($filter["date_end"]);
    $date_end = str_replace('T',' ',$date_end);
    $date_end = str_replace('.000Z',' ',$date_end);
    $date_filter = " AND \"date\" <= TO_TIMESTAMP('$date_end', 'YYYY-MM-DD HH24:MI:SS')";
} elseif (empty($filter['date']['from']) && empty($filter['date']['to'])) {
    $month_start = date("Y-m") . "-01 00:00:00";
    $date_filter = " AND \"date\" >= TO_TIMESTAMP('$month_start', 'YYYY-MM-DD HH24:MI:SS')";
}
//$transaction_filter = " AND transaction_type = 0 AND transaction = 6";
$transaction_filter = " AND transaction_type = 0 AND transaction = 1";
$usr_id = intval($filter["user_id"]);
if ($usr_id > 0) {
    $user_filter = " AND user_id = $usr_id";
}
if (!empty($_REQUEST["sort"])) {
    $sort = $_REQUEST["sort"];
    $sort = json_decode($sort, true);
    if (is_array($sort)) {
        $field = $sort["field"];
        $direction = $sort["order"];
    } else {
        $field = "date";
        $direction = "DESC";
    }
} else {
    $field = "date";
    $direction = "DESC";
}
$limit = $_GET['limit'];
$offset = $_GET['offset'];
$query = "SELECT * FROM tg_notification tn RIGHT JOIN tg_branches tb ON tn.branch_id = tb.\"ID\" $branch_filter $date_filter $user_filter $transaction_filter ORDER BY \"$field\" $direction limit $limit offset $offset";
$queries[] = $query;
$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
$transfers = array();
while ($transfer = pg_fetch_array($result)) {
    $notification_id = $transfer[0];
    $transfer = exclude_indexes($transfer);
    $transfer = normalize_array($transfer, array("ID", "active", "country_code"), $transfer["time_zone"]);
    if ($transfer["transaction_type"] == 0) {
        $transfer["transaction_type"] = "income";
    } elseif ($transfer["transaction_type"] == 1) {
        $transfer["transaction_type"] = "outcome";
    }
    $tr = array("id" => $notification_id);
    $tr = array_merge($tr, $transfer);
    $transfer = array();
    foreach ($tr as $key => $value) {
        if ($key == "name") {
            break;
        }
        $transfer[$key] = $value;
        unset($tr[$key]);
    }
    $transfer["branch"]["id"] = $transfer["branch_id"];
    foreach ($tr as $key => $value) {
        $transfer["branch"][$key] = $value;
    }
    //Извлекаем данные пользователя
    $query = "SELECT * FROM tg_users WHERE \"ID\" = " . $transfer["user_id"];
    $queries[] = $query;
    $r = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $u = pg_fetch_array($r);
    $queries[] = $u;
    $transfer["user"]["id"] = intval($u["ID"]);
    $transfer["user"]["username"] = $u["username"];
    //Извлекаем данные пользователя. отправившего средства
    $query = "SELECT * FROM tg_users WHERE \"ID\" = " . $transfer["sender"];
    $queries[] = $query;
    $r = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $u = pg_fetch_array($r);
    $queries[] = $u;
    $sender["id"] = intval($u["ID"]);
    $sender["username"] = $u["username"];
    $sender["comp_id"] = intval($transfer["sender_pc"]);
    $sender["ip"] = $transfer["sender_ip"];
    $transfer["sender"] = array();
    $transfer["sender"] = $sender;
    unset ($transfer["sender_pc"]);
    unset ($transfer["sender_ip"]);
    unset($transfer["transaction"]);
    unset($transfer["pay_type"]);
    $transfers[] = $transfer;
}
$queries[] = $transfers;
$output = array("result" => "RESULT_SUCCESS", "payload" => array());
$output["payload"]["items"] = $transfers;
$output["payload"]["totalCount"] = count($transfers);
header("Content-type:application/json");
exit(format_array($output));
?>
