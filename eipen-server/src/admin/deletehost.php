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
		$hostid = $_GET['id'];

		mysql_connect($dbhost,$username,$password);
		@mysql_select_db($database) or die( "Unable to select database");

		if ($hostid < 0) {
			setMessage("Invalid hostid given", "bad");
			mysql_close();

			gotopage($_SERVER['PHP_SELF']);
		}

		$query = "DELETE FROM host WHERE hostid=$hostid";
		$result = mysql_query($query);

		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		mysql_close();

		setMessage("Host deleted sucessfully.", "good");
		gotopage($_SERVER['PHP_SELF']);

	} elseif (isset($_GET['id'])) {
		$hostid = $_GET['id'];

		mysql_connect($dbhost,$username,$password);
		@mysql_select_db($database) or die( "Unable to select database");

		if ($hostid < 0) {
			setMessage("Invalid hostid given", "bad");

			mysql_close();

			gotopage($_SERVER['PHP_SELF']);
		}

		$query = "SELECT ipaddr, xenslots FROM host WHERE hostid = $hostid";
		$result = mysql_query($query);

		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		if (mysql_num_rows($result) == 0) {
			setMessage("Invalid hostid given", "bad");

			mysql_close();

			gotopage($_SERVER['PHP_SELF']);
		}

		$row = mysql_fetch_array($result);
		$ipaddr = $row['ipaddr'];
		$xen = $row['xenslots'];

?>
<html>
<head><title>Eipen - Delete Host</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix" >
<?php
	nav();
	subnav("delete");
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?id=$hostid";?>">
<ul id="form">
<li class="formlabel"><b>IP Address: </b></li>
<li class="formdata"><?php print $ipaddr; ?></li>
<li class="formsplit"></li>
<li class="formlabel"><b>Xen Slots: </b></li>
<li class="formdata"><?php print $xen; ?></li>
<li class="formsplit"></li>
</ul>
<input type="submit" value="Delete host" name="submit" />
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
<head><title>Eipen - Delete Host</title>
<style>@import url(defaultstyle.css);</style></head>
<body>  
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("delete");
	print_messages();
?>
<div id="header">
<ul><li>Host ip<div id="status_right_header">Link</div></li></ul>
</div>
<div id="status_sub">
<ul>
<?php
	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT hostid, ipaddr FROM host ORDER BY ipaddr";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) == 0 ) {
		print "<li><center>There are no hosts in the database</center></li>\n";
	} else {
		while($row = mysql_fetch_array($result)) {
			$hostid = $row['hostid'];
			$ip = $row['ipaddr'];

			print "<li>$ip <div id=\"status_right\"><div id=\"status_box\"><a href=\"deletehost.php?id=$hostid\">".
				"Delete Host</a></div></div></li>\n";
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
