<?php
session_start();
chdir('..');
include getcwd().'/php/boot.php';
if(isset($_SESSION['login_cookie'])) {
	$login_cookie = unserialize($_SESSION['login_cookie']);
} else {
	$login_cookie = new UserCookie();
}
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {
	$username = $login_cookie->username;
	$userval = new UserGrab($username);
	$admin = $userval->admin;

	header('Content-Type: application/json');
	$session_id = session_id();
	echo json_encode(array("session_id" => $session_id));
}	
