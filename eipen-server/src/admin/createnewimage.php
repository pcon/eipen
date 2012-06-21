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
		$userid=$_POST['username'];
		$imageid=(int)($_POST['baseimage']);

		$image = getAdminImageInfo($imageid);
		if ($image != NULL) {
			$macinfo = getMacAddr();

			if ($macinfo != NULL) {
				$memory = 512;
				$fullvirt = $image['fullvirt'];
				$hostid = getHost($memory, $fullvirt, 0);

				if ($hostid != -1) {
					$total = getTotalRunning($hostid);
					$hostip = getHostIP($hostid);

					if ($hostip != NULL) {
						list($uname,$domain) = split('@', $userid);
						$guestid = getDomainName($macinfo['macid']);

						if ($guestid == NULL) {
							$guestid = $hostid."-".$imageid."-admin-".$uname."-".$total;
						}

						$status = createSessionAdmin ($userid, $macinfo['macid'], $hostid, $guestid);

						if ($status == 0) {
							$imagetype = $image['type'];
							$imageloc = $image['image'];

							$macaddr = $macinfo['addr'];
							$guestip = $macinfo['ipaddr'];

							$imagename = "Admin";

							//figure out which type of machine to start
							switch($imagetype) {
								case 0:   //Linux
									if ($fullvirt == 0) {
										//Start a paravirt linux guest
										startLinuxParavirt ($hostip, $imageloc, $guestid, $macaddr, $memory, $userid, $guestip, $imagename);
									} else {
										//Start a fullvirt linux guest
										startLinuxFullvirt ($hostip, $imageloc, $guestid, $macaddr, $memory, $userid, $guestip, $imagename);
									}
									break;
								case 1:   //Solaris                                                                                  
									if ($fullvirt == 0) {
										//Start a paravirt solaris guest
										startSolarisParavirt ($hostip, $imageloc, $guestid, $macaddr, $memory, $userid, $guestip, $imagename);
									} else {
										//Start a fullvirt solaris guest
										startSolarisFullvirt ($hostip, $imageloc, $guestid, $macaddr, $memory, $userid, $guestip, $imagename);
									}
									break;
								case 2:   //Windows
									startWindows ($hostip, $imageloc, $guestid, $macaddr, $memory, $userid, $guestip, $imagename);
									break;
								default:
									setMessage("Invalid image type set", "bad");
									return;
							}

							setMessage("Machine started.  Email will arrive after machine is ready", "good");
						}
					}
				}
			}
		}

		gotopage($_SERVER['PHP_SELF']);
	} else {
?>
<html>
<head><title>Eipen - Create Course Image</title>
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
<li class="formlabel">Course</li>
<li class="forminput"><select name="baseimage">
<?php
	global $username;
	global $password;
	global $database;
	global $dbhost;

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT imageid, name FROM admin_base_images";

	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	while($row = mysql_fetch_array($result)) {
		print "<option value=\"".$row['imageid']."\">".$row['name']."</option>\n";
	}
?>
</select></li>
<li class="formsplit"></li>
<input type="hidden" name="username" value="<?php print get_email(); ?>" />
</ul>
<input type="submit" value="Start Image" name="submit" /><br />
</form>

<br /><br />
Please note:  It may take up to 15 minutes for you machine to be ready based on course you have requested.
<?php
	footer();
?>
</div> <!-- end wrapper -->
</body>
</html>

<?php
}
?>
