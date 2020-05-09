<?php
require_once ('../classes/classes.php');
require_once ('../config/config.php');
$usurper = new UserCookie();
$usurper->DeleteIt();
$usurper->CheckIt();
session_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Calendar Counter</title>
<?php
require_once ('../functions.plzone.net/functions.php');
?>
<body>
<a href="<?php echo $_SERVER[PHP_SELF] ?>?header=cookieDel">logout</a>
<center><div id=header>
<img src="../images/web_banner.jpg" alt="CleanFlow/Dalco">
</div>
<?php
	$querypop1="SELECT * FROM customers WHERE calendar_a='YES' AND calendar_n='NO'";
	$querypop2="SELECT * FROM customers WHERE calendar_a='NO' AND calendar_n='YES'";
	$querypop3="SELECT * FROM customers WHERE calendar_a='YES' AND calendar_N='YES'";
	$querypop4="SELECT * FROM customers WHERE catalog='YES'";

	$resultpop1=$db->query($querypop1) or die('select statement failed!');
	$resultpop2=$db->query($querypop2) or die('select statement failed!');
	$resultpop3=$db->query($querypop3) or die('select statement failed!');
	$resultpop4=$db->query($querypop4) or die('select statement failed!');

	$num_a=$resultpop1->size();
	$num_n=$resultpop2->size();
	$num_both=$resultpop3->size();
	$num_total_a=$num_a + $num_both;
	$num_total_n=$num_n + $num_both;
	$num_cat=$resultpop4->size();

	echo "<HR>";
	echo "<H1>";
	echo "Animal Calendars: " . $num_a . "<BR>";
	echo "Nudie Calendars: " . $num_n . "<BR>";
	echo "Both Calendars: " . $num_both . "<BR>";
	echo "<HR>";
	echo "Total Animal Calendars Needed: " . $num_total_a . "<BR>";
	echo "total Nudie Calendars Needed: " . $num_total_n . "<BR>";
	echo "<HR>";
	echo "Total Catalogues to Send Out: " . $num_cat . "<BR>";
	echo "<HR>";
	echo "</center></H1><H3>";
	echo "<a href=\"http://contacts/index.php?sort=&show=all&page=zero\">BACK</a>";
	echo "</H3>";

?>
</body>
</html>
