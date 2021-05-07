<?php
  session_start();
  $_SESSION["username"] = "";
  $_SESSION["password"] = "";
  $output = array("result"=>"RESULT_SUCCESS");
  setcookie("PHPSESSID", "", time() + 1);
  exit(format_array($output));
?>
