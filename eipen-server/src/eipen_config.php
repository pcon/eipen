<?php
	$ini_array = parse_ini_file("/etc/eipen/eipen-server.conf", true);

	$username = $ini_array["database"]["user_id"];
	$password = $ini_array["database"]["db_password"];
	$database = $ini_array["database"]["db"];
	$dbhost = $ini_array["database"]["host"];

	$clientPort = $ini_array["main"]["client_port"];

	$daemonLockPath = $ini_array["main"]["daemon_lock_file"];

	$heartBeat = $ini_array["main"]["heart_beat"];

	$authentication = $ini_array["main"]["authentication"];

	$ldapServer = $ini_array["ldap"]["server_name"];
	$ldapPort = $ini_array["ldap"]["server_port"];
	$ldapBase = $ini_array["ldap"]["base"];

	session_start();

	function footer () {
		print "<div id=\"footerwrapper\"><center>Eipen version 2.0</center></div>\n";
	}

	function print_messages() {
		if (isset($_SESSION['message'])) {
			$status = $_SESSION['message_status'];
			$message = $_SESSION['message'];

			print "<div id=\"message\"><div id=\"message_$status\">$message</div></div>\n";

			unset($_SESSION['message_status']);
			unset($_SESSION['message']);
		}
	}

	function setMessage ($message, $status) {
		$_SESSION['message'] = $message;
		$_SESSION['message_status'] = $status;
	}

	function gotopage ($page) {
		Header("Location: $page");
	}
?>
