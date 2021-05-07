<?php
  $phone = $_REQUEST["phone"];
  if (empty($phone)){
    die(format_result("ERROR_EMPTY_PHONE"));
  }
  $msg = $_REQUEST["msg"];
  if (empty($msg)){
    die(format_result("ERROR_EMPTY_MSG"));
  }
  send_sms($phone, $msg);
?>
