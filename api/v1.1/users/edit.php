<?php
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $user_id = intval($arr[1]);
    if ($user_id < 1) {
        die(format_result("ERROR_INVALID_USER_ID"));
    }
    $login = $_REQUEST['login'];
    $login = pg_escape_string($login);
    $user_id = intval($user_id);
    $query = "SELECT * FROM tg_users WHERE \"ID\" = $user_id";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    if (pg_num_rows($result) < 1) {
        die(format_result("ERROR_USER_DOES_NOT_EXISTS"));
    }
    $query = "SELECT * FROM tg_users WHERE UPPER(username) = UPPER('{$login}') ";
    $username = pg_query($query);
    $username = pg_fetch_all($username);
    if ($username == false)
    {
        $query = "UPDATE tg_users SET username = '{$login}' WHERE \"ID\"=$user_id";
        pg_query($query);
        $query = "SELECT * FROM tg_users WHERE \"ID\"=$user_id";
        $user = pg_query($query);
        $out = pg_fetch_assoc($user);

        $result = array("result" => "RESULT_SUCCESS");
        $result["payload"] = array();
        $result["payload"]["items"] = $out;
        $result["payload"]["currencies_total"] = '';
        $result["payload"]["total"] = '';
        $result["payload"]["count"] = '';
        header("Content-type:application/json");
        exit(format_array($result));
    }
    else {
        die(format_result("ERROR_LOGIN_ALREADY_EXISTS"));
    }
}
