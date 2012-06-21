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

	print '<center>Course usage for the past seven days <br />';
	print '<img src="stats/courses.php?type=overview" /><br /><br />';
	print 'Image usage for the past seven days <br />';
	print '<img src="stats/images.php?type=overview" /></center>';

?>
