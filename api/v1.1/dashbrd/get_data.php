<?php
//Массивы со списком полей
$total_sum_fields = array("cash", "card", "bar_cash", "bar_card", "acquiring"); //Полный доход клуба
$check_count_fields = array("cash_coll", "card_coll", "bar_cash_coll", "bar_card_coll", "acquiring_coll");
$_REQUEST["filter"] = str_replace('\\', '', $_REQUEST["filter"]);
if (!empty($_REQUEST["filter"])){
    $filter = $_REQUEST["filter"];
    $filter = json_decode($filter, true);
    if (intval($filter["filter"]["branch_id"]) > 0){
        require_once(dirname(__FILE__).'/computers_list.php');
    }
}
if (!is_array($filter["id"]) || count($filter["id"]) < 1){
    if (intval($user["is_superhost"]) == 1){
        //Получаем список всех филиалов в системе
        $query = "SELECT * FROM tg_branches WHERE currency_code = 'RUB' ORDER BY \"ID\"";
        $queries[] = $query;
        $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
        while ($row = pg_fetch_array($result)){
            $filter["id"][] = $row["ID"];
        }
    }
    else {
        $query = "SELECT * FROM tg_users_to_branches WHERE user_id = ".$user["ID"];
        $queries[] = $query;
        $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
        while ($row = pg_fetch_array($result)){
            $filter["id"][] = $row["branch_id"];
        }
    }
}
if (!empty($filter['created_at']['from']) && !empty($filter['created_at']['to'])) {
    $date_start = pg_escape_string($filter['created_at']['from']);
    $date_start = str_replace('T', ' ', $date_start);
    $date_start = str_replace('.000Z', ' ', $date_start);
    $date_end = pg_escape_string($filter['created_at']['to']);
    $date_end = str_replace('T', ' ', $date_end);
    $date_end = str_replace('.000Z', ' ', $date_end);
    $date_filter = "AND (\"date_start\" BETWEEN TO_TIMESTAMP('$date_start', 'YYYY-MM-DD HH24:MI:SS') AND TO_TIMESTAMP('$date_end', 'YYYY-MM-DD HH24:MI:SS'))";
} elseif (!empty($filter['created_at']['from'])) {
    $date_start = pg_escape_string($filter['created_at']['from']);
    $date_start = str_replace('T', ' ', $date_start);
    $date_start = str_replace('.000Z', ' ', $date_start);
    $date_filter = " AND \"date_start\" >= TO_TIMESTAMP('$date_start', 'YYYY-MM-DD HH24:MI:SS')";
} elseif (!empty($filter['created_at']['to'])) {
    $date_end = pg_escape_string($filter["date_end"]);
    $date_end = str_replace('T', ' ', $date_end);
    $date_end = str_replace('.000Z', ' ', $date_end);
    $date_filter = " AND \"date_start\" <= TO_TIMESTAMP('$date_end', 'YYYY-MM-DD HH24:MI:SS')";
} elseif (empty($filter['created_at']['from']) && empty($filter['date']['to'])) {
    $month_start = date("Y-m") . "-01 00:00:00";
    $date_filter = " AND \"date_start\" >= TO_TIMESTAMP('$month_start', 'YYYY-MM-DD HH24:MI:SS')";
}
$data = array();//Массив для всех филиалов
$total_data = array(); //Данные по всем выводимым клубам
$i = 0;
//Получаем список валют
$query = "SELECT DISTINCT(currency_code) AS currency_code, currency FROM tg_branches WHERE \"ID\" IN(".implode(",", $filter["id"]).") ORDER BY currency_code";
$queries[] = $query;
$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
while ($row = pg_fetch_array($result)) {
    $total_data["currencies"][$row["currency_code"]] = array("currency"=>$row["currency"], "currency_code"=>$row["currency_code"], "total"=>0, "cash"=>0, "card"=>0, "bar_cash"=>0, "bar_card"=>0, "acquiring"=>0);
}
foreach ($filter["id"] as $branch_id){
    $data[$i]["branch_id"] = $branch_id;
    //Извлекаем валюту клуба
    $query = "SELECT * FROM tg_branches WHERE \"ID\" = $branch_id";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    if (pg_num_rows($result) < 1){
        $res = array("result"=>"ERROR_BRANCH_DOES_NOT_EXISTS", "payload"=>array("branch_id"=>intval($branch_id)));
        exit(format_array($res));
    }
    $row = pg_fetch_array($result);
    $data[$i]["currency"] = $row["currency"];
    $club_currency = $row["currency_code"];
    $data[$i]["currency_code"] = $row["currency_code"];
    $total_sum_flds = implode(" + ", $total_sum_fields);
    $fields = implode(", ", $total_sum_fields);
    $query = "SELECT $total_sum_flds as total, $fields FROM tg_kassa_smena WHERE branch_id = $branch_id AND STATUS = 0";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $row = pg_fetch_array($result);
    $data[$i]["total"] = money_to_float($row["total"]);
    $total_data["currencies"][$club_currency]["total"] += money_to_float($row["total"]);
    $data[$i]["cash"] = money_to_float($row["cash"]);
    $total_data["currencies"][$club_currency]["cash"] += money_to_float($row["cash"]);
    $data[$i]["card"] = money_to_float($row["card"]);
    $total_data["currencies"][$club_currency]["card"] += money_to_float($row["card"]);
    $data[$i]["bar_cash"] = money_to_float($row["bar_cash"]);
    $total_data["currencies"][$club_currency]["bar_cash"] += money_to_float($row["bar_cash"]);
    $data[$i]["bar_card"] = money_to_float($row["bar_card"]);
    $total_data["currencies"][$club_currency]["bar_card"] += money_to_float($row["bar_card"]);
    $data[$i]["aсquiring"] = money_to_float($row["acquiring"]);
    $total_data["currencies"][$club_currency]["acquiring"] += money_to_float($row["acquiring"]);
    pg_free_result($result);
    $query = "SELECT COUNT(*) AS users_count FROM tg_pc_info WHERE branch_id = $branch_id AND user_id > 0";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $row = pg_fetch_array($result);
    $data[$i]["users_online"] = intval($row["users_count"]);
    $total_data["users_online"] += intval($row["users_count"]);
    pg_free_result($result);
    //Получаем количество компьютеров в клубе
    $query = "SELECT COUNT(*) AS comp_count FROM tg_pc_info WHERE branch_id = $branch_id";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $row = pg_fetch_array($result);
    //Средняя загрузка клуба(ов)
    $total_data["computers_count"] += intval($row["comp_count"]);
    $data[$i]["computers_count"] = intval($row["comp_count"]);
    if ($row["comp_count"] > 0){
        $club_occupancy = $data[$i]["users_online"] / $row["comp_count"] * 100;
    }
    else {
        $club_occupancy = 0;
    }
    $data[$i]["club_occupancy"] = $club_occupancy;
    if ($total_data["computers_count"] > 0){
        $total_data["club_occupancy"] = $total_data["users_online"] / $total_data["computers_count"] * 100;
    }
    else{
        $total_data["club_occupancy"] = 0;
    }
    pg_free_result($result);
    //Количество новых игроков
    $query = "SELECT COUNT(*) as new_users_count FROM tg_users WHERE reg_date >= (SELECT date_start FROM tg_kassa_smena 
    WHERE status = 0 AND branch_id = $branch_id ORDER BY date_start LIMIT 1) 
    AND \"ID\" IN(SELECT user_id FROM tg_users_to_branches WHERE branch_id = $branch_id)";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $row = pg_fetch_array($result);
    $new_users_count = $row["new_users_count"];
    $total_data["new_users_count"] += intval($new_users_count);
    $data[$i]["new_users_count"] = intval($new_users_count);
    pg_free_result($result);
    //Получаем количество чеков
    $check_count_flds = implode(" + ", $check_count_fields);
    $query = "SELECT $check_count_flds as check_count FROM tg_kassa_smena WHERE status = 0 AND branch_id = $branch_id";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $row = pg_fetch_array($result);
    $total_data["currencies"][$club_currency]["receipt_count"] += intval($row["check_count"]);
    $total_data["receipt_count"] += intval($row["check_count"]);
    if ($total_data["currencies"][$club_currency]["receipt_count"] > 0){
        $total_data["currencies"][$club_currency]["average_receipt"] = $total_data["currencies"][$club_currency]["total"] / $total_data["currencies"][$club_currency]["receipt_count"];
    }
    else {
        $total_data["currencies"][$club_currency]["average_receipt"] = 0;
    }
    $data[$i]["receipt_count"] = intval($row["check_count"]);
    if ($data[$i]["receipt_count"] > 0){
        $data[$i]["average_receipt"] = $data[$i]["total"] / $row["check_count"];
    }
    else{
        $data[$i]["average_receipt"] = 0;
    }
    pg_free_result($result);
    //Получаем отыгранные часы
    $query = "SELECT SUM(duration) AS duration FROM tg_game_time WHERE \"date_start\" > 
    (SELECT date_start FROM tg_kassa_smena WHERE status = 0 AND branch_id = $branch_id ORDER BY date_start LIMIT 1) 
    AND branch_id = $branch_id";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $row = pg_fetch_array($result);
    if ($row["duration"] % 60 < 10){
        $game_time = floor($row["duration"] / 60).":0".$row["duration"] % 60;
    }
    else{
        $game_time = floor($row["duration"] / 60).":".$row["duration"] % 60;
    }
    $data[$i]["game_time"] = $game_time;
    $data[$i]["game_time_minutes"] = intval($row["duration"]);
    if ($total_data["game_time_minutes"] % 60 < 10){
        $total_data["game_time"] = floor($total_data["game_time_minutes"] / 60).":0".$total_data["game_time_minutes"] % 60;
    }
    else{
        $total_data["game_time"] = floor($total_data["game_time_minutes"] / 60).":".$total_data["game_time_minutes"] % 60;
    }
    $total_data["game_time_minutes"] += intval($row["duration"]);
    $i++;
}
$res["result"] = "RESULT_SUCCESS";
$res["payload"] = array();
$res["payload"]["total"] = $total_data;
$res["payload"]["branches"] = $data;
header("Content-type:application/json");
echo format_array($res);
?>
