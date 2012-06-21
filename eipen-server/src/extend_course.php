<?php
	require_once("eipen_config.php");
     require_once("eipen_functions.php");
     require_once("eipen_userfunctions.php");

	$extend_time = 20 * 60;

     if (isloggedin() != 0) {
          $_SESSION['from'] = $_SERVER['PHP_SELF'];
          gotopage('login.php');
          return;
     }

     if (isDaemonRunning() != 0) {
          setMessage("The daemon is not running.  Please contact the system administrator", "bad");
		gotopage('list.php');
		return;
     }

	$sessionid = (int)($_GET['id']);

	if ($sessionid <= 0) {
		setMessage("ExC1: Invalid session");
		gotopage('list.php');
		return;
	}

	global $username;
	global $password;
	global $database;
	global $dbhost;

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT sessionid FROM running_courses WHERE userid = '".get_email()."' AND sessionid = $sessionid AND extended = 0";

	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) != 1) {
		mysql_close();

		setMessage("ExC2: Invalid session", "bad");
		gotopage("list.php");
		return;
	} else {
		$query = "UPDATE running_courses SET extended = 1, end = from_unixtime(unix_timestamp(end) + $extend_time) WHERE sessionid = $sessionid";
		mysql_query($query);

		$query = "UPDATE running_vms SET end = from_unixtime(unix_timestamp(end) + $extend_time) WHERE sessionid = $sessionid";
		mysql_query($query);

		$query = "UPDATE running_baremetal SET end = from_unixtime(unix_timestamp(end) + $extend_time) WHERE sessionid = $sessionid";
		mysql_query($query);

		mysql_close();

		setMessage("Course extended", "good");
		gotopage("list.php");
		return;
	}

?>
