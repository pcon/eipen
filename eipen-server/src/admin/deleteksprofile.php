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

	if (isset($_GET['id']) && isset($_POST['submit'])) {
		$profileid = $_GET['id'];

		mysql_connect($dbhost,$username,$password);
		@mysql_select_db($database) or die( "Unable to select database");

		if ($profileid < 0) {
			setMessage("Invalid profileid given", "bad");
			mysql_close();

			gotopage($_SERVER['PHP_SELF']);
		}

		$query = "DELETE FROM ks_profiles WHERE profileid=$profileid";
		$result = mysql_query($query);

		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		mysql_close();

		setMessage("Profile deleted sucessfully", "good");

		gotopage($_SERVER['PHP_SELF']);

	} elseif (isset($_GET['id'])) {
		$profileid = $_GET['id'];

		mysql_connect($dbhost,$username,$password);
		@mysql_select_db($database) or die( "Unable to select database");

		if ($profileid < 0) {
			setMessage("Invalid profileid given", "bad");
			mysql_close();

			gotopage($_SERVER['PHP_SELF']);
		}

		$query = "SELECT name, label FROM ks_profiles WHERE profileid = $profileid";
		$result = mysql_query($query);

		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		if (mysql_num_rows($result) == 0) {
			setMessage("Invalid profileid given", "bad");
			mysql_close();

			gotopage($_SERVER['PHP_SELF']);
		}

		$row = mysql_fetch_array($result);
		$name = $row['name'];
		$label = $row['label'];

?>
<html>
<head><title>Eipen - Delete Profile</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix" >
<?php
	nav();
	subnav("delete");
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?id=$profileid";?>">
<ul id="form">
<b>Profile Name: </b><?php print $name; ?> <br />
<b>Label: </b><?php print $label; ?> <br />
<input type="submit" value="Delete Profile" name="submit" />
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
<head><title>Eipen - Delete Profile</title>
<style>@import url(defaultstyle.css);</style></head>
<body>  
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("delete");
	print_messages();
?>
<div id="header">
<ul><li>Profile Name<div id="status_right_header">Link</div></li></ul>
</div>
<div id="status_sub">
<ul>
<?php
	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");
	$query = "SELECT profileid, name FROM ks_profiles ORDER BY name";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) == 0 ) {
		print "<li><center>There are no profiles in the database</center></li>\n";
	} else {
		while($row = mysql_fetch_array($result)) {
			$name = $row['name'];
			$profileid = $row['profileid'];

			print "<li>$name <div id=\"status_right\"><div id=\"status_box\"><a href=\"deleteksprofile.php?id=$profileid\">".
				"Delete Profile</a></div></div></li>\n";
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
