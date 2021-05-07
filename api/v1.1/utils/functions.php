<?php
    define("EMAIL_PATTERN", '#[-_0-9a-zа-я\.]{3,}@[-_0-9a-zа-я\.]{2,}\.[0-9a-zа-я]{2,}#isU', true);
    define("PHONE_PATTERN", "#^\+[0-9]{11, 14}$#isU", true);
    define("DATETIME_PATTERN", "#[0-9]{4}\.[0-9]{2}\.[0-9]{2}\|[0-9]{2}:[0-9]{2}:[0-9]{2}#isU", true);
    define("IP_PATTERN", "#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#isU", true);
	if (! defined('WORK')) die('HIERARСHY_ERROR');
	/**************************************************************************************************
	***********************************Различные функции***********************************************
	**************************************************************************************************/
	//Генерация паролей и токенов
	function generate_password($symbols_count = 10, $small_letters = true, $big_letters = true, $numbers = true, $symbols = true){
		$smb = '';
		if ($small_letters){
			$smb .= 'abcdefghijklmnopqrstuvwxyz';
		}
		if ($big_letters){
			$smb .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		}
		if ($numbers){
			$smb .= '0123456789';
		}
		if ($symbols){
			$smb .= '!|\\/?!*^&:;><%$#@~-_+=';
		}
		$smb = str_split($smb);
		$res = '';
		for ($i = 0; $i < $symbols_count; $i++){
			$rnd = rand(0, count($smb) - 1);
			$res .= $smb[$rnd];
		}
		return $res;
	}
	//Проверка, соответствует ли запись в таблице указанному филиалу
	function check_filial_id($tb_name, $record_id, $filial_id){
		$query = "SELECT * FROM \"$tb_name\" WHERE \"ID\" = $record_id";
		$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
		$record = pg_fetch_array($result);
		return $record["filial_id"] = $filial_id;
	}
	//нормализуем дату/время
	function normalize_datetime($datetime){
	    $datetime = str_replace('|', ' ', $datetime);
	    $datetime = str_replace('.', '-', $datetime);
	    return $datetime;
	}
	function check_ip($ip){
	    if (!preg_match(IP_PATTERN, $ip)){
	        return false;
	    }
	    else{
	        $arr = explode('.', $ip);
	        $result = true;
	        foreach ($arr as $value){
	            if ($value > 255){
	                $result = false;
	                break;
	            }
	        }
	        return $result;
	    }
	}
  //Проверяем корректность денежной суммы
  function check_money($sum){
    $pattern = "#^([0-9]+)|([0-9]+\.[0-9]{1, 2})|([0-9]+,[0-9]{1, 2})$#isU";
    if (!preg_match($pattern, $sum)){
      return false;
    }
    else{
      return str_replace(',', '.', $sum);
    }
    /*if (strpos($sum, '.')){
      $items = explode('.', $sum);
      $pattern = "#^[0-9]{1, 8}$#is";
      if (preg_match($pattern, $items[0])){
        $pattern = "#^[0-9]{1, 2}$#is";
        if (preg_match($pattern, $items[1])){
          return $items[0].".".$items[1];
        }
        else {
          return false;
        }
      }
      else {
        return false;
      }
    }
    elseif (strpos($sum, ',')){
      $items = explode(',', $sum);
      $pattern = "#^[0-9]{1, 8}$#is";
      if (preg_match($pattern, $items[0])){
        $pattern = "#^[0-9]{1, 2}$#is";
        if (preg_match($pattern, $items[1])){
          return $items[0].".".$items[1];
        }
        else {
          return false;
        }
      }
      else {
        return false;
      }
    }
    elseif (intval($sum) > 0){
      return $sum;
    }
    else {
      return false;
    }*/
  }
  function send_sms($phone, $text){
    //Проверяем наличие рабочего токена авторизации
    global $queries;
    $query = "SELECT * FROM tg_sms_tokens WHERE recieved > CURRENT_TIMESTAMP - INTERVAL '24 HOUR'";
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    if (pg_num_rows($result) > 0){
      $row = pg_fetch_array($result);
      $token = $row["token"];
    }
    else {
      //Получаем токен авторизации
      $url = 'https://online.sigmasms.ru/api/login';
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, '{"username":"truegamers","password":"ezy1SP6d"}');
      curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
      $output = curl_exec($ch);
      if (!$output){
        die(format_result('ERROR_SENDING_SMS'));
      }
      $out = json_decode($output, true);
      $token = $out["token"];
      if (empty($token)){
        die(format_result('ERROR_SENDING_SMS'));
      }
      $query = "INSERT INTO tg_sms_tokens(recieved, token) VALUES(CURRENT_TIMESTAMP, '$token')";
      $queries[] = $query;
      pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    }
  }
  function send_email($email, $from, $subject, $content){

    $headers  = "Content-type: text/html; charset=utf-8 \r\n";
    $headers .= "From: $from\r\n";
    $headers .= "Reply-to: $from\r\n";
    $headers .= "X-Mailer: PHP/".phpversion()."\r\n\r\n";

    mail($email, $subject, $content, $headers);
  }

  //Переводим тип money во float

  function money_to_float($value){
    $value = str_replace(array("$", ","), "", $value);
    return (float)$value;
  }

  function normalize_array($arr, $exclude_fields = array(), $timezone = '+03:00'){
    $res = array();
    $float_pattern = "#^[0-9]+(\.)[0-9]+$#is";
    $money_pattern = "#^[\$0-9\,]+\.[0-9]{0,2}$#isU";
    $datetime_pattern = "#^([0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})#isU";
    foreach ($arr as $key=>$value){
      if (is_array($value)){
        //$arr[$key] = normalize_array($value);
        continue;
      }
      if (is_int($key)){
        continue;
      }
      if (in_array($key, $exclude_fields, true)){
        continue;
      }
      if (empty($value)){
        $res[$key] = null;
      }
      if (is_numeric($value) && !is_float($value)){
        //целочисленное значение
        $res[$key] = intval($value);
      }
      elseif (is_float($value)){
        //число с плавающей точкой
        $res[$key] = (float)$value;
      }
      elseif (preg_match($money_pattern, $value)){
        //число с плавающей точкой
        $res[$key] = money_to_float($value);
      }
      elseif (preg_match($datetime_pattern, $value, $out)){
        //число с плавающей точкой
        $res[$key] = $out[1].$timezone;
        $res[$key] = str_replace(' ', 'T', $res[$key]);
      }
      else {
        $res[$key] = trim($value);
      }
    }
    return $res;
  }

  function get_user_profile($user){
    if ($user["deleted"] == 1){
      $user["active"] = "deleted";
    }
    elseif ($user["banned"] == 1){
      $user["active"] = "banned";
    }
    else{
      $user["active"] = "active";
    }
    //Получаем филиалы, в которых зарегистрировн пользователь
    $query = "SELECT * FROM tg_users_to_branches WHERE user_id = ".$user["ID"];
    $queries[] = $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $user["branches"] = array();
    while ($branch = pg_fetch_array($result)){
      $query = "SELECT * FROM tg_branches WHERE \"ID\" = ".$branch["branch_id"];
      $queries[] = $query;
      $r = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      $br = pg_fetch_array($r);
      pg_free_result($r);
      $user["branches"][] = array("id"=>intval($branch["branch_id"]), "name"=>$br["name"]);
    }
    //Суперхост
    if ($user["is_superhost"] == 1){
  		$user["is_superhost"] = true;
  	}
  	else{
  		$user["is_superhost"] = false;
  	}
    //ранг пользователями
    $user["rang_id"] = $user["rang"];
  	$query = "SELECT * FROM tg_rang WHERE branch_id = ".$user["branches"][0]["id"]." AND num = ".$user["rang"];
  	$queries[] = $query;
  	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  	$rang = pg_fetch_array($result);
  	$user["rang"] = array("id"=>intval($user["rang"]), "name"=>trim($rang["name"]), "num"=>intval($rang["num"]), "duration"=>intval($rang["duration"]));
  	//Вычисляем следующий ранг
  	$query = "SELECT * FROM tg_rang WHERE branch_id = ".$user["branches"][0]["id"]." AND num > ".$user["rang"]["id"]." ORDER BY num LIMIT 1";
  	$queries[] = $query;
  	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  	$next_rang = pg_fetch_array($result);
  	$required_duration = $next_rang["duration"] - $rang["duration"];
  	$current_duration = $user["game_time"] - $rang["duration"];
    if ($required_duration > 0){
  			$rang_progress = round($current_duration / $required_duration * 100);
  	}
  	else {
  		$rang_progress = 0;
  	}
    $user["rang"]["progress"] = intval($rang_progress);
  	$user["rang"]["next_id"] = intval($next_rang["ID"]);
  	$user["rang"]["next_num"] = intval($next_rang["num"]);
  	$user["rang"]["next_name"] = $next_rang["name"];
  	$user["rang"]["next_duration"] = intval($next_rang["duration"]);
    //получаем любимый тариф пользователя
    $query = "SELECT  COUNT(*) AS cnt, packet_id FROM tg_reservation WHERE user_id = {$user['ID']} GROUP BY packet_id ORDER BY cnt DESC LIMIT 1";
    $queries[] = $query;
  	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $tarif = pg_fetch_array($result);
    $queries[] = $tarif;
    if ($tarif["packet_id"] > 0){
      $query = "SELECT * FROM tg_prices WHERE \"ID\" = ".$tarif["packet_id"];
      $queries[] = $query;
    	$result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
      $tarif = pg_fetch_array($result);
      $queries[] = $tarif;
    }
    else {
      $tarif["name"] = "Поминутный";
    }
    $favorite_tarif = trim($tarif["name"]);
    $user["favorite_tarif"] = array("id"=>intval($tarif["ID"]), "name"=>trim($tarif["name"]));
    return $user;
  }

  function branch_id_by_ip($ip){
    global $queries;
    if (empty($ip)) $ip = $_SERVER["REMOTE_ADDR"];
    $query = "SELECT * FROM tg_branch_ip LEFT JOIN tg_branches WHERE \"ip\" = '$ip'::inet";
    $queries[] =  $query;
    $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
    $branch = pg_fetch_array($result);
    return $branch["branch_id"];
  }
?>
