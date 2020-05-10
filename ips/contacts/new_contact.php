<?php
if ($_COOKIE['login']){
        list($c_username,$cookie_hash) = split(',',$_COOKIE['login']);
                if (md5($c_username.$secret_word) == $cookie_hash)
                { }
                else { header('Location: ../index.php'); }
                }else { header('Location: ../index.php');  }
?>

<head>
<title>Dalco Contact Database</title>
<link rel="stylesheet" type="text/css" href="../style.css">
<script src="../javascript/IEmarginFix.js" type="text/javascript"></script>
</head>
<html>
<body>
<center><div class=page>
<div id=header>
<img src="../images/dalco_logo.jpg">
</div>

<div id=leftcontent>
<div id=leftbox>
<div class=menuitem>
<a href="../menu.php">home</a>
</div>
<div class=menuitem>
<a href="index.php">contact database</a>
</div>
<div class=menuitem>
<a href=index.php?body=add>add a contact</a>
</div>
<div class=menuitem>
</div>
<div class=menuitem>
</div>
</div>
</div>

<?php
require_once ('../classes/classes.php');
require_once ('../config/config.php');
require_once ('../functions/functions.php');
if($_GET[body]) {
	echo '<div id="rightcontent">';
	switch ($_GET[body]) {
	case 'phpinfo':
		include './phpinfo.php';
		break;
	case 'add':
		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
?>

<form name="add" action="index.php?body=add" method="POST">
company:
<input type="text" name="company"><br /><br />
first name:
<input type="text" name="first_name">
last name:
<input type="text" name="last_name"<br /><br />
street address:
<input type="text" name="address" size="50"><br /><br />
business phone:
<input type="text" name="b_phone" size="13" maxlength="13">
cell phone:
<input type="text" name="c_phone" size="13" maxlength="13"><br /><br />
fax phone:
<input type="text" name="f_phone" size="13" maxlength="13">
home phone:
<input type="text" name="h_phone" size="13" maxlength="13"><br /><br />
email address:
<input type="text" name="email" size="25" maxlength="100"><br /><br />
<input type="submit" value="Create Contact">

<?php
	} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$query = "INSERT INTO contacts (company, first_name, last_name, address, business_phone, cell_phone, fax_phone, home_phone, email) VALUES ('$_POST[company]', '$_POST[first_name]', '$_POST[last_name]', '$_POST[address]', '$_POST[b_phone]', '$_POST[c_phone]', '$_POST[f_phone]', '$_POST[h_phone]', '$_POST[email]')";
		$result = $db->query($query) or die('Insert statement failed: Entry not added.');
		echo "Entry has been updated!<br><a href='index.php'>Back to list.</a>";
	}
		break;
	case 'remove':
		if($_GET[delete]) {
			if($_GET[confirm] == 0) {
				$query = "SELECT * FROM contacts WHERE contact_id='$_GET[delete]'";
				$result = $db->query($query) or die ('Select statement failed: Entry not available.');
				echo "are you certain you wish to delete the following entry?<br><br>"; 
				print_r($result);
				echo "<br><br><a href='index.php?body=remove&delete=$_GET[delete]&confirm=1>yes</a> or <a href=index.php>no</a>";
			} elseif($_GET[confirm] == 1) {
				$query = "DELETE FROM contacts WHERE contact_id='$_GET[delete]'";
				$db->query($query);
				echo "Deleted requested entry!";
			}
		} else {
			echo "Nothing to delete!";
		}
		break;
	case 'view':
		if($_GET[view]) {
			$query = "SELECT * FROM contacts WHERE contact_id='$_GET[view]'";
			$result = $db->query($query) or die ('Select statement failed: Entry not available.');
			$view = $result->fetch();
			printf("Contact ID: %s<br>", $view[contact_id]);
			echo "<a href=index.php>go back to listing</a><br><br>";
?>
			<form name="change" action="index.php?body=change&change=<?php echo $view[contact_id]; ?>" method="POST">
			company:
			<input type="text" name="company" value="<?php echo $view[company] ?>"><br /><br />
			first name:
			<input type="text" name="first_name" value="<?php echo $view[first_name] ?>">
			last name:
			<input type="text" name="last_name" value="<?php echo $view[last_name] ?>"><br /><br />
			street address:
			<input type="text" name="address" size="50" value="<?php echo $view[address] ?>"><br /><br />
			business phone:
			<input type="text" name="b_phone" size="13" maxlength="13" value="<?php echo $view[business_phone] ?>">
			cell phone:
			<input type="text" name="c_phone" size="13" maxlength="13" value="<?php echo $view[cell_phone] ?>"><br /><br />
			fax phone:
			<input type="text" name="f_phone" size="13" maxlength="13" value="<?php echo $view[fax_phone] ?>">
			home phone:
			<input type="text" name="h_phone" size="13" maxlength="13" value="<?php echo $view[home_phone] ?>"><br /><br />
			<a href="mailto:<?php echo $view[email] ?>">email address:</a>
			<input type="text" name="email" size="25" maxlength="100" value="<?php echo $view[email] ?>"><br /><br />
			<input type="submit" value="change contact">
<?php
		} else {
			echo "nothing to view.";
		}
		break;
	case 'change':
		if($_GET[change]) {
			$query1 = "SELECT * FROM contacts WHERE contact_id='$_GET[change]'";
			$query2 = "UPDATE contacts SET company='$_POST[company]', first_name='$_POST[first_name]', last_name='$_POST[last_name]', address='$_POST[address]', business_phone='$_POST[b_phone]', cell_phone='$_POST[c_phone]', fax_phone='$_POST[f_phone]', home_phone='$_POST[h_phone]', email='$_POST[email]' WHERE contact_id='$_GET[change]'";
			$db->query($query2) or die ('Insert statement failed: Entry not updated.');
			$result = $db->query($query1) or die ('Select statement failed: Entry not available.');
			$view = $result->fetch();
			echo "contact modified<br>";
			printf("contact id: %s<br>", $view[contact_id]);
			printf("<a href=index.php?body=view&view=%s>go back to view</a><br><br>", $view[contact_id]);
?>   
			<form name="change" action="index.php?body=change&change=<?php echo $view[contact_id]; ?>" method="POST">
			company:
			<input type="text" name="company" value="<?php echo $view[company] ?>"><br /><br />
			first name:
			<input type="text" name="first_name" value="<?php echo $view[first_name] ?>">
			last name:
			<input type="text" name="last_name" value="<?php echo $view[last_name] ?>"><br /><br />
			street address:
			<input type="text" name="address" size="50" value="<?php echo $view[address] ?>"><br /><br />
			business phone:
			<input type="text" name="b_phone" size="13" maxlength="13" value="<?php echo $view[business_phone] ?>">
			cell phone:
			<input type="text" name="c_phone" size="13" maxlength="13" value="<?php echo $view[cell_phone] ?>"><br /><br />
			fax phone:
			<input type="text" name="f_phone" size="13" maxlength="13" value="<?php echo $view[fax_phone] ?>">
			home phone:
			<input type="text" name="h_phone" size="13" maxlength="13" value="<?php echo $view[home_phone] ?>"><br /><br />
			<a href="mailto:<?php echo $view[email] ?>">email address:</a>
			<input type="text" name="email" size="25" maxlength="100" value="<?php echo $view[email] ?>"><br /><br />
			<input type="submit" value="change contact">
<?php	
		} else {
			echo "nothing to change";
		}
		break;
	}
} else {
	echo '<div id="rightcontent">';

	$query = "SELECT * FROM customers";
	$result = $db->query($query) or die('Select statement failed: Entries not shown.');
	$contact_array = $result->fetchArray();	
	if (($_GET[sort]) AND ($contact_array)) {
		switch ($_GET[sort]) {	
		case 'customers_organization':
			array_key_multi_sort($contact_array, 'customers_organization');	
			break;
		case 'customers_name':
			array_key_multi_sort($contact_array, 'customers_name');
			break;
		case 'customers_address_1':
			array_key_multi_sort($contact_array, 'customers_address_1');
			break;
		case 'customers_address_2':
			array_key_multi_sort($contact_array, 'customers_address_2');
			break;
		case 'customers_city':
			array_key_multi_sort($contact_array, 'customers_city');
			break;
		case 'customers_province':
			array_key_multi_sort($contact_array, 'customers_province');
			break;
		case 'customers_postal_code':
			array_key_multi_sort($contact_array, 'customers_postal_code');
			break;
		}
	} else {
		array_key_multi_sort($contact_array, 'company');
	}
	$bc_open = "<b><center>";
	$bc_close = "</center></b>";
	if($contact_array) {
	echo "<table class=bodytable>";
	echo "<tr class=bodytitle><td class=bodytitleitem>$bc_open Delete $bc_close</td><td class=bodytitleitem>$bc_open <a href='index.php?sort=company'>Company</a> $bc_close</a></td><td class=bodytitleitem>$bc_open <a href='index.php?sort=first_name'>First Name</a> $bc_close</td><td class=bodytitleitem>$bc_open <a href='index.php?sort=last_name'>Last Name</a> $bc_close</td><td class=bodytitleitem>$bc_open <a href='index.php?sort=business_phone'>Business Phone</a> $bc_close</td><td class=bodytitleitem>$bc_open <a href='index.php?sort=email'>E-Mail</a> $bc_close</td></tr>";
	foreach ($contact_array as $value) {
		echo "<tr>";
		echo "<td class=bodyitem>$bc_open<a href='index.php?body=remove&delete=$value[contact_id]&confirm=0'>*</a>$bc_close</td>";
		echo "<td class=bodyitem><a href='index.php?body=view&view=$value[contact_id]'>$value[company]</a></td>";
		echo "<td class=bodyitem>$value[first_name]</td>";
		echo "<td class=bodyitem>$value[last_name]</td>";
		echo "<td class=bodyitem>$value[business_phone]</td>";
		echo "<td class=bodyitem><a href=mailto:$value[email]>$value[email]</a></td>";
		echo "<tr>";
	}
	echo "<table>";
	} else {
		echo "no contact information to display.";
	}
	
	echo '</div>';
}
?>
</div>
</div>
</center>
</body>
</html>
