<?php 
/*
PiBoss v0.00

Coded by Adam Marthaler

TODO:

*/
session_start();

//error_reporting(E_ALL ^ E_WARNING);
error_reporting(E_ALL);
include("./functions.php");
include("./config.php");

$vcheck = False;
$status = "";

//Check for temp changes
if (isset($_POST['set_pit_temp'])) {
	//Pit temp was set
	$input_pit_temp = $_POST['input_pit_temp'];
	//Send command to piboss
	piboss_cmd("setpit" . $temppref . "=" .$input_pit_temp);
	//Set session for pit temp
	$_SESSION['set_pit_temp'] = $input_pit_temp;
}

//Poll for what set temps are and set defaults if necessary
$_SESSION["set_pit_temp"] = check_default_temps($_SESSION["set_pit_temp"]);
$set_pit_temp = $_SESSION["set_pit_temp"];
$_SESSION["set_probe1_temp"] = check_default_temps($_SESSION["set_probe1_temp"]);
$set_probe1_temp = $_SESSION["set_probe1_temp"];
$_SESSION["set_probe2_temp"] = check_default_temps($_SESSION["set_probe2_temp"]);
$set_probe2_temp = $_SESSION["set_probe2_temp"];
$_SESSION["set_probe3_temp"] = check_default_temps($_SESSION["set_probe3_temp"]);
$set_probe3_temp = $_SESSION["set_probe3_temp"];

//Begin version check
$getver = piboss_cmd("vcheck");
$response = ver_check($getver);

if ($response == "pass") {
	$vcheck = True;
} elseif  ($response == "fail") {
	$status .= "Version mismatch!";
} else {
	$status .= $response;
}

if ($vcheck) {
	//Version check passed, begin polling for data
	$response = piboss_cmd("temp");
	$temparray = split_temp($response);
	$counter = 0;
	foreach ($temparray as $temp) {
		if ($counter == 0) {
			$pittemp = $temp;
		} else {
			$v = "temp" . $counter;
			$$v = $temp;
		}
		$counter += 1;
	}
}

//No errors, display active status
$status = "Idle";
?>
<html>
	<head>
		<title>PiBoss - v0.00</title>
	</head>
	<body>
		<h1>PiBoss - Status: <?php echo $status; ?></h1>
		<h2>Pit Temp: <?php display_temp($pittemp); ?></h2>
		<h2>Probe 1: <?php display_temp($temp1); ?></h2>
		<h2>Probe 2: <?php display_temp($temp2); ?></h2>
		<h2>Probe 3: <?php display_temp($temp3); ?></h2>
		<h2>Alarms:</h2>
		<ul>
			<li>No alarms set</li>
		</ul>
		<table>
			<form action="index.php" method="post">
			<tr>
				<td><label for="input_pit_temp">Pit Temp</label></td>
				<td><select id="input_pit_temp" name="input_pit_temp">
					<?php populate_temp_menu($set_pit_temp); ?>
				</select></td>
				<td><input type="submit" name="set_pit_temp" value="Set Pit Temp"></td>
			</tr>
			<tr>
				<td><label for="input_probe1_alarm">Probe 1 Alarm - Not Set</label></td>
				<td><select id="input_probe1_alarm" name="input_probe1_alarm">
					<?php populate_temp_menu($set_probe1_temp); ?>
				</select></td>
				<td><input type="submit" name="set_probe_1_alarm" value="Set Probe 1 Alarm"></td>
			</tr>
			<tr>
				<td><label for="input_probe2_alarm">Probe 2 Alarm - Not Set</label></td>
                	        <td><select id="input_probe2_alarm" name="input_probe2_alarm">
					<?php populate_temp_menu($set_probe2_temp); ?>
				</select></td>
				<td><input type="submit" name="set_probe_2_alarm" value="Set Probe 1 Alarm"></td>
			</tr>
			<tr>
				<td><label for="input_probe3_alarm">Probe 3 Alarm - Not Set</label></td>
                	        <td><select id="input_probe3_alarm" name="input_probe3_alarm">
					<?php populate_temp_menu($set_probe3_temp); ?>
				</select></td>
				<td><input type="submit" name="set_probe_3_alarm" value="Set Probe 3 Alarm"></td>
			</tr>
			</form>
		</table>
	</body>
</html>

