<?php
include $_SERVER['DOCUMENT_ROOT'].'/ips/php/boot.php';
session_start();
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {

	define("USERNAME", $login_cookie->username);
	$_SESSION['user_id'] = $_POST['id'];

	$userval = new UserGrab(USERNAME);
	define("ADMIN_STATUS", $userval->admin);

	if(ADMIN_STATUS){
		$user_object = new ActOnSingleUser();
		$user_entry = $user_object->user_entry;
		echo json_encode($user_entry);
	} else {
		$errors['entry'] = "failed!";
		echo json_encode($errors);
	}

}
?>
