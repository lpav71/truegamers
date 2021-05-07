<?php
  define("WORK", true, true);
  require_once("utils/requires.php");
  $query = "SELECT DISTINCT(\"time\"::date) AS \"date\" FROM tg_request ORDER BY \"date\" DESC";
  $result = pg_query($query) or die("Ошибка обращения к БД. Строка: ".__LINE__);
  $dates = array();
  while ($date = pg_fetch_array($result)){
    $dates[] = $date["date"];
  }
  //Извлекаем записи для указанной даты
  if (!empty($_GET["date"])){
    $date = pg_escape_string($_GET["date"]);
  }
  else{
    $date =$dates[0];
  }
  $query = "SELECT \"time\"::time as tm, * FROM tg_request WHERE \"time\"::date = '$date' ORDER BY \"time\" DESC";
  $result = pg_query($query) or die("Ошибка обращения к БД. Строка: ".__LINE__);
  $times = array();
  while ($time = pg_fetch_array($result)){
    $uri = str_replace('/api/v1.1/', '', $time["uri"]);
    $uri = explode('?', $uri);
    $times[] = array("id"=>$time['ID'], "time"=>$time["tm"], "uri"=>$uri[0], "ip"=>$time["ip"]);
  }
  header("Content-type: text/html; Charset=Utf-8");
?>
<!DOCTYPE html>
<html lang="ru" dir="ltr">
  <head>
    <meta http-equiv="Content-type" content="text/html; Charset=utf-8">
    <title>Дамп запросов к API</title>
    <script type="text/javascript" src="https://yastatic.net/jquery/3.3.1/jquery.min.js"></script>
    <script type="text/javascript">
      $(document).ready(function(){
        $("#dates").bind("change", function(event){
          window.location.href = 'request_list.php?date=' + this.options[this.selectedIndex].value;
        });
        $("a").bind("click", function(event){
          $("a").css("text-decoration", "none").css("color", "blue");
          this.style.textDecoration = 'underline';
          this.style.color = '#000000';
        });
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
      #dates{
        width: 100%;
      }
    </style>
  </head>
  <body>
    <select name="dates" id="dates">
      <?php foreach ($dates as $value): ?>
        <option value="<?php echo $value; ?>"<?php if ($_GET["date"] == $value) echo " selected";?>><?php echo $value; ?></option>
      <?php endforeach; ?>
    </select>
    <ol>
      <?php foreach ($times as $value): ?>
        <li>
          <a href="dump.php?id=<?php echo $value["id"]; ?>" target="dump"><?php echo $value["time"]." (".$value["id"].")&nbsp;&ndash;&nbsp;".$value["uri"]; ?></a>&nbsp;|&nbsp;<?php echo $value["ip"];?>
        </li>
      <?php endforeach; ?>
    </ol>
  </body>
</html>
