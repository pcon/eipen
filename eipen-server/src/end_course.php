<?php
	require_once("eipen_config.php");
     require_once("eipen_functions.php");
     require_once("eipen_userfunctions.php");

     if (isloggedin() != 0) {
          $_SESSION['from'] = $_SERVER['PHP_SELF'];
          gotopage('login.php');
          return;
     }

     if (isDaemonRunning() != 0) {
          setMessage("The damon is not running.  Please contact the system administrator", "bad");
		gotopage('list.php');
		return;
     }

	$sessionid = (int)($_GET['id']);

	if ($sessionid <= 0) {
		setMessage("EC1: Invalid session");
		gotopage('list.php');
		return;
	}

	global $username;
	global $password;
	global $database;
	global $dbhost;

	mysql_connect($dbhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");


	$query = "SELECT c.profileid AS profileid, rc.sessionid AS sessionid ".
			"FROM running_courses rc, courses c WHERE rc.courseid = c.courseid AND ".
			"userid = '".get_email()."' AND sessionid = $sessionid";

	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	if (mysql_num_rows($result) != 1) {
		mysql_close();

		setMessage("EC2: Invalid session", "bad");
		gotopage("list.php");
		return;
	}

	$row = mysql_fetch_array($result);
	
	if ((int)($row['profileid']) == -1) {
		$query = "SELECT h.ipaddr AS ipaddr, rv.xenname AS xenname, m.macid AS macid, rv.course_imageid AS course_imageid ".
				"FROM host h, running_vms rv, macaddr m ".
				"WHERE rv.hostid = h.hostid AND rv.macid = m.macid AND rv.sessionid = $sessionid";

		$result = mysql_query($query);

		if (mysql_num_rows($result) >= 1) {
			while ($row = mysql_fetch_array($result)) {
				$ipaddr = $row['ipaddr'];
				$xenname = $row['xenname'];
				$macid = $row['macid'];
				$course_imageid = $row['course_imageid'];

				destroyMachine($ipaddr, $xenname);

				$query2 = "UPDATE macaddr SET status=0 WHERE macid = $macid";
				mysql_query($query2);

				$query2 = "DELETE FROM running_vms WHERE sessionid = $sessionid and course_imageid=$course_imageid";
				mysql_query($query2);
			}
			$query = "INSERT INTO complete_courses (userid, courseid, start, end, extend) ".
					"(SELECT userid, courseid, start, now(), extended FROM running_courses ".
					"WHERE sessionid = $sessionid)";

			$result = mysql_query($query);
			if (!$result) {
				die('Invalid query: ' . mysql_error());
			}

			$query = "DELETE FROM running_courses WHERE sessionid = $sessionid";
			mysql_query($query);

			mysql_close();

			setMessage("Course ended", "good");
			gotopage("list.php");
			return;
		}

		mysql_close();

		setMessage("EC3v: Invalid session", "bad");
		gotopage("list.php");
		return;
	} else {	
		$query = "SELECT h.ipaddr AS ipaddr FROM host h, running_baremetal rb ".
				"WHERE rb.hostid = h.hostid AND rb.sessionid = $sessionid";

		$result = mysql_query($query);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}

		if (mysql_num_rows($result) == 1) {
			$query = "UPDATE running_courses SET end = now() WHERE sessionid = $sessionid";

			$result = mysql_query($query);			
			if (!$result) {
				die('Invalid query: ' . mysql_error());
			}

			$query = "UPDATE running_baremetal SET end = now() WHERE sessionid = $sessionid";

			$result = mysql_query($query);			
			if (!$result) {
				die('Invalid query: ' . mysql_error());
			}

			setMessage("Course ended", "good");
			gotopage("list.php");
			return;
		}

		mysql_close();

		setMessage("EC3b: Invalid session", "bad");
		gotopage("list.php");
		return;
	}

?>
