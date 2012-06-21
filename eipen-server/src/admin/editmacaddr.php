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
		$macid = (int)($_GET['id']);

		if ($macid < 0) {
			setMessage("Invalid macid", "bad");
			gotopage($_SERVER['PHP_SELF']);
		}

		if (isset($_POST['submit'])) {
			global $username;
			global $password;
			global $database;
			global $dbhost;

			$ipaddr = $_POST['ipaddr'];
			$macaddr = $_POST['macaddr'];
			$dnsname = $_POST['dnsname'];

			if (ereg('^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$', $ipaddr)) {    //checkip addr
				if (ereg('^[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}', $macaddr)) {
					if (eregi('^[0-9a-z.-]{1,}$', $dnsname)) {
						mysql_connect($dbhost,$username,$password);
						@mysql_select_db($database) or die( "Unable to select database");

						$query = "SELECT macid FROM macaddr WHERE macid=$macid";
						$result = mysql_query($query);

						if (!$result) {
							die ('Invalid query: ' . mysql_error());
						}

						if (mysql_num_rows($result) != 1) {
							mysql_close();
							setMessage("Invalid macid", "bad");
							gotopage($_SERVER['PHP_SELF']);
						}

						mysql_query("UPDATE macaddr SET addr=\"$macaddr\", ipaddr=\"$ipaddr\", name=\"$dnsname\" WHERE macid=$macid") 
							or die ('Invalid query: ' .  mysql_error());
						mysql_close();

						setMessage("Mac address added successfully", "good");
					} else {
						setMessage("Invalid DNS name", "bad");
					}
				} else {
					setMessage("Invalid mac address", "bad");
				}
			} else {
				setMessage("Invalid IP address", "bad");
			}

			gotopage($_SERVER['PHP_SELF']);
		} else {

			global $username;
			global $password;
			global $database;
			global $dbhost;

			mysql_connect($dbhost,$username,$password);
			@mysql_select_db($database) or die( "Unable to select database");

			$query = "SELECT addr, ipaddr, name FROM macaddr WHERE macid=$macid";
			$result = mysql_query($query);

			if (!$result) {
				die ('Invalid query: ' . mysql_error());
			}

			if (mysql_num_rows($result) != 1) {
				setMessage("Invalid macid", "bad");
				gotopage($_SERVER['PHP_SELF']);
			}

			$row = mysql_fetch_array($result);
			$macaddr = $row['addr'];
			$ipaddr = $row['ipaddr'];
			$dnsname = $row['name'];
?>
<html>
<head><title>Eipen - Edit Mac Address</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("edit");
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?id=$macid";?>">
<ul id="form">
<li class="formlabel">Mac Addr</li>
<li class="forminput"><input type="text" size="17" maxlength="17" name="macaddr" value="<?php print $macaddr; ?>" />
<img src="../img/help.png" alt="Colon notation 00:00:00:00:00:00" title="Colon notation 00:00:00:00:00:00" /></li>
<li class="formsplit"></li>
<li class="formlabel">IP Addr</li>
<li class="forminput"><input type="text" size="17" maxlength="15" name="ipaddr" value="<?php print $ipaddr; ?>" />
<img src="../img/help.png" alt="Dotted notation 10.10.10.10" title="Dotted notation 10.10.10.10" /></li>
<li class="formsplit"></li>
<li class="formlabel">DNS Name</li>
<li class="forminput"><input type="text" size="17" maxlength="150" name="dnsname" value="<?php print $dnsname; ?>" /></li>
<li class="formsplit"></li>
</ul>
<input type="submit" value="Edit Mac Address" name="submit" />
</form>
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
<head><title>Eipen - Edit Macaddr</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("edit");
	print_messages();
?>
<div id="header">
<ul><li>Mac Address<div id="status_right_header">Link</div></li></ul>
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

			print "<li>$addr - $ip <div id=\"status_right\"><div id=\"status_box\"><a href=\"".$_SERVER['PHP_SELF']."?id=$macid\">".
				"Edit Macaddr</a></div></div></li>\n";
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
