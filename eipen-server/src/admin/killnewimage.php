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

	if ($sessionid >= 0) {
		setMessage("Invalid sessionid given", "bad");
		mysql_close();

		gotopage("newimagestatus.php");
		return;
	}

	$query = "SELECT xenname FROM running_vms WHERE sessionid = $sessionid";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) != 1) {
		setMessage("Invalid sessionid given", "bad");
		mysql_close();

		gotopage("newimagestatus.php");
		return;
	}

	$row = mysql_fetch_array($result);
	$xenname = $row['xenname'];

	if (isset($_POST['submit'])) {
		$dest = $_POST['image'];

		$query = "SELECT h.ipaddr AS ipaddr, rv.macid AS macid FROM host h, running_vms rv WHERE".
				" rv.sessionid = $sessionid AND rv.hostid = h.hostid";
		$result = mysql_query($query);

		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		$row = mysql_fetch_array($result);
		$hostip = $row['ipaddr'];
		$macid = $row['macid'];

		exec ("/usr/bin/eipen/eipen-server destroymachine $hostip $xenname &");

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

		setMessage("Machine destroyed correctly", "good");

		mysql_close();

		gotopage("statuseipen.php");
	} else {
?>

<html>
<head><title>Eipen - Destroy Image</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	nav();
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?id=$sessionid";?>">
<b>Are you sure you want to destroy <?php print $xenname; ?>?</b><br />
<input type="submit" value="Destroy Image" name="submit" />
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
