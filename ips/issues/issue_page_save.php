<?php
// Corrects the text of a page (POST id, page, text) or, with restore=1, takes the correction away. The text read from the PDF is kept; what is shown and searched becomes the correction. Any
// signed-in user may correct a draft; only an administrator a published or private issue. Answers the page.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
[$user, $admin] = ips_issues_user(true);
$id = isset($_POST['id']) && ctype_digit((string) $_POST['id']) ? (int) $_POST['id'] : 0;
$page = isset($_POST['page']) && ctype_digit((string) $_POST['page']) ? (int) $_POST['page'] : 0;
$issue = $id ? ips_issues_get($id) : null;
if ($issue === null || !$page || ips_issues_page($id, $page) === null) {
	ips_issues_refuse(404, 'That page does not exist.');
}
if (!ips_issues_may_edit($issue, $admin)) {
	ips_issues_refuse(403, 'Only an administrator changes the text of an issue that is published or private.');
}
if (($_POST['restore'] ?? '') === '1') {
	$done = ips_issues_restore_page($id, $page);
} elseif (isset($_POST['text']) && is_string($_POST['text'])) {
	$done = ips_issues_correct_page($id, $page, $_POST['text'], $user);
} else {
	ips_issues_refuse(422, 'No text was sent.');
}
if ($done !== true) {
	ips_issues_refuse(422, $done);
}
echo json_encode(['success' => true, 'page' => ips_issues_page($id, $page), 'pages' => ips_issues_page_list($id)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
