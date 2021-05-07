<?php
if ($_SERVER['REQUEST_METHOD'] = 'GET') {
    if (!empty($_REQUEST["ip"])) {
        $ip = pg_escape_string($_REQUEST["ip"]);
    }
    $ip = $_SERVER["REMOTE_ADDR"];
    $query = "SELECT * FROM tg_branches WHERE ip = '" . $ip . "' LIMIT 1";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $branch = pg_fetch_array($result);
    $out["result"] = "RESULT_SUCCESS";
    $out["payload"] = exclude_indexes($branch);
    if ($branch == false)
    {
        $out['ip'] = $ip;
    }
    header("Content-type:application/json");
    echo format_array($out);
    exit();
}
