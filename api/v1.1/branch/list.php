<?php
    /**************************************************************************************************
     *************************************Список филиалов**********************************************
     **************************************************************************************************/
    /**************************************************************************************************
     * Фильтрующие параметры:
     *     id (число, список через запятую) - по ID филиала(ов)
     *     name (строка) - по названию (? заменяет один символ, * заменяет произвольное количество символов)
     *     city (строка) - по городу (фильтрация как с полем name)
     * Для сортировки укажите параметр order=
     *     ID - по ID
     *     name - По названию
     *     city - По городу
     *     comp_count - по количеству компьютеров
     *     ip - По IP
     * Ограничение количества элементов в выборке:
     *     limit={количество в виде числа}
     * Пропустить некоторое количество записей в начале:
     *     offset={сколько пропустить в виде числа}
     * Все параметры являются необзательными
     *************************************************************************************************/
    define('WORK', true, true);
    require_once ('../utils/requires.php');
    //Проверяем, авторизован ли пользователь
    require_once("../users/authorise.php");
    $where = 'WHERE "ID" > 0';
    if (!empty($_GET["id"])){
        $id = pg_escape_string($_GET["id"]);
        $where .= " AND \"ID\" IN($id)";
    }
    if (!empty($_GET["name"])){
        $name = pg_escape_string($_GET["name"]);
        $name = str_replace('?', '_', $name);
        $name = str_replace('*', '%', $name);
        $where .= " AND \"name\" LIKE('$name')";
    }
    if (!empty($_GET["city"])){
        $city = pg_escape_string($_GET["city"]);
        $city = str_replace('?', '_', $city);
        $city = str_replace('*', '%', $city);
        $where .= " AND \"city\" LIKE('$city')";
    }
    if (!empty($_GET["order"])){
        $order = pg_escape_string($_GET["order"]);
        $where .= " ORDER BY \"$order\"";
    }
    if (!empty($_GET["limit"])){
        $limit = intval($_GET["limit"]);
        $where .= " LIMIT $limit";
    }
    if (!empty($_GET["offset"])){
        $offset = intval($_GET["offset"]);
        $where .= " OFFSET $offset";
    }
    $query = "SELECT * FROM tg_filial $where";
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $out = array();
    while($row = pg_fetch_array($result)){
        $out[] = $row;
    }
    pg_free_result($result);
    $res = output_format_table($out);
    exit($res);
?>