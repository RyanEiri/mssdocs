<?php
require_once(getcwd().'/php/start_sess.php');
include getcwd().'/php/boot.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$userval = new UserGrab($_REQUEST['username']);
	$secretword = $userval->validateUser($_REQUEST['username'],$_REQUEST['password']);
	if($secretword) {
		$login_cookie = new UserCookie();
		$login_cookie->CreateIt($_REQUEST['username'],$secretword);
	} else {
		header('Location: ./login.php?1');
		exit;
	}
} else {
	// Already signed in? Go straight to the home page.
	$peek = new UserCookie();
	if ($peek->Peek() !== false) {
		header('Location: ./index.php');
		exit;
	}
	$ips_config = ['view' => 'login', 'loginError' => ($_SERVER['QUERY_STRING'] === '1')];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Sign in · <?php echo htmlspecialchars(SITE_NAME) ?></title>
<?php include(HTML_TEMPLATES.'app-head.php'); ?>
</head>
<body>
<div id="app"></div>
<?php include(HTML_TEMPLATES.'app-foot.php'); ?>
</body>
</html>
<?php
}
?>
