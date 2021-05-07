<?php
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $fields = $_REQUEST["fields"];
    if (empty($fields)) {
        die(format_result("ERROR_EMPTY_FIELDS"));
    }
    $fields = json_decode($fields, true);
    if (!is_array($fields)) {
        die(format_result("ERROR_FIELDS_IS_NOT_ARRAY"));
    }
    foreach ($fields as $field) {
        $comp_id = $field[0];
        $branch_id = $field[1];
        $values = $field[2];
        $query = "select * from tg_pc_info where branch_id = $branch_id and comp_id = $comp_id";
        $add = pg_query($query);
        $add = pg_fetch_all($add);
        if ($add == false) {
            $result = array("result" => "ERROR");
            header("Content-type:application/json");
            exit(format_array($result));
        }
        $query = "update tg_pc_info
            set user_id = $values[0],
                status = $values[1], 
                money = $values[2],
                bonus = $values[3],
                packet_id = $values[4],
                minutes = $values[5]
                where branch_id = $branch_id and comp_id = $comp_id";
        $add = pg_query($query);
    }
    $result = array("result" => "RESULT_SUCCESS");
    header("Content-type:application/json");
    exit(format_array($result));
}