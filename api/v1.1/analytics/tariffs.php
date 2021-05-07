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
        $date_filter = "AND (\"start\" BETWEEN TO_TIMESTAMP('$date_start', 'YYYY-MM-DD HH24:MI:SS') AND TO_TIMESTAMP('$date_end', 'YYYY-MM-DD HH24:MI:SS'))";
    } elseif (!empty($filter["date_start"])) {
        $date_start = pg_escape_string($filter["date_start"]);
        $interval = time() - strtotime($date_start);
        $hours = round($interval / 3600, 5);
        $date_filter = " AND \"start\" >= TO_TIMESTAMP('$date_start', 'YYYY-MM-DD HH24:MI:SS')";
    } elseif (!empty($filter["date_end"])) {
        die(format_result("ERROR_DATE_START_REQUIRED"));
    } elseif (empty($filter["date_start"]) && empty($filter["date_end"])) {
        $month_start = date("Y-m") . "-01 00:00:00";
        $interval = time() - strtotime($month_start);
        $hours = round($interval / 3600, 5);
        $date_filter = " AND \"start\" >= TO_TIMESTAMP('$month_start', 'YYYY-MM-DD HH24:MI:SS')";
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
    $allowed = array('game_time', 'choices_count', 'choices_percentage');
    $keyField = array_search($field, $allowed);
    if (gettype($keyField)  == 'integer') {
        $query = "DROP TABLE IF EXISTS analytics_tariff_temp";
        $queries[] = $query;
        pg_ping();
        pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
        $query = "CREATE TEMPORARY TABLE analytics_tariff_temp AS (SELECT \"ID\",name, '' AS game_time, 0 AS choices_count, 0 AS choices_percentage FROM tg_prices $branch_filter)";
        $queries[] = $query;
        pg_ping();
        pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
        $query = "SELECT * FROM analytics_tariff_temp";
        $queries[] = $query;
        pg_ping();
        $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
        while ($res = pg_fetch_assoc($result)) {
            //$res['ID']=499;
            $query = "SELECT * FROM tg_reservation WHERE packet_id = " . $res['ID'] . " " . $date_filter;
            pg_ping();
            $reservation = pg_query($query);
            $reservation = pg_fetch_all($reservation);
            if ($reservation) //Если есть данные в связанной таблице tg_reservation
            {
                $choices_count = count($reservation); //Считаем количество записей
                $reservCountTime = 0;
                foreach ($reservation as $reserv) {
                    $start = strtotime($reserv['start']);
                    $end = strtotime($reserv['end']);
                    $game_time = $end - $start;
                    $reservCountTime = $reservCountTime + $game_time;
                }
                $reservCountTime = $reservCountTime / 60;
                $reservCountTime = intval($reservCountTime);
                $query = "UPDATE analytics_tariff_temp SET choices_count = " . $choices_count . ", 
                        game_time =" . $reservCountTime . " WHERE \"ID\" = " . $res['ID'];
                pg_ping();
                pg_query($query); //Вносим во временную таблицу
            }
        }
//Считаем сумму всех использований тарифов
        $query = "SELECT * FROM analytics_tariff_temp WHERE choices_count>0";
        pg_ping();
        $cnt = pg_query($query);
        $cnt = pg_fetch_all($cnt);
        $summ_choices_count = 0; //Сумма всех выборов тарифов
        foreach ($cnt as $cn) $summ_choices_count = $summ_choices_count + $cn['choices_count'];
        foreach ($cnt as $cn) {
            settype($cn['choices_count'], 'integer');
            $percent = $cn['choices_count'] / $summ_choices_count * 100;
            $query = "UPDATE analytics_tariff_temp SET choices_percentage = " . $percent . " WHERE \"ID\" = " . $cn['ID'];
            pg_ping();
            pg_query($query);
        }

        $count = "SELECT COUNT(*) FROM analytics_tariff_temp";
        pg_ping();
        $count = pg_query($count);
        $count = pg_fetch_object($count);
        $query = "SELECT * FROM analytics_tariff_temp ORDER BY " . $field . " " . $direction;
        pg_ping();
        $freqTariff = pg_query($query);
        $freqTariff = pg_fetch_all($freqTariff);
        $queries[] = $query;
        $result = array("result" => "RESULT_SUCCESS");
        $result["payload"] = array();
        $result["payload"]["items"] = $freqTariff;
        $result["payload"]["currencies_total"] = '';
        $result["payload"]["total"] = $count->count;
        $result["payload"]["count"] = $count->count;
//Уничтожаем временную таблицу
        $query = "DROP TABLE IF EXISTS analytics_general_temp";
        $queries[] = $query;
        pg_ping();
        pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
        header("Content-type:application/json");
        exit(format_array($result));
    }
}
