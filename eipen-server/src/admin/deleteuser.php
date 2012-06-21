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

	global $authentication;

	if (strcmp($authentication, "ldap") == 0) {
//BEGIN ldap section
		if (isset($_GET['id']) && isset($_POST['submit'])) {
			$adminid = (int)($_GET['id']);

			if ($adminid <= 0) {
				setMessage("Invalid adminid", "bad");
				gotopage($_SERVER['PHP_SELF']);
				return;
			}

			if ( deleteLdapUser($adminid) == 0) {
				setMessage("User deleted properly", "good");
				gotopage($_SERVER['PHP_SELF']);
				return;
			} else {
				setMessage("Invalid adminid", "bad");
				gotopage($_SERVER['PHP_SELF']);
				return;
			}

		} elseif (isset($_GET['id'])) {
			$adminid = (int)($_GET['id']);

			if ($adminid <= 0) {
				setMessage("Invalid adminid", "bad");
				gotopage($_SERVER['PHP_SELF']);
				return;
			}

			$userid = getLdapUserId($adminid);

			if ($userid == NULL) {
				gotopage($_SERVER['PHP_SELF']);
				return;
			}
?>
<html>
<head><title>Eipen - Delete User</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php   
	nav();
	subnav("user");
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?id=".$adminid;?>">
<ul id="form">
<li class="formlabel"><b>Userid: </b></li>
<li class="formdata"><?php print $userid; ?></li>
<li class="formsplit"></li>
</ul>
<input type="submit" value="Delete User" name="submit" />
</form>
<?php   
	footer();
?>
</div> <!-- end wrapper -->
</body>
</html>
<?php
		} else {
?>
<html>
<head><title>Eipen - Delete User</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("user");
	print_messages();
?>
<div id="header">
<ul><li>User Name<div id="status_right_header">Link</div></li></ul>
</div>
<div id="status_sub">
<ul>
<?php
	global $username;
	global $password;
	global $database;
	global $dbhost;
	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT adminid, userid FROM ldap_admin ORDER BY userid";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) == 0 ) {
		print "<li><center>There are no users in the database</center></li>\n";
	} else {
		while($row = mysql_fetch_array($result)) {
			$userid = $row['userid'];
			$adminid = $row['adminid'];

			print "<li>$userid <div id=\"status_right\"><div id=\"status_box\"><a href=\"".$_SERVER['PHP_SELF']."?id=$adminid\">".
				"Delete User</a></div></div></li>\n";
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
<?php
		}
//END ldap section
	} else {
//BEGIN none and simple authentication

	if (isset($_GET['id']) && isset($_POST['submit'])) {
		$uid = (int)($_GET['id']);

		if (deleteUser($uid) == 0) {
			setMessage("User deleted properly", "good");	
		}

		gotopage($_SERVER['PHP_SELF']);
	} else if (isset($_GET['id'])) {
		$uid = (int)($_GET['id']);

		if ($uid <= 0) {
			setMessage("Invalid userid", "bad");
			gotopage($_SERVER['PHP_SELF']);
		}

		$userinfo = getUserInfo($uid);

		if ($userinfo == NULL) {
			gotopage($_SERVER['PHP_SELF']);
		}
?>
<html>
<head><title>Eipen - Delete User</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php   
	nav();
	subnav("user");
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?id=".$uid;?>">
<ul id="form">
<li class="formlabel"><b>Username: </b></li>
<li class="formdata"><?php print $userinfo['username']; ?></li>
<li class="formsplit"></li>
<li class="formlabel"><b>Real Name: </b></li>
<li class="formdata"><?php print $userinfo['realname']; ?></li>
<li class="formsplit"></li>
</ul>
<input type="submit" value="Delete User" name="submit" />
</form>
<?php   
	footer();
?>
</div> <!-- end wrapper -->
</body>
</html>
<?php
} else {
?>
<html>
<head><title>Eipen - Delete User</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("user");
	print_messages();
?>
<div id="header">
<ul><li>User Name<div id="status_right_header">Link</div></li></ul>
</div>
<div id="status_sub">
<ul>
<?php
	global $username;
	global $password;
	global $database;
	global $dbhost;
	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT userid, username, realname, admin FROM users ORDER BY username";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) == 0 ) {
		print "<li><center>There are no users in the database</center></li>\n";
	} else {
		while($row = mysql_fetch_array($result)) {
			$uname = $row['username'];
			$realname = $row['realname'];
			$uid = $row['userid'];

			print "<li>$uname ($realname) <div id=\"status_right\"><div id=\"status_box\"><a href=\"deleteuser.php?id=$uid\">".
				"Delete User</a></div></div></li>\n";
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

<?php
}
// END none and simple authentication
}
?>
