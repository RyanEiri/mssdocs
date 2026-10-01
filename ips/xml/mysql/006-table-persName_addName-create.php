<?php
include $_SERVER['DOCUMENT_ROOT'].'/ips/php/boot.php';
session_start();
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {
	define("USERNAME", $login_cookie->username);
	$userval = new UserGrab(USERNAME);
	define("ADMIN_STATUS", $userval->admin);

	// begin our ajax handling
	$errors	= array();	// array to hold validation errors
	$warnings = array(); // array to hold warning info
	$data		= array();	// array to pass back data

	//
	// Initialization and Validation
	//

	// Initialize POST variable.
	if(!empty($_POST['sex']) && ADMIN_STATUS){
		$sex_post = $_POST['sex'];
	} else {
		$sex_post = NULL;
		$warnings['sex'] = "No posted sex request!";
	}

	// Initialize SESSION data
	if(!empty($_SESSION['persName_addName']) && ADMIN_STATUS){
		$persName_addName_session = $_SESSION['persName_addName'];
	} else {
		$persName_addName_session = NULL;
		$errors['sex'] = "No sex SESSION data available! Perhaps you need to reload the page.";
	}
	
	// Initialize database connection
	define("MIH_DB_NAME", "mih");
	$mih_db = new SQL(DB_HOST, DB_USER, DB_PASS, MIH_DB_NAME);

	// If initialization succeeds, proceed with MySQL statement.
	if($sex_post !== NULL && $persName_addName_session !== NULL && ADMIN_STATUS) {

		$query = "CREATE TABLE `mih`.`persName_addName` ( `persName_addName_id` INT NOT NULL ,  `persName_id` INT NOT NULL ,  `attribute_id` INT NOT NULL ,  `addName` VARCHAR(255) NULL ,    PRIMARY KEY  (`persName_addName_id`)) ENGINE = InnoDB;";
		$result = $mih_db->query($query);
		if($result->isError()){
			$errors['sex'] = $result->queryErrorMessage();
		}

		$foreign_key_persName_id = "ALTER TABLE `persName_addName` ADD  CONSTRAINT `persName_addName-persName_id` FOREIGN KEY (`persName_id`) REFERENCES `persName`(`persName_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;";
		$fkey_persName_id_result = $mih_db->query($foreign_key_persName_id);
		if($fkey_persName_id_result->isError()){
			$errors['sex'] = $fkey_persName_id_result->queryErrorMessage();
		}
		
		$foreign_key_attribute_id = "ALTER TABLE `persName_addName` ADD  CONSTRAINT `persName_addName-attribute_id` FOREIGN KEY (`attribute_id`) REFERENCES `attributes`(`attribute_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;";
		$fkey_attribute_id_result = $mih_db->query($foreign_key_attribute_id);
		if($fkey_attribute_id_result->isError()){
			$errors['sex'] = $fkey_attribute_id_result->queryErrorMessage();
		}

		if(!empty($errors)){
			$data['success'] = FALSE;
			$data['errors'] = $errors;
			if(isset($warnings)){ $data['warnings'] = $warnings; }			
		} else {
			$data['success'] = TRUE;
			$data['message'] = "Query succeeded: " . ' SQL: ' . $query;
			if(isset($warnings)){ $data['warnings'] = $warnings; }
		}


	} else {
		$data['success'] = FALSE;
		$data['errors'] = $errors;
		$data['warnings']['sex'] = 'There is something wrong, please contact your admin.';	
	}
	// end of sex_post, sex_session and ADMIN_STATUS validation

	// complete our ajax handling with our json output
	// return all our data to an AJAX call
	header('Content-Type: application/json');
	echo json_encode($data);
}
// end of cookie validation
?>