<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/ips/php/boot.php';
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {

	define("USERNAME", $login_cookie->username);

	$userval = new UserGrab(USERNAME);
	define("ADMIN_STATUS", $userval->admin);

	if(ADMIN_STATUS){
	  // instantiate an ActOnSingleUser object
	  if(!isset($files_object)) {
	  	$files_object = new FileList();
	  }
	  // begin our ajax handling
	  $errors	= array();	// array to hold validation errors
		$warnings = array(); // array to hold validation warnings
	  $data		= array();	// array to pass back data

	  // validate the variables ========================================
	  // If any of these variables don't exist, add an error to the
	  // $errors array.

	  if (empty($_SESSION['file_urls'])) {
			$errors['file_urls'] = 'Missing file URL data.';
		} else {
			$file_urls = $_SESSION['file_urls'];
		}

	  if ($_POST['change_urls'] === 'true') {
			$_SESSION['change_urls'] = 1;
	  } else {
			$_SESSION['change_urls'] = 0;
			$warnings['change_urls'] = 'If you want to make the changes, confirm by checking the URL checkbox!';
	  }

	// return a validation response =============================================

	  // if there are any errors in our errors array,
	  // return a success boolean of false
	  if (!empty($errors) || !empty($warnings)) {

			// if there are items in our errors array,
			// return those errors
			$data['success'] = false;
			if(!empty($errors)) {
				$data['errors'] = $errors;
			}
			if (!empty($warnings)) {
				$data['warnings'] = $warnings;
			}

	  } else {

			// Perform DB query upon validation
			$fileChange = new ActOnSingleFile();
			// Make the requested changes to DATABASE
			foreach($file_urls as $value) {
				if($fileChange->changeFileSystemDB($value['id'], $value['name'], $value['url'], $value['folder_id']) !== FALSE){
					continue;
				} else {
					if($fileChange->change_file_mysql_error){
						$errors['database'] = $fileChange->change_file_mysql_error;
					}
					break;
				}
			}

		  // show a message of success and provide a true
		  // success variable
		  $data['success'] = true;
		  $data['message'] = 'Success!';
	  }

	  // complete our ajax handling with our json output
	  // return all our data to an AJAX call
	  header('Content-Type: application/json');
	  echo json_encode($data);

	}
}
?>
