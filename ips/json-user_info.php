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
	$username = $login_cookie->username;
	$userval = new UserGrab($username);
	$admin = $userval->admin;
	
	if($admin){
		$user_object = new ActOnSingleUser();
		$user_entry = $user_object->user_entry;
		echo json_encode($user_entry);	
	} else {
		echo null;
	}
}
?>
