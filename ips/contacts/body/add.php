<?php
require_once ('../../classes/classes.php');
require_once ('../../config/config.php');
$usurper = new UserCookie();
$usurper->DeleteIt();
$usurper->CheckIt();
session_start();
require_once ('../../functions/php.php');
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
if($_POST[add] == 'yes') {
	// Gather variables from POST request if they aren't NULL
	if($_POST['address_1'] != NULL){$address_1 = $_POST['address_1'];}
	if($_POST['address_2'] != NULL){$address_2 = $_POST['address_2'];}
	if($_POST['city'] != NULL){$city = $_POST['city'];}
	if($_POST['postal_code'] != NULL){$postal_code = $_POST['postal_code'];}
	if($_POST['b_phone'] != NULL){$b_phone = $_POST['b_phone'];}
	if($_POST['f_phone'] != NULL){$f_phone = $_POST['f_phone'];}
	if($_POST['h_phone'] != NULL){$h_phone = $_POST['h_phone'];}
	if($_POST['c_phone'] != NULL){$c_phone = $_POST['c_phone'];}
	if($_POST['toll_free_phone'] != NULL){$toll_free_phone = $_POST['toll_free_phone'];}
	if($_POST['email'] != NULL){$email = $_POST['email'];}

	// Instantiate validation object
	$validator_object = new Validate();
	
	// Check for errors in request variables if they exist
	if($address_1){
	if(!$validator_object->check_address($address_1)){
		$error = "Address 1 is not a properly formatted address. Please correct and resubmit.\n\n";
	}
	}
	if($address_2){
	if(!$validator_object->check_address($address_2)){
		$error = $error . "Address 2 is not a properly formatted address. Please correct and resubmit.\n\n";
	}
	}
	if($city){
	if(!$validator_object->check_city($city)){
		$error = $error . "The city field is improperly formatted. Please correct and resubmit.\n\n";
	}
	}
	if($postal_code){
	if(!$validator_object->check_postal_code('Canada', $postal_code)){
		$error = $error . "The postal code is formatted incorrectly. Please correct and resubmit.\n\n";
	}
	}
	if($b_phone){
	if(!$validator_object->check_phone($b_phone)){
		$error = $error . "The business phone number is formatted incorrectly. Please correct and resubmit.\n\n";
	}
	}
	if($f_phone){
	if(!$validator_object->check_phone($f_phone)){
		$error = $error . "The fax phone number is formatted incorrectly. Please correct and resubmit.\n\n";
	}
	}
	if($h_phone){
	if(!$validator_object->check_phone($h_phone)){
		$error = $error . "The home phone number is formatted incorrectly. Please correct and resubmit.\n\n";
	}
	}
	if($c_phone){
	if(!$validator_object->check_phone($c_phone)){
		$error = $error . "The cell phone number is formatted incorrectly. Please correct and resubmit.\n\n";
	}
	}
	if($toll_free_phone){
	if(!$validator_object->check_phone($toll_free_phone)){
		$error = $error . "The toll free phone number is formatted incorrectly. Please correct and resubmit.\n\n";
	}
	}
	if($email){
	if(!$validator_object->check_email($email)){
		$error = $error . "The email address is either formatted incorrectly or the domain doesn't exist. Please correct and resubmit.\n\n";
	}
	}

	if((!$error) && ($_POST['change'])){
		$contact_object->ChangeContact();
		echo 'Change Successful!';
	} elseif((!$error) && ($_POST['add'])){
		$contact_object->AddContact();
		echo 'Added Contact Successfully!';
	} else {
		print($error);	
	}
//	print_r($_POST);
}
?>
