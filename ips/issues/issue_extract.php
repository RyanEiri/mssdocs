<?php
// Reads the text of an issue's PDF again (after a failure, or when the extraction was interrupted): starts the background worker and answers at once. An administrator or the person who made the
// issue (while it is a draft) may. A correction an editor made to a page is kept.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
[$user, $admin] = ips_issues_user(true);
$id = isset($_POST['id']) && ctype_digit((string) $_POST['id']) ? (int) $_POST['id'] : 0;
$issue = $id ? ips_issues_get($id) : null;
if ($issue === null) {
	ips_issues_refuse(404, 'That issue no longer exists.');
}
if (!$issue['has_pdf']) {
	ips_issues_refuse(422, 'This issue has no PDF to read.');
}
if (!$admin && !($issue['created_by'] === $user && $issue['status'] === 'draft')) {
	ips_issues_refuse(403, 'Only an administrator, or the person who made a draft, can read its text again.');
}
if ($issue['extraction'] === 'running' && $issue['extraction_at'] !== null && strtotime($issue['extraction_at']) > time() - 600) {
	ips_issues_refuse(409, 'The text is being read now.');
}
ips_issues_set_state($id, 'pending', 'Waiting to read the text…');
session_write_close();
ips_issues_start_worker($id);
echo json_encode(['success' => true, 'issue' => ips_issues_get($id)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
