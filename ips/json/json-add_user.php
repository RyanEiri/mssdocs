<?php
chdir('..');
include getcwd().'/php/boot.php';
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {

	define("USERNAME", $login_cookie->username);

	$userval = new UserGrab(USERNAME);
	define("ADMIN_STATUS", $userval->admin);

if(ADMIN_STATUS){
	// Refuse cross-site or token-less POSTs (see UserCookie::RequireCsrf).
	$login_cookie->RequireCsrf();
  // instantiate an ActOnSingleUser object
  if(!isset($user_object)) {
  	$user_object = new ActOnSingleUser();
  }
  // begin our ajax handling
  $errors	= array();	// array to hold validation errors
  $data		= array();	// array to pass back data
  $value	= array();

  // validate the variables ========================================
  // If any of these variables don't exist, add an error to the
  // $errors array.

  if (empty($_POST['username'])) {
		$errors['username'] = 'A username is required.';
	} else {
		$_SESSION['username'] = test_input($_POST['username']);
	}

	if (empty($_POST["email"])) {
			$errors['email'] = "An email address is required.";
	} else {
			$_SESSION['email'] = test_input($_POST["email"]);
			// check if e-mail address syntax is valid
			if (!filter_var($_SESSION['email'], FILTER_VALIDATE_EMAIL)) {
					$errors['email'] = "The email address entered is invalid.";
			}
	}

	if(!empty($_POST['password']) && ($_POST['password'] === $_POST['cpassword'])) {
    $_SESSION['password'] = test_input($_POST['password']);
    $_SESSION['cpassword'] = test_input($_POST['cpassword']);
    if (strlen($_SESSION['password']) < '8') {
			$errors['password'] = 'The password must contain at least 8 characters!';
    }
    elseif(!preg_match('#[0-9]+#', $_SESSION['password'])) {
			$errors['password'] = 'The password must contain at least 1 number!';
    }
    elseif(!preg_match('#[A-Z]+#', $_SESSION['password'])) {
			$errors['password'] = 'The password must contain at least 1 capital letter!';
    }
    elseif(!preg_match('#[a-z]+#', $_SESSION['password'])) {
			$errors['password'] = 'The password must contain at least 1 lowercase letter!';
    }
	} elseif(!empty($_POST['password'])) {
		$errors['cpassword'] = 'Confirm the password!';
	} else {
		$errors['password'] = 'Please enter a password!';
	}

  if ($_POST['admin'] === 'true') {
		$_SESSION['admin'] = 1;
  } else {
		$_SESSION['admin'] = 0;
  }

// return a validation response =============================================

  // if there are any errors in our errors array,
  // return a success boolean of false
  if (!empty($errors)) {

	// if there are items in our errors array,
	// return those errors
	$data['success'] = false;
	$data['errors'] = $errors;
	$data['value'] = $value;
  } else {

// If there are no validation errors submit to database.
// DATABASE PROCESSING
		if($user_object->addUser() !== FALSE){

		  // show a message of success and provide a true
		  // success variable
		  $data['success'] = true;
		  $data['message'] = 'Success!';
		  $data['value'] = $value;
		} else {
			if($user_object->mysqlError){
				$errors['database'] = $user_object->mysqlError;
			}
			if($user_object->duplicateError){
		  	$errors['duplicate'] = 'Username unavailable! Please choose another';
			}
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
