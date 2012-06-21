<?php

require_once("../eipen_config.php");
require_once("eipen_admin.php");

if (isset($_POST['submit'])) {
        $user=$_POST['username'];
        $pass=$_POST['password'];

        global $username;
        global $password;
        global $database;
        global $dbhost;

        mysql_connect($dbhost,$username,$password);
        @mysql_select_db($database) or die( "Unable to select database");

        $query = "SELECT userid FROM admin_user WHERE username = '$user' AND passhash='".md5($pass)."'";

        $results = mysql_query($query);
        if (!$results) {
                die('Invalid query: ' . mysql_error());
        }

        if (mysql_num_rows($results) != 1) {
                setMessage("Unable to login.  Invalid creditials", "bad");
		gotopage($_SERVER['PHP_SELF']);
        } else {
                $row = mysql_fetch_array($results);
                $_SESSION['username'] = $user;
                $_SESSION['passhash'] = md5($pass);
                $_SESSION['userid'] = $row['userid'];
                gotopage($_SESSION['from']);
        }
} else {

?>

<html>
<head><title>Login</title></head>
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
