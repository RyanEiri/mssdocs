<?php
if ($_COOKIE['login']){
        list($c_username,$cookie_hash) = split(',',$_COOKIE['login']);
                if (md5($c_username.$secret_word) == $cookie_hash)
                { }
                else { header('Location: ./index.php'); }
                }else { header('Location: ./login.php');  }
session_start();

if($_GET[copy]) {
	$copy = explode(",", $_GET[copy]);
	if($_GET[confirm] == 0) {
                 echo "this action has not been confirmed, please use the normal method of implementing this functionality.<br><br>";
	} elseif($_GET[confirm] == 1) {
		foreach($copy as $value) {
			$query = "INSERT INTO customers (customers_name, customers_position, customers_organization, customers_address_1, customers_address_2, customers_city, customers_province, customers_postal_code, calendar_n, calendar_a, catalog, business_phone, cell_phone, fax_phone, home_phone, toll_free_phone, email, notes, vendor) SELECT customers_name,customers_position,customers_organization,customers_address_1,customers_address_2,customers_city,customers_province,customers_postal_code,calendar_n,calendar_a,catalog,business_phone,cell_phone,fax_phone,home_phone,toll_free_phone,email,notes,vendor FROM customers WHERE customers_id='$value'";
			$db->query($query) or die('copy failed!');
			echo "<h1>Entry " . $value . ": Updated. Item " . $value . " COPIED!<br></h1>";
		}
		echo "<h2>All requests complete. Please close this window.</h2>";
        }
} else {
	echo "Nothing to delete!";
}
?>
