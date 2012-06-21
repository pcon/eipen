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

	if (isset($_POST['submit'])) {
		$name = $_POST['name'];
		$image = $_POST['image'];
		$memory = (int)($_POST['memory']);
		$typeid = (int)($_POST['imagetype']);
		$fullvirt = (int)($_POST['fullvirt']);

		if (ereg('^[A-Za-z0-9\(\)\. -]{1,}$', $name)) {    //check name
			if (ereg('^[A-Za-z0-9_\./-]{1,}$',$image)) {    //check image name
				if ($memory > 0) {
					mysql_connect($dbhost,$username,$password);
					@mysql_select_db($database) or die( "Unable to select database");

					mysql_query("INSERT INTO images (name, image, memory, fullvirt, typeid) VALUES ".
						"(\"$name\", \"$image\", $memory, $fullvirt, $typeid)")
						or die ('Invalid query: ' .  mysql_error());

					mysql_close();

					setMessage("Base image added successfully","good");
				} else {  
					setMessage("Invalid memory amount", "bad");
				}
			} else {  
				setMessage("Invalid file location", "bad");
			}
		} else {
			setMessage("Invalid name","bad");
		}

		gotopage($_SERVER['PHP_SELF']);
	} else {
?>

<html>
<head><title>Eipen - Add Image</title>
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
<li class="formlabel">Image Name</li>
<li class="forminput"><input type="text" size="15" maxlength="75" name="name" /></li>
<li class="formsplit"></li>
<li class="formlabel">Image Location</li>
<li class="forminput"><input type="text" size="15" maxlength="255" name="image" />
<img src="../img/help.png" alt="Relative to images_dir" title="Relative to images_dir" /></li>
<li class="formsplit"></li>
<li class="formlabel">Memory Amount</li>
<li class="forminput"><input type="text" size="3" maxlength="5" name="memory" />
<img src="../img/help.png" alt="In megs" title="In megs" /></li>
<li class="formsplit"></li>
<li class="formlabel">Image Type</li>
<li class="forminput"><select name="imagetype">
<?php
	global $username;
	global $password;
	global $database;
	global $dbhost;

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT typeid, typename FROM image_type ORDER BY typeid";

	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	while($row = mysql_fetch_array($result)) {
		print "<option value=\"".$row['typeid']."\">".$row['typename']."</option>\n";
	}
?>
</select></li>
<li class="formsplit"></li>
<li class="formlabel">Is this fullvirt?</li>
<li class="forminput"><input type="checkbox" name="fullvirt" value="1" /></li>
<li class="formsplit"></li>
</ul>
<input type="submit" value="Add Image" name="submit" />
</form>
<?php   
	footer();
?>
</div><!-- end wrapper -->
</body>
</html>
<?php
}
?>
