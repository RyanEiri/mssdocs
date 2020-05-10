<?php
require_once ('../classes.plzone.net/classes.php');
require_once ('../config.plzone.net/config.php');
$usurper = new UserCookie();
$usurper->DeleteIt();
$usurper->CheckIt();
session_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>PLZone Contact Database</title>
<!--<link rel="icon" href="http://dalcoserve/favicon.ico" type="image/vnd.microsoft.icon">-->
<!--<link rel="stylesheet" type="text/css" href="css/style.css">-->
<?php
$_SESSION[paginate] = 1; 
require_once ('http://functions.plzone.net/functions.php');
/*require_once ('body/menu.php');*/
?>
</head>

<body>
<a href="<?php echo $_SERVER[PHP_SELF] ?>?header=cookieDel">logout</a>&nbsp;&nbsp;&nbsp;<a href="./counter.php">counter</a>
<center>
<div id=header>
<img src="./images/web_banner.jpg" alt="CleanFlow/Dalco">
</div>

<?php
echo '<div id="centermain">';
if (!$contact_object) {
	$contact_object = new ContactGrab($db, 'customers_organization');
	$serialized_contact_object = serialize($contact_object);
	$fp = fopen("/var/www/htdocs/contacts/serialized_objects/contact_object", "w");
	fwrite($fp, $serialized_contact_object);
	fclose($fp);
	$_SESSION[contact_array] = $contact_object->sorted_contacts;
} else {
	$serialized_contact_object = implode("", @file("/var/www/htdocs/contacts/serialized_objects/contact_object"));
	$contact_object = unserialize($serialized_contact_object);
}

if($contact_object->error=='nohits') {
	echo "<font color='red'><center><div class='warning'><strong>Search criteria found no match!<br>Displaying all contacts.</strong></div></center></font><br>";
}

