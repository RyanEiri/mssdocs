<?php
require_once(getcwd().'/php/start_sess.php');
include getcwd().'/php/boot.php';
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {
	$userval = new UserGrab($login_cookie->username);
	ips_ensure_user_folder($login_cookie->username, (bool)$userval->admin);
	$ips_config = ['view' => 'browser', 'user' => $login_cookie->username, 'admin' => (bool)$userval->admin, 'csrf' => $login_cookie->CsrfToken()];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Files · <?php echo htmlspecialchars(SITE_NAME) ?></title>
<?php include(HTML_TEMPLATES.'ui-head.php'); ?>
</head>
<body>
<div id="app"></div>
<?php include(HTML_TEMPLATES.'ui-foot.php'); ?>
</body>
</html>
<?php
} else {
	header('Location: ./login.php');
	exit;
}
?>
