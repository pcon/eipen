<?php

require_once("eipen_config.php");

function updateHB() {
	$_SESSION['hb'] = time();
}

function isloggedin () {
	if (!isset($_SESSION['uid']) && !isset($_SESSION['challenge']) && !isset($_SESSION['hb'])) {
		return -1;
	}

	global $heartBeat;

	if ((time() - $_SESSION['hb']) >= $heartBeat)  {
		return -1;
	}

	if (strcmp($_SESSION['challenge'], $_COOKIE['challenge']) != 0) {
		return -1;
	}

	return 0;
}

function logout() {
	$_SESSION = array();

	setcookie(session_name(), "", time() - 3600, "/");

	session_destroy();

	return 0;
}

function login($user, $pass) {
	global $authentication;

	if (strcasecmp($authentication,"") == 0) {
		return simple_login($user, $pass);
	} elseif (strcasecmp($authentication, "simple") == 0) {
		return simple_login($user, $pass);
	} elseif (strcasecmp($authentication, "ldap") == 0) {
		return ldap_login($user, $pass);
	}

	trigger_error("No authentication method set.");

	return -1;
}

function simple_login($user, $pass) {
	global $username;
	global $password;
	global $database;
	global $dbhost;

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT userid FROM users WHERE username = '$user' AND passhash='".md5($pass)."'";

	$results = mysql_query($query);
	if (!$results) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($results) != 1) {
		setMessage("Unable to login.  Invalid creditials", "bad");
		mysql_close();

		return -1;
	}

	$row = mysql_fetch_array($results);
	$_SESSION['uid'] = $row['userid'];
	$_SESSION['hb'] = time();

	$challenge = sha1($_SESSION['uid'].getenv('REMOTE_ADDR').$_SESSION['hb']);

	$_SESSION['challenge'] = $challenge;
	setcookie('challenge', $challenge);

	mysql_close();
	
	return 0;
}

function ldap_login($user, $pass) {
	global $ldapServer;
	global $ldapPort;
	global $ldapBase;

	$FILTER = "(&(objectclass=person) (uid=$user))";

	$ldap_conn = ldap_connect($ldapServer, $ldapPort) or die("Could not connect to server");
     ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3) or die("Failed to set protocol version to 3");
     ldap_start_tls($ldap_conn);

     $r = ldap_bind($ldap_conn) or die("Could not bind to server");

     $result = ldap_search($ldap_conn, $ldapBase, $FILTER) or die ("Error in search query");
     $info = ldap_get_entries($ldap_conn, $result);

	if ($info["count"] != 1) {
		setMessage("Unable to login.  Invalid creditials", "bad");
		return -1;
	}

     if(!ldap_bind($ldap_conn, "uid=$user,$ldapBase", $pass))
	{
		setMessage("Unable to login.  Invalid creditials", "bad");
		return -1;
	}

	ldap_close($ldap_conn);

	$_SESSION['uid'] = $user;
	$_SESSION['hb'] = time();

	$challenge = sha1($_SESSION['uid'].getenv('REMOTE_ADDR').$_SESSION['hb']);

	$_SESSION['challenge'] = $challenge;
	setcookie('challenge', $challenge);

	return 0;
}

function get_email() {
	global $authentication;

	if (strcasecmp($authentication, "simple") == 0) {
		return get_simple_email();
	} elseif (strcasecmp($authentication, "ldap") == 0) {
		return get_ldap_email();
	}

	trigger_error("No authentication method set.");

	return -1;
}
function get_ldap_email() {
	global $ldapServer;
	global $ldapPort;
	global $ldapBase;

	$user = $_SESSION['uid'];

	$FILTER = "(&(objectclass=person) (uid=$user))";

	$ldap_conn = ldap_connect($ldapServer, $ldapPort) or die("Could not connect to server");
	ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3) or die("Failed to set protocol version to 3");
	ldap_start_tls($ldap_conn);

	$r = ldap_bind($ldap_conn) or die("Could not bind to server");

	$result = ldap_search($ldap_conn, $ldapBase, $FILTER) or die ("Error in search query");
	$info = ldap_get_entries($ldap_conn, $result);

	if ($info["count"] != 1) {
		setMessage("Unable to login.  Invalid creditials", "bad");
		return -1;
	}

	$email = $info[0]["mail"][0];

	ldap_close($ldap_conn);

	return $email;
}

function get_simple_email() {
	global $username;
	global $password;
	global $database;
	global $dbhost;

	$uid = (int)($_SESSION['uid']);

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT email FROM users WHERE userid = $uid";

	$results = mysql_query($query);
	if (!$results) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($results) != 1) {
		setMessage("Not logged in.  Invalid creditials", "bad");
		mysql_close();

		return -1;
	}

	$row = mysql_fetch_array($results);

	mysql_close();

	return $row['email'];
}

function is_admin() {
	global $authentication;

	if (strcasecmp($authentication, "simple") == 0 || strcmp($authentication, "") == 0) {
		return simple_is_admin();
	} elseif (strcasecmp($authentication, "ldap") == 0) {
		return ldap_is_admin();
	}

	trigger_error("No authentication method set.");

	return -1;
}

function simple_is_admin() {
	global $username;
	global $password;
	global $database;
	global $dbhost;

	$uid = (int)($_SESSION['uid']);

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT userid FROM users WHERE userid = $uid AND admin = 1";

	$results = mysql_query($query);


	if (mysql_num_rows($results) != 1) {
		mysql_close();
		return -1;
	}

	mysql_close();
	
	return 0;
}

function ldap_is_admin() {
	global $username;
	global $password;
	global $database;
	global $dbhost;

	$uid = $_SESSION['uid'];

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT adminid FROM ldap_admin WHERE userid = '$uid'";

	$results = mysql_query($query);

	if (mysql_num_rows($results) != 1) {
		trigger_error("not a good admin");
		mysql_close();
		return -1;
	}

	mysql_close();
	
	return 0;
}

function updateUserPassword ($oldhash, $newhash) {
	$uid = (int)($_SESSION['uid']);

	global $username;
	global $password;
	global $database;
	global $dbhost;

	$uid = (int)($_SESSION['uid']);

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT userid FROM users WHERE userid = $uid AND passhash = '$oldhash'";
	$results = mysql_query($query);
	if (mysql_num_rows($results) != 1) {
		setMessage("Old password incorrect");
		return -1;
	}
	
	$query = "UPDATE users SET passhash = '$newhash' WHERE userid = $uid";
	$results = mysql_query($query);

	return 0;
}

?>
