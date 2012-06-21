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
		$hostid = $_GET['id'];

		if ($hostid < 0) {
			setMessage("Invalid hostid", "bad");
			gotopage($_SERVER['PHP_SELF']);
		}

		if (isset($_POST['submit'])) {
			global $username;
			global $password;
			global $database;
			global $dbhost;

			$ipaddr = $_POST['ipaddr'];
			$slots = $_POST['xencount'];
			$memory = $_POST['memory'];
			$fence_command = $_POST['fence_command'];
			$fullvirt = (int)($_POST['fullvirt']);

			if (ereg('^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$', $ipaddr)) {    //checkip addr
				if ($memory > 0) {
					if ($slots >= 0 && $slots < 100) {
						mysql_connect($dbhost,$username,$password);
						@mysql_select_db($database) or die( "Unable to select database");

						$query="SELECT hostid FROM host WHERE hostid=$hostid";
						$result = mysql_query($query);

						if (!$result) { 
							die('Invalid query: ' . mysql_error());
						}

						if (mysql_num_rows($result) != 1) {
							mysql_close();
							setMessage("Invalid hostid", "bad");

							gotopage($_SERVER['PHP_SELF']);
						}

						mysql_query("UPDATE host SET ipaddr=\"$ipaddr\", memory=$memory, xenslots=$slots, ".
							"fullvirt=$fullvirt, fence_command=\"$fence_command\" WHERE hostid=$hostid") 
							or die ('Invalid query: ' .  mysql_error());

						mysql_close();

						setMessage("Host added successfully", "good");
					} else {
						setMessage("Invalid number of xen slots", "bad");
					}
				} else {
					setMessage("Invalid amount of memory", "bad");
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

			$query="SELECT hostid FROM host WHERE hostid=$hostid";
			$result = mysql_query($query);

			if (!$result) { 
				die('Invalid query: ' . mysql_error());
			}

			if (mysql_num_rows($result) != 1) {
				mysql_close();
				setMessage("Invalid hostid", "bad");

				gotopage($_SERVER['PHP_SELF']);
			}

			$query="SELECT ipaddr, memory, xenslots, fullvirt, fence_command FROM host WHERE hostid=$hostid";
			$result = mysql_query($query);

			if (!$result) { 
				die('Invalid query: ' . mysql_error());
			}

			if (mysql_num_rows($result) != 1) {
				mysql_close();
				setMessage("Invalid hostid", "bad");

				gotopage($_SERVER['PHP_SELF']);
			}

			$row = mysql_fetch_array($result);
			$ipaddr = $row['ipaddr'];
			$memory = $row['memory'];
			$slots = $row['xenslots'];
			$fence_command = $row['fence_command'];
			$fullvirt = $row['fullvirt'];

?>
<html>
<head><title>Eipen - Edit Host</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("edit");
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?id=$hostid";?>">
<ul id="form">
<li class="formlabel">IP Addr</li>
<li class="forminput"><input type="text" size="15" maxlength="15" name="ipaddr" value="<?php print $ipaddr; ?>" /> 
<img src="../img/help.png" alt="Dotted notation 10.10.10.10" title="Dotted notation 10.10.10.10" /></li>
<li class="formsplit"></li>
<li class="formlabel">Allocatable Memory</li>
<li class="forminput"><input type="text" size="4" maxlength="6" name="memory" value="<?php print $memory; ?>" />
<img src="../img/help.png" alt="In megs" title="In megs" /></li>
<li class="formsplit"></li>
<li class="formlabel">Xen Slots</li>
<li class="forminput"><input type="text" size="2" maxlength="3" name="xencount" value="<?php print $slots; ?>" />
<img src="../img/help.png" alt="If a baremetal machine, use 0" title="If a baremetal machine use 0" /></li>
<li class="formsplit"></li>
<li class="formlabel">Fence Command</li>
<li class="forminput"><input type="text" size="15" maxlength="255" name="fence_command" value="<?php print $fence_command; ?>" />
<img src="../img/help.png" alt="Only if a baremetal machine" title="Only if a baremetal machine" /></li>
<li class="formsplit"></li>
<li class="formlabel">Is this fullvirt?</li>
<li class="forminput"><input type="checkbox" name="fullvirt" value="1" <?php if ($fullvirt == 1) { print "checked "; } ?>/></li>
<li class="formsplit"></li>
</ul>
<input type="submit" value="Edit Host" name="submit" />
</form>
<?php
	footer();
?>
</div> <!-- end wrapper -->
</body>
</html>
<?php
		}
	} else {
?>
<html>
<head><title>Eipen - Delete Host</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("edit");
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

			print "<li>$ip <div id=\"status_right\"><div id=\"status_box\"><a href=\"".$_SERVER['PHP_SELF']."?id=$hostid\">".
				"Edit Host</a></div></div></li>\n";
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
