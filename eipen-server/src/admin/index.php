<?php

require_once ("../eipen_config.php");
require_once ("../eipen_userfunctions.php");
require_once ("../eipen_functions.php");
require_once ("eipen_admin.php");

if (isloggedin() != 0 || is_admin() != 0) {
	$_SESSION['from'] = $_SERVER['PHP_SELF'];
	gotopage("../login.php");
} else {
	gotopage("statuseipen.php");
}

?>
