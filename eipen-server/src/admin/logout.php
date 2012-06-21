<?php
	require_once("../eipen_config.php");
	require_once("eipen_admin.php");
	
	session_unset('username');
	session_unset('passhash');
	session_unset('userid');
	session_destroy();

	gotopage("index.php");
?>
