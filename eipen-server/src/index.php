<?php
	require_once("eipen_config.php");
	require_once("eipen_functions.php");
	require_once("eipen_userfunctions.php");

	global $authentication;

	if (isloggedin() != 0 && strcmp($authentication, "") != 0) {
		$_SESSION['from'] = $_SERVER['PHP_SELF'];
		gotopage('login.php');
		return;
	}

	if (isDaemonRunning() != 0) {
		setMessage("The damon is not running.  Please contact the system administrator", "bad");
	}
	if (isset($_POST['submit'])) {
		$userid=$_POST['username'];
		$courseid=$_POST['course'];

		if (!ereg('^[A-Za-z0-9_\.-]{1,}\@[A-Za-z0-9_\.-]{1,}$',$userid)) {
               setMessage("Invalid userid","bad");
          } else {
			$courseinfo = getCourseInfo ($courseid);

			if ($courseinfo == NULL) {
				setMessage("Course not available", "bad");
				gotopage("list.php");
				return;
			}

			//Check to see if the course is a xen 
			if ($courseinfo['xen'] == 1) {

				$length = $courseinfo['length'];
				$imagelist = getImageList($courseid);

				//Check to see if the course must be loaded all on one host
				if ($courseinfo['samemachine'] == 1) {
					$totalMemory = getTotalMemory($courseid);
					$fullvirt = areAnyFullVirt($courseid);
					$hostid = getHost($totalMemory, $fullvirt,0);

					//Make sure there is a host available
					if ($hostid != -1) {
						$sessionid = createSession($userid, $courseid, $length);

						if ($sessionid < 0) {
							gotopage("list.php");
							return;
						}

						//Go through all images and start up machines
						while ($image = mysql_fetch_array($imagelist)) {
							$memory = $image['memory'];
							$imageid = $image['imageid'];
							$course_imageid = $image['course_imageid'];
							$imagetype = $image['type'];
							$imageloc = $image['image'];
							$fullvirt = $image['fullvirt'];
							$imagename = $image['imagename'];

							$macinfo = getMacAddr();
							$macaddr = $macinfo['addr'];
							$guestip = $macinfo['ipaddr'];

							$hostip = getHostIP($hostid);
							$total = getTotalRunning($hostid);

							list($uname,$domain) = split('@', $userid);
							$guestid = getDomainName($macinfo['macid']);

							if ($guestid == NULL) {
								$guestid = "".$hostid."-".$courseid."-".$imageid."-".$uname."-".$total;
							}

							$status = createVMSession($sessionid, $userid, $course_imageid, $macinfo['macid'], $hostid, $guestid, $length);

							if ($status == 0) {
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
										gotopage("list.php");
										return;
								}
							}
						}

						setMessage("Course started.  You will receive and email shortly.", "good");
						gotopage("list.php");
						return;
					}
				} else {
					$sessionid = createSession($userid, $courseid, $length);
					//get each image from list
					//Go through all images and gather info for sessions.
					// After sessioninfo is together go through all and startup machines

					if ($sessionid < 0) {
						gotopage("list.php");
						return;
					}

					$systems = array();

					while ($image = mysql_fetch_array($imagelist)) {
						$memory = $image['memory'];
						$imageid = $image['imageid'];
						$course_imageid = $image['course_imageid'];
						$imagetype = $image['type'];
						$imageloc = $image['image'];
						$fullvirt = $image['fullvirt'];
						$imagename = $image['imagename'];

						$macinfo = getMacAddr();
						$macaddr = $macinfo['addr'];
						$guestip = $macinfo['ipaddr'];

						$hostid = getHost($memory, $fullvirt,0);

						//We can't get enough hosts.  Bail out, and clean up.  And report an error.
						if ($hostid == -1) {
							destroySession($sessionid);
							setMessage("Creating course failed", "bad");
							gotopage("list.php");
							return;
						}

						$hostip = getHostIP($hostid);
						$total = getTotalRunning($hostid);

						list($uname,$domain) = split('@', $userid);
						$guestid = getDomainName($macinfo['macid']);

						if ($guestid == NULL) {
							$guestid = "".$hostid."-".$courseid."-".$imageid."-".$uname."-".$total;
						}

						$status = createVMSession($sessionid, $userid, $course_imageid, $macinfo['macid'], $hostid, $guestid, $length);

						$system = array ('hostip' => $hostip,
									'imagetype' => $imagetype,
									'imageloc' => $imageloc,
									'guestid' => $guestid,
									'macaddr' => $macaddr,
									'memory' => $memory,
									'userid' => $userid,
									'guestip' => $guestip,
									'imagename' => $imagename);

						$systems[] = $system;
					}

					foreach ($systems as $system)
					{
						$hostip = $system['hostip'];
						$imageloc = $system['imageloc'];
						$imagetype = $system['imagetype'];
						$guestid = $system['guestid'];
						$macaddr = $system['macaddr'];
						$memory = $system['memory'];
						$userid = $system['userid'];
						$guestip = $system['guestip'];
						$imagename = $system['imagename'];

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
								gotopage("list.php");
								return;
						}
					}

					setMessage("Course started.  You will receive and email shortly.", "good");
					gotopage("list.php");
					return;
				}
			} else {
				//find and use a bare-metal machine
				$memory = $courseinfo['memory'];
				$fullvirt = $courseinfo['fullvirt'];
				$length = $courseinfo['length'];
				$profileid = $courseinfo['profileid'];
				$profilelabel = getProfileLabel($profileid);
				$hostid = getHost($memory, $fullvirt, 1);
				$coursename = $courseinfo['name'];

	    	          //Make sure a host is available
				if ($hostid != -1) {
					$hostip = getHostIP($hostid);
					$course_imageid =0;

					$sessionid = createSession($userid, $courseid, $length);

					if ($sessionid < 0) {
						gotopage("list.php");
						return;
					}

					$status = createBaremetalSession($sessionid, $courseid, $hostid, $length);

					if ($status == 0) {
						startBareMetal($hostip, $profilelabel, $userid, $coursename);
					}
				}

				setMessage("Course started.  You will receive and email shortly.", "good");
				gotopage("list.php");
				return;
			}
		}
			

		}
	?>
	<html>
	<title>Eipen - Reserve a machine</title>
	<style>@import url(defaultstyle.css);</style></head>
	<body>
<div id="wrapper" class="clearfix">
<?php
	user_nav();
	print_messages();
?>

<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
	<ul id="form">
	<li class="formlabel">Course </li>
	<li class="forminput"><select name="course">
<?php
	global $username;
	global $password;
	global $database;
        global $dbhost;

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT courseid, name, coursedesc FROM courses WHERE courseid > 0 AND enabled = 1 ORDER BY name";

	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}


	while($row = mysql_fetch_array($result)) {
		print "<option value=\"".$row['courseid']."\">".$row['name'].": ".$row['coursedesc']."</option>\n";
	}

	print "</select>\n";

?>
	</li>
	<li class="formsplit"></li>
<?php
	global $authentication;

	if (strcasecmp($authentication, "") == 0) {
?>
	<li class="formlabel">Email Address</li>
	<li class="forminput"><input type="text" size="12" maxlength="255" name="username" /></li>
	<li class="formsplit"></li>
<?php
	} else {
		print '<input type="hidden" name="username" value="'.get_email().'" />';
	}
?>
	</ul>
	<input type="submit" value="Start Course" name="submit" /><br />
</form>
<br /><br />
Please note:  It may take up to 15 minutes for you machine to be ready based on course you have requested.
<?php
	footer();
?>
</div> <!-- wrapper end -->
</body>
</html>
