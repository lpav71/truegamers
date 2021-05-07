<?php
    /**************************************************************************************************
     *************************************Список компьютеров*******************************************
     **************************************************************************************************/
    /**************************************************************************************************
     * Фильтр полей:
     *     fields (название поля или список через запятую)(ID, filial_id - любое поле из таблицы tg_pc_info). Пример: list.php?fields=ID,filial_id,status
     * Фильтрующие параметры:
     *     id (число, список через запятую) - по ID компьютера(ов)
     *     filial_id (число) - по ID филиала(ов) (обязательный параметр)
     *     category (число, список через запятую) - по категории
     *     status (число, список через запятую) - по статусу
     * Для сортировки укажите параметр order=
     *     id - по ID
     *     filial_id - По ID филиала
     *     category - По категории
     *     status - По статусу
     *     user_id - По ID пользователя
     *     money - По балансу пользователя
     *     bonus - По бонусному балансу пользователя
     *     packet_id - По ID пакета
     *     minutes - по оставшемуся времени
     * Ограничение количества элементов в выборке:
     *     limit={количество в виде числа}
     * Пропустить некоторое количество записей в начале:
     *     offset={сколько пропустить в виде числа}
     *************************************************************************************************/
    define('WORK', true, true);
    require_once ('../utils/requires.php');
    //Проверяем, авторизован ли пользователь
    require_once("../users/authorise.php");
    if (intval($_GET["filial_id"]) < 1){
        exit(format_result("ERROR_EMPTY_FILIAL_ID"));
    }
    $filial_id = intval($_GET["filial_id"]);
    $where = "WHERE filial_id = $filial_id";
    if (! empty($_GET["id"])){
        $id = pg_escape_string($_GET["id"]);
        $where .= " AND \"ID\" IN($id)";
    }
    if (! empty($_GET["category"])){
        $category = pg_escape_string($_GET["category"]);
        $where .= " AND \"category\" IN($category)";
    }
    if (! empty($_GET["status"])){
        $status = pg_escape_string($_GET["status"]);
        $where .= " AND \"status\" IN($status)";
    }
    if (!empty($_GET["order"])){
        $order = pg_escape_string($_GET["order"]);
        $where .= " ORDER BY $order";
    }
    if (!empty($_GET["limit"])){
        $limit = intval($_GET["limit"]);
        $where .= " LIMIT $limit";
    }
    if (!empty($_GET["offset"])){
        $offset = intval($_GET["offset"]);
        $where .= " OFFSET $offset";
    }
    if (!empty($_GET["fields"])){
        $fields = pg_escape_string($_GET["fields"]);
        $fields = '"'.str_replace(',', '","', $fields).'"';
    }
    else {
        $fields = "*";
    }
    $query = "SELECT $fields FROM tg_pc_info $where";
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $out = array();
    while($row = pg_fetch_array($result)){
        $out[] = $row;
    }
    pg_free_result($result);
    $res = output_format_table($out);
    exit($res);
?>