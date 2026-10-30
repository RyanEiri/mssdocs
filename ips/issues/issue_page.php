<?php
// One page of an issue for the correction screen: GET id and page (1...). {success, page: {page, text_raw, text_corrected (null when none), corrected_by, corrected_at}, pages: [{page, has_text,
// corrected}]}. The text read from the PDF is always there as text_raw; a correction is text_corrected. For signed-in users (the editors).
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
ips_issues_user(false);
$id = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$page = isset($_GET['page']) && ctype_digit((string) $_GET['page']) ? (int) $_GET['page'] : 0;
if (!$id || ips_issues_get($id) === null) {
	ips_issues_refuse(404, 'That issue does not exist.');
}
$found = $page ? ips_issues_page($id, $page) : null;
if ($found === null) {
	ips_issues_refuse(404, 'That page has no text (the issue may not be read yet).');
}
echo json_encode(['success' => true, 'page' => $found, 'pages' => ips_issues_page_list($id)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
