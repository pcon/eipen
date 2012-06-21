<?php

require_once("eipen_config.php");

function isDaemonRunning () {
	global $daemonLockPath;
	if  (!file_exists($daemonLockPath)) {
		return -1;
	}

	return 0;
}

function user_nav() {
	print "<div id=\"navwrapper\">\n";
	print "<center>\n";
	print "<ul id=\"nav\">\n";
	print "<li id=\"nav\"><a href=\"index.php\">New Course</a></li>\n";
	print "<li id=\"nav\"><a href=\"list.php\">List Running Courses</a></li>\n";
	print "<li id=\"nav\"><a href=\"logout.php\">Logout</a></li>\n";
	print "</ul></center></div>\n";
}

function getCourseInfo ($courseid) {
	if ($courseid < 0) {
		setMessage("GCI1: Invalid Courseid", "bad");
		return NULL;
	}

	global $username;
	global $password;
	global $database;
	global $dbhost;

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT name, enabled, coursedesc, xen, length, samemachine, memory, fullvirt, profileid FROM courses WHERE courseid = $courseid";

	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) == 0) {
		mysql_close();
		setMessage("GCI2: Invalid Courseid", "bad");
                return NULL;
	}

	$row = mysql_fetch_array($result);

	mysql_close();

	if ($row['enabled'] != 1) {
		return NULL;
	}

	return $row;
}

function getImageList ($courseid) {
	if ($courseid < 0) {
          setMessage("GIL1: Invalid Courseid", "bad");
          return NULL;
     }

	global $username;
     global $password;
     global $database;
     global $dbhost;

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT images.imageid AS imageid, images.memory AS memory, images.image AS image, images.typeid AS type, ".
		"images.fullvirt AS fullvirt, course_images.course_imageid AS course_imageid, course_images.name AS imagename ".
		"FROM images, course_images WHERE course_images.imageid = images.imageid AND course_images.courseid = $courseid";

	$result = mysql_query($query);
     if (!$result) {
          die('Invalid query: ' . mysql_error());
     }

     if (mysql_num_rows($result) == 0) {
          mysql_close();
          setMessage("GIL2: Invalid Courseid", "bad");
                return NULL;
     }

     mysql_close();

	return $result;
}

function getTotalMemory ($courseid) {
     if ($courseid < 0) {
          setMessage("GTM1: Invalid Courseid", "bad");
          return NULL;
     }

     global $username;
     global $password;
     global $database;
     global $dbhost;

	mysql_connect($dbhost,$username,$password);
     @mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT sum(images.memory) AS memtotal FROM course_images, images ".
		"WHERE course_images.imageid = images.imageid AND courseid = $courseid GROUP BY courseid";

     $result = mysql_query($query);
     if (!$result) {
          die('Invalid query: ' . mysql_error());
     }

     if (mysql_num_rows($result) == 0) {
          mysql_close();
          setMessage("GTM2: Invalid Courseid", "bad");
                return NULL;
     }

	$row = mysql_fetch_array($result);

     mysql_close();

     return $row['memtotal'];
}

function getCourseIdFromName($coursename) {
	if (!ereg('^[A-Za-z0-9_]{1,}$',$coursename)) {
		setMessage("GCIFN1: Invalid coursename", "bad");
		return -1;
	}

	global $username;
        global $password;
        global $database;
        global $dbhost;

        mysql_connect($dbhost,$username,$password);
        @mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT courseid FROM courses WHERE name = \"$coursename\"";

	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) != 1) {
		setMessage("GCIFN2: Invalid coursename", "bad");
                return -1;
	}

	$row = mysql_fetch_array($result);

	mysql_close();	

	return $row['courseid'];
}

function getMacAddr () {
	global $username;
	global $password;
	global $database;
	global $dbhost;

	mysql_connect($dbhost,$username,$password);
        @mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT macid, addr, ipaddr FROM macaddr WHERE status = 0 AND macid != 1 ORDER BY rand() LIMIT 1";

	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) == 0) {
		mysql_close();
                setMessage("GMA1: No mac addresses avaliable", "bad");
                return NULL;
	}

	$row = mysql_fetch_array($result);

	mysql_close();

	return $row;
}

function getHostIP ($hostid) {
	if ($hostid <= 0) {
                setMessage("GHI1: No hosts avaliable", "bad");
                return NULL;
        }

	global $username;
	global $password;
	global $database;
        global $dbhost;

	mysql_connect($dbhost,$username,$password);
        @mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT ipaddr FROM host WHERE hostid = $hostid";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) != 1) {
		setMessage("GHI2: No hosts avaliable", "bad");
                return NULL;
	}

	$row = mysql_fetch_array($result);

	mysql_close();

	return $row['ipaddr'];
}

