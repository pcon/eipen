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
		$courseid = $_GET['id'];

		mysql_connect($dbhost,$username,$password);
		@mysql_select_db($database) or die( "Unable to select database");

		if ($courseid < 0) {
			setMessage("Invalid courseid given", "bad");
			mysql_close();

			gotopage($_SERVER['PHP_SELF']);
		}

		$query = "DELETE FROM courses WHERE courseid=$courseid";
		$result = mysql_query($query);

		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		mysql_close();

		setMessage("Course deleted sucessfully.  Image must be deleted manually", "good");

		gotopage($_SERVER['PHP_SELF']);

	} elseif (isset($_GET['id'])) {
		$courseid = $_GET['id'];

		mysql_connect($dbhost,$username,$password);
		@mysql_select_db($database) or die( "Unable to select database");

		if ($courseid < 0) {
			setMessage("Invalid courseid given", "bad");
			mysql_close();

			gotopage($_SERVER['PHP_SELF']);
		}

		$query = "SELECT name, coursedesc, xen, length FROM courses WHERE courseid = $courseid";
		$result = mysql_query($query);

		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		if (mysql_num_rows($result) == 0) {
			setMessage("Invalid courseid given", "bad");
			mysql_close();

			gotopage($_SERVER['PHP_SELF']);
		}

		$row = mysql_fetch_array($result);
		$name = $row['name'];
		$desc = $row['coursedesc'];
		$xen = $row['xen'];
		$length = $row['length'];

?>
<html>
<head><title>Eipen - Delete Course</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix" >
<?php
	nav();
	subnav("delete");
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?id=$courseid";?>">
<ul id="form">
<b>Course Name: </b><?php print $name; ?> <br />
<b>Description: </b><?php print $desc; ?> <br />
<b>Course Length: </b><?php print $length; ?> <br />
<b>Is a xen course?: </b><?php if ($xen == 0) { print "No"; } else { print "Yes"; } ?> <br /
<input type="submit" value="Delete course" name="submit" />
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
<head><title>Eipen - Delete Course</title>
<style>@import url(defaultstyle.css);</style></head>
<body>  
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("delete");
	print_messages();
?>
<div id="header">
<ul><li>Course Name<div id="status_right_header">Link</div></li></ul>
</div>
<div id="status_sub">
<ul>
<?php
	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");
	$query = "SELECT courseid, name, coursedesc FROM courses WHERE courseid > 0 ORDER BY name";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) == 0 ) {
		print "<li><center>There are no courses in the database</center></li>\n";
	} else {
		while($row = mysql_fetch_array($result)) {
			$name = $row['name'];
			$courseid = $row['courseid'];
			$desc = $row['coursedesc'];

			print "<li>$name: $desc <div id=\"status_right\"><div id=\"status_box\"><a href=\"deletecourse.php?id=$courseid\">".
				"Delete Course</a></div></div></li>\n";
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
