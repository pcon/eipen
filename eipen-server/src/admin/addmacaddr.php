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

		$ipaddr = $_POST['ipaddr'];
		$macaddr = $_POST['macaddr'];
		$dnsname = $_POST['dnsname'];

		if (ereg('^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$', $ipaddr)) {    //checkip addr
			if (ereg('^[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}', $macaddr)) {
				if (eregi('^[0-9a-z.-]{1,}$', $dnsname)) {
					mysql_connect($dbhost,$username,$password);
					@mysql_select_db($database) or die( "Unable to select database");

					mysql_query("INSERT INTO macaddr (addr, ipaddr, name) VALUES (\"$macaddr\", \"$ipaddr\", \"$dnsname\")") 
						or die ('Invalid query: ' .  mysql_error());

					mysql_close();

					setMessage("Mac address added successfully","good");
				} else {
					setMessage("Invalid DNS name","bad");
				}
			} else {
				setMessage("Invalid mac address","bad");
			}
		} else {
			setMessage("Invalid IP address", "bad");
		}

		gotopage($_SERVER['PHP_SELF']);
	} else {
?>
<html>
<head><title>Eipen - Add Mac Address</title>
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
<li class="formlabel">Mac Addr</li>
<li class="forminput"><input type="text" size="17" maxlength="17" name="macaddr" />
<img src="../img/help.png" alt="Colon notation 00:00:00:00:00:00" title="Colon notation 00:00:00:00:00:00" /></li>
<li class="formsplit"></li>
<li class="formlabel">IP Addr</li>
<li class="forminput"><input type="text" size="17" maxlength="15" name="ipaddr" />
<img src="../img/help.png" alt="Dotted notation 10.10.10.10" title="Dotted notation 10.10.10.10" /></li>
<li class="formsplit"></li>
<li class="formlabel">DNS Name</li>
<li class="forminput"><input type="text" size="17" maxlength="150" name="dnsname" /></li>
<li class="formsplit"></li>
</ul>
<input type="submit" value="Add Mac Address" name="submit" />
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
