<?php
$branches = $_REQUEST["filter"];

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
    $branches = implode(',', $branch_id);
}
else {
    $branches = $allowed_branches_subquery;
}

$limitStart = $_GET['offset'];
$limitCount = $_GET['limit'];

if (empty($branches)) {
    $query = "SELECT * FROM tg_branches ORDER BY \"ID\"";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $br = array();
    while ($branch = pg_fetch_array($result)) {
        $br[] = exclude_indexes($branch);
        $branches[] = $branch["ID"];
    }
    $queries[] = $br;
}
$sort = $_REQUEST["sort"];
if (!empty($sort)) {
    $sort = json_decode($sort, true);
    $sort_field = '"' . pg_escape_string($sort["field"]) . '"';
    $sort_direction = pg_escape_string($sort["order"]);
} else {
    $sort_field = 'username';
    $sort_direction = "ASC";
}
$search = pg_escape_string($_REQUEST["search"]);
if (!empty($search)) {
    $search = str_replace('*', '%', $search);
    $search = str_replace('?', '_', $search);
    $where = " WHERE status = 5 AND (username LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%' OR name LIKE '%$search%' OR surname LIKE '%$search%')";
} else {
    $where = " WHERE status = 5";
}
$where .= " AND tutb.branch_id IN($branches)";
$query = "SELECT * FROM tg_users tu RIGHT JOIN tg_users_to_branches tutb ON tu.\"ID\" = tutb.user_id$where ORDER BY tu.$sort_field $sort_direction";
$count = pg_query($query);
$count = count(pg_fetch_all($count));
$query = "SELECT * FROM tg_users tu RIGHT JOIN tg_users_to_branches tutb ON tu.\"ID\" = tutb.user_id$where ORDER BY tu.$sort_field $sort_direction limit $limitCount offset $limitStart";
$queries[] = $query;
$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
$users = array();
$exclude_fields = array("password", "salt", "phone_code", "email_token", "ban_reason", "ban_reason_admin", "ban_start", "ban_end", "temp", "user_id", "deleted");
while ($usr = pg_fetch_array($result)) {
    $usr["id"] = $usr["user_id"];
    $usr["bonus_balance"] = $usr["bonus"];
    unset($usr["bonus"]);
    $usr = normalize_array($usr, $exclude_fields, $usr["time_zone"]);
    unset($usr["ID"]);
    $query = "SELECT * FROM tg_rang WHERE branch_id = " . $usr["branch_id"] . " AND num = " . $usr["rang"];
    $res = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $rang = pg_fetch_array($res);
    $usr["rang"] = array("id" => intval($usr["rang"]), "name" => trim($rang["name"]));
    $query = "SELECT * FROM tg_branches WHERE \"ID\" = " . $usr["branch_id"];
    $res = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $branch = pg_fetch_array($res);
    $usr["branch"] = array("id" => intval($usr["branch_id"]), "name" => trim($branch["name"]), "currency" => trim($branch["currency"]), "currency_code" => trim($branch["currency_code"]));
    unset ($usr["branch_id"]);
    $usr["banned"] = $usr["banned"] == 1;
    $usr["send_sms"] = $usr["send_sms"] == 1;
    $usr["send_push"] = $usr["send_push"] == 1;
    $usr["is_superhost"] = $usr["is_superhost"] == 1;
    if ($usr["deleted"] == 1) {
        $usr["active"] = "deleted";
    } else {
        if ($usr["banned"] == 1) {
            $usr["active"] = "banned";
        } else {
            $usr["active"] = "active";
        }
    }
    $users[] = $usr;
}
$queries[] = $users;
$output = array("result" => "RESULT_SUCCESS", "payload" => array("items" => array(), "totalCount" => $count));
$output["payload"]["items"] = $users;
header("Content-type:application/json");
exit(format_array($output));
?>
