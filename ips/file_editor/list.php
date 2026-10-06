<?php
// The file list as JSON: the files this user may see in the templates folders (lib.php), each with whether they may change or delete it.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
header('Content-Type: application/json');
$login_cookie = new UserCookie();
if ($login_cookie->Peek() === false) {
	http_response_code(403);
	echo json_encode(['success' => false, 'error' => 'Sign in to see the files.']);
	exit;
}
$scope = ips_fe_scope($login_cookie->username);
echo json_encode(['success' => true, 'name' => 'files', 'type' => 'folder', 'items' => ips_fe_tree($scope), 'types' => ips_fe_public_types()]);
