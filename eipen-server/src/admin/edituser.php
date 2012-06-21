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
		setMessage("Editing users not avaliable for ldap authentication", "bad");
		gotopage("statuseipen.php");
		return;
	}

	if (isset($_GET['id'])) {
		$userid = (int)($_GET['id']);

		global $username;
		global $password;
		global $database;
		global $dbhost;

		if ($userid < 0) {
			setMessage("Invalid userid", "bad");
			gotopage($_SERVER['PHP_SELF']);
		}

		if (isset($_POST['submit'])) {
			$name = $_POST['username'];
			$realname = $_POST['realname'];
			$email = $_POST['email'];
			$admin = 0;
			if (isset($_POST['admin'])) {
				$admin = (int)($_POST['admin']);
			}

			if (ereg('^[A-Za-z0-9_\.-]{1,}$', $name)) {
				if (ereg('^[A-Za-z0-9_\.\ ]{1,}$', $realname)) {
					if (ereg('^[A-Za-z0-9_\.\ ]{1,}@[A-Za-z0-9_\.\ ]{1,}\.[A-Za-z0-9_\.\ ]{1,}$', $email)) {
						mysql_connect($dbhost,$username,$password);
						@mysql_select_db($database) or die( "Unable to select database");

						$query = "SELECT userid FROM users WHERE username = \"$name\" ".
								"AND userid != $userid";
						$result = mysql_query($query);

						if (!$result) {
							die ('Invalid query: ' . mysql_error());
						}

						if (mysql_num_rows($result) > 0) {
							mysql_close();
				
							setMessage("Username already being used", "bad");
							gotopage($_SERVER['PHP_SELF']);
							return;
						}

						$query = "SELECT userid FROM users WHERE userid = $userid";
						$result = mysql_query($query);

						if (!$result) {
							die ('Invalid query: ' . mysql_error());
						}

						if (mysql_num_rows($result) != 1) {
							mysql_close();

							setMessage("Invalid userid", "bad");
							gotopage($_SERVER['PHP_SELF']);
							return;
						}

						mysql_query("UPDATE users SET username=\"$name\", realname=\"$realname\", ".
							"admin=$admin, email=\"$email\" WHERE userid=$userid")
							or die ('Invalid query: ' .  mysql_error());

						mysql_close();

						setMessage("User saved successfully", "good");
					} else {
						setMessage("Invalid email address", "bad");
					}
				} else {
					setMessage("Invalid real name for user", "bad");
				}
			} else {
				setMessage("Invalid username", "bad");
			}
		gotopage($_SERVER['PHP_SELF']);
	} else {

		mysql_connect($dbhost,$username,$password);
		@mysql_select_db($database) or die( "Unable to select database");

		$query = "SELECT username, realname, email, admin FROM users WHERE userid = $userid";
		$result = mysql_query($query);

		if (!$result) {
			die ('Invalid query: ' . mysql_error());
		}

		mysql_close();

		if (mysql_num_rows($result) != 1) {
			setMessage("Invalid userid", "bad");
			gotopage($_SERVER['PHP_SELF']);
		}

		$row = mysql_fetch_array($result);

		$name = $row['username'];
		$realname = $row['realname'];
		$email = $row['email'];
		$admin = $row['admin'];
?>
<html>
<head><title>Eipen - Edit Username</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("edit");
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?id=".$userid;?>">
<ul id="form">
<li class="formlabel">User Name</li>
<li class="forminput"><input type="text" size="20" maxlength="20" name="username" value="<?php print $name; ?>" /></li>
<li class="formsplit"></li>
<li class="formlabel">Real Name</li>
<li class="forminput"><input type="text" size="20" maxlength="255" name="realname" value="<?php print $realname; ?>" /></li>
<li class="formsplit"></li>
<li class="formlabel">Email</li>
<li class="forminput"><input type="text" size="20" maxlength="255" name="email" value="<?php print $email; ?>" /></li>
<li class="formsplit"></li>
<li class="formlabel">Is a admin?</li>
<li class="forminput"><input type="checkbox" name="admin" value="1" <?php if($admin == 1) { print "checked"; } ?>></li>
<li class="formsplit"></li>
<input type="submit" value="Save User" name="submit" />
</ul>
</form>

<?php
	mysql_close();
?>

<?php 
	footer();
?>
</div> <!-- end wrapper -->
</body>
</html>
<?php
		}  //end else
	} else {
?>
<html>
<head><title>Eipen - Edit User</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("edit");
	print_messages();
?>
<div id="header">
<ul><li>User Name<div id="status_right_header">Link</div></li></ul>
</div>                  
<div id="status_sub">
<ul>
<?php           
	global $authentication;

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");


	$query = "SELECT userid, username, realname FROM users ORDER BY username";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}       

	if (mysql_num_rows($result) == 0 ) {
		print "<li><center>There are no users in the database</center></li>\n";
	} else {
		while($row = mysql_fetch_array($result)) {
			$name = $row['username'];
			$userid = $row['userid'];
			$realname = $row['realname'];

			print "<li>$name ($realname) <div id=\"status_right\"><div id=\"status_box\"><a href=\"".$_SERVER['PHP_SELF']."?id=$userid\">".
				"Edit User</a></div></div></li>\n";
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
?>
