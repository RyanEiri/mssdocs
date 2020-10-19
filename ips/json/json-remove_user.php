<?php
session_start();
chdir('..');
include getcwd().'/php/boot.php';
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {
	
	define("USERNAME", $login_cookie->username);
	
	$userval = new UserGrab(USERNAME);
	define("ADMIN_STATUS", $userval->admin);
	
if(ADMIN_STATUS){
  // instantiate an ActOnSingleUser object
  if(!isset($user_object)) {
		$user_object = new ActOnSingleUser();
  }
  // begin our ajax handling
  $errors	= array();	// array to hold validation errors
  $data		= array();	// array to pass back data
  $value	= array();

// return a response =============================================

	// if there are any errors in our errors array, 
	// return a success boolean of false
  if ( ! empty($errors)) {

	// if there are items in our errors array, 
	// return those errors
	$data['success'] = false;
	$data['errors'] = $errors;
	$data['value'] = $value;
  } else {

	// if there are no errors process our form, 
	// then return a message

	// DO ALL YOUR FORM PROCESSING HERE
	// THIS CAN BE WHATEVER YOU WANT TO DO 
	// (LOGIN, SAVE, UPDATE, WHATEVER)
	if($user_object->removeUser() !== FALSE){

  	  // show a message of success and provide a true 
	  // success variable
	  $data['success'] = true;
	  $data['message'] = 'Success!';
	  $data['value'] = $value;
	} else {
	  $errors['id'] = 'User not deleted!';
	  $data['success'] = false;
	  $data['errors'] = $errors;
	  $data['value'] = $value;
	}
  }

  // complete our ajax handling with our json output
  // return all our data to an AJAX call
  header('Content-Type: application/json');
  echo json_encode($data);
}
}
?>
