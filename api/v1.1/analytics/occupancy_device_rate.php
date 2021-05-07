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
            $field = "comp_id";
            $direction = "ASC";
        }
    } else {
        $field = "comp_id";
        $direction = "ASC";
    }
    $allowed = array('comp_id', 'minutes');
    $keyField = array_search($field, $allowed);
    if (gettype($keyField)  == 'integer')
    {
        $query = "SELECT comp_id FROM tg_pc_info $branch_filter";
        $pcInfo = pg_query($query);
        $pcInfoAll = pg_fetch_all($pcInfo);
        $pcCount[] = count($pcInfoAll);
        $out = array();
        foreach ($pcInfoAll as $pcInfo) {
            $query = "SELECT duration FROM tg_game_time $branch_filter $date_filter AND comp_id = " . $pcInfo['comp_id'];
            $gameTime = pg_query($query);
            $summ = 0;
            while ($gTime = pg_fetch_object($gameTime)) {
                $summ = $summ + $gTime->duration;
            }
            $out[$pcInfo['comp_id']] = $summ;
            $count = count($out);
        }

        if ($field == 'comp_id' && $direction == "ASC") {
            ksort($out);
        }
        if ($field == 'comp_id' && $direction == "DESC") {
            krsort($out);
        }
        if ($field == 'minutes' && $direction == "ASC") {
            asort($out);
        }
        if ($field == 'minutes' && $direction == "DESC") {
            arsort($out);
        }
        $queries[] = $query;
        $result = array("result" => "RESULT_SUCCESS");
        $result["payload"] = array();
        $result["payload"]["items"] = $out;
        $result["payload"]["currencies_total"] = '';
        $result["payload"]["total"] = $count;
        $result["payload"]["count"] = $count;
        header("Content-type:application/json");
        exit(format_array($result));
    }
}
