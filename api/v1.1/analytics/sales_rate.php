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
        $branch_filter = "WHERE branch_id IN(" . implode(',', $branch_id) . ")";
    } else {
        $branch_filter = "WHERE branch_id IN (" . $allowed_branches_subquery . ")";
    }
    if (!empty($filter["date_start"]) && !empty($filter["date_end"])) {
        $date_start = pg_escape_string($filter["date_start"]);
        $date_end = pg_escape_string($filter["date_end"]);
        $interval = strtotime($date_end) - strtotime($date_start);
        $hours = round($interval / 3600, 5);
        $date_filter = "AND (\"date\" BETWEEN TO_TIMESTAMP('$date_start', 'YYYY-MM-DD HH24:MI:SS') AND TO_TIMESTAMP('$date_end', 'YYYY-MM-DD HH24:MI:SS'))";
    } elseif (!empty($filter["date_start"])) {
        $date_start = pg_escape_string($filter["date_start"]);
        $interval = time() - strtotime($date_start);
        $hours = round($interval / 3600, 5);
        $date_filter = " AND \"date\" >= TO_TIMESTAMP('$date_start', 'YYYY-MM-DD HH24:MI:SS')";
    } elseif (!empty($filter["date_end"])) {
        die(format_result("ERROR_DATE_START_REQUIRED"));
    } elseif (empty($filter["date_start"]) && empty($filter["date_end"])) {
        $month_start = date("Y-m") . "-01 00:00:00";
        $interval = time() - strtotime($month_start);
        $hours = round($interval / 3600, 5);
        $date_filter = " AND \"date\" >= TO_TIMESTAMP('$month_start', 'YYYY-MM-DD HH24:MI:SS')";
    }
    if (!empty($_REQUEST["sort"])) {
        $sort = $_REQUEST["sort"];
        $sort = json_decode($sort, true);
        if (is_array($sort)) {
            $field = $sort["field"];
            $direction = $sort["order"];
        } else {
            $field = "admin_name";
            $direction = "ASC";
        }
    } else {
        $field = "admin_name";
        $direction = "ASC";
    }
    $allowed = array('ID', 'date', 'admin_id', 'admin_name', 'money', 'set_type', 'pos', 'status', 'branch_id', 'smena');
    $keyField = array_search($field, $allowed);
    if (gettype($keyField) == 'integer') {
        $query = "SELECT * FROM tg_bar_check $branch_filter $date_filter ORDER BY $field $direction";
        $bar = pg_query($query);
        $barCheck = pg_fetch_all($bar);
        $checkLists = array();
        $summ = 0;
        foreach ($barCheck as $check_id) {
            $check_id['admin_name'] = trim($check_id['admin_name']);
            $query = "SELECT * FROM tg_bar_check_list WHERE check_id = '{$check_id['ID']}'";
            $check = pg_query($query);
            $out = array();
            $out = $check_id;
            $checkAll = pg_fetch_all($check);
            $i = 0;
            foreach ($checkAll as $item) {
                $checkAll[$i]['product'] = trim($item['product']);
                $checkAll[$i]['admin_name'] = trim($item['admin_name']);
                $i++;
            }
            $out['list'] = $checkAll;
            $checkLists[] = $out;
            $money = money_to_float($check_id['money']);
            $summ = $summ + $money;
        }
        $result = array("result" => "RESULT_SUCCESS");
        $result["payload"] = array();
        $result["payload"]["items"] = $checkLists;
        $result["payload"]["currencies_total"] = $summ;
        $result["payload"]["total"] = count($barCheck);
        $result["payload"]["count"] = count($barCheck);
        header("Content-type:application/json");
        exit(format_array($result));
    }
}
