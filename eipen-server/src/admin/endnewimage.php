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

	global $username;
	global $password;
	global $database;
	global $dbhost;

	$sessionid = (int)($_GET['id']);

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	if ($sessionid > 0) {
		setMessage("Invalid sessionid given", "bad");
		mysql_close();

		gotopage("newimagestatus.php");
	}

	$query = "SELECT xenname FROM running_vms WHERE sessionid = $sessionid";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) == 0) {
		setMessage("Invalid sessionid given", "bad");
		mysql_close();

		gotopage("newimagestatus.php");
	}

	$row = mysql_fetch_array($result);
	$xenname = $row['xenname'];

	if (isset($_POST['submit'])) {
		$dest = $_POST['image'];
		$overwrite = 0;

		if (isset($_POST['overwrite'])) {
			$overwrite = $_POST['overwrite'];
		}

		$query = "SELECT host.ipaddr as ipaddr, xenname, userid, macid FROM running_vms, host WHERE sessionid = $sessionid AND ".
			"running_vms.hostid = host.hostid";
		$result = mysql_query($query);

		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		$row = mysql_fetch_array($result);
		$hostip = $row['ipaddr'];
		$xenname = $row['xenname'];
		$email = $row['userid'];
		$macid = $row['macid'];

		exec ("/usr/bin/eipen/eipen-server savemachine $hostip $xenname $dest $overwrite $email &");

		$query = "DELETE FROM running_vms WHERE sessionid=$sessionid";
		$result = mysql_query($query);

		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		$query = "DELETE FROM running_courses WHERE sessionid=$sessionid";
		$result = mysql_query($query);

		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		$query = "UPDATE macaddr SET status = 0 WHERE macid = $macid";
		$result = mysql_query($query);

		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		setMessage("Machine saved correctly.  An email will arrive when done", "good");
		mysql_close();

		gotopage("editcourse.php");
	} else {
?>

<html>
<head><title>Eipen - End Image</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	nav();
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?id=$sessionid";?>">
<ul id="form">
<center>Where do you want to save <?php print $xenname; ?> to?</center>
<li class="formsplit"></li>
<li class="formlabel">Image Location</li>
<li class="forminput"><input type="text" size="20" maxlength="255" name="image" />
<img src="../img/help.png" alt="Relative images_dir" title="Relative images_dir" /></li>
<li class="formsplit"></li>
<li class="formlabel">Overwrite image?</li>
<li class="forminput"><input type="checkbox" name="overwrite" value="1" ></li>
<li class="formsplit"></li>

<input type="submit" value="Save Image" name="submit" />
</form>
<?php
	footer();
?>
</div><!-- end wrapper -->
</body>
</html>

<?php
mysql_close();
}
?>
