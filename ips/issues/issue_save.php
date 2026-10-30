<?php
// Changes an issue's description: series, label, ISO 8601 date, volume, number, external address, notes. Any signed-in user may change a draft; only an administrator may change a published or
// private issue, or set its status or access. Answers the issue.
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
if (!$admin && $issue['status'] !== 'draft') {
	ips_issues_refuse(403, 'Only an administrator changes an issue that is published or private.');
}
[$v, $problem] = ips_issues_fields($_POST, $issue);
if ($problem !== null) {
	ips_issues_refuse(422, $problem);
}
if ($v['external_url'] === '' && !$issue['has_pdf']) {
	ips_issues_refuse(422, 'An issue with no PDF needs its external address.');
}
$status = $_POST['status'] ?? $issue['status'];
$access = $_POST['access'] ?? $issue['access'];
if (!is_string($status) || !isset(IPS_ISSUE_STATUSES[$status]) || !is_string($access) || !isset(IPS_ISSUE_ACCESS[$access])) {
	ips_issues_refuse(422, 'The status is draft, published or private; the access is open or signed-in.');
}
if (!$admin && ($status !== $issue['status'] || $access !== $issue['access'])) {
	ips_issues_refuse(403, 'Only an administrator sets the status or the access of an issue.');
}
$db->query("UPDATE issues SET series=?, label=?, issue_date=?, volume=?, number=?, external_url=?, notes=?, status=?, access=?, updated_by=? WHERE id=?", [
	['type' => 's', 'value' => $v['series']], ['type' => 's', 'value' => $v['label']], ['type' => 's', 'value' => $v['issue_date']], ['type' => 's', 'value' => $v['volume']],
	['type' => 's', 'value' => $v['number']], ['type' => 's', 'value' => $v['external_url']], ['type' => 's', 'value' => $v['notes']], ['type' => 's', 'value' => $status],
	['type' => 's', 'value' => $access], ['type' => 's', 'value' => $user], ['type' => 'i', 'value' => $id],
]);
echo json_encode(['success' => true, 'issue' => ips_issues_get($id)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
