<?php
if ($_SERVER['REQUEST_METHOD'] = 'GET') {
    $filter = $_REQUEST["filter"];
    if (empty($filter)) {
        die(format_result("ERROR_EMPTY_FILTER"));
    }
    $filter = json_decode($filter, true);
    $id = $filter["id"];
    $sort = $_REQUEST["sort"];
    if (!empty($sort))
    {
        $sort = json_decode($sort,true);
        $promo_filter = " ORDER BY ".$sort['field']." ".$sort['order'];
    }
    //Проверяем существует ли такой ID
    $query = "SELECT * FROM tg_promo WHERE \"ID\"=$id";
    $interResult = pg_query($query);
    $queries[] = $query;
    //Формируем массив promo
    $promo = array();
    $interResultArray = pg_fetch_assoc($interResult);
    $promo['id'] = $interResultArray['ID'];
    $promo['promo_name'] = $interResultArray['promo_name'];
    $promo['description'] = $interResultArray['description'];
    $promo['bonus'] = $interResultArray['bonus'];
    $promo['max_activations'] = $interResultArray['max_activations'];
    $promo['create_date'] = $interResultArray['create_date'];
    $promo['end_date'] = $interResultArray['end_date'];
    $promo['used'] = $interResultArray['used'];
    $promo['status'] = $interResultArray['status'];
    $promo['total_sum'] = $interResultArray['total_sum'];
    $promo['code'] = $interResultArray['code'];
    $promo['branch_id'] = $interResultArray['branch_id'];
    if ($interResultArray['ID'] == $id) //Если ID существует
    {
        if (!empty($sort))
        {
            $query = "SELECT * FROM tg_promo_activations WHERE \"promo_id\"=" . $id." ".$promo_filter;
        }
        else
        {
            $query = "SELECT * FROM tg_promo_activations WHERE \"promo_id\"=" . $id;
        }
        $queries[] = $query;
        $tgPromoActivationsRecord = pg_query($query);
        $tgPromoActivationsRecords = pg_fetch_all($tgPromoActivationsRecord);
        //Получаем данные пользователя
        $items = array(); $i=0;
        foreach ($tgPromoActivationsRecords as $activationsRecord)
        {
            $query = "SELECT \"ID\", username, time_zone FROM tg_users WHERE \"ID\"=".$activationsRecord['user_id'];
            $tgUsersRecords = pg_query($query);
            $tgUsersRecords = pg_fetch_assoc($tgUsersRecords);
            $items[$i]['id'] = $activationsRecord['ID'];
            $items[$i]['date'] = $activationsRecord['date'].$tgUsersRecords['time_zone'];
            $user = array();
            $user['id'] = $tgUsersRecords['ID'];
            $user['usernane'] = $tgUsersRecords['username'];
            $items[$i]['user'] = $user;
            $i++;
        }

        $result = array("result" => "RESULT_SUCCESS");
        $result["payload"] = array();
        $result["payload"]["promo"] = $promo;
        $result["payload"]["items"] = $items;
        $result["payload"]["currencies_total"] = "";
        $result["payload"]["total"] = $i;
        $result["payload"]["count"] = count($items);
        header("Content-type:application/json");
        exit(format_array($result));
    }
}