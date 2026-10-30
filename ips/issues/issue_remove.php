<?php
// Removes an issue: its record, its pages and its contents, and its PDF when no other issue uses the same file. An administrator may remove any issue; the person who made a draft may remove it.
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
if (!$admin && !($issue['created_by'] === $user && $issue['status'] === 'draft')) {
	ips_issues_refuse(403, 'Only an administrator, or the person who made a draft, can remove an issue.');
}
$sha = ips_issues_sha($id);
foreach (['issue_pages', 'issue_toc'] as $table) {
	$db->query("DELETE FROM $table WHERE issue_id=?", [['type' => 'i', 'value' => $id]]);
}
$db->query("DELETE FROM issues WHERE id=?", [['type' => 'i', 'value' => $id]]);
if ($sha !== '' && $db->query("SELECT id FROM issues WHERE pdf_sha256=? LIMIT 1", [['type' => 's', 'value' => $sha]])->size() === 0) {
	@unlink(ips_issues_pdf_path($sha));
}
echo json_encode(['success' => true]);
