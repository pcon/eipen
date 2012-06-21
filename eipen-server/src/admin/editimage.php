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

	if (isset($_GET['id'])) {
		$imageid=(int)($_GET['id']);

		if ($imageid < 0) {
			setMessage("Invalid image id", "bad");
			gotopage($_SERVER['PHP_SELF']);
		}

		if (isset($_POST['submit'])) {
			global $username;
			global $password;
			global $database;
			global $dbhost;

			$name = $_POST['name'];
			$image = $_POST['image'];
			$memory = $_POST['memory'];
			$typeid = (int)($_POST['imagetype']);
			$fullvirt = 0;
			if (isset($_POST['fullvirt'])) {
				$fullvirt = (int)($_POST['fullvirt']);
			}

			if (ereg('^[A-Za-z0-9\(\)\. -]{1,}$', $name)) {    //check name
				if (ereg('^[A-Za-z0-9_\./-]{1,}$',$image)) {	   //check image name
					mysql_connect($dbhost,$username,$password);
					@mysql_select_db($database) or die( "Unable to select database");

					$query = "SELECT imageid FROM images WHERE imageid=$imageid";
					$result = mysql_query($query);

					if (!$result) {
						die ('Invalid query: ' . mysql_error());
					}

					if (mysql_num_rows($result) != 1) {
						mysql_close();
						setMessage("Invalid image id", "bad");
						gotopage($_SERVER['PHP_SELF']);
					}

					mysql_query("UPDATE images SET name=\"$name\", image=\"$image\", ".
						"fullvirt=$fullvirt, typeid=$typeid, memory=$memory WHERE imageid=$imageid") 
						or die ('Invalid query: ' .  mysql_error());

					mysql_close();

					setMessage("Image added successfully", "good");
				} else {
					setMessage("Invalid file location","bad");
				}
			} else {
				setMessage("Invalid name", "bad");
			}

			gotopage($_SERVER['PHP_SELF']);
		} else {

			global $username;
			global $password;
			global $database;
			global $dbhost;

			mysql_connect($dbhost,$username,$password);
			@mysql_select_db($database) or die( "Unable to select database");

			$query = "SELECT name, image, fullvirt, typeid, memory FROM images WHERE imageid=$imageid";
			$result = mysql_query($query);

			if (!$result) {
				die ('Invalid query: ' . mysql_error());
			}

			if (mysql_num_rows($result) != 1) {
				mysql_close();
				setMessage("Invalid image id", "bad");

				gotopage($_SERVER['PHP_SELF']);
			}

			$row = mysql_fetch_array($result);
			$name = $row['name'];
			$image = $row['image'];
			$memory = $row['memory'];
			$fullvirt = $row['fullvirt'];
			$typeid = $row['typeid'];

?>
<html>
<head><title>Eipen - Edit Image</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("edit");
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?id=".$imageid;?>">
<ul id="form">
<li class="formlabel">Image Name</li>
<li class="forminput"><input type="text" size="15" maxlength="75" name="name" value="<?php print $name; ?>" /></li>
<li class="formsplit"></li>
<li class="formlabel">Image Location</li>
<li class="forminput"><input type="text" size="15" maxlength="255" name="image" value="<?php print $image; ?>" />
<img src="../img/help.png" alt="Relative to images_dir" title="Relative to images_dir" /></li>
<li class="formsplit"></li>
<li class="formlabel">Memory Amount</li>
<li class="forminput"><input type="text" size="3" maxlength="5" name="memory" value="<?php print $memory; ?>" />
<img src="../img/help.png" alt="In megs" title="In megs" /></li>
<li class="formsplit"></li>
<li class="formlabel">Image Type</li>
<li class="forminput"><select name="imagetype">
<?php
	$query = "SELECT typeid, typename FROM image_type ORDER BY typeid";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}


	while($row = mysql_fetch_array($result)) {
		print "<option ";
		if ($row['typeid'] == $typeid) { print "selected "; }
			print "value=\"".$row['typeid']."\">".$row['typename']."</option>\n";
		}
?>
</select></li>
<li class="formsplit"></li>
<li class="formlabel">Is this fullvirt?</li>
<li class="forminput"><input type="checkbox" name="fullvirt" value="1" <?php if ($fullvirt == 1) { print "checked "; } ?>/></li>
<li class="formsplit"></li>
</ul>
<input type="submit" value="Edit Image" name="submit" />
</form>

<center><div id="status_box">
<a href="editdiskimage.php?id=<?php print $imageid; ?>"><b>Edit Disk Image</b></a>
</div></center><br />

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
<head><title>Eipen - Edit Image</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("edit");
	print_messages();
?>
<div id="header">
<ul><li>Image name<div id="status_right_header">Link</div></li></ul>
</div>
<div id="status_sub">
<ul>
<?php
	global $username;
	global $password;
	global $database;
	global $dbhost;

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT imageid, name FROM images WHERE imageid >= 0 ORDER BY name";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) == 0 ) {
		print "<li><center>There are no images in the database</center></li>\n";
	} else {
		while($row = mysql_fetch_array($result)) {
			$name = $row['name'];
			$imageid = $row['imageid'];

			print "<li>$name <div id=\"status_right\"><div id=\"status_box\"><a href=\"".$_SERVER['PHP_SELF']."?id=$imageid\">".
				"Edit Image</a></div></div></li>\n";
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
