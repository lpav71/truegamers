<?php
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $filter = $_REQUEST["filter"];
    if (empty($filter)) {
        die(format_result("ERROR_EMPTY_FILTER"));
    }
    $filter = json_decode($filter, true);
    $branch_id = $filter["branch_id"];
    if (is_array($branch_id)) {
        die(format_result("ERROR_INVALID_BRANCH_ID"));
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
    $query = "SELECT product,  sum(coll) as quantity, sum (price_total) as total
        FROM tg_bar_check_list where branch_id = $branch_id $date_filter group by rollup (product)";
    //$query = "SELECT product_id, product, date, price_total FROM tg_bar_check_list where branch_id = $branch_id $date_filter ORDER BY date";
    $bar = pg_query($query);
    $barCheck = pg_fetch_all($bar);
    $out = array();
    $i = 0;
    foreach ($barCheck as $item) {
        $item['product'] = trim($item['product']);
        $out[$i] = $item;
        $i++;
    }
    $s = 0;
    foreach ($barCheck as $item) {
        $s = $s + money_to_float($item['total']);
    }
    $query = "select currency from tg_branches where \"ID\" = $branch_id";
    $currency = pg_query($query);
    $currency = pg_fetch_row($currency);
    $currency = $currency[0];
    $result = array("result" => "RESULT_SUCCESS");
    $result["payload"] = array();
    $result["payload"]["items"] = $out;
    $result["payload"]["currencies_total"] = $s;
    $result["payload"]["currency"] = $currency;
    $result["payload"]["total"] = count($barCheck);
    $result["payload"]["count"] = count($barCheck);
    header("Content-type:application/json");
    exit(format_array($result));
}