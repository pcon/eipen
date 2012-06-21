<?php
	require_once ("../eipen_config.php");
	require_once ("../eipen_userfunctions.php");
	require_once ("../eipen_functions.php");
	require_once ("eipen_admin.php");

	if (isloggedin() != 0 || is_admin() != 0) {
		$_SESSION['from'] = $_SERVER['PHP_SELF'];
		gotopage("../login.php");
		return;
	}

	updateHB();

	//TODO: Validate $_GET['coursename']

	$type = "overview";

	if(isset($_GET['type'])) {
		$type = $_GET['type'];
	}

	if(strcmp($type, 'usage') == 0 && isset($_GET['coursename'])) {
		$coursename = $_GET['coursename'];
		print "<center>";
		print "$coursename<br />";
		print "<img src=\"stats/courses.php?type=$type&coursename=$coursename\" />";
		print "</center>";
	} elseif (strcmp($type, 'usage') == 0) {
		global $username;
		global $password;
		global $database;
		global $dbhost;

		mysql_connect($dbhost,$username,$password);
		@mysql_select_db($database) or die( "Unable to select database");

		$query = "SELECT name, coursedesc FROM courses WHERE courseid > 0 ORDER BY name";
		$result = mysql_query($query);

		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		while ($row = mysql_fetch_array($result)) {
			print '<a href="stats.php?stat=courses&type='.$type.'&coursename='.$row['name'].'">'.$row['coursedesc'].'</a><br />'."\n";
		}

	} else {
		print "<center><img src=\"stats/courses.php?type=$type\" /></center>\n";
	}
?>
