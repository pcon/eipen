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
		$label = $_POST['label'];

		if (ereg('^[A-Za-z0-9_ -]{1,}$',$name)) { 		//check name
			if (ereg('^[A-Za-z0-9_-]{1,}$',$label)) { 		//check label
				mysql_connect($dbhost,$username,$password);
				@mysql_select_db($database) or die( "Unable to select database");

				mysql_query("INSERT INTO ks_profiles (name, label) VALUES (\"$name\", \"$label\")")
					or die ('Invalid query: ' .  mysql_error());

				mysql_close();

				setMessage("KS Profile added successfully", "good");
			} else {
				setMessage("Invalid label of profile", "bad");
			}
		} else {
			setMessage("Invalid name of profile", "bad");
		}
		gotopage($_SERVER['PHP_SELF']);
	} else {
?>
<html>
<head><title>Eipen - Add Profile</title>
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
<li class="formlabel">Profile Name</li>
<li class="forminput"><input type="text" size="20" maxlength="255" name="name" />
<img src="../img/help.png" alt="Ex. Default RHEL5 Profile" title="Ex. Default RHEL5 Profile" /></li>
<li class="formsplit"></li>
<li class="formlabel">Label</li>
<li class="forminput"><input type="text" size="20" maxlength="255" name="label" />
<img src="../img/help.png" alt="The cobbler profile name" title="The cobbler profilename" /></li>
<li class="formsplit"></li>
</ul>
<input type="submit" value="Add Profile" name="submit" />
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
