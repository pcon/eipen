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
		$courseid = (int)($_GET['id']);

		global $username;
		global $password;
		global $database;
		global $dbhost;

		if ($courseid < 0) {
			setMessage("Invalid courseid", "bad");
			gotopage("editcourse.php");
		}

		if (isset($_POST['submit'])) {
			$name = $_POST['name'];
			$imageid = (int)($_POST['image']);

			if (ereg('^[A-Za-z0-9_]{1,}$',$name)) {
				mysql_connect($dbhost,$username,$password);
				@mysql_select_db($database) or die( "Unable to select database");

				mysql_query("INSERT INTO course_images (courseid, imageid, name) VALUES ".
					"($courseid, $imageid, \"$name\")")
					or die ('Invalid query: ' .  mysql_error());

				mysql_close();

				setMessage("Course added successfully", "good");
				gotopage("editcourse.php?id=$courseid");
				return;
			} else {
				setMessage("Invalid name of pairing", "bad");
				gotopage("editcourse.php?id=$courseid");
				return;
			}
		} else {
			global $username;
			global $password;
			global $database;
			global $dbhost;

			mysql_connect($dbhost,$username,$password);
			@mysql_select_db($database) or die( "Unable to select database");

			$query = "SELECT name FROM courses WHERE courseid=$courseid";
			$result = mysql_query($query);

			if (!$result) {
				die('Invalid query: ' . mysql_error());
			}

			if (mysql_num_rows($result) != 1) {
				setMessage("Invalid courseid", "bad");
				gotopage("editcourse.php");
				return;
			}

			$row = mysql_fetch_array($result);
			$coursename = $row['name'];

?>

<html>
<head><title>Eipen - Pair Course to Image</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("edit");
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?id=$courseid";?>">
<ul id="form">
<li class="formlabel">Course</li>
<li class="formdata"><b><?php print $coursename; ?></b></li>
<li class="formsplit"></li>
<li class="formlabel">Pairing Name</li>
<li class="forminput"><input type="text" size="15" maxlength="100" name="name" />
<img src="../img/help.png" alt="Ex. Server" title="Ex. Server" /></li>
<li class="formsplit"></li>
<li class="formlabel">Image</li>
<li class="forminput"><select name="image">
<?php
	$query = "SELECT imageid, name FROM images WHERE imageid >= 0 ORDER BY name";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	while($row = mysql_fetch_array($result)) {
		print "<option value=\"".$row['imageid']."\">".$row['name']."</option>\n";
	}
?>
</select></li>
</ul>
<input type="submit" value="Pair Image" name="submit" />
</form>
<?php   
	footer();
?>
</div> <!-- end wrapper -->
</body>
</html>

<?php
		}
	} else {
		gotopage("editcourse.php");
		return;
	}
?>
