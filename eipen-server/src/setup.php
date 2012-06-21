<?php
	include ("eipen_config.php");

	global $database;
	global $username;
	global $password;
	global $dbhost;

if (isset($_POST['submit'])) {
	$admin = $_POST['admin'];

	mysql_connect($dbhost, $username, $password);
	@mysql_select_db($database) or die("Unable to select database \"$database\"");

	$query = "INSERT INTO ldap_admin (userid) VALUES (\"$admin\")";
	$result = mysql_query($query);

	print "Setup is complete.  Please remove the setup.php file, and navigate to eipen/admin/\n";
	return;

} else {

	if (strcmp($dbhost, "mysql-server.example.com") == 0) {
		die ("Database host not set.  Please edit /etc/eipen-server/eipen-server.conf");
	} else {
		//Check to see if db is populated
		mysql_connect($dbhost, $username, $password);
		@mysql_select_db($database) or die("Unable to select database \"$database\"");

		$query = "SHOW TABLES FROM $database";
		$result = mysql_query($query);

		if (mysql_num_rows($result) != 0) {
			die ("Database \"$database\" is already populated. You probably want to use update.php");
		}

		//Populate database
		$queries = array (
			'CREATE TABLE macaddr (macid INT NOT NULL AUTO_INCREMENT PRIMARY KEY, addr VARCHAR(17) NOT NULL, ipaddr VARCHAR(15) NOT NULL DEFAULT "0.0.0.0", name VARCHAR(100), status INT NOT NULL DEFAULT 0)',
			'CREATE TABLE host (hostid INT NOT NULL AUTO_INCREMENT PRIMARY KEY, memory INT NOT NULL DEFAULT 512, xenslots INT NOT NULL DEFAULT 0, ipaddr VARCHAR(15) NOT NULL DEFAULT "0.0.0.0", fullvirt INT NOT NULL DEFAULT 0, fence_command VARCHAR(255))',
			'CREATE TABLE courses (courseid INT NOT NULL AUTO_INCREMENT PRIMARY KEY, enabled INT NOT NULL DEFAULT 1, name VARCHAR(20) NOT NULL, coursedesc VARCHAR(255) NOT NULL, xen INT NOT NULL DEFAULT 1, length INT NOT NULL DEFAULT 120, samemachine INT NOT NULL DEFAULT 0, memory INT NOT NULL DEFAULT 0, fullvirt INT NOT NULL DEFAULT 0, profileid INT NOT NULL DEFAULT 0)',
			'CREATE TABLE images (imageid INT NOT NULL AUTO_INCREMENT PRIMARY KEY, typeid INT NOT NULL, name VARCHAR(75) NOT NULL, fullvirt INT NOT NULL DEFAULT 0, memory INT NOT NULL DEFAULT 512, image VARCHAR(255) NOT NULL)',
			'CREATE TABLE image_type (typeid INT NOT NULL PRIMARY KEY, typename VARCHAR(100))',
			'CREATE TABLE course_images (course_imageid INT NOT NULL AUTO_INCREMENT PRIMARY KEY, courseid INT NOT NULL, imageid INT NOT NULL, name VARCHAR(100) NOT NULL)',
			'CREATE TABLE running_courses (sessionid INT NOT NULL AUTO_INCREMENT PRIMARY KEY, userid VARCHAR(100) NOT NULL, courseid INT NOT NULL, start DATETIME NOT NULL, end DATETIME NOT NULL, extended INT NOT NULL DEFAULT 0, warning INT NOT NULL DEFAULT 0)',
			'CREATE TABLE running_vms (sessionid INT NOT NULL, userid VARCHAR(100) NOT NULL, course_imageid INT NOT NULL, PRIMARY KEY (sessionid, course_imageid), macid INT NOT NULL, hostid INT NOT NULL, xenname VARCHAR(255), start DATETIME NOT NULL, end DATETIME NOT NULL)',
			'CREATE TABLE running_baremetal (sessionid INT NOT NULL, courseid INT NOT NULL, PRIMARY KEY (sessionid, courseid), hostid INT NOT NULL, start DATETIME NOT NULL, end DATETIME NOT NULL)',

			'CREATE TABLE running_profiles (sessionid INT NOT NULL DEFAULT -1, hostid INT NOT NULL, PRIMARY KEY (sessionid, hostid), remove DATETIME NOT NULL)',
			'CREATE TABLE ks_profiles(profileid INT NOT NULL AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, label VARCHAR(255) NOT NULL)',

			'CREATE TABLE users (userid INT NOT NULL AUTO_INCREMENT PRIMARY KEY, username VARCHAR(20) NOT NULL, passhash VARCHAR(255) NOT NULL, email VARCHAR(155) NOT NULL, realname VARCHAR(255) NOT NULL, admin INT NOT NULL DEFAULT 0) AUTO_INCREMENT = 1',
			'CREATE TABLE ldap_admin (adminid INT NOT NULL AUTO_INCREMENT PRIMARY KEY, userid VARCHAR(255) NOT NULL) AUTO_INCREMENT = 1',
			'CREATE TABLE admin_base_images (imageid INT NOT NULL AUTO_INCREMENT PRIMARY KEY, name VARCHAR(75) NOT NULL, image VARCHAR(255) NOT NULL, fullvirt INT NOT NULL DEFAULT 2, typeid INT NOT NULL)',
			'CREATE TABLE admin (version VARCHAR(20))',

			'CREATE TABLE complete_courses (ccid INT NOT NULL AUTO_INCREMENT PRIMARY KEY, userid VARCHAR(100) NOT NULL, courseid INT NOT NULL, start DATETIME NOT NULL, end DATETIME NOT NULL, extend INT NOT NULL)',

			'INSERT INTO admin (version) VALUES ("2.1.0")',

			'INSERT INTO image_type (typeid, typename) VALUES (0, "Linux")',
			'INSERT INTO image_type (typeid, typename) VALUES (1, "Solaris")',
			'INSERT INTO image_type (typeid, typename) VALUES (2, "Windows")',

			'INSERT INTO courses (courseid, name, coursedesc, xen, length, samemachine, memory, fullvirt, profileid) VALUES (-1, "Admin", "Admin", 1, 0, 0, 0, 0, -1)',
			'INSERT INTO images (imageid, typeid, name, fullvirt, memory, image) VALUES (-1, 0, "Admin", 0, 512, "")',
			'INSERT INTO course_images (course_imageid, courseid, imageid, name) VALUES (-1, -1, -1, "Admin")',

			'CREATE VIEW stats_transcript AS SELECT cc.userid, c.name, c.coursedesc, COUNT(cc.courseid) AS times, ROUND(AVG(TIMESTAMPDIFF(MINUTE, cc.start, cc.end)),0) AS length, c.length AS course_length FROM complete_courses cc, courses c WHERE c.courseid = cc.courseid GROUP BY cc.courseid, cc.userid ORDER BY cc.userid',
			'CREATE VIEW stats_courses AS select c.name as name, c.coursedesc as coursedesc, ROUND(AVG(TIMESTAMPDIFF(MINUTE, cc.start, cc.end)),0) AS length, SUM(extend) AS extended, COUNT(cc.ccid) AS times, c.length AS course_length from complete_courses cc, courses c WHERE c.courseid = cc.courseid GROUP BY cc.courseid ORDER BY name',
			'CREATE VIEW stats_courses_dates AS SELECT c.name AS name, c.coursedesc AS coursedesc, DATE_FORMAT(cc.start, \'%Y-%m-%d\') AS date, COUNT(cc.ccid) AS times FROM complete_courses cc, courses c WHERE cc.courseid = c.courseid GROUP BY date, cc.courseid ORDER BY date DESC, name',
			'CREATE VIEW stats_courses_byday AS SELECT COUNT(ccid) AS times, DAYNAME(start) AS dayName, DAYOFWEEK(start) AS dayOfWeek FROM complete_courses GROUP BY dayOfWeek ORDER BY dayOfWeek',
			'CREATE VIEW stats_courses_byhour AS SELECT HOUR(start) AS hour, COUNT(ccid) AS times FROM complete_courses GROUP BY hour',
			'CREATE VIEW stats_image AS SELECT i.name AS name, i.image as image, ROUND(AVG(TIMESTAMPDIFF(MINUTE, cc.start, cc.end)),0) AS length, COUNT(cc.ccid) AS times FROM complete_courses cc, course_images ci, images i WHERE cc.courseid = ci.courseid and ci.imageid = i.imageid GROUP BY ci.imageid ORDER BY name',
			'CREATE VIEW stats_image_dates AS SELECT i.name AS name, i.image AS image, DATE_FORMAT(cc.start, \'%Y-%m-%d\') AS date, COUNT(ci.course_imageid) AS times FROM complete_courses cc, images i, course_images ci WHERE cc.courseid = ci.courseid AND ci.imageid = i.imageid GROUP BY date ORDER BY date DESC',
			'CREATE VIEW stats_image_byday AS SELECT COUNT(ci.course_imageid) AS times, DAYNAME(cc.start) as dayName, DAYOFWEEK(start) AS dayOfWeek FROM complete_courses cc, course_images ci WHERE cc.courseid = ci.courseid GROUP BY dayOfWeek ORDER BY dayOfWeek',
			'CREATE VIEW stats_image_byhour AS SELECT HOUR(cc.start) AS hour, COUNT(ci.course_imageid) AS times FROM complete_courses cc, course_images ci WHERE cc.courseid = ci.courseid GROUP BY hour ORDER BY hour',
			'CREATE VIEW stats_dates AS SELECT DATE_FORMAT(start, \'%Y-%m-%d\') AS date, COUNT(ccid) AS times FROM complete_courses GROUP BY date ORDER BY date DESC',
			'CREATE VIEW stats_machines AS SELECT DATE_FORMAT(cc.start, \'%Y-%m-%d\') AS date, COUNT(ci.course_imageid) AS machine_count FROM course_images ci, complete_courses cc WHERE cc.courseid = ci.courseid GROUP BY date ORDER BY date DESC',
			'CREATE VIEW stats_image_permonth AS SELECT MONTH(cc.start) AS month, YEAR(cc.start) AS year, COUNT(ci.course_imageid) AS times FROM course_images ci, complete_courses cc WHERE cc.courseid = ci.courseid GROUP BY YEAR(cc.start), MONTH(cc.start) ORDER BY year, month',
			'CREATE VIEW stats_courses_permonth AS SELECT MONTH(start) AS month, YEAR(start) AS year, COUNT(userid) AS times FROM complete_courses GROUP BY YEAR(start), MONTH(start) ORDER BY year, month'
		);

		foreach ($queries as $query) {
			$result = mysql_query($query);
			if (!$result) {
				die ("An Error has occured creating the tables. ".mysql_error());
			}
		}

		mysql_close();

		global $authentication;
		if (strcmp($authentication, "") == 0 || strcmp($authentication, "simple") == 0) {
			mysql_connect($dbhost, $username, $password);
			@mysql_select_db($database) or die("Unable to select database \"$database\"");

			$passhash = md5("password");

			$query = "INSERT INTO users (username, passhash, email, realname, admin) VALUES (\"admin\", \"$passhash\", 'root@localhost', 'Administrator', 1)";
			$result = mysql_query($query);
			if (!$result) {
				die ("An error has occured adding admin. ".mysql_error());
			}

			print "Admin user added.  Username: \"admin\" Password: \"password\"\n";
			print "Setup is complete.  Please remove the setup.php file, and navigate to eipen/admin/\n";

			mysql_close();
		} elseif (strcmp($authentication, "ldap") == 0) {
?>
<html>
<head>
<title>Eipen - Setup</title>
<style>@import url(defaultstyle.css);</style>
</head>
<body>
<div id="wrapper" class="clearfix">
<div id="navwrapper">&nbsp;</div>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<ul id="form">                
<li class="formlabel">Admin LDAP login</li>
<li class="forminput"><input type="text" size="50" maxlength="20" name="admin" />
<li class="formsplit"></li>
</ul>
<input type="submit" value="Save Course" name="submit" />
</form>
<?php 
	footer(); 
?>
</body>
</html>
<?php
		} else {
			die ("Invalid authentication type \"$authentication\"");
		}

	}
}
?>
