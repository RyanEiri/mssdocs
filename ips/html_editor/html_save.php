<?php
/*
 * Saves the HTML editor's content (form POST from edit.php). Requires a signed-in user and that user's CSRF token, and
 * only ever writes an existing *.html file under upload/files/ (see html_files.php).
 */
chdir('..');
require_once getcwd().'/php/boot.php';
require_once __DIR__.'/html_files.php';

$login_cookie = new UserCookie();
if (!isset($_COOKIE['login']) || $login_cookie->Peek() === false) {
	http_response_code(403);
	exit('Sign in required.');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
	http_response_code(405);
	header('Allow: POST');
	exit('POST required.');
}
if (!hash_equals($login_cookie->CsrfToken(), (string)($_POST['csrf'] ?? ''))) {
	http_response_code(403);
	exit('Bad CSRF token.');
}

$rel = $_POST['html_file'] ?? '';
$content = $_POST['editor1'] ?? null;
$path = ips_html_path($rel);
if ($path === false || !is_string($content)) {
	http_response_code(422);
	exit('Not an editable HTML file.');
}
if (!is_writable($path) || file_put_contents($path, $content, LOCK_EX) === false) {
	http_response_code(500);
	exit('The file could not be written.');
}
header('Location: edit.php?html_file='.rawurlencode($rel), true, 303);
exit;
