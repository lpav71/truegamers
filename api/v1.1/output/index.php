<?php
  $ip = $_SERVER["REMOTE_ADDR"];
  file_put_contents('output/ip.txt', $ip."\r\n".date("Y-m-d H:i:s"));
  echo $ip;
?>
