<?php
// Replaces the contents of an issue: POST id and entries (a JSON list of {title, page_from, page_to, author, kind}, in order). Any signed-in user may change the contents of a draft; only an
// administrator those of a published or private issue. Answers the contents.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
[, $admin] = ips_issues_user(true);
$id = isset($_POST['id']) && ctype_digit((string) $_POST['id']) ? (int) $_POST['id'] : 0;
$issue = $id ? ips_issues_get($id) : null;
if ($issue === null) {
	ips_issues_refuse(404, 'That issue no longer exists.');
}
if (!ips_issues_may_edit($issue, $admin)) {
	ips_issues_refuse(403, 'Only an administrator changes the contents of an issue that is published or private.');
}
$given = isset($_POST['entries']) && is_string($_POST['entries']) ? json_decode($_POST['entries'], true) : null;
[$entries, $problem] = ips_issues_toc_entries($given, $issue['page_count']);
if ($problem !== null) {
	ips_issues_refuse(422, $problem);
}
ips_issues_toc_replace($id, $entries);
echo json_encode(['success' => true, 'toc' => ips_issues_toc($id)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
