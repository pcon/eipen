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
		$profileid = (int)($_GET['id']);

		global $username;
		global $password;
		global $database;
		global $dbhost;

		if ($profileid < 0) {
			setMessage("Invalid profileid", "bad");
			gotopage($_SERVER['PHP_SELF']);
		}

		if (isset($_POST['submit'])) {
			$name = $_POST['name'];
			$label = $_POST['label'];
			if (ereg('^[A-Za-z0-9_ -]{1,}$',$name)) { 				//check name
				if (ereg('^[A-Za-z0-9_-]{1,}$',$label)) { 		//check label
					mysql_connect($dbhost,$username,$password);
					@mysql_select_db($database) or die( "Unable to select database");

					$query = "SELECT profileid FROM ks_profiles WHERE profileid = $profileid";
					$result = mysql_query($query);

					if (!$result) {
						die ('Invalid query: ' . mysql_error());
					}

					if (mysql_num_rows($result) != 1) {
						mysql_close();
						setMessage("Invalid profileid", "bad");

						gotopage($_SERVER['PHP_SELF']);
					}

					mysql_query("UPDATE ks_profiles SET name=\"$name\", label=\"$label\" ".
						"WHERE profileid=$profileid")
						or die ('Invalid query: ' .  mysql_error());

					mysql_close();

					setMessage("KS Profile saved successfully", "good");
				} else {
					setMessage("Invalid KS Profile label", "bad");
				}
			} else {
				setMessage("Invalid KS Profile name", "bad");
			}
			gotopage($_SERVER['PHP_SELF']);
		} else {

			mysql_connect($dbhost,$username,$password);
			@mysql_select_db($database) or die( "Unable to select database");

			$query = "SELECT name, label FROM ks_profiles WHERE profileid = $profileid";
			$result = mysql_query($query);

			if (!$result) {
				die ('Invalid query: ' . mysql_error());
			}

			mysql_close();

			if (mysql_num_rows($result) != 1) {
				setMessage("Invalid profileid", "bad");
				gotopage($_SERVER['PHP_SELF']);
			}

		$row = mysql_fetch_array($result);

		$name = $row['name'];
		$label = $row['label'];

?>
<html>
<head><title>Eipen - Edit Profile</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("edit");
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?id=".$profileid;?>">
<ul id="form">
<li class="formlabel">Profile Name</li>
<li class="forminput"><input type="text" size="20" maxlength="255" name="name" value="<?php print $name; ?>" />
<img src="../img/help.png" alt="Ex. Default RHEL5 Profile" title="Default RHEL5 Profile" /></li>
<li class="formsplit"></li>
<li class="formlabel">Label</li>
<li class="forminput"><input type="text" size="20" maxlength="255" name="label" value="<?php print $label; ?>" />
<img src="../img/help.png" alt="The profile name for cobbler" title="The profile name for cobbler" /></li>
<li class="formsplit"></li>
</ul>
<input type="submit" value="Save Profile" name="submit" />
</form>
</div>

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
<head><title>Eipen - Edit Profile</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("edit");
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

			print "<li>$name <div id=\"status_right\"><div id=\"status_box\"><a href=\"".$_SERVER['PHP_SELF']."?id=$profileid\">".
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
