<?php

$logFile = "/alidata1/www_v3/pay.canpoint.net/Application/Runtime/Logs/" . date('Y-m-d') . '.txt' . $ext;
//$msg = date ( '[Y-m-d H:i:s]' ) . '	' . $msg . '	{in file:' . $_SERVER ['REQUEST_URI'] . "}\r\n";
$msg = date('[Y-m-d H:i:s]') . "sdfsdfsdf\r\n";
file_put_contents($logFile, $msg, FILE_APPEND);
?>