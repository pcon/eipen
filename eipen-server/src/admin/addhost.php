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
		$slots = $_POST['xencount'];
		$memory = $_POST['memory'];
		$fullvirt = (int)($_POST['fullvirt']);
		$fence_command = $_POST['fence_command'];

		if (ereg('^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$', $ipaddr)) {    //checkip addr
			if ($memory > 0) {
				if ($slots >= 0 && $slots < 100) {
					mysql_connect($dbhost,$username,$password);
					@mysql_select_db($database) or die( "Unable to select database");

					mysql_query("INSERT INTO host (ipaddr, memory, xenslots, fullvirt, fence_command) VALUES ".
						"(\"$ipaddr\", $memory, $slots, $fullvirt, \"$fence_command\")")
						or die ('Invalid query: ' .  mysql_error());

					mysql_close();

					setMessage("Host added successfully", "good");
				} else {
					setMessage("Invalid number of xen slots", "bad");
				}
			} else {
				setMessage("Invalid amount of memory","bad");
			}
		} else {
			setMessage("Invalid IP address","bad");
		}

		gotopage($_SERVER['PHP_SELF']);
	} else {
?>
<html>
<head><title>Eipen - Add Host</title>
<style>@import url(defaultstyle.css);</style>
</head>
<body>
<div id="toolTip"></div>
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("add");
	print_messages();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<ul id="form">
<li class="formlabel">IP Addr</li>
<li class="forminput"><input type="text" size="15" maxlength="15" name="ipaddr" />
<img src="../img/help.png" alt="Using dotted notation (10.10.10.10)" title="Using dotted notation (10.10.10.10)" /></li>
<li class="formsplit"></li>
<li class="formlabel">Allocatable Memory</li>
<li class="forminput"><input type="text" size="4" maxlength="6" name="memory" /> 
<img src="../img/help.png" alt="In megs ie 1024 = 1Gb" title="In megs ie 1024 = 1Gb" /></li>
<li class="formsplit"></li>
<li class="formlabel">Xen Slots</li>
<li class="forminput"><input type="text" size="2" maxlength="3" name="xencount" />
<img src="../img/help.png" alt="If not xen then use 0" title="If not xen then use 0" /></li>
<li class="formsplit"></li>
<li class="formlabel">Fence Command</li>
<li class="forminput"><input type="text" size="15" maxlength="255" name="fence_command" />
<img src="../img/help.png" alt="Only if a baremetal machine" title="Only if a baremetal machine" /></li>
<li class="formsplit"></li>
<li class="formlabel">Is this fullvirt capable?</li>
<li class="forminput"><input type="checkbox" name="fullvirt" value="1" /></li>
<li class="formsplit"></li>
</ul>
<input type="submit" value="Add Host" name="submit" />
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
