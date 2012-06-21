<?php

function nav () {
	print "<div id=\"navwrapper\">\n";
	print "<center>\n";
	print "<ul id=\"nav\">\n";
	print "<li id=\"nav\"><a href=\"statuseipen.php\">Status & Stats</a></li>\n";
	print "<li id=\"nav\"><a href=\"addcourse.php\">Add</a></li>\n";
	print "<li id=\"nav\"><a href=\"deletecourse.php\">Delete</a></li>\n";
	print "<li id=\"nav\"><a href=\"editcourse.php\">Edit</a></li>\n";
	print "<li id=\"nav\"><a href=\"adduser.php\">Users</a></li>\n";
	print "<li id=\"nav\"><a href=\"../logout.php\">Logout</a></li>\n";
	print "</ul></center></div>\n";
}

function subnav($which) {
	print "<div id=\"subnavwrapper\">\n";
	print "<center>\n";
	print "<ul id=\"subnav\">\n";

	if (strcmp($which, "add") == 0) {
		print "<li id=\"subnav\"><a href=\"addhost.php\">Add Host</a></li>\n";
		print "<li id=\"subnav\"><a href=\"addmacaddr.php\">Add Macaddr</a></li>\n";
		print "<li id=\"subnav\"><a href=\"addksprofile.php\">Add KS Profile</a></li>\n";
		print "<li id=\"subnav\"><a href=\"addcourse.php\">Add Course</a></li>\n";
		print "<li id=\"subnav\"><a href=\"addimage.php\">Add Image</a></li>\n";
		print "<li id=\"subnav\"><a href=\"addbaseimage.php\">Add Base Image</a></li>\n";
		print "<li id=\"subnav\"><a href=\"createnewimage.php\">Create an image</a></li>\n";
	} elseif (strcmp($which, "delete") == 0) {
		print "<li id=\"subnav\"><a href=\"deletehost.php\">Delete Host</a></li>\n";
		print "<li id=\"subnav\"><a href=\"deletemacaddr.php\">Delete Macaddr</a></li>\n";
		print "<li id=\"subnav\"><a href=\"deleteksprofile.php\">Delete KS Profile</a></li>\n";
		print "<li id=\"subnav\"><a href=\"deletecourse.php\">Delete Course</a></li>\n";
		print "<li id=\"subnav\"><a href=\"deleteimage.php\">Delete Image</a></li>\n";
		print "<li id=\"subnav\"><a href=\"deletebaseimage.php\">Delete Base Image</a></li>\n";
	} elseif (strcmp($which, "edit") == 0) {
		print "<li id=\"subnav\"><a href=\"edithost.php\">Edit Host</a></li>\n";
		print "<li id=\"subnav\"><a href=\"editmacaddr.php\">Edit Macaddr</a></li>\n";
		print "<li id=\"subnav\"><a href=\"editksprofile.php\">Edit KS Profile</a></li>\n";
		print "<li id=\"subnav\"><a href=\"editcourse.php\">Edit Course</a></li>\n";
		print "<li id=\"subnav\"><a href=\"editimage.php\">Edit Image</a></li>\n";
		print "<li id=\"subnav\"><a href=\"editbaseimage.php\">Edit Base Image</a></li>\n";
	} elseif (strcmp($which, "user") == 0) {
		print "<li id=\"subnav\"><a href=\"adduser.php\">Add User</a></li>\n";
		print "<li id=\"subnav\"><a href=\"deleteuser.php\">Delete User</a></li>\n";

		global $authentication;

		if (strcmp($authentication, "ldap") != 0) {
			print "<li id=\"subnav\"><a href=\"edituser.php\">Edit User</a></li>\n";
			print "<li id=\"subnav\"><a href=\"../passwduser.php\">Change Password</a></li>\n";
		}
	} elseif (strcmp($which, "stats") == 0) {
		print "<li id=\"subnav\"><a href=\"statuseipen.php\">General Status</a></li>\n";
		print "<li id=\"subnav\"><a href=\"newimagestatus.php\">Image Status</a></li>\n";
	}

	print "</ul></center></div>\n";
}

function createSessionAdmin ($userid, $macid, $hostid, $guestid) {
	$macid = (int)($macid);
	$hostid = (int)($hostid);

	if ($macid < 0) {
		setMessage("CSA1: Invalid macid", "bad");
		return -1;
	}               

	if ($hostid < 0) {
		setMessage("CSA1: Invalid hostid","bad");
		return -1;
	}

	global $username; 
	global $password;
	global $database;
	global $dbhost;

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT min(sessionid) as sessionid FROM running_vms WHERE sessionid < 0 GROUP BY course_imageid";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	$sessionid = -1;

	if (mysql_num_rows($result) == 1) {
		$row = mysql_fetch_array($result);
		$sessionid = $row['sessionid'] - 1;
	}

	$query = "INSERT INTO running_vms (sessionid, userid, course_imageid, macid, hostid, xenname, start, end) VALUES ".
		"($sessionid, \"$userid\",-1, $macid, $hostid, \"$guestid\", now(), ".
		"'2037-01-01 01:01:00')";
	$result = mysql_query($query); 

	$query = "INSERT INTO running_courses (sessionid, userid, courseid, start, end) VALUES ".
		"($sessionid, \"$userid\", -1, now(), '2037-01-01 01:01:00')";
	$result = mysql_query($query); 

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}        

	$query = "UPDATE macaddr SET status = 1 WHERE macid = $macid";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	mysql_close();

	return 0;
}

