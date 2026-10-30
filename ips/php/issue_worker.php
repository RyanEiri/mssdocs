<?php
// The background worker that reads the text of an issue's PDF: php issue_worker.php <issue id>. CLI only (like zip_worker.php); started by the issue endpoints, which return at once, and
// reporting through the issue's own extraction state (pending, running, done, no-text or failed, with a note) that the page polls. It connects to the database from php/classes/config.php
// without the web bootstrap (boot.php needs a request).
if (php_sapi_name() !== 'cli') {
	http_response_code(403);
	exit(1);
}
ini_set('max_execution_time', '0');
ini_set('memory_limit', '256M');
$id = (int) ($argv[1] ?? 0);
if ($id < 1) {
	exit(1);
}
define('CLASSES_DIR', __DIR__ . '/classes/');
require_once CLASSES_DIR . 'config.php'; // defines $db
require_once __DIR__ . '/text_fold.php';
require_once __DIR__ . '/../issues/extract.php';
$ips_root = dirname(__DIR__);
exit(ips_issues_run_extraction($id, function ($sha) use ($ips_root) {
	return $ips_root . '/upload/archives/issues/' . substr($sha, 0, 2) . '/' . $sha . '.pdf';
}) ? 0 : 2);
