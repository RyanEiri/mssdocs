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
    phpinfo();
  }
}
