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
            $field = "category";
            $direction = "ASC";
        }
    } else {
        $field = "category";
        $direction = "ASC";
    }
    $allowed = array('ID', 'user_id', 'comp_id', 'category', 'status', 'branch_id', 'money', 'bonus', 'packet_id', 'minutes', 'pos_x', 'pos_y',
        'mac', 'ip', 'version', 'chair_pos');
    $keyField = array_search($field, $allowed);
    if (gettype($keyField)  == 'integer') {
        $query = "SELECT * FROM tg_pc_categories " . $branch_filter;
        pg_ping();
        $categories = pg_query($query);
        $categories = pg_fetch_all($categories);
        foreach ($categories as $category) {
            $numbers[] = $category['number'];
        }
        $pcInfoAll = array();
        foreach ($numbers as $number) {
            $query = "SELECT * FROM tg_pc_info $branch_filter AND category = $number ORDER BY $field $direction";
            $pcInfo = pg_query($query);
            $pcInfo = pg_fetch_all($pcInfo);
            $pcInfoAll[] = $pcInfo;
            $pcCount = count($pcInfo);
        }
        $query = "SELECT duration FROM tg_game_time $branch_filter $date_filter";
        $gameTime = pg_query($query);
        $gameTimes = pg_fetch_all($gameTime);
        $totalGameTime = 0;
        foreach ($gameTimes as $gameTime) {
            $totalGameTime = $totalGameTime + $gameTime['duration'];
        }

        $queries[] = $query;
        $result = array("result" => "RESULT_SUCCESS");
        $result["payload"] = array();
        $result["payload"]["items"] = $pcInfoAll;
        $result["payload"]["currencies_total"] = '';
        $result["payload"]["total"] = $pcCount;
        $result["payload"]["count"] = $pcCount;
        header("Content-type:application/json");
        exit(format_array($result));
    }
}
