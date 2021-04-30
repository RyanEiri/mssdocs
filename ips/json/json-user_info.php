<?php
chdir('..');
include getcwd().'/php/boot.php';
$login_cookie = new UserCookie();
// Test the cookie for deletion.
$login_cookie->DeleteIt();
// Authenticate and grab user info.
if($login_cookie->CheckIt()) {

	define("USERNAME", $login_cookie->username);
	$_SESSION['user_id'] = $_POST['id'];

	$userval = new UserGrab(USERNAME);
	define("ADMIN_STATUS", $userval->admin);

	// Only admins can edit a user.
	if(ADMIN_STATUS){
		$user_object = new ActOnSingleUser();
		$user_entry = $user_object->user_entry;
		// Provide user information to calling script in JSON format.
		echo json_encode($user_entry);
	} else {
		$errors['entry'] = "failed!";
		// Provide error information to calling script in JSON format.
		echo json_encode($errors);
	}

}
?>
