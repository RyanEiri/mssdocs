<?php
// One issue with how much of its text was read: {success, issue, pages: {pages, with_text, corrected}}. For signed-in users (who are the editors).
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
ips_issues_user(false);
$id = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$issue = $id ? ips_issues_get($id) : null;
if ($issue === null) {
	ips_issues_refuse(404, 'That issue does not exist.');
}
echo json_encode(['success' => true, 'issue' => $issue, 'pages' => ips_issues_page_counts($id)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
