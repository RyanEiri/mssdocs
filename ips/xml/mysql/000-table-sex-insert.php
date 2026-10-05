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
	if(!empty($_SESSION['sex']) && ADMIN_STATUS){
		$sex_session = $_SESSION['sex'];
	} else {
		$sex_session = NULL;
		$errors['sex'] = "No sex SESSION data available! Perhaps you need to reload the page.";
	}
	
	// Initialize database connection
	define("MIH_DB_NAME", "mih");
	$mih_db = new SQL(DB_HOST, DB_USER, DB_PASS, MIH_DB_NAME);

	// If initialization succeeds, proceed with MySQL statement.
	if($sex_post !== NULL && $sex_session !== NULL && ADMIN_STATUS) {
		
		foreach($sex_session as $sex_value) {
			$sex_id = $sex_value['sex_id'];
			$gender = $sex_value['gender'];
			$code = $sex_value['code'];

			$query = "INSERT INTO `sex` (`sex_id`, `gender`, `code`) VALUES ('".$sex_id."', '".$gender."', '".$code."');";
			$result = $mih_db->query($query);
			if($result->isError()){
				$errors['sex'] = $result->queryErrorMessage();
			}
			
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