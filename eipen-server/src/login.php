<?php

require_once("eipen_config.php");
require_once("eipen_userfunctions.php");

if (isset($_POST['submit'])) {
	$user=$_POST['username'];
	$pass=$_POST['password'];

	$result = login($user,$pass);

	if ($result == 0) {
		if (isset($_SESSION['from']) == 1 && strcmp($_SESSION['from'], "") != 0 && strcmp($_SESSION['from'], $_SERVER['PHP_SELF']) != 0) {
			gotopage($_SESSION['from']);
			return;
		}
		gotopage("index.php");
		return;
	}

	gotopage($_SERVER['PHP_SELF']);
} else {

?>

<html>
<head><title>Eipen - Login</title></head>
<style>@import url(defaultstyle.css);</style>
<body>
<div id="wrapper" class="clearfix">
<div id="navwrapper">&nbsp;</div>
<?php 
	print_messages(); 
?>
<form method="post" action="<?php print $_SERVER['PHP_SELF']; ?>">
<ul id="form">
<li class="formlabel">Username</li>
<li class="forminput"><input type="text" name="username" size="15" maxlength="255" /></li>
<li class="formsplit"></li>
<li class="formlabel">Password</li>
<li class="forminput"><input type="password" name="password" size="15" maxlength="255" /></li>
<li class="formsplit"></li>
</ul>
<input type="submit" value="Login" name="submit"/>
</form>
<?php
	footer();
?>
</div> <!-- end wrapper -->
</body>
</html>
<?php
}
?>
