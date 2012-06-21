<?php
	require_once ("../../eipen_config.php");
	require_once ("../../eipen_userfunctions.php");
	require_once ("../../eipen_functions.php");
	require_once ("../eipen_admin.php");

	require_once ("graph-lib.php");

	if (isloggedin() != 0 || is_admin() != 0) {
		$_SESSION['from'] = $_SERVER['PHP_SELF'];
		gotopage("../../login.php");
		return;
	}

	updateHB();

	$data = getPastSevenDays();

	global $username;
	global $password;
	global $database;
	global $dbhost;

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT date, times FROM stats_dates LIMIT 7";

	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	mysql_close();

	while ($row = mysql_fetch_array($result)) {
		for ($i=0; $i<sizeof($data); $i++) {
			if (strcmp($row['date'],$data[$i]['label']) == 0) {
				$data[$i]['data'] = $row['times'];
			}
		}		
	}

	drawGraph($data,'bar');
?>
