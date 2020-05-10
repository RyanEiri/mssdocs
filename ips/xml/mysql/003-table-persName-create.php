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
	if(!empty($_SESSION['persName']) && ADMIN_STATUS){
		$persName_session = $_SESSION['persName'];
	} else {
		$persName_session = NULL;
		$errors['sex'] = "No sex SESSION data available! Perhaps you need to reload the page.";
	}
	
	// Initialize database connection
//	define("DB_HOST", "localhost");
//	define("DB_USER", "d7gonzo");
//	define("DB_PASS", "aSUjiSpHfXrt2pwTxZnw");
	define("MIH_DB_NAME", "mih");
	$mih_db = new SQL(DB_HOST, DB_USER, DB_PASS, MIH_DB_NAME);

	// If initialization succeeds, proceed with MySQL statement.
	if($sex_post !== NULL && $persName_session !== NULL && ADMIN_STATUS) {

		$query = "CREATE TABLE `mih`.`persName` ( `persName_id` INT NOT NULL ,  `person_id` INT NOT NULL ,  `xml_id` VARCHAR(255) NULL ,  `attribute_id` INT NOT NULL ,    PRIMARY KEY  (`persName_id`)) ENGINE = InnoDB;";
		$result = $mih_db->query($query);
		if($result->isError()){
			$errors['sex'] = $result->queryErrorMessage();
		}

		$foreign_key_person_id = "ALTER TABLE `persName` ADD  CONSTRAINT `persName-person_id` FOREIGN KEY (`person_id`) REFERENCES `person`(`person_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;";
		$fkey_person_id_result = $mih_db->query($foreign_key_person_id);
		if($fkey_person_id_result->isError()){
			$errors['sex'] = $fkey_person_id_result->queryErrorMessage();
		}
		
		$foreign_key_xml_id = "ALTER TABLE `persName` ADD  CONSTRAINT `persName-xml_id` FOREIGN KEY (`xml_id`) REFERENCES `person`(`xml_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;";
		$fkey_xml_id_result = $mih_db->query($foreign_key_xml_id);
		if($fkey_xml_id_result->isError()){
			$errors['sex'] = $fkey_xml_id_result->queryErrorMessage();
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