<?php
$AMIUser = test;
$AMIPass = test;
query asterisk AMI(s) to pull list of active calls
$socket = fsockopen("127.0.0.1","5038", $errno, $errstr, $timeout);
fputs($socket, "Action: Login\r\n");
fputs($socket, "UserName: $AMIUser\r\n");
fputs($socket, "Secret: $AMIPass\r\n\r\n");
fputs($socket, "Action: Command\r\n\r\n");
fputs($socket, "Action: core show channels\r\n\r\n");
while (!feof($socket)) {
  $activeCalls .= fread($socket, 8192);
}
fclose($socket);

print_r($ActiveCalls);
?>