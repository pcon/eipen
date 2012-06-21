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
		$imageid = $_GET['id'];

		mysql_connect($dbhost,$username,$password);
		@mysql_select_db($database) or die( "Unable to select database");

		if ($imageid < 0) {
			setMessage("Invalid imageid given", "bad");
			mysql_close();

			gotopage($_SERVER['PHP_SELF']);
		}

		$query = "DELETE FROM admin_base_images WHERE imageid=$imageid";
		$result = mysql_query($query);

		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		mysql_close();

		setMessage("Base Image delete sucessfully.  The file must be removed by hand", "good");
		gotopage($_SERVER['PHP_SELF']);

	} elseif (isset($_GET['id'])) {
		$imageid = $_GET['id'];

		mysql_connect($dbhost,$username,$password);
		@mysql_select_db($database) or die( "Unable to select database");

		if ($imageid < 0) {
			setMessage("Invalid imageid given", "bad");
			mysql_close();

			gotopage($_SERVER['PHP_SELF']);
		}

		$query = "SELECT name, image FROM admin_base_images WHERE imageid = $imageid";
		$result = mysql_query($query);

		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		if (mysql_num_rows($result) == 0) {
			setMessage("Invalid imageid given", "bad");
			mysql_close();

			gotopage($_SERVER['PHP_SELF']);
		}

		$row = mysql_fetch_array($result);
		$name = $row['name'];
		$image = $row['image'];

?>
<html>
<head><title>Eipen - Delete Base Image</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix" >
<?php
		nav();
		subnav("delete");
		print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?id=$imageid";?>">
<b>Image Name: </b><?php print $name; ?> <br />
<b>Image Location: </b><?php print $image; ?> <br />
<input type="submit" value="Delete image" name="submit" />
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
<head><title>Eipen - Delete Base Image</title>
<style>@import url(defaultstyle.css);</style></head>
<body>  
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("delete");
	print_messages();
?>
<div id="header">
<ul><li>Image name<div id="status_right_header">Link</div></li></ul>
</div>
<div id="status_sub">
<ul>
<?php
	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT imageid, name FROM admin_base_images ORDER BY name";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) == 0 ) {
		print "<li><center>There are no base images in the database</center></li>\n";
	} else {
		while($row = mysql_fetch_array($result)) {
			$name = $row['name'];
			$imageid = $row['imageid'];

			print "<li>$name <div id=\"status_right\"><div id=\"status_box\"><a href=\"deletebaseimage.php?id=$imageid\">".
				"Delete Image</a></div></div></li>\n";
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
