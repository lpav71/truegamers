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
$limit = $_GET['limit'];
$offset = $_GET['offset'];
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
//Фильтрация по админам
if (isset($filter["admins"])) {
    if (empty($filter["admins"])) {
        $query = "SELECT * FROM tg_users WHERE \"status\" = 3 AND \"ID\" IN(SELECT user_id FROM tg_users_to_branches WHERE branch_id IN ($allowed_branches_subquery))";
        $queries[] = $query;
        $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
        while ($admin = pg_fetch_array($result)) {
            $filter["admins"][] = $admin["ID"];
            $admins[] = $admin;
        }
        $admins_filter = " AND admin_id IN(" . implode(",", $filter["admins"]) . ")";
        $queries[] = $admins;
    } else {
        $admins_filter = " AND admin_id IN(" . implode(",", $filter["admins"]) . ")";
    }
} else {
    $admins_filter = "";
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
$query = "SELECT count(*) FROM tg_notification tn RIGHT JOIN tg_branches tb ON tn.branch_id = tb.\"ID\" $branch_filter $date_filter $user_filter $transaction_filter $admins_filter";
$total = pg_query($query);
$total = pg_fetch_object($total);

$query = "SELECT * FROM tg_notification tn RIGHT JOIN tg_branches tb ON tn.branch_id = tb.\"ID\" $branch_filter $date_filter $user_filter $transaction_filter $admins_filter ORDER BY \"$field\" $direction limit $limit offset $offset";
$queries[] = $query;
$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
$transfers = array();
while ($transfer = pg_fetch_array($result)) {
    $notification_id = $transfer[0];
    $transfer = exclude_indexes($transfer);
    $transfer = normalize_array($transfer, array("ID", "active", "country_code"), $transfer["time_zone"]);
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
    $query = "SELECT * FROM tg_users WHERE \"ID\" = " . $transfer["admin_id"];
    $queries[] = $query;
    $r = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $u = pg_fetch_array($r);
    $queries[] = $u;
    $sender["id"] = intval($u["ID"]);
    $sender["username"] = $u["username"];
    $transfer["admin"] = array();
    $transfer["admin"] = $sender;
    $t_type = $transaction_types[$transfer["transaction"]];
    $t_account = $transac[$transfer["transaction_type"]];
    $transfer["transaction_type"] = $t_type;
    $transfer["account"] = $t_account;
    unset ($transfer["sender"]);
    unset ($transfer["admin_id"]);
    unset ($transfer["sender_pc"]);
    unset ($transfer["sender_ip"]);
    unset($transfer["transaction"]);
    unset($transfer["pay_type"]);
    $transfers[] = $transfer;
}
$queries[] = $transfers;
$output = array("result" => "RESULT_SUCCESS", "payload" => array());
$output["payload"]["items"] = $transfers;
$output["payload"]["totalCount"] = $total->count;
header("Content-type:application/json");
exit(format_array($output));
?>
