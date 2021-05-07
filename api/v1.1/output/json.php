<?php
	if (! defined('WORK')) die('HIERARСHY_ERROR');
	$queries = array();
	//IP-адрес отладчика
	$file = file($_SERVER["DOCUMENT_ROOT"].'/api/v1.1/output/ip.txt');
	$debugger_ip = $file[0];
	$debugger_time = $file[1];
	//echo $debugger_time."|".date("Y-m-d H:i:s");
	/**************************************************************************************************
	****************Форматирование данных в формате JSON***********************************************
	**************************************************************************************************/
	//Вывод одиночного результата
	function format_result($data){
		$file = file($_SERVER["DOCUMENT_ROOT"].'/api/v1.1/output/ip.txt');
		$debugger_ip = $file[0];
		$debugger_time = $file[1];
		global $request_insert_id, $queries;
		if ($_SERVER["HTTP_X_CLIENT"] == "ClientShell"){
			$resp = json_encode(array($data));
			$sess = json_encode($_SESSION);
			$sess_id = session_id();
			$q = pg_escape_string(json_encode($queries));
			$query = "UPDATE tg_request SET response = '$resp', session_data = '$sess', session_id = '$sess_id', ";
			$query .= "end_time = CURRENT_TIMESTAMP, queries = '$q' WHERE \"ID\" = $request_insert_id";
			@pg_query($query);
			$params = "?request_insert_id=$request_insert_id&response_length=".strlen($resp)."&queries_length=".strlen($q);
			send_data("$params");
			header("Content-type:application/json");
			return json_encode(array($data));
		}
		else{
			$resp = json_encode(array($data));
			$sess = json_encode($_SESSION);
			$sess_id = session_id();
			$q = pg_escape_string(json_encode($queries));
			$query = "UPDATE tg_request SET response = '$resp', session_data = '$sess', session_id = '$sess_id', ";
			$query .= "end_time = CURRENT_TIMESTAMP, queries = '$q' WHERE \"ID\" = $request_insert_id";
			@pg_query($query);
			$params = "?request_insert_id=$request_insert_id&response_length=".strlen($resp)."&queries_length=".strlen($q);
			send_data("$params");
			header("Content-type:application/json");
			return json_encode(array("result"=>$data));
		}
	}

	//Вывод массива
	function format_array($arr, $caption = "General"){
		global $request_insert_id, $queries;
		$data = array();
		$data = array_merge($data, $arr);
		$resp = json_encode($data);
		$sess = json_encode($_SESSION);
		$sess_id = session_id();
		$q = pg_escape_string(json_encode($queries));
		$query = "UPDATE tg_request SET response = '$resp', session_data = '$sess', session_id = '$sess_id', ";
		$query .= "end_time = CURRENT_TIMESTAMP, queries = '$q' WHERE \"ID\" = $request_insert_id";
		@pg_query($query);
		$params = "?request_insert_id=$request_insert_id&response_length=".strlen($resp)."&queries_length=".strlen($q);
		send_data("$params");
		header("Content-type:application/json");
		return json_encode($data);
	}

	//Форматирование таблицы (двумерного массива) для вывода
	function output_format_table($tbl){
		$file = file($_SERVER["DOCUMENT_ROOT"].'/api/v1.1/output/ip.txt');
		$debugger_ip = $file[0];
		$debugger_time = $file[1];
		global $request_insert_id, $queries;
		$res = array("count"=>count($tbl), "payload"=>array());
		//Перебираем переданную таблицу построчно
		foreach ($tbl as $key=>$value){
			$row = exclude_indexes($value);//Исключаем индексные элементы
			$res["payload"][] = $row;
		}
		$resp = json_encode($res);
		$sess = json_encode($_SESSION);
		$sess_id = session_id();
		$q = pg_escape_string(json_encode($queries));
		$query = "UPDATE tg_request SET response = '$resp', session_data = '$sess', session_id = '$sess_id', ";
		$query .= "end_time = CURRENT_TIMESTAMP, queries = '$q' WHERE \"ID\" = $request_insert_id";
		@pg_query($query);
		$params = "?request_insert_id=$request_insert_id&response_length=".strlen($resp)."&queries_length=".strlen($q);
		send_data("$params");
		header("Content-type:application/json");
		return json_encode($res);
	}

	//Вывод сообщение об ошибке запроса к БД
	function database_query_error($query, $file, $line){
		$file = file($_SERVER["DOCUMENT_ROOT"].'/api/v1.1/output/ip.txt');
		$debugger_ip = $file[0];
		$debugger_time = $file[1];
		global $request_insert_id, $queries;
		$msg = pg_last_error();
		$result = array('Result'=>'DATABASE_QUERY_ERROR', 'Error_message'=>$msg, 'Query'=>$query, 'File'=>$file, 'Line'=>$line);
		$data = array();
		$data = array_merge($data, $arr);
		$resp = json_encode($result);
		$sess = json_encode($_SESSION);
		$sess_id = session_id();
		$q = pg_escape_string(json_encode($queries));
		$query = "UPDATE tg_request SET response = '$resp', session_data = '$sess', session_id = '$sess_id', ";
		$query .= "end_time = CURRENT_TIMESTAMP, queries = '$q' WHERE \"ID\" = $request_insert_id";
		//echo $query;
		@pg_query($query);
		$params = "?request_insert_id=$request_insert_id&response_length=".strlen($resp)."&queries_length=".strlen($q);
		send_data("$params");
		header('content-type:application/json');
		return json_encode($result);
	}

	function send_data($url){
		//$url = 'https://truegamers.pro';
		//echo $url;
		$file = file($_SERVER["DOCUMENT_ROOT"].'/api/v1.1/output/ip.txt');
		$debugger_ip = trim($file[0]);
		$debugger_time = $file[1];
		if ((time() - strtotime($debugger_time)) < 5){
			//Инициализирует сеанс
	    $connection = curl_init();
	    //Устанавливаем адрес для подключения
	    curl_setopt($connection, CURLOPT_URL, "http://$debugger_ip:3115/$url");
			//echo "http://$debugger_ip:3115/$url";
	    //Указываем, что мы будем вызывать методом POST
	    //curl_setopt($connection, CURLOPT_POST, 1);
	    //Передаем параметры методом POST
	    //curl_setopt($connection, CURLOPT_POSTFIELDS, "id=1");
	    //Говорим, что нам необходим результат
	    curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
	    //Выполняем запрос с сохранением результата в переменную
			//echo $_SERVER["REMOTE_ADDR"] . $debugger_ip;
			if ($_SERVER["REMOTE_ADDR"] == trim($debugger_ip)){
				//echo curl_errno($connection);
				//echo "http://$debugger_ip:3115/$url";
			}
	    $rezult = curl_exec($connection);
				//
	    //Завершает сеанс
	    curl_close($connection);
		}
	}