if($_GET[body]) {
	switch ($_GET[body]) {
	case 'add':
		require_once ('./body/add.php');
		break;
	case 'delete':
		require ('./body/delete.php');
		break;
	case 'view':
		require_once ('./body/view.php');
		break;
	case 'change':
		require_once ('./body/change.php');
		break;
	case 'copy':
		require ('./body/copy.php');
		break;
	case 'menustick':
		if ($_GET[menustick]==1) {
			$_SESSION[menu_show]=1;
		} elseif ($_GET[menustick]==0) {
			$_SESSION[menu_show]=0;
		}
		break;
	case 'test':
		pagisort('customers_name');
		print_r($_SESSION[contact_object]->sorted_contacts);
		break;
	}
} else {
	$criteria = ltrim($_SESSION[show], "\/");
	$criteria = rtrim($criteria, "\/i");
	if ($_SESSION[paginate]=='1') {
		if($contact_object->PreviousPage < 0) {
			printf("<div class=bodytable><font size=2>previous&nbsp;&nbsp;");
		} elseif($contact_object->PreviousPage==0) {
			printf("<div class=bodytable><font size=2><a href='%s?page=zero'>previous&nbsp;&nbsp;", $_SERVER[PHP_SELF]);
		} else {
			printf("<div class=bodytable><font size=2><a href='%s?page=%s'>previous</a>&nbsp;&nbsp;", $_SERVER[PHP_SELF], $contact_object->PreviousPage);
		}
		if ($contact_object->NumberOfPages) {
			foreach($contact_object->NumberOfPages as $value) {
				$array_num = $value - 1;
				if($array_num==0) $array_num='zero';
       		        	printf("<a href='%s?page=%s'>%s</a>", $_SERVER[PHP_SELF], $array_num, $value);
				if($value != $contact_object->PageSize) {
       		               		echo " - ";
       	       	  		}
       		 	}
		}
		if($contact_object->NextPage==$contact_object->PageSize) {
			printf("&nbsp;&nbsp;next</font></div>");
		} else {
			printf("&nbsp;&nbsp;<a href='%s?page=%s'>next</a></font></div>", $_SERVER[PHP_SELF], $contact_object->NextPage);
		}
	}
?>
	<div class=bodytable>
<center><?php if ($_SESSION[paginate]=='1') { ?><font size=2>page:&nbsp;<?php printf("%s", ($contact_object->Page + 1));?>&nbsp;|&nbsp;<?php } ?>sorted by:&nbsp;<?php echo $_SESSION[sort];?>&nbsp;|&nbsp;search criteria:&nbsp;<?php echo $contact_object->show_search ?></font></center>
	</div>

	<div class=bodytable>
	<center>
	<div class=bodytablelimitedcontent>
	<form name="search" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="GET">
	search: 
	<input type="text" size="50" name="search">
	<input type="hidden" name="show" value="search">
	<input type="hidden" name="page" value="zero">
	<input type="submit" value="GO!">
	</form>	
	<script language="Javascript" type="text/javascript">
	document.search.search.focus();
	</script>
	</div>
	</center>
	</div>
<?php
	$letters_array = range('a', 'z');
	$letters_array_size = sizeof($letters_array);
	echo "<div class=bodytable><font size=3><center>";
	for ($counter = 0; $counter < $letters_array_size; $counter++) {
		printf("<a href='%s?sort=%s&show=%s&page=zero'>%s</a>", $_SERVER[PHP_SELF], $_SESSION[sort], $letters_array[$counter], $letters_array[$counter]);
		if ($letters_array_size != ($counter + 1)) {
			echo " - ";
		}
	}
	printf("<br><a href='%s?sort=%s&show=all&page=zero'>all</a>", $_SERVER[PHP_SELF], $_SESSION[sort]);
	echo '</div>';
	if(is_array($_SESSION[contact_array])) {
	echo "<table class=bodytable>";
?>
	<form name=checks>
	<tr class=bodytitle><td class=bodytitleitem>Select</td><td class=bodytitleitem><a href='<?php echo $_SERVER['PHP_SELF'] ?>?sort=customers_name&page=zero'>Name</a></a></td><td class=bodytitleitem><a href='<?php echo $_SERVER['PHP_SELF'] ?>?sort=customers_organization&page=zero'>Organization</a></td><td class=bodytitleitem><a href='<?php echo $_SERVER['PHP_SELF'] ?>?sort=customers_city&page=zero'>City</a></td><td class=bodytitleitem><a href='<?php echo $_SERVER['PHP_SELF'] ?>?sort=calendar_n&search=yes&show=search&page=zero'>Cal.N.</a></td><td class=bodytitleitem><a href='<?php echo $_SERVER['PHP_SELF'] ?>?sort=calendar_a&search=yes&show=search&page=zero'>Cal.A.</a></td><td class=bodytitleitem><a href='<?php echo $_SERVER['PHP_SELF'] ?>?sort=catalog&search=yes&show=search&page=zero'>Cat.</a></td></tr>
<?php
	if($_SESSION[paginate]==1) {
		foreach ($contact_object->chunks[$contact_object->Page] as $value) {
			$customers_organization = addslashes($value['customers_organization']);
			echo "<tr>";
			echo "<td class=bodyitem><center><a name=$value[customers_id]><input type=checkbox name=select value=" . $value['customers_id'] . "></a></center></td>";
//			echo "<td class=bodyitem><a href=\" \" onclick=\"window.open('body/view.php?view=" . $value['customers_id'] . "','" . $value['customers_name'] . "','width=520,height=475')\">" . $value['customers_name'] . "</a></td>";
			echo "<td class=bodyitem><a href=\" \" onclick=\"newWindow('body/view.php?form=change&view=" . $value['customers_id'] . "','" . $value['customers_name'] . "')\">" . $value['customers_name'] . "</a></td>";
//			echo "<td class=bodyitem><a href=\" \" onclick=\"window.open('body/view.php?view=" . $value['customers_id'] . "','" . $value['customers_name'] . "','width=520,height=475')\">$value[customers_organization]</a></td>";
			echo "<td class=bodyitem><a href=\" \" onclick=\"newWindow('body/view.php?form=change&view=" . $value['customers_id'] . "','" . $customers_organization . "')\">" . $value['customers_organization'] . "</a></td>";
			echo "<td class=bodyitem>$value[customers_city]</td>";
			if ($value[calendar_n]=="YES") { echo "<td class=bodyitem>$value[calendar_n]</td>"; } else { echo "<td class=bodyitem>&nbsp;</td>"; }
			if ($value[calendar_a]=="YES") { echo "<td class=bodyitem>$value[calendar_a]</td>"; } else { echo "<td class=bodyitem>&nbsp;</td>"; }
			if ($value[catalog]=="YES") { echo "<td class=bodyitem>$value[catalog]</td>"; } else { echo "<td class=bodyitem>&nbsp;</td>"; }
			echo "<tr>";
		}
	} else {
		foreach ($contact_object->sorted_contacts as $value) {
                        echo "<tr>";
                        echo "<td class=bodyitem><center><a name=$value[customers_id]><input type=checkbox name=select value=$value[customers_id]></a></center></td><td class=bodyitem><a href='$_SERVER[PHP_SELF]?body=view&view=$value[customers_id]'>$value[customers_name]</a></td>";
                        echo "<td class=bodyitem><a href='$_SERVER[PHP_SELF]?body=view&view=$value[customers_id]'>$value[customers_organization]</td>";
			echo "</a>";
                        echo "<td class=bodyitem>$value[customers_city]</td>";
                        if ($value[calendar_n]=="YES") { echo "<td class=bodyitem>$value[calendar_n]</td>"; } else { echo "<td class=bodyitem>&nbsp;</td>"; }
                        if ($value[calendar_a]=="YES") { echo "<td class=bodyitem>$value[calendar_a]</td>"; } else { echo "<td class=bodyitem>&nbsp;</td>"; }
                        if ($value[catalog]=="YES") { echo "<td class=bodyitem>$value[catalog]</td>"; } else { echo "<td class=bodyitem>&nbsp;</td>"; }
                        echo "<tr>";
                }
	}
	echo "<table>";
	echo "</font></center>";
	echo "</form>";
	} else {
		echo "<font size=2>no contact information matches search criteria.</font>";
	}
	echo "</table>";
	echo "<div class=bodytable><font size=3><center>";
	for ($counter = 0; $counter < $letters_array_size; $counter++) {
		printf("<a href='%s?sort=%s&show=%s&page=zero'>%s</a>", $_SERVER[PHP_SELF], $_SESSION[sort], $letters_array[$counter], $letters_array[$counter]);
		if ($letters_array_size != ($counter + 1)) {
			echo " - ";
		}
	}
	printf("<br><a href='%s?sort=%s&show=all&page=zero'>all</a>", $_SERVER[PHP_SELF], $_SESSION[sort]);
	echo '</div>';
}
?>
</div>
</div>
</center>
</body>
</html>
