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

	if (isset($_POST['submit'])) {
		global $username;
		global $password;
		global $database;
		global $dbhost;

		$name = $_POST['name'];
		$desc = $_POST['desc'];
		$enabled = (int)($_POST['enabled']);
		$xen = (int)($_POST['xen']);
		$samemachine = (int)($_POST['samemachine']);
		$length = (int)($_POST['length']);
		if ($xen == 0) {
			$memory = (int)($_POST['memory']);
			$fullvirt = 0;
			if (isset($_POST['fullvirt'])) {
				$fullvirt = (int)($_POST['fullvirt']);
			}
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

				mysql_query("INSERT INTO courses (name, coursedesc, enabled, xen, length, samemachine, memory, fullvirt, profileid) VALUES ".
					"(\"$name\", \"$desc\", $enabled, $xen, $length, $samemachine, $memory, $fullvirt, $profileid)")
					or die ('Invalid query: ' .  mysql_error());

				mysql_close();

				setMessage("Course added successfully", "good");
			} else {
				setMessage("Invalid description of course","bad");
			}
		} else {
			setMessage("Invalid name of course", "bad");
		}
	} else {
		setMessage("Invalid length of course","bad");
	}
		gotopage($_SERVER['PHP_SELF']);
	} else {
?>
<html>
<head><title>Eipen - Add Course</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("add");
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<ul id="form">
<li class="formlabel">Course Name</li>
<li class="forminput"><input type="text" size="20" maxlength="20" name="name" />
<img src="../img/help.png" alt="Ex. FOO133" title="Ex. FOO133" /></li>
<li class="formsplit"></li>
<li class="formlabel">Description</li>
<li class="forminput"><input type="text" size="20" maxlength="255" name="desc" /></li>
<li class="formsplit"></li>
<li class="formlabel">Enabled?</li>
<li class="forminput"><input type="checkbox" name="enabled" value="1" checked></li>
<li class="formsplit"></li>
<li class="formlabel">Course Length</li>
<li class="forminput"><input type="text" size="3" maxlength="5" name="length" />
<img src="../img/help.png" alt="In minutes" title="In minutes" /></li>
<li class="formsplit"></li>
<li class="formlabel">Is a xen course?</li>
<li class="forminput"><input type="checkbox" name="xen" value="1" checked></li>
<li class="formsplit"></li>
<li class="formlabel">All images run on same machine?</li>
<li class="forminput"><input type="checkbox" name="samemachine" value="1" checked></li>
<li class="formsplit"></li>
<li class="formlabel">Memory required</li>
<li class="forminput"><input type="text" size="4" maxlength="6" name="memory" >
<img src="../img/help.png" alt="In megs.  Only if not a xen course" title="In megs.  Only if not a xen course" /></li>
<li class="formsplit"></li>
<li class="formlabel">Full Virt host required?</li>
<li class="forminput"><input type="checkbox" name="fullvirt" value="1" unchecked>
<img src="../img/help.png" alt="Only if not a xen course" title="Only if not a xen course" /></li>
<li class="formsplit"></li>
<li class="formlabel">Kickstart Profile</li>
<li class="forminput"><select name="profileid">
<option selected value="-1">None</option>
<?php
	global $username;
	global $password;
	global $database;
	global $dbhost;

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT profileid, name FROM ks_profiles ORDER BY profileid";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	while($row = mysql_fetch_array($result)) {
		print "<option value=\"".$row['profileid']."\">".$row['name']."</option>\n";
	}

	mysql_close();
?>
</select>
<img src="../img/help.png" alt="Only if not a xen course" title="Only if not a xen course" /></li>
<li class="formsplit"></li>
</ul>
<input type="submit" value="Add Course" name="submit" />
</form>
<?php   
	footer();
?>
</div> <!-- end wrapper -->
</body>
</html>
<?php
}  //end else
?>
