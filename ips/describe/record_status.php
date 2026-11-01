<?php
// Sets a record's status (draft, published or private): POST dir, id, etag, status. Administrators only, same-origin, with the CSRF token. A record with questions still open cannot be published or made
// private (draft stays possible): the open questions come back in the refusal. Answers {success, changed, record}.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/endpoint.php';
[$user] = ips_describe_user(true);
$folder = ips_describe_folder($_POST['dir'] ?? '');
ips_describe_record_file($folder, $_POST['id'] ?? '');
$status = $_POST['status'] ?? '';
if (!in_array($status, IPS_DESCRIBE_STATUSES, true)) {
	ips_describe_refuse(400, 'The status is draft, published or private.');
}
if (!is_string($_POST['etag'] ?? null) || $_POST['etag'] === '') {
	ips_describe_refuse(400, 'The form did not say which version of the record it was changing.');
}
$lock = ips_describe_lock($folder);
$done = ips_describe_update($folder, $_POST['id'], $_POST['etag'], function ($current) use ($status) {
	$open = ips_describe_questions($current);
	if ($status !== 'draft' && $open) {
		return 'Answer the open questions first (' . implode(', ', array_column($open, 0)) . '): a record is published or made private only when nothing is open.';
	}
	return array_replace($current, ['status' => $status]);
}, $user);
ips_describe_unlock($lock);
if (!$done['ok']) {
	ips_describe_refuse($done['status'], $done['error'], $done['extra']);
}
echo json_encode(['success' => true, 'changed' => $done['changed'], 'record' => $done['view']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
