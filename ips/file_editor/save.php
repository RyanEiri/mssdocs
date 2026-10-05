<?php
// Saves the editor's file (edit.php posts here with fetch and shows the JSON answer). Needs a signed-in user, a same-origin POST with that user's CSRF token, an
// existing editable file the user may change (lib.php), and, for XML, well-formed content. The text is saved exactly as posted: edit.php prints it escaped, so
// the editor holds precisely what the file holds (a bare & in XML is not well-formed and is refused).
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
	ips_fe_refuse(403, 'Sign in to save files.');
}
$login_cookie->RequireCsrf();
$scope = ips_fe_scope($login_cookie->username);
$real = ips_fe_resolve($_POST['file'] ?? '', $scope);
if ($real === false) {
	ips_fe_refuse(404, 'That file was not found.');
}
if (!ips_fe_may_write($real, $scope)) {
	ips_fe_refuse(403, 'You can only save files inside your own folder (html_templates/' . $login_cookie->username . ' or xml_templates/' . $login_cookie->username . '). Only administrators can save this one.');
}
if (!isset($_POST['editor1']) || !is_string($_POST['editor1']) || $_POST['editor1'] === '') {
	ips_fe_refuse(400, 'No editor data found!');
}
$data = $_POST['editor1'];
if (ips_fe_kind_of($real) === 'xml') {
	libxml_use_internal_errors(true);
	if (simplexml_load_string($data) === false) {
		$problem = libxml_get_errors()[0] ?? null;
		ips_fe_refuse(422, 'Not saved: the XML is not well-formed' . ($problem ? ' (line ' . $problem->line . ': ' . trim($problem->message) . ')' : '') . '.');
	}
}
if (!is_writable($real) || file_put_contents($real, $data, LOCK_EX) === false) {
	ips_fe_refuse(500, 'The file could not be written.');
}
echo json_encode(['success' => true, 'file' => ips_fe_name($real), 'bytes' => strlen($data)]);
