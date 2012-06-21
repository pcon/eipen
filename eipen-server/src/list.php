<?php
	require_once("eipen_config.php");
     require_once("eipen_functions.php");
     require_once("eipen_userfunctions.php");

     if (isloggedin() != 0  && strcmp($authentication, "") != 0) {
          $_SESSION['from'] = $_SERVER['PHP_SELF'];
          gotopage('login.php');
          return;
     }

     if (isDaemonRunning() != 0) {
          setMessage("The damon is not running.  Please contact the system administrator", "bad");
     }

	global $authentication;

	if (strcmp($authentication, "ldap") != 0 && strcmp($authentication, "simple")) {
		gotopage("index.php");
	} 

?>
<html>
<head>
<title>Eipen - Course management</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	user_nav();
	print_messages();
?>
<div id="header">
<ul><li>Course Name - Start - End<div id="status_right_header">Links</div></li></ul>
</div>
<div id="status_sub">
<ul>
<?php

	global $username;
	global $password;
	global $database;
	global $dbhost;

	$link = mysql_connect($dbhost,$username,$password);
	if (!$link) {
		die('Could not connect: '.mysql_error());
	}
	@mysql_select_db($database, $link) or die( "Unable to select database");

	$query = "SELECT rc.sessionid AS sessionid, c.name AS name, rc.start AS start, rc.end AS end FROM courses c, running_courses rc ".
		"WHERE c.courseid = rc.courseid AND rc.userid = '".get_email()."' AND rc.end > now() AND rc.courseid != -1 ORDER BY name";

	$result = mysql_query($query, $link);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) == 0) {
		print "<li><center>No courses started</center></li>\n";
	} else {
		while ($row = mysql_fetch_array($result)) {
			list ($sDate, $sTime) = split(" ", $row['start'], 2);
			list ($eDate, $eTime) = split(" ", $row['end'], 2);

			if (strcmp($sDate, $eDate) != 0) {
				print "<li>".$row['name']." ".$row['start']." - ".$row['end']."<div id=\"status_right\">";
			} else {
				print "<li>".$row['name']." $sDate $sTime - $eTime <div id=\"status_right\">";
			}

			$query2 = "SELECT sessionid FROM running_courses WHERE sessionid = ".$row['sessionid']." AND extended = 0";
			$result2 = mysql_query($query2);
			if (mysql_num_rows($result2) == 1) {
				print "<div id=\"status_box\"><a href=\"extend_course.php?id=".$row['sessionid']."\">Extend Course</a></div>&nbsp;";
			}

			print "<div id=\"status_box\"><a href=\"end_course.php?id=".$row['sessionid']."\">End Course</a></div>".
				"</div></li>";
		}
	}

	mysql_close($link);

	footer();
?>
</div>
</body>
</html>
