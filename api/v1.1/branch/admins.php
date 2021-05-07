<?php
    /**************************************************************************************************
     *************************************Список админов клуба*****************************************
     **************************************************************************************************/
    /**************************************************************************************************
     * Фильтр полей:
     *     fields (название поля или список через запятую)(ID, username, email, phone - любое поле из таблицы tg_users). Пример: admins.php?fields=email, username
     * Фильтрующие параметры:
     *     id (число, список через запятую) - по ID пользователя
     *     filial_id (число, список через запятую) - по ID филиала(ов)
     *     filial_ip (строка) - по IP-адресу клуба (? заменяет один символ, * заменяет произвольное количество символов)
     *     city (строка) - по городу клуба (? заменяет один символ, * заменяет произвольное количество символов)
     * Для сортировки укажите параметр order=
     *     id - по ID
     *     username - По имени пользователя
     * Ограничение количества элементов в выборке:
     *     limit={количество в виде числа}
     * Пропустить некоторое количество записей в начале:
     *     offset={сколько пропустить в виде числа}
     * Все параметры являются необзательными
     *************************************************************************************************/

    $where = 'WHERE "ID" <> 0';
    if (!empty($_REQUEST["id"])){
        $id = pg_escape_string($_REQUEST["id"]);
        $where .= " AND \"ID\" IN($id)";
    }
    if (!empty($_REQUEST["branch_ip"])){
        if ($_REQUEST["branch_ip"] == 'auto'){
            $ip = $_SERVER["REMOTE_ADDR"];
        }
        else{
            if (check_ip($_REQUEST["branch_ip"])){
                $ip = $_REQUEST["branch_ip"];
            }
        }
        $query = "SELECT * FROM tg_branches WHERE ip = '$ip'";
        $queries[] = $query;
        //echo $query;
        $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
        if (pg_num_rows($result) < 1){
            exit(format_result("NO_BRANCHES_FOUND"));
        }
        $branch = pg_fetch_array($result);
        //while ($filial = pg_fetch_array($result)) {
            $id .= $branch["ID"];
        //}
        $where .= " AND \"branch_id\" = $id";
    }
    elseif (! empty($_REQUEST["city"])){
        $city = pg_escape_string($_REQUEST["city"]);
        $city = str_replace('*', '%', $city);
        $city = str_replace('?', '_', $city);
        $query = "SELECT \"ID\" FROM tg_branches WHERE city LIKE '$city'";
        $queries[] = $query;
        $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
        if (pg_num_rows($result) < 1){
            exit(format_result("NO_BRANCHES_FOUND"));
        }
        $id = "1000000000";
        while ($branch = pg_fetch_array($result)) {
            $id .= ", ".$branch["ID"];
        }
        $where .= " AND \"branch_id\" IN($id)";
    }
    else {
        if (!empty($_REQUEST["branch_id"])){
            $branch_id = pg_escape_string($_REQUEST["branch_id"]);
            $where .= " AND branch_id IN($branch_id)";
        }
    }
    if (!empty($_REQUEST["order"])){
        $order = pg_escape_string($_REQUEST["order"]);
        $where .= " ORDER BY $order";
    }
    if (!empty($_REQUEST["limit"])){
        $limit = intval($_REQUEST["limit"]);
        $where .= " LIMIT $limit";
    }
    if (!empty($_REQUEST["offset"])){
        $offset = intval($_REQUEST["offset"]);
        $where .= " OFFSET $offset";
    }
    if (!empty($_REQUEST["fields"])){
        $fields = pg_escape_string($_REQUEST["fields"]);
        $fields = '"'.str_replace(',', '","', $fields).'"';
    }
    else {
        $fields = "*";
    }
    $query = "SELECT $fields FROM tg_users WHERE \"ID\" IN (SELECT \"user_id\" FROM tg_users_to_branches $where) AND status IN(1, 3)";
    //echo $query;
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $out = array();
    while($row = pg_fetch_array($result)){
        $out[] = $row;
    }
    pg_free_result($result);
    $res = output_format_table($out);
    exit($res);
?>
