<?php
  header('Access-Control-Allow-Origin: '.$_SERVER["HTTP_ORIGIN"]);
  header('Access-Control-Allow-Headers: Origin,Content-Type,Accept,X-Requested-With');
  //Давим кэширование
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
  header("Last-Modified: " . gmdate("D, d M Y H:i:s")." GMT");
  header("Cache-Control: no-cache, must-revalidate");
  header("Cache-Control: post-check=0,pre-check=0", false);
  header("Cache-Control: max-age=0", false);
  header("Pragma: no-cache");
?><!DOCTYPE html>
<html lang="ru">
  <head>
    <title>Справка по API</title>
    <script type="text/javascript" src="https://yastatic.net/jquery/3.3.1/jquery.min.js"></script>
    <script type="text/javascript">
      $(document).ready(function(){

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
    </style>
  </head>
  <body>
<?php
$login = $_POST['login'];
$password = $_POST['password'];
if ($login == 'truegamers' && $password == '12801024qwE')
{
    $version = $_REQUEST["version"];
    if (empty($version)){
        $version = '1.1';
    }
    $cookie_options = array();
    $cookie_options["expires"] = time() + 86400 * 30 * 6;
    $cookie_options["path"] = '/';
    $cookie_options["domain"] = 'truegamers.pro';
    $cookie_options["secure"] = true;
    $cookie_options["httponly"] = false;
    $cookie_options["samesite"] = 'None';
    setcookie('version', $version, $cookie_options);
    $folder = $_SERVER["DOCUMENT_ROOT"]."/api/v$version/access_points";
    $files = glob($folder.'/*.txt');
    $output = array();
    foreach ($files as $key => $value) {
        $content = file_get_contents($value);
        $arr = explode('/', $value);
        $filename = $arr[count($arr) - 1];
        $pattern = "#(.*<title>)(.*)(<\/title>.*)#isU";
        preg_match($pattern, $content, $out);
        $title = $out[2];
        $filetime = date ("d-m-Y H:i:s.", filemtime($value));
        $output[] = array("link"=>"https://truegamers.pro/api/v$version/access_points/$filename", "title"=>$title, "filetime"=>$filetime);
    }

?>
    <ul>
      <?php foreach ($output as $key => $value): ?>
        <li>
          <a href="<?= $value["link"];?>"><?= $value["title"]." (".$value["filetime"].")";?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php }
else{
    print_r('Пара логин-пароль не совпадают');
}
?>
  </body>
</html>
