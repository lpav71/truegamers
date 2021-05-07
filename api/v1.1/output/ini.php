<?php
	if (! defined('WORK')) die('HIERARСHY_ERROR');
	/**************************************************************************************************
	************************Форматирование данных в формате INI-файла**********************************
	**************************************************************************************************/

	//Вывод одиночного результата
	function format_result($data){
		$result = <<<OUT_END
[General]
Result=$data
OUT_END;
		return $result;
	}

	//Вывод массива
	function format_array($arr, $caption = "General"){
		$result = "[$caption]\r\n";
		foreach($arr as $key=>$value){
			$result .= "$key=$value\r\n";
		}
		return $result;
	}

	//Форматирование таблицы (двумерного массива) для вывода
	function output_format_table($tbl){
		$res = "[General]\r\n";
		$res .= "Count=".count($tbl)."\r\n";
		//Перебираем переданную таблицу построчно
		foreach($tbl as $key=>$value){
			$caption = 'Item'.$key;
			$res .= "[$caption]\r\n";
			$value = exclude_indexes($value); //Исключаем индексные элементы
			//Перебираем строку таблицы по ячейкам
			foreach ($value as $k=>$val){
				$res .= "$k=$val\r\n";
			}
		}
		return $res;
	}

	//Вывод сообщение об ошибке запроса к БД
	function database_query_error($query, $file, $line){
		$msg = pg_last_error();
		$result = <<<OUT_END
[General]
Result=DATABASE_QUERY_ERROR
Error_message=$msg
Query=$query
File=$file
Line=$line
OUT_END;
		return $result;
	}
?>