function getUsedMemory ($hostid) {
	if ($hostid <= 0) {
		setMessage("SUM1: Invalid hostid", "bad");
		return -1;
	}

	global $username;
	global $password;
     global $database;
     global $dbhost;
        
     mysql_connect($dbhost,$username,$password);
     @mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT sum(images.memory) AS memtotal ".
		 "FROM running_vms, images, course_images ".
		 "WHERE images.imageid = course_images.imageid AND running_vms.course_imageid = course_images.course_imageid AND ".
		 "running_vms.hostid = $hostid GROUP BY running_vms.hostid";
	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	$row = mysql_fetch_array($result);

	mysql_close();

	return $row['memtotal'];

}

function areAnyFullVirt($courseid) {
     if ($courseid < 0) {
          setMessage("GTM1: Invalid courseid", "bad");
          return NULL;
     }

     global $username;
     global $password;
     global $database;
     global $dbhost;

	mysql_connect($dbhost,$username,$password);
     @mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT count(*) AS count FROM course_images, images WHERE ".
		"course_images.imageid = images.imageid AND images.fullvirt = 1 AND courseid = $courseid";

     $result = mysql_query($query);
     if (!$result) {
          die('Invalid query: ' . mysql_error());
	}

	$row = mysql_fetch_array($result);

     mysql_close();

	if ($row['count'] == 0) {
		return 0;
	}

	return 1;	
}

function getHost ($memory, $fullvirt, $baremetal) {

	if ($memory <= 0) {
		setMessage("GH1: No hosts avaliable", "bad");
		return -1;
	}

	global $username;
	global $password;
	global $database;
	global $dbhost;

	global $clientPort;
	$sshclientport = 22;

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	//Get a Baremetal host
	if ($baremetal == 1) {
		$query = "SELECT h.hostid AS hostid, h.memory AS memory, h.fullvirt AS fullvirt ".
			"FROM host h LEFT JOIN running_profiles rp ON h.hostid = rp.hostid LEFT JOIN ".
			"running_baremetal rb ON h.hostid = rb.hostid WHERE ".
			"rb.sessionid IS NULL AND rp.sessionid IS NULL AND h.xenslots = 0";

		$result = mysql_query($query);

		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		while ($row = mysql_fetch_array($result)) {
			$hostid = $row['hostid'];
			$totalmemory = $row['memory'];
			$ipaddr = getHostIP($hostid);

			$fp = fsockopen($ipaddr, $sshclientport, $errno,$errstr, 4);

			if (!$fp) { }
			elseif ($totalmemory >= $memory) {
				if ($fullvirt == 0 || $row['fullvirt'] == $fullvirt) { 
					fclose($fp);
					return $hostid;
				}
			}
		}
	} else {

		$query = "SELECT host.hostid AS hostid, host.xenslots AS xenslots, host.memory as memory, host.fullvirt AS fullvirt, ".
			"count(running_vms.sessionid) AS total FROM ".
			"running_vms NATURAL RIGHT JOIN host WHERE xenslots != 0 ".
			"GROUP BY host.hostid ORDER BY total";

		$result = mysql_query($query);

		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		while ($row = mysql_fetch_array($result)) {
			if ($row['xenslots'] > $row['total']) {
				$hostid = $row['hostid'];
				$totalmemory = $row['memory'];
				$usedmemory = getUsedMemory($hostid);

				$ipaddr = getHostIP($hostid);

				$fp = fsockopen($ipaddr, $clientPort, $errno,$errstr, 4);

				if (!$fp) { }
				elseif (($totalmemory - $usedmemory) >= $memory) {
					if ($fullvirt == 0 || $row['fullvirt'] == $fullvirt) {
						fclose($fp);
						return $hostid;
					}
				}
			}
		}
	}

	setMessage("GH2: No hosts avaliable", "bad");

	return -1;
}

function getProfileLabel ($profileid) {
	if ( $profileid <= 0) {
		setMessage("GPL1: Invalid profileid", "bad");
		return "None";
	}
	global $username;
	global $password;
	global $database;
	global $dbhost;
        
	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");
	$query = "SELECT label from ks_profiles where profileid = $profileid";
	$result = mysql_query($query);

	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) != 1) {
		setMessage("GPL2: Invalid profileid", "bad");
		return "None";
	}

	$row = mysql_fetch_array($result);

	mysql_close();

	$profilelabel = $row['label'];

	return $profilelabel;
}

