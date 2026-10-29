<?php
// The PDF of an issue: GET id. Anyone may read a published issue that is open to everyone; a signed-in user a published one for signed-in readers (and any draft or private issue, being an
// editor); nobody else (ips_access_can_read()). Streamed with byte-range support (a PDF viewer asks for ranges), nosniff, and a cache header that keeps a restricted issue out of shared
// caches. Not JSON: a refusal is a plain status. The direct address under upload/archives/ is never public (the gate), this is the way in.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
function ips_issue_file_stop($status) {
	http_response_code($status);
	header('Content-Type: text/plain; charset=utf-8');
	header('Cache-Control: no-store');
	echo $status === 416 ? 'Range not satisfiable' : ($status === 403 ? 'Not allowed' : 'Not found');
	exit;
}
if (!ips_issues_ready()) {
	ips_issue_file_stop(404);
}
$id = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$rows = $id ? $db->query("SELECT status, access, pdf_sha256, pdf_size, label, issue_date FROM issues WHERE id=? LIMIT 1", [['type' => 'i', 'value' => $id]])->fetchArray() : [];
if (!$rows || $rows[0]['pdf_sha256'] === '') {
	ips_issue_file_stop(404);
}
$issue = $rows[0];
$viewer = ips_issues_viewer();
session_write_close();
if (!ips_access_can_read($viewer, $issue['status'], $issue['access'])) {
	ips_issue_file_stop($viewer === null ? 404 : 403); // a visitor is not told that a draft exists
}
$path = ips_issues_pdf_path($issue['pdf_sha256']);
$size = is_file($path) ? filesize($path) : false;
if ($size === false) {
	ips_issue_file_stop(404);
}
$etag = '"' . $issue['pdf_sha256'] . '"';
$public = $issue['status'] === 'published' && $issue['access'] === 'open';
header('Content-Type: application/pdf');
header('X-Content-Type-Options: nosniff');
header('Accept-Ranges: bytes');
header('ETag: ' . $etag);
header('Cache-Control: ' . ($public ? 'public, max-age=3600' : 'private, max-age=600'));
$name = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim(($issue['label'] !== '' ? $issue['label'] : 'issue-' . $id) . ($issue['issue_date'] !== '' ? '-' . $issue['issue_date'] : ''))) . '.pdf';
header('Content-Disposition: inline; filename="' . trim($name, '-.') . '"');
if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
	http_response_code(304);
	exit;
}
$start = 0;
$end = $size - 1;
$range = $_SERVER['HTTP_RANGE'] ?? '';
if ($range !== '') {
	if (!preg_match('/^bytes=(\d*)-(\d*)$/', $range, $m) || ($m[1] === '' && $m[2] === '')) {
		ips_issue_file_stop(416);
	}
	if ($m[1] === '') { // the last N bytes
		$start = max(0, $size - (int) $m[2]);
	} else {
		$start = (int) $m[1];
		$end = $m[2] === '' ? $end : min((int) $m[2], $end);
	}
	if ($start > $end || $start >= $size) {
		header('Content-Range: bytes */' . $size);
		ips_issue_file_stop(416);
	}
	http_response_code(206);
	header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
}
header('Content-Length: ' . ($end - $start + 1));
set_time_limit(0);
$handle = fopen($path, 'rb');
fseek($handle, $start);
$left = $end - $start + 1;
while ($left > 0 && !feof($handle)) {
	$chunk = fread($handle, min(65536, $left));
	if ($chunk === false || $chunk === '') {
		break;
	}
	echo $chunk;
	$left -= strlen($chunk);
	flush();
}
fclose($handle);
