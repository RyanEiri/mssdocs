<?php
// Load the initialiazation files.
chdir('..');
include getcwd().'/php/boot.php';

// Initialize authentication control cookie
$login_cookie = new UserCookie();

if($login_cookie->CheckIt()) {
	// Grab the current user's info.
	$username = $login_cookie->username;
	$userval = new UserGrab($username);
	$admin = $userval->admin;

  // begin our ajax handling
  $errors	= array();	// array to hold validation errors
  $data		= array();	// array to pass back data

  // validate the variables ========================================
  // If any of these variables don't exist, add an error to our
  // $errors array. If they do exist add them to a variable value.
  // If the folder value is the same as the current path, invalidate
  // and pass error.

  if (empty($_POST['filesInFolder'])) {
	$errors['filesInFolder'] = 'No files array found! Aborting operation.';
  } else {
	$files_in_folder = $_POST['filesInFolder'];
  }
  if (!$admin) {
	$errors['admin'] = 'Administrator access is required for these changes.';
  }

// return a response =============================================

	// if there are any errors in our errors array,
	// return a success boolean of false
  if ( ! empty($errors)) {

	// if there are items in our errors array,
	// return those errors
	$data['success'] = false;
	$data['errors'] = $errors;
	if(isset($warnings)) {
	  $data['warnings'] = $warnings;
	}
  } else {

	// if there are no errors process our form,
	// then return a message

	// DO ALL YOUR FORM PROCESSING HERE
	// THIS CAN BE WHATEVER YOU WANT TO DO
	// (LOGIN, SAVE, UPDATE, WHATEVER)



	// show a message of success and provide a boolean
	// success variable set to true.
	$data['success'] = true;
	if(isset($warnings)) {
	  $data['warnings'] = $warnings;
	}
	$data['message'] = 'Success!';
	$data['files_in_folder'] = $files_in_folder;
  }

  // complete our ajax handling with our json output
  // return all our data to an AJAX call

  header('Content-Type: application/json');
  echo json_encode($data);
}
?>