function getAdminImageInfo ($imageid) {
	global $username;
	global $password;
	global $database;
	global $dbhost;

	$imageid = (int)($imageid);

	if ($imageid <= 0) {
		setMessage ("GAII1: Invalid image id", "bad");
		return NULL;
	}

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT image, fullvirt, typeid AS type FROM admin_base_images WHERE imageid=$imageid";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) != 1) {
		mysql_close();
		setMessage("GAII2: Invalid image id", "bad");
		return NULL;
	}

	$row = mysql_fetch_array($result);
	mysql_close();

	return $row;
}

function getImageInfo ($imageid) {
	global $username;
	global $password;
	global $database;
	global $dbhost;

	$imageid = (int)($imageid);

	if ($imageid <= 0) {
		setMessage ("GII1: Invalid image id", "bad");
		return NULL;
	}

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT image, fullvirt, typeid FROM images WHERE imageid=$imageid";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) != 1) {
		mysql_close();
		setMessage("GII2: Invalid image id", "bad");
		return NULL;
	}

	$row = mysql_fetch_array($result);
	mysql_close();

	return $row;
}

function addUser ($uname, $realname, $email, $passhash, $admin) {
	global $username;
	global $password;
	global $database;
	global $dbhost;

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	mysql_query ("INSERT INTO users (username, realname, email, passhash, admin) VALUES ".
				"('$uname', '$realname', '$email', '$passhash', $admin)")
		or die ('Invalid query: ' .  mysql_error());

	mysql_close();

	return 0;
}

function addLdapUser ($uname) {
	global $username;
	global $password;
	global $database;
	global $dbhost;

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	mysql_query ("INSERT INTO ldap_admin (userid) VALUES ('$uname')")
		or die ('Invalid query: ' .  mysql_error());

	mysql_close();

	return 0;
}

function deleteLdapUser ($adminid) {
	global $username;
	global $password;
	global $database;
	global $dbhost;

	$adminid = (int)($adminid);

	if ($adminid <= 0) {
		setMessage("DLU1: Invalid adminid", "bad");
		return -1;
	}

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT adminid FROM ldap_admin WHERE adminid = $adminid";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) != 1) {
		mysql_close();
		setMessage("DLU2: Invalid adminid", "bad");
		return -1;
	}

	mysql_query ("DELETE FROM ldap_admin WHERE adminid = $adminid")
		or die ('Invalid query: ' .  mysql_error());

	mysql_close();

return 0;
}

function deleteUser ($uid) {
	global $username;
	global $password;
	global $database;
	global $dbhost;

	$uid = (int)($uid);

	if ($uid <= 0) {
		setMessage("DU1: Invalid userid", "bad");
		return -1;
	}

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT userid FROM users WHERE userid = $uid";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) != 1) {
		mysql_close();
		setMessage("DU2: Invalid userid", "bad");
		return -1;
	}

	mysql_query ("DELETE FROM users WHERE userid = $uid")
		or die ('Invalid query: ' .  mysql_error());

	mysql_close();

return 0;
}

function getLdapUserId ($adminid) {
	global $username;
	global $password;
	global $database;
	global $dbhost;

	$adminid = (int)($adminid);

	if ($adminid <= 0) {
		setMessage("GLUI1: Invalid adminid", "bad");
		return NULL;
	}

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT userid FROM ldap_admin WHERE adminid=$adminid";
	$result = mysql_query($query);

	if (!$result) {
		die ('Invalid query: ' .  mysql_error());
	}

	if (mysql_num_rows($result) != 1) {
		mysql_close();
		setMessage("GLUI2: Invalid adminid", "bad");
		return NULL;
	}

	$userinfo = mysql_fetch_array($result);
	$userid = $userinfo['userid'];
	mysql_close();

	return $userid;
}

function getUserInfo ($uid) {
	global $username;
	global $password;
	global $database;
	global $dbhost;

	$uid = (int)($uid);

	if ($uid <= 0) {
		setMessage("GUI1: Invalid userid", "bad");
		return NULL;
	}

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT username, realname FROM users WHERE userid=$uid";
	$result = mysql_query($query);

	if (!$result) {
		die ('Invalid query: ' .  mysql_error());
	}

	if (mysql_num_rows($result) != 1) {
		mysql_close();
		setMessage("GUI2: Invalid userid", "bad");
		return NULL;
	}

	$userinfo = mysql_fetch_array($result);
	mysql_close();

	return $userinfo;
}

?>
