<?php
// The access check behind nginx's auth_request for everything under /ips/upload/files/ (the site's nginx asks here, then serves the file itself).
// Answers 204 (let nginx serve it) or 403; nginx maps any other status to a 500, so a path that does not exist is a 204 for a signed-in visitor (nginx then
// answers 404) and a 403 for anyone else. A file is allowed when it is in a public folder (a folder with the marker file, or below one: see ips_dir_is_public)
// or the visitor is signed in. It reads only the request's path and never writes. The answer carries X-Gate-Cache-Control, which nginx sends as the file's
// Cache-Control: public files may be cached, anything else must not be, or a CDN would hand a private file to the next visitor.
chdir('..');
include getcwd().'/php/boot.php';

function gate_answer($status, $public) {
	http_response_code($status);
	header('X-Gate-Cache-Control: '.($public ? 'public, max-age=3600' : 'private, no-store'));
	exit;
}

// The path the visitor asked for (nginx's own IPS_ORIGINAL_URI parameter: a client cannot set it the way it can a header), relative to upload/files/; null when it is not under it.
function gate_requested_path() {
	$uri = $_SERVER['IPS_ORIGINAL_URI'] ?? '';
	$path = is_string($uri) ? parse_url($uri, PHP_URL_PATH) : null;
	$marker = '/upload/files/';
	$at = is_string($path) ? strpos($path, $marker) : false;
	if ($at === false) {
		return null;
	}
	$rel = rawurldecode(substr($path, $at + strlen($marker)));
	return ($rel === '' || strpos($rel, "\0") !== false) ? null : $rel;
}

$rel = gate_requested_path();
$root = ips_files_root();
if ($rel === null || $root === false) {
	gate_answer(403, false);
}
// No hidden entry is ever served (the marker, .deleted/, backups), whatever the file system says.
foreach (explode('/', $rel) as $part) {
	if ($part !== '' && $part[0] === '.') {
		gate_answer(403, false);
	}
}
$real = realpath($root.'/'.$rel);
if ($real !== false && strpos($real.'/', $root.'/') !== 0) {
	gate_answer(403, false); // a link out of upload/files/
}
$folder = $real === false ? false : (is_dir($real) ? $real : dirname($real));
if ($folder !== false && ips_dir_is_public($folder)) {
	gate_answer(204, true);
}
// Peek(), not CheckIt(): CheckIt() redirects a visitor who is not signed in to the login page, and nginx cannot take a redirect as an answer.
$login_cookie = new UserCookie();
gate_answer($login_cookie->Peek() !== false ? 204 : 403, false);
