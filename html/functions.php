<?php
function piboss_cmd($cmd) {
	include("./config.php");

	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 500000));
        $result = socket_connect($socket, $host, $port);

	$msg = $cmd;
	$len = strlen($msg);

	//socket_sendto($f, $msg, $len, 0, $host, $port);
	socket_write($socket, $msg, $len) or die ('Could not send data\n');

	$input = socket_read($socket, 1024) or die ('Could not read server response\n');
	$input = trim($input);

	socket_close($socket);

	if ($input) {
		return($input);
	} else {
		return($failresponse);
	}
}

function check_default_temps($session_var) {
	if (!$session_var) {
		//No temp set, default to 0
		return(0);
	} else {
		return($session_var);
	}
}

function ver_check($getver) {
	include("./config.php");
	$response = "";

	if ($getver == $version) {
		$response = "pass";
	} else {
		$response = "fail";
	}

	return($response);
}

function split_temp($tempstring) {
	$tempstring = str_replace(array("[", "]", " "), "", $tempstring);
	$temparray = explode(",", $tempstring);
	return($temparray);
}

function display_temp($temp) {
	include("./config.php");
	if ($temp < 500) {
		echo(number_format($temp, 2, '.', ',') . " " . $temppref);
	} else {
		echo ("D/C");
	}
}

function populate_temp_menu($current_value) {
	include("./config.php");
	//Set an option for 0/off
	$selected = "";
	if ($current_value == 0) {
		$selected = " selected";
	}
	echo "<option value=0".$selected.">0".$temppref."</label>";
	//Display other options for temps
	for ($x = $temp_low_end; $x <= $temp_high_end; $x += $temp_increment) {
		$selected = "";
		if ($current_value == $x) {
			$selected = " selected";
		}
		echo "<option value=".$x.$selected.">".$x.$temppref."</label>";
	}
}
?>
