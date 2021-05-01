<?php
chdir('..');
require_once(getcwd().'/php/boot.php');
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {
	$username = $login_cookie->username;
	$userval = new UserGrab($username);
	$admin = $userval->admin;

	$file = isset($_POST['session_id']) ? $_POST['session_id'] : null;
	$file = USER_FILES_BASE . "tmp/" . $file . ".txt";

	header('HTTP/1.1 200 OK');
	header('Content-Type: application/json');
	if(file_exists($file)) {
		$text = file_get_contents($file);
		echo $text;

		$obj = json_decode($text);
		if($obj->success === true) {
			unlink($file);
		}
	} else {
		echo json_encode(array("percent" => null));
	}

	/*if(isset($_SESSION['zip_progress'])) {
		$zip_progress = $_SESSION['zip_progress'];
		echo $zip_progress;

		$obj = json_decode($zip_progress);
		if ($obj->percent == 100) {
			unset($_SESSION['zip_progress']);
		}
	} else {
		echo json_encode(array("percent" => null));
	}*/
}
?>
