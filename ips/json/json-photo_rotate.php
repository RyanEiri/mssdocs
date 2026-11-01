<?php
// Turns a photograph for display WITHOUT changing the photograph or its EXIF data: the turn is recorded beside it (ips_rotation_set(), files_table.php) and pages apply it when they draw the picture.
// A same-origin POST with the user's CSRF token: dir=<folder, relative to upload/, e.g. files/ryan/Collection>&name=<file>&turn=90|-90|180 (a further turn) or set=0|90|180|270 (the turn itself).
// Who may: an administrator anywhere, anyone else in their own folder (files/<username>) and below, as for moving or removing a file. The photograph is never opened or written: the answer
// is {success, name, rotation} where rotation is the extra clockwise turn now recorded (0, 90, 180 or 270).
chdir('..');
require_once(getcwd().'/php/start_sess.php');
include getcwd().'/php/boot.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

function photo_rotate_answer($status, $body) {
	http_response_code($status);
	echo json_encode($body);
	exit;
}

$login_cookie = new UserCookie();
if ($login_cookie->Peek() === false) { // Peek(), not CheckIt(): that one redirects to the login page instead of answering
	photo_rotate_answer(403, ['success' => false, 'error' => 'You must be logged in!']);
}
$login_cookie->RequireCsrf();
[$user, $admin] = ips_current_user();
$name = $_POST['name'] ?? '';
$real = ips_resolve_dir($_POST['dir'] ?? '', ips_upload_base());
if ($real === false || ($real === ips_files_root() && !$admin) || preg_match(IPS_VERSION_DIR, basename($real))) {
	photo_rotate_answer(400, ['success' => false, 'error' => 'Choose the folder the photograph is in (inside upload/files/).']);
}
if (!$admin && !ips_in_users_tree($real, $user)) {
	photo_rotate_answer(403, ['success' => false, 'error' => 'You can only turn photographs in your own folder (files/' . $user . ').']);
}
$file = is_string($name) && ips_safe_name($name) ? $real . '/' . $name : '';
if ($file === '' || !preg_match(IPS_ROTATION_TYPES, $name) || !is_file($file) || is_link($file)) {
	photo_rotate_answer(400, ['success' => false, 'error' => 'That photograph is not in the folder (a JPEG, PNG or GIF can be turned).']); // 400, not 404: nginx replaces a PHP 404 body with its own page
}
$current = ips_rotation_of($real, $name);
if (isset($_POST['set'])) {
	$wanted = $_POST['set'];
} elseif (isset($_POST['turn']) && in_array($_POST['turn'], ['90', '-90', '180'], true)) {
	$wanted = $current + (int) $_POST['turn'];
} else {
	photo_rotate_answer(400, ['success' => false, 'error' => 'Say how to turn it: turn=90, -90 or 180, or set=0, 90, 180 or 270.']);
}
$now = ips_rotation_set($real, $name, $wanted);
if ($now === false) {
	photo_rotate_answer(is_numeric($wanted) && (int) $wanted == $wanted && (((int) $wanted % 90) === 0) ? 500 : 400, ['success' => false, 'error' => 'The turn must be a multiple of 90 degrees (and the folder must be writable).']);
}
photo_rotate_answer(200, ['success' => true, 'name' => $name, 'rotation' => $now]);
