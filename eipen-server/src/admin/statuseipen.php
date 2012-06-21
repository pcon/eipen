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

	global $clientPort;

?>
<html>
<head><title>Eipen - Status</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("stats");
	print_messages();
?>
<div id="status_header">Daemon</div>
<div id="status_sub">
<?php
	$status_eipend = exec("ps aux | grep eipend | grep -v grep");

	if (strcmp($status_eipend,"") != 0) {
		global $daemonLockPath;
		$started = date ("F d Y H:i:s", filemtime($daemonLockPath));

		print "<ul><li>Started $started <div id=\"status_good\">[RUNNING]</div></li></ul>\n";
	} else {
		print "<ul><li>Not Running <div id=\"status_bad\">[FAIL]</div></li></ul>\n";
	}
?>
</div>   <!--daemon status_sub-->
<br />
<div id="status_header">Host</div>
<div id="status_sub">
<?php
	$link = mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT hostid, ipaddr, xenslots, memory FROM host";
		"session NATURAL RIGHT JOIN host GROUP BY host.hostid ORDER BY total";

	$result = mysql_query($query, $link);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	while($row = mysql_fetch_array($result)) {
		$hostid = $row['hostid'];
		$ipaddr = $row['ipaddr'];
		$slots = (int)($row['xenslots']);
		$memory = (int)($row['memory']);

		$running = (int)(getTotalRunning($hostid));
		$usedmemory = (int)(getUsedMemory($hostid));

		$status = "";

		if ($slots == 0) {
			$clientPort = 22;


			$query = "SELECT c.name AS name FROM courses c, running_baremetal rb, host h ".
					"WHERE rb.hostid = h.hostid AND rb.courseid = c.courseid AND rb.hostid = $hostid";

			$results2 = mysql_query($query, $link);
			if (!$results2) {
				die('Invalid query: ' . mysql_error());
			}

			if (mysql_num_rows($results2) == 1) {
				$row2 = mysql_fetch_array($results2);
				$status = $row2['name'];
			}

			$query = "SELECT sessionid FROM running_profiles WHERE hostid = $hostid";
			$results2 = mysql_query($query, $link);
			if (!$results2) {
				die('Invalid query: ' . mysql_error());
			}

			if (mysql_num_rows($results2) == 1) {
				if (strcmp($status, "") != 0) {
					$status .= " - ";
				}

				$status .= "Installing";
			}


		}

		$fp = fsockopen($ipaddr, $clientPort, $errno,$errstr, 4);
		if (!$fp){
			print "<ul><li>$ipaddr <div id=\"status_center\">$status</div><div id=\"status_bad\">[FAIL]</div></li></ul>\n";
		} else {
			print "<ul><li>$ipaddr <div id=\"status_center\">$status</div><div id=\"status_good\">[GOOD]</div></li>\n";

			if ($slots > 0 ) {
				$slotPercent = (int)(($running / $slots)*100);
				$memoryPercent = (int)(($usedmemory / $memory)*100);
				print "<ul>\n";
				print "<li>Slots <div id=\"status_info\"><div class=\"graph\"><span>$running / $slots</span>";
				print "<div class=\"graphFill\" style=\"width: $slotPercent%;\"></div></div></li>\n";
				print "<li>Memory <div id=\"status_info\"><div class=\"graph\"><span>$usedmemory / $memory</span>";
				print "<div class=\"graphFill\" style=\"width: $memoryPercent%;\"></div></div></li>\n";
				print "</ul>\n";
			}

			print "</ul>\n";

			fclose($fp);
		}
	}

	mysql_close($link);


	$macTotal = 0;
	$macCount = 0;
	$macPercent = 0;

	$link = mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT COUNT(macid) AS total FROM macaddr";
	$result = mysql_query($query, $link);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) > 0) {
		$row = mysql_fetch_array($result);
		$macTotal = $row['total'];
	}
	
	$query = "SELECT COUNT(macid) AS total FROM macaddr WHERE status != 0";
	$result = mysql_query($query, $link);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) > 0) {
		$row = mysql_fetch_array($result);
		$macCount = $row['total'];
	}

	mysql_close($link);

	if ($macTotal > 0) {
		$macPercent = (int)(($macCount / $macTotal)*100);
	}
?>
</div>   <!--host status_sub-->
<div id="status_header">Mac</div>
<div id="status_sub">
<?php
	$link = mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT macid, addr, status FROM macaddr";
	$result = mysql_query($query, $link);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	print "<ul>\n";

	print "<li>Slots";
		print "<div id=\"status_info\">";
			print "<div class=\"graph\"><span>$macCount / $macTotal</span>";
				print "<div class=\"graphFill\" style=\"width: $macPercent%;\"></div>";
			print "</div>";
		print "</div>";
	print "</li>\n";

	while($row = mysql_fetch_array($result)) {
		$macid = $row['macid'];
		$macaddr = $row['addr'];
		$status = $row['status'];

		if ($status == 0) {
			print "<li>$macaddr <div id=\"status_good\">[FREE]</div></li>\n";
		} else {
			$info = getStatusInfoFromMacid($macid);
			print "<li>$macaddr <div id=\"status_center\">$info</div><div id=\"status_bad\">[USED]</div></li>\n";
		}
	}

	print "</ul>\n";

	mysql_close($link);
?>
</div>   <!--mac status_sub-->
<?php
	footer();
?>
</div>   <!--wrapper-->
</body>
</html>