function getStatusInfoFromMacid ($macid) {
	if ($macid <= 0) {
		setMessage("GSIFM1: Invalid macid", "bad");
		return -1;
	}

	global $username;
	global $password;
	global $database;
	global $dbhost;
        
	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");
        
	$query = "SELECT CONCAT_WS(' ', courses.name, course_images.name) AS name, running_vms.xenname AS xenname ".
		"FROM courses, running_vms, course_images WHERE courses.courseid = course_images.courseid AND ".
		"running_vms.course_imageid = course_images.course_imageid AND running_vms.macid = $macid";

	$result = mysql_query($query);

        if (!$result) {
                die('Invalid query: ' . mysql_error());
        }

        if (mysql_num_rows($result) != 1) {
                setMessage("GSIFM2: Invalid macid", "bad");
                return -1;
        }

        $row = mysql_fetch_array($result);

        mysql_close();

	$info = $row['name']." <b>-</b> ".$row['xenname'];

        return $info;
}

function getTotalRunning ($hostid) {
	if ($hostid <= 0) {
		setMessage("GTR1: No hosts avaliable", "bad");
		return -1;
	}

	global $username;
	global $password;
	global $database;
        global $dbhost;

	mysql_connect($dbhost,$username,$password);
        @mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT host.hostid AS hostid, host.xenslots AS xenslots, host.memory as memory, count(running_vms.sessionid) AS total FROM ".
                 "running_vms NATURAL RIGHT JOIN host WHERE hostid=$hostid GROUP BY host.hostid ORDER BY total";
	
	$result = mysql_query($query);

	if (!$result) {
                die('Invalid query: ' . mysql_error());
        }

	if (mysql_num_rows($result) != 1) {
		setMessage("GTR2: No hosts avaliable", "bad");
                return -1;
	}

	$row = mysql_fetch_array($result);

	mysql_close();

	return $row['total'];
}

function getDomainName ($macid) {
	if ($macid < 0) {
		setMessage("GDN1: Invalid macid", "bad");
		return -1;
	}

	global $username;
     global $password;
     global $database;
     global $dbhost;

     mysql_connect($dbhost,$username,$password);
     @mysql_select_db($database) or die( "Unable to select database");

     $query = "SELECT name FROM macaddr WHERE macid = \"$macid\"";

     $result = mysql_query($query);
	if (!$result) {
          die('Invalid query: ' . mysql_error());
     }

     if (mysql_num_rows($result) != 1) {
          mysql_close();
          setMessage("GDN2: Invalid macid", "bad");
          return -1;
	}

	$row = mysql_fetch_array($result);

	mysql_close();

	return $row['name'];

}

