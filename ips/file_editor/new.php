<?php
// Creates a new file for the Files page's "New file": type=<an offered file type> (html: a blank page, xml: a TEI skeleton, ...: lib.php), in the user's own folder of the templates folder
// (an administrator's at the top). Needs a signed-in user and a same-origin POST with that user's CSRF token; an existing file is never replaced.
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
	ips_fe_refuse(403, 'Sign in to create files.');
}
$login_cookie->RequireCsrf();

$kind = $_POST['type'] ?? (ips_fe_types()[0] ?? '');
if (!is_string($kind) || ips_fe_type($kind) === null) {
	ips_fe_refuse(422, 'The type must be ' . (implode(' or ', ips_fe_types()) ?: 'one this site offers') . '.');
}
$scope = ips_fe_scope($login_cookie->username);
if ($scope === null) {
	ips_fe_refuse(403, 'Your user name cannot be used as a folder name, so files cannot be made for it: ask an administrator.');
}
$filename = ips_fe_new_filename($_POST['name'] ?? null, $kind);
if ($filename === false) {
	ips_fe_refuse(422, 'Use letters, digits, spaces, "_" and "-" for the name (up to 100 characters).');
}
$dir = ips_fe_new_dir($kind, $scope);
if ($dir === false) {
	ips_fe_refuse(500, 'The folder for the ' . $kind . ' files could not be made.');
}
// "x" mode fails if the file exists, so nothing is ever replaced (and two requests cannot both create it).
$handle = @fopen("$dir/$filename", 'x');
if ($handle === false) {
	ips_fe_refuse(file_exists("$dir/$filename") ? 409 : 500, file_exists("$dir/$filename") ? 'A file called ' . $filename . ' already exists.' : 'The file could not be created.');
}
fwrite($handle, ips_fe_skeleton($kind, pathinfo($filename, PATHINFO_FILENAME)));
fclose($handle);
@chmod("$dir/$filename", 0664);
$name = ips_fe_name("$dir/$filename");
echo json_encode(['success' => true, 'file' => $name, 'edit' => 'edit.php?file=' . rawurlencode($name)]);
