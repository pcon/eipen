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

	$imageid = $_GET['id'];

	$userid = get_email();
	$image = getImageInfo($imageid);

	if ($image != NULL) {
		$macinfo = getMacAddr();

		if ($macinfo != NULL) {
			$memory = 512;
			$hostid = getHost($memory);

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
						$fullvirt = $image['fullvirt'];

						$macaddr = $macinfo['addr'];
						$guestip = $macinfo['ipaddr'];

						$imagename = "Admin-".$imageid;

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

	if (isset($_SERVER['HTTP_REFERER'])) {
		gotopage($_SERVER['HTTP_REFERER']);
		return;
	} else {
		gotopage("newimagestatus.php");
		return;
	}
?>
