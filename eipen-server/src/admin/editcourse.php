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
			gotopage($_SERVER['PHP_SELF']);
		}

		if (isset($_POST['submit'])) {
			$name = $_POST['name'];
			$desc = $_POST['desc'];
			$enabled = (int)($_POST['enabled']);
			$xen = (int)($_POST['xen']);
			$length = (int)($_POST['length']);
			$samemachine = (int)($_POST['samemachine']);

			if ($xen == 0) {
				$memory = (int)($_POST['memory']);
				$fullvirt = (int)($_POST['fullvirt']);
				$profileid = (int)($_POST['profileid']);
			} else {
				$memory = 0;
				$fullvirt = 0;
				$profileid = -1;
			}

		if ($length > 0) { 				//check length
			if (ereg('^[A-Za-z0-9_]{1,}$',$name)) { 		//check name
				if (ereg('^[^\;\'\"]{1,}$', $desc)) {	//check desc
					mysql_connect($dbhost,$username,$password);
					@mysql_select_db($database) or die( "Unable to select database");

					$query = "SELECT courseid FROM courses WHERE courseid = $courseid";
					$result = mysql_query($query);

					if (!$result) {
						die ('Invalid query: ' . mysql_error());
					}

					if (mysql_num_rows($result) != 1) {
						mysql_close();
						setMessage("Invalid courseid", "bad");

						gotopage($_SERVER['PHP_SELF']);
					}

					mysql_query("UPDATE courses SET name=\"$name\", coursedesc=\"$desc\", ".
						"enabled=$enabled, xen=$xen, length=$length, samemachine=$samemachine, ".
						"memory=$memory, fullvirt=$fullvirt, profileid=$profileid ".
						"WHERE courseid=$courseid")
						or die ('Invalid query: ' .  mysql_error());

					mysql_close();

					setMessage("Course saved successfully", "good");
				} else {
					setMessage("Invalid description of course", "bad");
				}
			} else {
				setMessage("Invalid name of course", "bad");
			}
		} else {
			setMessage("Invalid length of course", "bad");
		}
		gotopage($_SERVER['PHP_SELF']);
	} else {

		mysql_connect($dbhost,$username,$password);
		@mysql_select_db($database) or die( "Unable to select database");

		$query = "SELECT name, enabled, coursedesc, xen, length, samemachine, memory, fullvirt, profileid FROM courses WHERE courseid = $courseid";
		$result = mysql_query($query);

		if (!$result) {
			die ('Invalid query: ' . mysql_error());
		}

		mysql_close();

		if (mysql_num_rows($result) != 1) {
			setMessage("Invalid courseid", "bad");
			gotopage($_SERVER['PHP_SELF']);
		}

		$row = mysql_fetch_array($result);

		$name = $row['name'];
		$desc = $row['coursedesc'];
		$enabled = $row['enabled'];
		$xen = $row['xen'];
		$samemachine = $row['samemachine'];
		$length = $row['length'];
		$memory = $row['memory'];
		$fullvirt = $row['fullvirt'];
		$profileid = $row['profileid'];

?>
<html>
<head><title>Eipen - Edit Course</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("edit");
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?id=".$courseid;?>">
<ul id="form">
<li class="formlabel">Course Name</li>
<li class="forminput"><input type="text" size="20" maxlength="20" name="name" value="<?php print $name; ?>" />
<img src="../img/help.png" alt="Ex. FOO133" title="Ex. FOO133" /></li>
<li class="formsplit"></li>
<li class="formlabel">Description</li>
<li class="forminput"><input type="text" size="20" maxlength="255" name="desc" value="<?php print $desc; ?>" /></li>
<li class="formsplit"></li>
<li class="formlabel">Enabled?</li>
<li class="forminput"><input type="checkbox" name="enabled" value="1" <?php if($enabled == 1) { print "checked"; } ?>></li>
<li class="formsplit"></li>
<li class="formlabel">Course Length</li>
<li class="forminput"><input type="text" size="3" maxlength="5" name="length" value="<?php print $length; ?>" />
<img src="../img/help.png" alt="In minutes" title="In minutes" /></li>
<li class="formsplit"></li>
<li class="formlabel">Is a xen course?</li>
<li class="forminput"><input type="checkbox" name="xen" value="1" <?php if($xen == 1) { print "checked"; } ?>></li>
<li class="formsplit"></li>
<li class="formlabel">All images run on the same machine?</li>
<li class="forminput"><input type="checkbox" name="samemachine" value="1" <?php if($samemachine == 1) { print "checked"; } ?>></li>
<li class="formsplit"></li>
<li class="formlabel">Memory required</li>
<li class="forminput"><input type="text" size="4" maxlength="6" name="memory" value="<?php print $memory; ?>"/>
<img src="../img/help.png" alt="In megs.  Only if not a xen course" title="In megs.  Only if not a xen course" /></li>
<li class="formsplit"></li>
<li class="formlabel">Full virt capabilities required?</li>
<li class="forminput"><input type="checkbox" name="fullvirt" value="0" <?php if($fullvirt == 1) { print "checked"; } ?>/>
<img src="../img/help.png" alt="Only if not a xen course" title="Only if not a xen course" /></li>
<li class="formsplit"></li>
<li class="formlabel">Kickstart Profile</li>
<li class="forminput"><select name="profileid">
<?php
     mysql_connect($dbhost,$username,$password);
     @mysql_select_db($database) or die( "Unable to select database");

     $query = "SELECT profileid, name FROM ks_profiles ORDER BY profileid";
     $result = mysql_query($query);

     if (!$result) {
          die('Invalid query: ' . mysql_error());
     }

		print "<option ";
		if ($profileid == -1) { print "selected "; }
		print "value=\"-1\">None</option>\n";

     while($row = mysql_fetch_array($result)) {
          print "<option ";
		if ($row['profileid'] == $profileid) { print "selected "; }
		print "value=\"".$row['profileid']."\">".$row['name']."</option>\n";
     }

?>
</select>
<img src="../img/help.png" alt="Only if not a xen course" title="Only if not a xen course" /></li>
<li class="formsplit"></li>
</ul>
<input type="submit" value="Save Course" name="submit" />
</form>

<?php

if ($xen == 1) {

?>

<center><div id="status_box">
<a href="pairimagetocourse.php?id=<?php print "$courseid"; ?>"><b>Pair and image to a course</b></a>
</div></center><br />

<div id="header">
<ul><li>Image Pairing<div id="status_right_header">Link</div></li></ul>
</div>
<div id="status_sub">
<ul>
<?php
	$query = "SELECT course_images.course_imageid AS pairingid, images.name AS imagename, ".
		"course_images.name AS pairingname FROM course_images, images ".
		"WHERE images.imageid = course_images.imageid AND course_images.courseid = $courseid ".
		"ORDER BY course_images.course_imageid";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}       

	if (mysql_num_rows($result) == 0 ) {
		print "<li><center>There are no pairing for this course in the database</center></li>\n";
	} else {
		while($row = mysql_fetch_array($result)) {
			$pairingid = $row['pairingid'];
			$imagename = $row['imagename'];
			$pairingname = $row['pairingname'];

			print "<li>$imagename: $pairingname <div id=\"status_right\">".
				"<div id=\"status_box\"><a href=\"removepairing.php?cid=$courseid&pid=$pairingid\">".
				"Remove Pairing</a></div></div></li>\n";
		}
	}

	mysql_close();
?>
</ul>
</div>

<?php 
}
  
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
<head><title>Eipen - Edit Course</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("edit");
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

			print "<li>$name: $desc <div id=\"status_right\"><div id=\"status_box\"><a href=\"".$_SERVER['PHP_SELF']."?id=$courseid\">".
				"Edit Course</a></div></div></li>\n";
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
