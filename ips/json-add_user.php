<?php
require_once ('./classes/classes.php');
require_once ('./config/config.php');
session_start();
if($_SESSION['login_cookie']) {
	$login_cookie = $_SESSION['login_cookie'];
} else {
	$login_cookie = new UserCookie();
}
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {
	// Grab the current user's info.
	$username = $login_cookie->username;
	$userval = new UserGrab($username);
	$admin = $userval->admin;
if($admin){
  // instantiate an ActOnSingleUser object
  if(!isset($user_object)) {
  	$user_object = new ActOnSingleUser();
  }
  // begin our ajax handling
  $errors	= array();	// array to hold validation errors
  $data		= array();	// array to pass back data
  $value	= array();

  // validate the variables ========================================
  // If any of these variables don't exist, add an error to our 
  // $errors array.

  if (empty($_POST['username']))
	$errors['username'] = 'A username is required.';

  if (empty($_POST['password']))
	$errors['password'] = 'A password is required.';

  if (empty($_POST['secretword']))
	$errors['secretword'] = 'A secret word is required.';

  if ($_POST['admin'] === "true") {
	$_POST['admin'] = 1;
  } else {
	$_POST['admin'] = 0;
  }
  $value['admin'] = $_POST['admin'];

// return a response =============================================

  // if there are any errors in our errors array, 
  // return a success boolean of false
  if (!empty($errors)) {

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
	if($user_object->addUser() !== FALSE){

	  // show a message of success and provide a true 
	  // success variable
	  $data['success'] = true;
	  $data['message'] = 'Success!';
	  $data['value'] = $value;
	} else {
	  $errors['username'] = 'Username unavailable! Please choose another';
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
