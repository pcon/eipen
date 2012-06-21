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

	$stat = "overview";

	if (isset($_GET['stat'])) {
		$stat = $_GET['stat'];
	}
?>

<html>
<head><title>Eipen - Statistics</title>
<style>@import url(defaultstyle.css);</style></head>
<body>
<div id="wrapper" class="clearfix">
<?php
	nav();
	subnav("stats");
	print_messages();
?>
<div id="stats">
<div id="stats_left">
<?php
	print '<ul class="statsmenu">';
	print '<li id="statsmainitem"><a href="stats.php?stat=overview">Overview</a></li>';
	if (strcmp($stat,"overview") == 0) {
		//print '<li><ul class="substats">';
		//Print overview submenu
		//print '</ul></li>';
	}
	print '<li id="statsmainitem"><a href="stats.php?stat=courses">Courses</a></li>';
	if (strcmp($stat,"courses") == 0) {
		print '<li><ul class="substats">';
		print '<li><a href="stats.php?stat=courses&type=usage">Course Usage</a></li>';
		print '<li><a href="stats.php?stat=courses&type=daily">Daily Usage</a></li>';
		print '<li><a href="stats.php?stat=courses&type=time">Time Usage</a></li>';
		print '</ul></li>';
	}
	print '<li id="statsmainitem"><a href="stats.php?stat=images">Images</a></li>';
	if (strcmp($stat,"images") == 0) {
		print '<li><ul class="substats">';
		print '<li><a href="stats.php?stat=images&type=usage">Image Usage</a></li>';
		print '<li><a href="stats.php?stat=images&type=daily">Daily Usage</a></li>';
		print '<li><a href="stats.php?stat=images&type=time">Time Usage</a></li>';
		print '</ul></li>';
	}
?>
</div>
<div id="stats_right">
<?php

	if (strcmp($stat, "courses") == 0) {
		require_once("stats/courses.inc");
	} elseif (strcmp($stat, "users") == 0) {
		require_once("stats/users.inc");
	} elseif (strcmp($stat, "images") == 0) {
		require_once("stats/images.inc");
	} else {
		require_once("stats/overview.inc");
	}
?>
</div>
</div>

<?php
	footer();
?>
</div> <!-- wrapper -->
</body>
</html>
