<?php
// Marks a folder under upload/files/ public (its files, and those of the folders below it, can be fetched without signing in) or private again. Administrators
// only, a same-origin POST with the user's CSRF token: dir=<folder, relative to upload/, e.g. files/ryan/Collection>&public=1|0.
// Answers {success, path, public, marked}: `marked` is the folder's own switch, `public` whether it is public at all (a folder below a public one is).
chdir('..');
require_once(getcwd().'/php/start_sess.php');
include getcwd().'/php/boot.php';
header('Content-Type: application/json');

function folder_public_answer($status, $body) {
	http_response_code($status);
	echo json_encode($body);
	exit;
}

$login_cookie = new UserCookie();
if ($login_cookie->Peek() === false) { // Peek(), not CheckIt(): that one redirects to the login page instead of answering
	folder_public_answer(403, ['success' => false, 'error' => 'You must be logged in!']);
}
$login_cookie->RequireCsrf();
$userval = new UserGrab($login_cookie->username);
if (empty($userval->admin)) {
	folder_public_answer(403, ['success' => false, 'error' => 'Only an administrator can make a folder public.']);
}
$dir = $_POST['dir'] ?? '';
$public = ($_POST['public'] ?? '') === '1';
$real = ips_resolve_dir($dir, ips_upload_base());
if ($real === false || $real === ips_files_root()) {
	folder_public_answer(400, ['success' => false, 'error' => 'Choose a folder inside upload/files/ (the whole files folder cannot be public).']);
}
if (!ips_set_dir_public($real, $public)) {
	folder_public_answer(500, ['success' => false, 'error' => 'The folder could not be changed (is it writable?).']);
}
folder_public_answer(200, [
	'success' => true,
	'path' => trim($dir, '/'),
	'public' => ips_dir_is_public($real),
	'marked' => ips_dir_marked_public($real),
]);
