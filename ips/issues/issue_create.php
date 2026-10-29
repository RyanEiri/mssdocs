<?php
// Makes an issue: from a PDF already in the file store (path "files/A/b.pdf", copied into the issues folder under its SHA-256 and read for its text by a background worker) or, with no
// path, from an external address only (an issue that is hosted elsewhere, such as timarit.is). Date, volume, number, label, series and notes as in issue_save.php. The issue is a draft
// with signed-in readers only; only an administrator may ask for another status or access. Answers the issue.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
[$user, $admin] = ips_issues_user(true);

[$v, $problem] = ips_issues_fields($_POST, null);
if ($problem !== null) {
	ips_issues_refuse(422, $problem);
}
$status = $_POST['status'] ?? 'draft';
$access = $_POST['access'] ?? 'signed-in';
if (!is_string($status) || !isset(IPS_ISSUE_STATUSES[$status]) || !is_string($access) || !isset(IPS_ISSUE_ACCESS[$access])) {
	ips_issues_refuse(422, 'The status is draft, published or private; the access is open or signed-in.');
}
if (!$admin && ($status !== 'draft' || $access !== 'signed-in')) {
	ips_issues_refuse(403, 'Only an administrator publishes an issue or opens it to everyone.');
}
$path = $_POST['path'] ?? '';
$sha = '';
$size = 0;
$name = '';
if ($path !== '') {
	$source = ips_issues_resolve_source($path);
	if (!is_array($source)) {
		ips_issues_refuse(422, $source);
	}
	$stored = ips_issues_store_pdf($source['real']);
	if (is_string($stored)) {
		ips_issues_refuse(422, $stored);
	}
	[$sha, $size, $name] = [$stored['sha'], $stored['size'], basename($source['real'])];
} elseif ($v['external_url'] === '') {
	ips_issues_refuse(422, 'Give a PDF from the file store, or the external address of an issue hosted elsewhere.');
}

$id = $db->query("INSERT INTO issues (series, label, issue_date, volume, number, pdf_sha256, pdf_size, original_name, external_url, status, access, extraction, notes, created_by, updated_by)
	VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
	['type' => 's', 'value' => $v['series']], ['type' => 's', 'value' => $v['label']], ['type' => 's', 'value' => $v['issue_date']], ['type' => 's', 'value' => $v['volume']],
	['type' => 's', 'value' => $v['number']], ['type' => 's', 'value' => $sha], ['type' => 'i', 'value' => $size], ['type' => 's', 'value' => $name],
	['type' => 's', 'value' => $v['external_url']], ['type' => 's', 'value' => $status], ['type' => 's', 'value' => $access], ['type' => 's', 'value' => $sha !== '' ? 'pending' : 'none'],
	['type' => 's', 'value' => $v['notes']], ['type' => 's', 'value' => $user], ['type' => 's', 'value' => $user],
])->getId();
if ($sha !== '') {
	session_write_close();
	ips_issues_start_worker($id);
}
echo json_encode(['success' => true, 'issue' => ips_issues_get($id)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
