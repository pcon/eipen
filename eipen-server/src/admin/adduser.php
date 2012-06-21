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
//START ldap authentication section
		if (isset($_POST['submit'])) {
			$uname = $_POST['username'];

			if (ereg('^[A-Za-z0-9_\.-]{1,}$', $uname)) {
				if (addLdapUser($uname) == 0) {
					setMessage("User added properly", "good");
				}
			} else {
				setMessage("Invalid username", "bad");
			}

			gotopage($_SERVER['PHP_SELF']);
			return;
		} else {
?>
<html>
<head><title>Eipen - Add Ldap Admin</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php   
	nav();
	subnav("user");
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<ul id="form">
<li class="formlabel">Username</li>
<li class="forminput"><input type="text" size="20" maxlength="20" name="username" /></li>
<li class="formsplit"></li>
</ul>
<input type="submit" value="Add User" name="submit" />
</form>
<?php   
	footer();
?>
</div> <!-- end wrapper -->
</body>
</html>
<?php
}
//END ldap authentication section
	} else {
//START none and simple authentication section

	if (isset($_POST['submit'])) {
		$uname = $_POST['username'];
		$passhash = md5($_POST['password']);
		$realname = $_POST['realname'];
		$email = $_POST['email'];
		$admin = 0;
		if (isset($_POST['admin'])) {
			$admin = (int)($_POST['admin']);
		}

		if (ereg('^[A-Za-z0-9_\.-]{1,}$', $uname)) {
			if (ereg('^[A-Za-z0-9_\.\ ]{1,}$', $realname)) {
				if (ereg('^[A-Za-z0-9_\.-]{1,}@[A-Za-z0-9_\.-]{1,}\.[A-Za-z0-9_\.-]{1,4}$', $email)) {
					if (addUser($uname, $realname, $email, $passhash, $admin) == 0) {
						setMessage("User added properly", "good");
					}
				} else {
					setMessage("Invalid email address", "bad");
				}
			} else {
				setMessage("Invalid real name", "bad");
			}
		} else {
			setMessage("Invalid username \"$uname\"","bad");
		}

		gotopage($_SERVER['PHP_SELF']);
	} else {
?>
<html>
<head><title>Eipen - Add User</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php   
	nav();
	subnav("user");
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<ul id="form">
<li class="formlabel">Username</li>
<li class="forminput"><input type="text" size="20" maxlength="20" name="username" /></li>
<li class="formsplit"></li>
<li class="formlabel">Real Name</li>
<li class="forminput"><input type="text" size="20" maxlength="255" name="realname" /></li>
<li class="formsplit"></li>
<li class="formlabel">Email</li>
<li class="forminput"><input type="text" size="20" maxlength="255" name="email" /></li>
<li class="formsplit"></li>
<li class="formlabel">Password</li>
<li class="forminput"><input type="password" size="20" maxlength="255" name="password" /></li>
<li class="formsplit"></li>
<li class="formlabel">Is a admin?</li>
<li class="forminput"><input type="checkbox" name="admin" value="1" <?php if($admin == 1) { print "checked"; } ?>></li>
<li class="formsplit"></li>
</ul>
<input type="submit" value="Add User" name="submit" />
</form>
<?php   
	footer();
?>
</div> <!-- end wrapper -->
</body>
</html>
<?php
}
//END none and simple authentication section
}
?>
