<?php
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
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
        $branch_filter = implode(',', $branch_id);
    } else {
        $branch_filter = $allowed_branches_subquery ;
    }
    if (!empty($filter["date_start"]) && !empty($filter["date_end"])) {
        $date_start = pg_escape_string($filter["date_start"]);
        $date_end = pg_escape_string($filter["date_end"]);
        $interval = strtotime($date_end) - strtotime($date_start);
        $hours = round($interval / 3600, 5);
        $date_filter = "AND (\"date_start\" BETWEEN TO_TIMESTAMP('$date_start', 'YYYY-MM-DD HH24:MI:SS') AND TO_TIMESTAMP('$date_end', 'YYYY-MM-DD HH24:MI:SS'))";
    } elseif (!empty($filter["date_start"])) {
        $date_start = pg_escape_string($filter["date_start"]);
        $interval = time() - strtotime($date_start);
        $hours = round($interval / 3600, 5);
        $date_filter = " AND \"date_start\" >= TO_TIMESTAMP('$date_start', 'YYYY-MM-DD HH24:MI:SS')";
    } elseif (!empty($filter["date_end"])) {
        die(format_result("ERROR_DATE_START_REQUIRED"));
    } elseif (empty($filter["date_start"]) && empty($filter["date_end"])) {
        $month_start = date("Y-m") . "-01 00:00:00";
        $interval = time() - strtotime($month_start);
        $hours = round($interval / 3600, 5);
        $date_filter = " AND \"date_start\" >= TO_TIMESTAMP('$month_start', 'YYYY-MM-DD HH24:MI:SS')";
    }
    if (!empty($_REQUEST["sort"])) {
        $sort = $_REQUEST["sort"];
        $sort = json_decode($sort, true);
        if (is_array($sort)) {
            $field = $sort["field"];
            $direction = $sort["order"];
        } else {
            $field = "choices_percentage";
            $direction = "DESC";
        }
    } else {
        $field = "choices_percentage";
        $direction = "DESC";
    }
    $query = "
CREATE TEMPORARY TABLE staff_temp AS (
        select tg_users .email, tg_users.phone, tg_users.name,
	            tg_users.surname, tg_kassa_smena.date_start, tg_kassa_smena.kassa_total,
	            tg_kassa_smena.bar_total, tg_kassa_smena.bonus, tg_branches.currency, tg_kassa_smena.bar_card_coll,
                tg_kassa_smena.bar_cash_coll, tg_kassa_smena.cash_coll, tg_kassa_smena.card_coll
        from tg_users
        join tg_users_to_branches on tg_users.\"ID\" = tg_users_to_branches.user_id
        join tg_branches on tg_users_to_branches.branch_id = tg_branches .\"ID\"
        join tg_kassa_smena on tg_kassa_smena.admin_id = tg_users.\"ID\" 
        where tg_branches.\"ID\" in ($branch_filter) and tg_users.status = 3 $date_filter
        order by $field $direction )
        ";
    $stuffs = pg_query($query);
    $query = "select * from staff_temp";
    $stuffs = pg_query($query);
    $count = 0;
    $out = array();
    $kassa_total = 0;
    $bar_total = 0;
    $bonus = 0;
    $i=0;
    while ($stuff = pg_fetch_object($stuffs))
    {
        $arr = array();
        $arr['email'] = $stuff->email;
        $arr['phone'] = $stuff->phone;
        $arr['name'] = $stuff->name;
        $arr['surname'] = $stuff->surname;
        $arr['date_start'] = $stuff->date_start;
        $arr['currency'] = $stuff->currency;
        $arr['kassa_total'] = $stuff->kassa_total;
        $arr['bar_total'] = $stuff->bar_total;
        $arr['bonus'] = $stuff->bonus;
        $arr['bar_card_coll'] = $stuff->bar_card_coll;
        $arr['bar_cash_coll'] = $stuff->bar_cash_coll;
        $arr['card_coll'] = $stuff->card_coll;
        $arr['cash_coll'] = $stuff->cash_coll;

        $kassa_total = $kassa_total + money_to_float($stuff->kassa_total);
        $bar_total = $bar_total + money_to_float($stuff->bar_total);
        $bonus = $bonus + money_to_float($stuff->bonus);
        $out[] = $arr;
        $i++;
    }

    $result = array("result" => "RESULT_SUCCESS");
    $result["payload"] = array();
    $result["payload"]["items"] = $out;
    $result["payload"]["currencies_kassa_total"] = $kassa_total;
    $result["payload"]["currencies_bar_total"] = $bar_total;
    $result["payload"]["currencies_bonus"] = $bonus;
    $result["payload"]["currency"] = $out[0]['currency'];
    $result["payload"]["total"] = $i;
    $result["payload"]["count"] = $i;
    pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    header("Content-type:application/json");
    exit(format_array($result));
}
