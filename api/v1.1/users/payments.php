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
$types = $filter["type"];
$account = $filter["account"];

$transactions = [];

foreach ($types as $type) {
    switch ($type) {
        case "admin_cash":      array_push($transactions, [0]); break;
        case "admin_card":      array_push($transactions, [1]); break;
        case "admin_bonus":     array_push($transactions, [2]); break;
        case "promocode_bonus": array_push($transactions, [3]); break;
        case "cashback_bonus":  array_push($transactions, [4]); break;
        case "new_rang_bonus":  array_push($transactions, [5]); break;
        case "crm":             array_push($transactions, [11, 14]); break;
        case "referall_bonus":  array_push($transactions, [12]); break;
        case "player_online":   array_push($transactions, [13]); break;
        default:                array_push($transactions, range(0, 13));
    }
}
$transactions = call_user_func_array('array_merge',$transactions);
switch ($account) {
    case "real": $transactions = array_intersect($transactions, [0, 1, 11, 13]); break;
    case "bonus":$transactions = array_intersect($transactions, [2, 3, 4, 5, 12, 14]); break;
}

$transaction_filter = " AND transaction_type = 0 AND transaction IN (" . implode(",", $transactions) . ")";
$limit = $_GET['limit'];
$offset = $_GET['offset'];
if (!empty($branch_id)) {
    $branch_filter = "WHERE branch_id IN(" . implode(',', $branch_id) . ")";
}
else {
    $branch_filter = "WHERE branch_id > 0";
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
//$query = "SELECT * FROM tg_notification WHERE branch_id=".$branch_id;
$query = "SELECT * FROM tg_notification $branch_filter limit $limit offset $offset";
$verify = pg_query($query);
$verify = pg_fetch_all($verify);
if (empty($verify)) {
    $err = array(
        'RESULT' => 'branch_id not found'
    );
    header("Content-type:application/json");
    $err = json_encode($err);
    echo $err;
    return;
}
$query = "SELECT count(*) FROM tg_notification tn RIGHT JOIN tg_branches ON tn.branch_id = tg_branches.\"ID\"
    $branch_filter $date_filter $user_filter $transaction_filter";
$total = pg_query($query);
$total = pg_fetch_object($total);
$query = "SELECT * FROM tg_notification tn RIGHT JOIN tg_branches ON tn.branch_id = tg_branches.\"ID\"
    $branch_filter $date_filter $user_filter $transaction_filter ORDER BY \"$field\" $direction
    limit $limit offset $offset";
$queries[] = $query;
$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
$transactions = array();
while ($transaction = pg_fetch_array($result)) {
    $transaction_types = array(
        "0" => "admin_cash",
        "1" => "admin_card",
        "2" => "admin_bonus",
        "3" => "promocode_bonus",
        "4" => "cashback_bonus",
        "5" => "new_rang_bonus",
        "11" => "crm",
        "12" => "referall_bonus",
        "13" => "player_online",
        "14" => "crm"
    );
    $transac = array(
        "0" => "real",
        "1" => "real",
        "2" => "bonus",
        "3" => "bonus",
        "4" => "bonus",
        "5" => "bonus",
        "11" => "real",
        "12" => "bonus",
        "13" => "real",
        "14" => "bonus"
    );
    $notification_id = $transaction[0];
    $transaction = exclude_indexes($transaction);
    $transaction = normalize_array($transaction, array("ID", "active", "country_code"), $transaction["time_zone"]);
    $tr = array("id" => $notification_id);
    $tr = array_merge($tr, $transaction);
    $transaction = array();
    foreach ($tr as $key => $value) {
        if ($key == "name") {
            break;
        }
        $transaction[$key] = $value;
        unset($tr[$key]);
    }
    $transaction["branch"]["id"] = $transaction["branch_id"];
    foreach ($tr as $key => $value) {
        $transaction["branch"][$key] = $value;
    }
    //Извлекаем данные пользователя
    $query = "SELECT * FROM tg_users WHERE \"ID\" = " . $transaction["user_id"];
    $queries[] = $query;
    $r = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $u = pg_fetch_array($r);
    $queries[] = $u;
    $transaction['account'] = $account;
    $transaction['admin']['id'] = $transaction['admin_id'];
    $transaction['admin']['username'] = $u['username'];
    $transaction["user"]["id"] = intval($u["ID"]);
    $transaction["user"]["username"] = $u["username"];
    $transaction["transaction_type"] = $transaction_types[$transaction["transaction"]];
    $transaction["account"] = $transac[$transaction["transaction"]];
    unset($transaction["sender"]);
    unset ($transaction["sender_pc"]);
    unset ($transaction["sender_ip"]);
    unset($transaction["pay_type"]);
    $transactions[] = $transaction;

}
$output = array("result" => "RESULT_SUCCESS", "payload" => array());
$output["payload"]["items"] = $transactions;
$output["payload"]["totalCount"] = $total->count;
header("Content-type:application/json");
exit(format_array($output));
?>