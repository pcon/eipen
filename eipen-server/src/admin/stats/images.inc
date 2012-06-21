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

	$type = "usage";

	if(isset($_GET['type'])) {
		$type = $_GET['type'];
	}

	if(strcmp($type, 'usage') == 0 && isset($_GET['image'])) {
		$image = $_GET['image'];
		print "<center><img src=\"stats/images.php?type=$type&image=$image\" /></center>";
	} else {
		print "<center><img src=\"stats/images.php?type=$type\" /></center>";
	}
?>
