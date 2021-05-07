<?php
  $db = mysqli_connect("localhost", "web_bunker10_usr", "Q3Ar1a1w2oVt9IC8");
  if (!$db){
    die("MySQL connection error");
  }
  if (!mysqli_select_db($db, "web_bunker101_db")){
    die("MySQL DB connection error");
  }
?>
