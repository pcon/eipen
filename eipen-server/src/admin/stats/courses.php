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

	$type = "";

	if (!isset($_GET['type'])) {
		$type = 'daily';
	} else {
		$type = $_GET['type'];
	}

	if (isset($_GET['coursename']) && strcmp($type, 'usage') == 0) {

		$coursename = $_GET['coursename'];

		$data = getPastSevenDays();

		global $username;
		global $password;
		global $database;
		global $dbhost;

		mysql_connect($dbhost,$username,$password);
		@mysql_select_db($database) or die( "Unable to select database");

		$query = "SELECT date, times FROM stats_courses_dates WHERE name = \"$coursename\" LIMIT 7";

		$result = mysql_query($query);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		while ($row = mysql_fetch_array($result)) {
			for ($i=0; $i<sizeof($data); $i++) {
				if (strcmp($row['date'],$data[$i]['label']) == 0) {
					$data[$i]['data'] = $row['times'];
				}
			}
		}

		mysql_close();

		drawGraph($data,'bar');		
	} elseif (strcmp($type, 'overview') == 0) {

		$data = getPastSevenDays();

		global $username;
		global $password;
		global $database;
		global $dbhost;

		mysql_connect($dbhost,$username,$password);
		@mysql_select_db($database) or die( "Unable to select database");

		$query = "SELECT date, SUM(times) AS times FROM stats_courses_dates GROUP BY date ORDER BY date DESC LIMIT 7";

		$result = mysql_query($query);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		while ($row = mysql_fetch_array($result)) {
			for ($i=0; $i<sizeof($data); $i++) {
				if (strcmp($row['date'],$data[$i]['label']) == 0) {
					$data[$i]['data'] = $row['times'];
				}
			}
		}

		mysql_close();

		drawGraph($data,'bar');		
	} elseif (strcmp($type, 'daily') == 0) {
		global $username;
		global $password;
		global $database;
		global $dbhost;

		mysql_connect($dbhost,$username,$password);
		@mysql_select_db($database) or die( "Unable to select database");

		$query = "SELECT dayName, times FROM stats_courses_byday";
		
		$result = mysql_query($query);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		$data = array();

		while ($row = mysql_fetch_array($result)) {
			$day = array('label'=>$row['dayName'], 'data'=>$row['times']);
			$data[]=$day;
		}

		mysql_close();

		drawGraph($data, 'bar');
	} elseif (strcmp($type, 'time') == 0) {
		global $username;
		global $password;
		global $database;
		global $dbhost;

		error_log("COURSENAME: $coursename");

		mysql_connect($dbhost,$username,$password);
		@mysql_select_db($database) or die( "Unable to select database");

		$query = "SELECT hour, times FROM stats_courses_byhour";
		
		$result = mysql_query($query);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		$data = array();
		for ($i=0; $i<24; $i++) {
			$data[]=array('label'=>$i, 'data'=>0);
		}

		while ($row = mysql_fetch_array($result)) {
			for ($i=0; $i<24; $i++) {
				if ($data[$i]['label'] == $row['hour']) {
					$data[$i]['data'] = $row['times'];
				}
			}
		}

		mysql_close();

		drawGraph($data, 'bar');
	}
?>
