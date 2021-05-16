<?php
chdir('..');
require_once(getcwd().'/php/start_sess.php');
include getcwd().'/php/boot.php';
$login_cookie = new UserCookie();

if($login_cookie->CheckIt()) {
	$username = $login_cookie->username;
	$userval = new UserGrab($username);
	$admin = $userval->admin;

	header('Content-Type: application/json');
	$session_id = session_id();
	echo json_encode(array("session_id" => $session_id));
}
