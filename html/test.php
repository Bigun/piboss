<?php 
/*
PiBoss v0.00

Coded by Adam Marthaler

TODO:

*/
$host = "localhost";
$port = 10000;

//set_time_limit(5);

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 500000));
$result = socket_connect($socket, $host, $port);

$msg = "message";
$len = strlen($msg);

//socket_sendto($f, $msg, $len, 0, $host, $port);
socket_write($socket, $msg, $len);

$input = socket_read($socket, 1024) or die('Could not read server response\n');
$input = trim($input);

socket_close($socket);

?>
<html>
	<head>
		<title>PiBoss - v0.00</title>
	</head>
	<body>
		<?php echo($input); ?>
	</body>
</html>

