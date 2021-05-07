<?php
    /**************************************************************************************************
     *************************************Удаление пользователя****************************************
     **************************************************************************************************/
    $id = intval($_GET["id"]);
    if ($id < 1){
        $id = intval($user["ID"]);
    }
    $query = "SELECT * FROM tg_users WHERE \"ID\" = $id";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  	if (pg_num_rows($result) < 1) die(format_result('ERROR_USER_DOES_NOT_EXISTS'));
    $usr = pg_fetch_array($result);
    $usr = exclude_indexes($usr);
    pg_free_result($result);
    $out["Result"] = "RESULT_SUCCESS";
    $out["payload"] = array("balance"=>money_to_float($usr["balance"]), "bonus_balance"=>money_to_float($usr["bonus"]));
    echo format_array($out);
?>
