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
		$macid = $_GET['id'];

		mysql_connect($dbhost,$username,$password);
		@mysql_select_db($database) or die( "Unable to select database");

		if ($macid < 0) {
			setMessage("Invalid macid given", "bad");
			mysql_close();

			gotopage($_SERVER['PHP_SELF']);
		}

		$query = "DELETE FROM macaddr WHERE macid=$macid";
		$result = mysql_query($query);

		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		mysql_close();
		setMessage("Mac address deleted sucessfully.", "good");

		gotopage($_SERVER['PHP_SELF']);
	} elseif (isset($_GET['id'])) {
		$macid = $_GET['id'];

		mysql_connect($dbhost,$username,$password);
		@mysql_select_db($database) or die( "Unable to select database");

		if ($macid < 0) {
			setMessage("Invalid macid given", "bad");
			mysql_close();

			gotopage($_SERVER['PHP_SELF']);
		}

		$query = "SELECT addr, ipaddr, name FROM macaddr WHERE macid = $macid";
		$result = mysql_query($query);

		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		if (mysql_num_rows($result) == 0) {
			setMessage("Invalid macid given", "bad");
			mysql_close();

			gotopage($_SERVER['PHP_SELF']);
		}

		$row = mysql_fetch_array($result);
		$addr = $row['addr'];
		$ip = $row['ipaddr'];
		$name = $row['name'];

?>
<html>
<head><title>Eipen - Delete Macaddr</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix" >
<?php
	nav();
	subnav("delete");
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?id=$macid";?>">
<ul id="form">
<li class="formlabel"><b>Mac Address: </b></li>
<li class="formdata"><?php print $addr; ?></li>
<li class="formsplit"></li>
<li class="formlabel"><b>IP Address: </b></li>
<li class="formdata"><?php print $ip; ?></li>
<li class="formsplit"></li>
<li class="formlabel"><b>DNS Name: </b></li>
<li class="formdata"><?php print $name; ?></li>
<li class="formsplit"></li>
</ul>
<input type="submit" value="Delete macaddr" name="submit" />
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
<head><title>Eipen - Delete Macaddr</title>
<style>@import url(defaultstyle.css);</style></head>
<body>  
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("delete");
	print_messages();
?>
<div id="header">
<ul><li>Mac Address<div id="status_right_header">Link</div></li></ul>
</div>
<div id="status_sub">
<ul>
<?php
	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT macid, addr, ipaddr FROM macaddr ORDER BY addr";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) == 0 ) {
		print "<li><center>There are no mac addresses in the database</center></li>\n";
	} else {
		while($row = mysql_fetch_array($result)) {
			$addr = $row['addr'];
			$macid = $row['macid'];
			$ip = $row['ipaddr'];

			print "<li>$addr - $ip <div id=\"status_right\"><div id=\"status_box\"><a href=\"deletemacaddr.php?id=$macid\">".
				"Delete Macaddr</a></div></div></li>\n";
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
