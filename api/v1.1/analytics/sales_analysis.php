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

}