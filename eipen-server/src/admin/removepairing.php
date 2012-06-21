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

	if (isset($_GET['cid']) && isset($_GET['pid']) && isset($_POST['submit'])) {
		$pairid = (int)($_GET['pid']);
		$courseid = (int)($_GET['cid']);

		mysql_connect($dbhost,$username,$password);
		@mysql_select_db($database) or die( "Unable to select database");

		if ($pairid < 0) {
			setMessage("Invalid pairid given", "bad");              
			mysql_close();
	
			gotopage("editcourse.php?id=$courseid");
		}

		$query = "DELETE FROM course_images WHERE course_imageid=$pairid";
		$result = mysql_query($query);

		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		mysql_close();
		setMessage("Pair removed sucessfully.", "good");

		gotopage("editcourse.php?id=$courseid");
	} elseif (isset($_GET['cid']) && isset($_GET['pid'])) {
		$courseid = (int)($_GET['cid']);
		$pairid = (int)($_GET['pid']);

		mysql_connect($dbhost,$username,$password);
		@mysql_select_db($database) or die( "Unable to select database");

		if ($courseid < 0) {
			setMessage("Invalid courseid given", "bad");
			mysql_close();

			gotopage("editcourse.php");
		}

		if ($pairid < 0) {
			setMessage("Invalid pairid given", "bad");
			mysql_close();

			gotopage("editcourse.php?id=$courseid");
		}

		$query = "SELECT images.name AS imagename, courses.name AS coursename, course_images.name AS pairname ".
			"FROM courses, images, course_images ".
			"WHERE courses.courseid = course_images.courseid AND images.imageid = course_images.imageid ".
			"AND course_images.course_imageid = $pairid";
		$result = mysql_query($query);

		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
																									
		if (mysql_num_rows($result) == 0) {                                                                                                
			setMessage("Invalid pairid given", "bad");
			mysql_close();

			gotopage("editcourse.php?id=$courseid");
		}

		$row = mysql_fetch_array($result);
		$coursename = $row['coursename'];
		$imagename = $row['imagename'];
		$pairname = $row['pairname'];

?>
<html>
<head><title>Eipen - Delete Image Pairing</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix" >
<?php
	nav();
	subnav("delete");
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?cid=$courseid&pid=$pairid";?>">
<ul id="form">
<b>Course Name: </b><?php print $coursename; ?> <br />
<b>Image name: </b><?php print $imagename; ?> <br />
<b>Pairing Name: </b><?php print $pairname; ?> <br />
<input type="submit" value="Remove Pairing" name="submit" />
</form>
</ul>
<?php
	footer();
?>
</div> <!-- end wrapper -->
</body>
</html>
<?php
	} else {
		gotopage("editcourse.php");
	}
?>
