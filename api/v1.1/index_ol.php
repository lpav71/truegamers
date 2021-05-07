<?php
ob_start();
//ini_set("display_errors", 1);
//Разрешаем кроссдоменный JavaScript
header('Access-Control-Allow-Origin: ' . $_SERVER["HTTP_ORIGIN"]);
header('Access-Control-Allow-Headers: Origin,Content-Type,Accept,X-Requested-With');
//Давим кэширование
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Cache-Control: post-check=0,pre-check=0", false);
header("Cache-Control: max-age=0", false);
header("Pragma: no-cache");
//Начинаем работу
define('WORK', true, true);
require_once('utils/requires.php');
//echo (intval($request_insert_id));
$route = str_replace('api/v1.1/', '', trim($_SERVER["REDIRECT_URL"], '/'));
$arr = explode('/', $route);
$arr[0] = str_replace(array($_SERVER["QUERY_STRING"], "?"), "", $arr[0]);
if (strpos($arr[0], "?") > 0) {
    $buf = explode("?", $arr[0]);
    $arr[0] = $buf[0];
}
if (strpos($arr[1], "?") > 0) {
    $buf = explode("?", $arr[1]);
    $arr[1] = $buf[0];
}
if ($arr[0] == "files") {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . $arr[count($arr) - 1]);
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($route));
    echo file_get_contents($route);
    exit();
}
//var_dump($arr);
if ($arr[0] == "debug.html") {
    header("Content-type: text/html; Charset=Utf-8");
    exit(file_get_contents('debug.html'));
}
if ($arr[0] == "request_list.php") {
    require_once("request_list.php");
    exit();
}
if ($arr[0] == "dump.php") {
    require_once("dump.php");
    exit();
}
if ($arr[0] == "output" && $arr[1] == 'index.php') {
    require_once("output/index.php");
    exit();
}
if ($arr[0] == "access_points") {
    header("Content-type: text/html; Charset=Utf-8");
    exit(file_get_contents(urldecode($arr[0] . "/" . $arr[1])));
}
//Пишем в базу данных информацию о запросе
$query = "INSERT INTO tg_request (\"time\", uri, post_data, server_data, request_data, ip, response, session_data, session_id, end_time) ";
$query .= "VALUES(CURRENT_TIMESTAMP, '" . $_SERVER["REQUEST_URI"] . "', '" . json_encode($_POST) . "', '" . json_encode($_SERVER) . "', '";
$query .= json_encode($_REQUEST) . "', '" . $_SERVER["REMOTE_ADDR"] . "', '', '', '', CURRENT_TIMESTAMP) RETURNING \"ID\" AS \"id\"";
$r = @pg_query($query);
$queries = array();
if (@pg_num_rows($r) > 0) {
    $row = pg_fetch_array($r);
    $request_insert_id = $row["id"];
}
if ($arr[0] == 'login') {
    //Аутентификация пользователя
    header('Access-Control-Allow-Credentials: true');
    require_once('users/auth.php');
} elseif ($arr[0] == "users" && $arr[1] == "register") {
    require_once('users/register.php');
} elseif ($arr[0] == "users" && $arr[1] == "admin_auth") {//Авторизация администратора клуба и отправка файла коннекта к БД
    require_once('users/admin_auth.php');
} elseif ($arr[0] == "branch" && $arr[1] == "admins") { //Список админов клуба для админской шелки
    require_once('branch/admins.php');
}
if ($arr[0] == 'branch' && $arr[1] == 'get_id_by_ip') {//Получение ID клуба по IP
    require_once('branch/get_id_by_ip.php');
}
if ($arr[0] == 'branch' && $arr[1] == 'occupancy') {//Получение ID клуба по IP
    require_once('branch/club_occupancy.php');
}
if ($arr[0] == 'computer' && $arr[1] == 'add_mac' && $_SERVER["HTTP_X_CLIENT"] == 'ClientShell') {//Получение ID клуба по IP
    require_once('computers/add_mac.php');
}
if ($arr[0] == 'computer' && $arr[1] == 'list_by_mac') {//Получение информации о компе по MAC
    require_once('computers/list_by_mac.php');
}
if ($arr[0] == 'computer' && $arr[1] == 'update') {//Добавление компьютера
    require_once('computers/update.php');
}
if ($arr[0] == 'software' && $arr[1] == 'hash') {
    require_once('software/hash.php');
}
if ($arr[0] == 'software' && $arr[1] == 'soft_param') {
    require_once('software/soft_param.php');
}
if ($arr[0] == 'reservations' && empty($arr[1])/* && (strpos($_SERVER["HTTP_USER_AGENT"], 'TrueGamers Admin Program') > 0 || $_SERVER["HTTP_X_CLIENT"] == 'AdminShell')*/) {
    require_once("reservation/club_reservations.php");
}
if ($arr[0] == "store" && $arr[1] == "products") {
    require_once("store/goods.php");
}
if ($arr[0] == "reservations" && $arr[1] == "get_busy_computers") {
    //Список компьютеров, занятых в указанный интервал времени
    require_once("reservation/get_busy_computers.php");
}
if ($arr[0] == "promo" && $arr[1] == "check") {
    //Проверка промокода
    require_once("promo/check.php");
}
if ($arr[0] == "users" && $arr[1] == "transfer") {
    //Проверка промокода
    require_once("users/transfer.php");
}
if ($arr[0] == "users" && $arr[1] == "calculate_rang") {
    //Проверка промокода
    require_once("users/calc_rang.php");
}
if ($arr[0] == "users" && $arr[1] == "format_phone") {
    require_once("users/format_phone.php");
} else {
    //Проверяем, авторизован ли пользователь
    header('Access-Control-Allow-Credentials: true');
    require_once('users/authorise.php');

    //Выводим профиль текущего пользователя

    if ($arr[0] == 'profile') {
        require_once('users/profile.php');
    }

    //Логаут

    if ($arr[0] == 'logout') {
        require_once('users/logout.php');
    }

    //Дашборд

    if ($arr[0] == 'dashboard') {
        //Выводим информацию по дашборду
        require_once('dashbrd/get_data.php');
    }

    if ($arr[0] == 'computers') {
        //Выводим список компьютеров для дашборда
        require_once('dashbrd/computers_list.php');
    }

    //Аналитика

    if ($arr[0] == 'reports') {

        if ($arr[1] == "general") {
            //Общие показатели аналитики
            require_once('analytics/general.php');
        }

        if ($arr[1] == "clients") {
            //Общие показатели аналитики
            require_once('analytics/clients.php');
        }

        if ($arr[1] == "promocodes") {
            //Аналитика по промокодам
            require_once('analytics/promo.php');
        }

        if ($arr[1] == "promohistory") {
            //Аналитика по промокодам
            require_once('analytics/promo_history.php');
        }

        if ($arr[1] == "tariffs") {
            //Аналитика по промокодам
            require_once('analytics/tariffs.php');
        }

        if ($arr[1] == "occupancy_room_rate") {
            //Аналитика по промокодам
            require_once('analytics/occupancy_room_rate.php');
        }

        if ($arr[1] == "occupancy_device_rate") {
            //Аналитика по промокодам
            require_once('analytics/occupancy_device_rate.php');
        }

        if ($arr[1] == "sales_rate") {
            //Аналитика по промокодам
            require_once('analytics/sales_rate.php');
        }

        if ($arr[1] == "staff_members") {
            //Аналитика по промокодам
            require_once('analytics/staff_members.php');
        }

        if ($arr[1] == "bar") {
            //Аналитика по промокодам
            require_once('analytics/bar.php');
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    //Клиентская шелка
    ///////////////////////////////////////////////////////////////////////////////////////////

    //Тарифы

    if ($arr[0] == 'prices') {

        //Список тарифов для конкретного компа (07.01.2021)
        if ($arr[1] == 'list-for-category') {
            require_once('prices/list-for-category.php');
        }

    }

    //Игры

    if ($arr[0] == 'games') {

        //Список игр для конкретного компа (10.01.2021)
        if ($arr[1] == 'list') {
            require_once('games/list.php');
        }

        //Статистика по играм
        if ($arr[1] == "statistics") {

            //Добавление / изменение записи в статистике
            if ($arr[2] == "write") {
                require_once('games/statistics_write.php');
            }


        }

    }

    //Магазин

    if ($arr[0] == "store") {

        //Список товаров в магазине
        if ($arr[1] == "products") {
            require_once("store/goods.php");
        }
    }

    //Пользователи

    if ($arr[0] == 'users' || $arr[0] == 'clients') {

        if (isset($arr[1])) {
            //Передан ID пользователя
            if (empty($arr[2]) && is_numeric($arr[2])) {
                //Профиль указанного пользователя
                require_once('users/client_profile.php');
            } elseif ($arr[2] == "password") {
                //Редактирование пароля пользователя
                require_once('users/password.php');
            } elseif ($arr[2] == "edit") {
                //Редактирование пароля пользователя
                require_once('users/edit.php');
            } elseif ($arr[2] == "payment") {
                //Редактирование пароля пользователя
                require_once('users/update_balance.php');
            }
        } elseif (empty($arr[1]) && $arr[0] == "clients") {
            require_once('users/list.php');
        }

        //Выводим баланс пользователя
        if ($arr[1] == 'balance') {
            require_once('users/balance.php');
        }

        //Уменьшение баланса или бонусов конкретного пользователя
        if ($arr[1] == 'decrement-balance') {
            require_once('users/decrement_balance.php');
        }

    }

    //визиты пользователей

    if ($arr[0] == 'visits') {
        require_once('users/visits.php');
    }

    //Переводы средств между пользователями

    if ($arr[0] == 'transfers') {
        require_once('users/transfers.php');
    }

    //финансовые отчеты для CRM

    if ($arr[0] == 'payments') {
        require_once('users/payments.php');
    }

    //бонусы для CRM

    if ($arr[0] == 'bonuses') {
        require_once('users/bonuses.php');
    }


    //Резервации

    if ($arr[0] == 'reservations') {

        //Текущая и будущая резервации текущего и других пользователей для конкретного компьютера

        if ($arr[1] == 'comp_reservations') {
            require_once("reservation/comp_reservations.php");
        }

        //Создание резервации

        if ($arr[1] == "add") {
            require_once("reservation/add.php");
        }

        //Создание резервации с фиксированной длительностью

        if ($arr[1] == "add_fixed") {
            require_once("reservation/add_fixed.php");
        }
    }

    //Ранги

    if ($arr[0] == 'rang') {

        //Список рангов

        if (intval($arr[1]) < 1) {
            require_once("rang/list.php");
        }

    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    //Админская шелка
    ///////////////////////////////////////////////////////////////////////////////////////////

    //Филиалы

    if ($arr[0] == "branch") {

        //Загрузка клуба
        if ($arr[1] == "occupancy") {
            require_once('branch/club_occupancy.php');
        }

    }

    if ($arr[0] == "utils") {

        //Загрузка клуба
        if ($arr[1] == "send_sms") {
            require_once('service/send_sms.php');
        }

    }

}
ob_end_flush();
?>
