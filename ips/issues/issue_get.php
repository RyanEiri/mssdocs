<?php
// One issue with how much of its text was read: {success, issue, pages: {pages, with_text, corrected}, direct_url}. For signed-in users (who are the editors); direct_url only for an administrator.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
[, $admin] = ips_issues_user(false);
$id = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$issue = $id ? ips_issues_get($id) : null;
if ($issue === null) {
	ips_issues_refuse(404, 'That issue does not exist.');
}
$sha = $admin && $issue['has_pdf'] ? ips_issues_sha($id) : '';
echo json_encode(['success' => true, 'issue' => $issue, 'pages' => ips_issues_page_counts($id),
	// where the PDF sits under the web root, for an administrator only: the page asks for it without credentials and warns when a visitor could get it (a site without the nginx gate)
	'direct_url' => $sha === '' ? null : PROGRAM_WEB_BASE . 'upload/archives/issues/' . substr($sha, 0, 2) . '/' . $sha . '.pdf'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
