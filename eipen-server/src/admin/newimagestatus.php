<?php
	require_once ("../eipen_config.php");
	require_once ("../eipen_userfunctions.php");
	require_once ("../eipen_functions.php");
	require_once ("eipen_admin.php");

	if (isloggedin() != 0 || is_admin() != 0) {
		$_SESSION['from'] = $_SERVER['PHP_SELF'];
		gotopage("../login.php");
	}

	updateHB();

	global $username;
	global $password;
	global $database;
	global $dbhost;

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");
?>

<html>
<head><title>Eipen - New Image Status</title>
<style>@import url(defaultstyle.css);</style></head>
<body>  
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("stats");
	print_messages();
?>
<div id="header">
<ul><li>Session Name<div id="status_right_header">Link</div></li></ul>
</div>
<div id="status_sub">
<ul>
<?php

	$query = "SELECT sessionid, xenname FROM running_vms WHERE course_imageid = -1";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) == 0 ) {
		print "<li><center>No new image session are currently running.</center></li>\n";
	} else {
		while($row = mysql_fetch_array($result)) {
			$xenname = $row['xenname'];
			$sessionid = $row['sessionid'];

			print "<li>$xenname <div id=\"status_right\"><div id=\"status_box\"><a href=\"killnewimage.php?id=$sessionid\">Destroy Session</a></div> <div id=\"status_box\"><a href=\"endnewimage.php?id=$sessionid\">End Session</a></div></div></li>\n";
		}
	}

	mysql_close();

?>
</ul>
</div><!-- end status_sub -->
<?php
	footer();
?>
</div><!-- end wrapper -->
</body>
</html>
