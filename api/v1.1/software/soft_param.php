<?php
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $query = "SELECT * FROM tg_soft_param";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $count = pg_num_rows($result);
    $out = array();
    while ($row = pg_fetch_array($result)) {
        $out_row = array();
        $out_row["id"] = intval($row["ID"]);
        $out_row["game"] = trim($row["game"]);
        $out_row["caption"] = trim($row["caption"]);
        $out_row["class"] = trim($row["class"]);
        $out[] = $out_row;
    }
    pg_free_result($result);
    $res = array();
    $res["result"] = "RESULT_SUCCESS";
    $res["count"] = intval($count);
    $res["payload"] = $out;
    header("Content-type:application/json");
    echo format_array($res);
    exit();
}
