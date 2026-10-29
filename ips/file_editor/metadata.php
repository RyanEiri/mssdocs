<?php
// The Metadata panel's data: the Dublin Core record, the RAD description and the completeness report for the TEI text in the editor (metadata/lib.php), as JSON
// {success, available, stage, message, record, dc, rad, completeness}. It describes only the text it is given (unsaved changes included) and reads no file, so it needs a signed-in
// user and a same-origin POST with that user's CSRF token, nothing more. "available" is false (and the editor shows no Metadata button) when PHP has no dom or xsl extension.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
require_once __DIR__.'/metadata/lib.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

function ips_fe_refuse($status, $message) {
	http_response_code($status);
	echo json_encode(['success' => false, 'error' => $message]);
	exit;
}

$login_cookie = new UserCookie();
if ($login_cookie->Peek() === false) {
	ips_fe_refuse(403, 'Sign in to see the metadata.');
}
$login_cookie->RequireCsrf();
if (!isset($_POST['text']) || !is_string($_POST['text']) || $_POST['text'] === '') {
	ips_fe_refuse(400, 'No text to describe.');
}
echo json_encode(['success' => true] + ips_fe_metadata_run($_POST['text']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
