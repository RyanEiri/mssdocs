<?php
// Deletes a file of the Files page. A regular user may delete files inside their own folder (and, where the site shares its pages, the shared ones: lib.php);
// an administrator any. Nothing is erased: the file is moved to .deleted/ in its templates folder. Needs a signed-in user and a same-origin POST with that
// user's CSRF token.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
header('Content-Type: application/json');

function ips_fe_refuse($status, $message) {
	http_response_code($status);
	echo json_encode(['success' => false, 'error' => $message]);
	exit;
}

$login_cookie = new UserCookie();
if ($login_cookie->Peek() === false) {
	ips_fe_refuse(403, 'Sign in to delete files.');
}
$login_cookie->RequireCsrf();
$scope = ips_fe_scope($login_cookie->username);
$real = ips_fe_resolve($_POST['file'] ?? '', $scope);
if ($real === false) {
	ips_fe_refuse(404, 'That file was not found.');
}
if (!ips_fe_may_write($real, $scope)) {
	ips_fe_refuse(403, 'You can only delete files inside your own folder (' . ips_fe_own_folders_text($login_cookie->username) . ').');
}
if (!ips_fe_trash($real)) {
	ips_fe_refuse(500, 'The file could not be deleted.');
}
error_log('File editor: ' . ips_fe_name($real) . ' deleted by ' . $login_cookie->username);
echo json_encode(['success' => true]);
