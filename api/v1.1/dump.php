<?php
#echo $_SERVER["REMOTE_ADDR"];
  define("WORK", true, true);
  require_once("utils/requires.php");
  $id = intval($_GET["id"]);
  if ($id < 1){
    $query = "SELECT * FROM tg_request ORDER BY \"time\" DESC LIMIT 1";
  }
  else {
    $query = "SELECT * FROM tg_request WHERE \"ID\" = $id";
  }
  $result = pg_query($query) or die("Ошибка обращения к БД. Строка: ".__LINE__);
  $row = pg_fetch_array($result);

  header("Content-type: text/html; Charset=Utf-8");
?>
<!DOCTYPE html>
<html lang="ru" dir="ltr">
  <head>
    <meta http-equiv="Content-type" content="text/html; Charset=utf-8">
    <title>Дамп запросов к API</title>
    <script type="text/javascript" src="https://yastatic.net/jquery/3.3.1/jquery.min.js"></script>
    <script type="text/javascript">
      jQuery(document).ready(function(){

      });
    </script>
    <style type="text/css">
      a{
        color: blue;
        text-decoration: none;
      }
      a:hover{
        color: red;
        text-decoration: underline;
      }
      blockquote{
        border-left: 3px #a4a4a4 solid;
      }
      blockquote pre{
        margin-left: 20px;
      }
      blockquote textarea{
        margin-left: 20px;
        width: 100%;
        height: 350px;
        border: 0;
      }
    </style>
  </head>
  <body>
    <h1>Запрос № <?php echo $row["ID"]; ?> от <?php echo $row["time"]; ?></h1>
    <hr />
    <ul>
      <li>URI: <?php echo urldecode($row["uri"]); ?></li>
      <li>IP-адрес: <?php echo $row["ip"]; ?></li>
      <li>ID сессии: <?php echo $row["session_id"]; ?></li>
      <li>Выполнен: <?php echo $row["end_time"]; ?></li>
    </ul>
    <hr />
    <h2>Ответ сервера:</h2>
    <blockquote>
        <textarea><?php echo trim($row["response"]); ?></textarea>
    </blockquote>
    <h2>Запросы:</h2>
    <blockquote>
        <textarea wrap="off"><?php print_r(json_decode($row["queries"])); ?></textarea>
    </blockquote>
    <hr />
    <h2>Суперглобальные массивы:</h2>
    <ul>
      <li>
        <h3>$_POST:</h3>
        <blockquote>
          <pre>
            <?php print_r(json_decode(trim($row["post_data"]))); ?>
          </pre>
        </blockquote>
      </li>
      <li>
        <h3>$_REQUEST:</h3>
        <blockquote>
          <pre>
            <?php print_r(json_decode(trim($row["request_data"]))); ?>
          </pre>
        </blockquote>
      </li>
      <li>
        <h3>$_SERVER:</h3>
        <blockquote>
          <pre>
            <?php print_r(json_decode(trim($row["server_data"]))); ?>
          </pre>
        </blockquote>
      </li>
      <li>
        <h3>$_SESSION:</h3>
        <blockquote>
          <pre>
            <?php print_r(json_decode(trim($row["session_data"]))); ?>
          </pre>
        </blockquote>
      </li>
    </ul>
  </body>
</html>
