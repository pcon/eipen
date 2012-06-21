<?php
	require_once ("eipen_config.php");
	require_once ("eipen_userfunctions.php");
	require_once ("eipen_functions.php");

	if (isloggedin() != 0) {
		$_SESSION['from'] = $_SERVER['PHP_SELF'];
		gotopage("login.php");
	}

	updateHB();

	global $authentication;
	if (strcmp($authentication, "ldap") == 0) {
		setMessage("Changing password not supported in ldap configuration", "bad");
		gotopage("index.php");
		return;
	} else {
//START none and simple authentication section

	if (isset($_POST['submit'])) {
		$oldpasswordhash = md5($_POST['oldpassword']);
		$newpassword = $_POST['newpassword'];
		$newpassword_verify = $_POST['newpassword_verify'];

		if (strcmp($newpassword, $newpassword_verify) != 0) {
			setMessage("New password does not match", "bad");
			gotopage($_SERVER['PHP_SELF']);
			return;
		}

		$newpasshash = md5($newpassword);

		if (updateUserPassword($oldpasswordhash, $newpasswordhash) == 0) {
			setMessage("Password update correctly", "good");
		}

		gotopage("index.php");
	} else {
?>
<html>
<head><title>Eipen - Add User</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php   
	user_nav();
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<ul id="form">
<li class="formlabel">Current Password</li>
<li class="forminput"><input type="password" size="20" maxlength="255" name="oldpassword" /></li>
<li class="formsplit"></li>
<li class="formlabel">New Password</li>
<li class="forminput"><input type="password" size="20" maxlength="255" name="newpassword" /></li>
<li class="formsplit"></li>
<li class="formlabel">New Password Again</li>
<li class="forminput"><input type="password" size="20" maxlength="255" name="newpassword_verify" /></li>
<li class="formsplit"></li>
</ul>
<input type="submit" value="Change Password" name="submit" />
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