function destroySession ($sessionid) {
	if ($sessionid < 0) {
		setMessage("DS1: Invalid sessionid", "bad");
		return -1;
	}

	global $username;
	global $password;
	global $database;
	global $dbhost;

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	$query = "SELECT sessionid AS sessionid FROM running_courses ".
		"WHERE sessionid = $sessionid";

	$result = mysql_query($query);
	if (!$result) {
		die ('invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) != 1) {
		mysql_close();
		setMessage("DS2: Invalid sessionid", "bad");
		return -1;
	}

	$query = "DELETE FROM running_vms WHERE sessionid = $sessionid";

	mysql_query($query);

	$query = "DELETE FROM running_courses WHERE sessionid = $sessionid";

	mysql_query($query);

	mysql_close();

	return 0;
}

function createSession ($userid, $courseid, $length) {
	if ($courseid < 0) {
		setMessage("CS1: Invalid courseid", "bad");
		return -1;
	}

	if ($length < 0) {
		setMessage("CS1: Invalid length", "bad");
		return -1;
	}

	global $username;
	global $password;
	global $database;
	global $dbhost;

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

	# check that the course is not already running
	$query = "SELECT sessionid AS sessionid FROM running_courses ".
		"WHERE userid = \"$userid\" AND courseid=$courseid";

	$result = mysql_query($query);
     	if (!$result) {
        	die('Invalid query: ' . mysql_error());
     	}

    	if (mysql_num_rows($result) != 0) {
		mysql_close();

		setMessage("GTM2: Class already in progress. Please terminate first.", "bad");
		return -1;
    	}

	# get a new session

	$query = "INSERT INTO running_courses (userid, courseid, start, end) VALUES " .
		 "(\"$userid\", $courseid, now(), " .
		 "from_unixtime(unix_timestamp(now()) + (60 * ($length + 5))))";

	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	# get the session id

	$query = "SELECT sessionid AS sessionid FROM running_courses ".
                 "WHERE userid = \"$userid\" AND courseid=$courseid";

        $result = mysql_query($query);
        if (!$result) {
                die('Invalid query: ' . mysql_error());
        }

        if (mysql_num_rows($result) == 0) {
                mysql_close();
                setMessage("GTM2: Error creating a new course session.", "bad");
                return -1;
        }

	$row = mysql_fetch_array($result);
	$sessionid = $row['sessionid'];

	mysql_close();

	return $sessionid;
}

function createVMSession ($sessionid, $userid, $course_imageid, $macid, $hostid, $guestid, $length) {
	if ($sessionid < 0) {
		setMessage("CVS1: Invalid sessionid", "bad");
		return -1;
	}
	if ($course_imageid < 0) {
		setMessage("CVS1: Invalid courseid", "bad");
		return -1;
	}

	if ($macid < 0) {
		setMessage("CVS1: Invalid macid","bad");
		return -1;
	}

	if ($hostid < 0) {
		setMessage("CVS1: Invalid hostid", "bad");
		return -1;
	}

	if ($length < 0) {
		setMessage("CVS1: Invalid length", "bad");
		return -1;
	}

	global $username;
	global $password;
	global $database;
	global $dbhost;

	mysql_connect($dbhost,$username,$password);
        @mysql_select_db($database) or die( "Unable to select database");

	# insert into running_vms

        $query = "INSERT INTO running_vms (sessionid, userid, course_imageid, macid, hostid, xenname, start, end) VALUES ".
		 "($sessionid, \"$userid\",$course_imageid, $macid, $hostid, \"$guestid\", now(), ".
		 "from_unixtime(unix_timestamp(now()) + (60 * ($length + 5))))";

	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	$query = "UPDATE macaddr SET status = 1 WHERE macid = $macid";

	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	mysql_close();

	return 0;
}

function createBaremetalSession ($sessionid, $courseid, $hostid, $length) {
	if ($sessionid < 0) {
		setMessage("CBS1: Invalid sessionid", "bad");
		return -1;
	}
	if ($courseid < 0) {
		setMessage("CBS1: Invalid courseid", "bad");
		return -1;
	}

	if ($hostid < 0) {
		setMessage("CBS1: Invalid hostid", "bad");
		return -1;
	}

	if ($length < 0) {
		setMessage("CBS1: Invalid length", "bad");
		return -1;
	}

	global $username;
	global $password;
	global $database;
	global $dbhost;

	mysql_connect($dbhost,$username,$password);
        @mysql_select_db($database) or die( "Unable to select database");

	# insert into running_baremetal

     $query = "INSERT INTO running_baremetal (sessionid, courseid,  hostid, start, end) VALUES ".
			"($sessionid, $courseid, $hostid, now(), ".
			"from_unixtime(unix_timestamp(now()) + (60 * ($length + 5))))";

	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	$query = "UPDATE running_courses SET warning = -1 WHERE sessionid = $sessionid";
	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	$query = "INSERT INTO running_profiles (sessionid, hostid, remove) VALUES ".
			"($sessionid, $hostid, from_unixtime(unix_timestamp(now()) + (60 * 5)))";
	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	mysql_close();

	return 0;
}

function startLinuxParavirt ($hostip, $courseimage, $guestid, $macaddr, $memory, $userid, $ipaddr, $imagename) {

	exec("/usr/bin/eipen/eipen-server createmachine $hostip $guestid $courseimage $macaddr $memory $userid $ipaddr $imagename linuxparavirt &");

	return 0;
}

function startLinuxFullvirt ($hostip, $courseimage, $guestid, $macaddr, $memory, $userid, $ipaddr, $imagename) {

	exec("/usr/bin/eipen/eipen-server createmachine $hostip $guestid $courseimage $macaddr $memory $userid $ipaddr $imagename linuxfullvirt &");

	return 0;
}

function startSolarisParavirt ($hostip, $courseimage, $guestid, $macaddr, $memory, $userid, $ipaddr, $imagename) {

	exec("/usr/bin/eipen/eipen-server createmachine $hostip $guestid $courseimage $macaddr $memory $userid $ipaddr $imagename solarisparavirt &");

	return 0;
}

function startSolarisFullvirt ($hostip, $courseimage, $guestid, $macaddr, $memory, $userid, $ipaddr, $imagename) {

	exec("/usr/bin/eipen/eipen-server createmachine $hostip $guestid $courseimage $macaddr $memory $userid $ipaddr $imagename solarisfullvirt &");

	return 0;
}

function startWindows ($hostip, $courseimage, $guestid, $macaddr, $memory, $userid, $ipaddr, $imagename) {

	exec("/usr/bin/eipen/eipen-server createmachine $hostip $guestid $courseimage $macaddr $memory $userid $ipaddr $imagename windows &");

	return 0;
}

function startBareMetal ($hostip, $profilename, $userid, $coursename) {

	exec("/usr/bin/eipen/eipen-server createbaremetal $hostip $hostip $profilename $userid $coursename & ");

	return 0;
}

function destroyMachine ($hostip, $guestid) {
	exec("/usr/bin/eipen/eipen-server destroymachine $hostip $guestid &");

	return 0;
}
?>
