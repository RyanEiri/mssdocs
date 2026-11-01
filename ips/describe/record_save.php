<?php
// Saves a record from the form: POST dir=<records folder>&id=<record id>&etag=<the etag the form was given>&struct=<JSON>. Administrators only, same-origin, with the CSRF token. The structure is checked
// (lib.php), a new photograph must be an image inside the store, the record must still be what it was when the form opened (409 with the current etag when not), the earlier version goes to
// .history/ and the new one is written atomically; nothing is written when nothing changed. Answers {success, changed, record, preview}.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/endpoint.php';
[$user] = ips_describe_user(true);
$folder = ips_describe_folder($_POST['dir'] ?? '');
ips_describe_record_file($folder, $_POST['id'] ?? '');
$input = json_decode((string) ($_POST['struct'] ?? ''), true);
if (!is_array($input)) {
	ips_describe_refuse(400, 'The form sent no readable answers.');
}
if (!is_string($_POST['etag'] ?? null) || $_POST['etag'] === '') {
	ips_describe_refuse(400, 'The form did not say which version of the record it was changing.');
}
$lock = ips_describe_lock($folder);
$done = ips_describe_update($folder, $_POST['id'], $_POST['etag'], function ($current) use ($input) { return array_replace($current, $input); }, $user);
ips_describe_unlock($lock);
if (!$done['ok']) {
	ips_describe_refuse($done['status'], $done['error'], $done['extra']);
}
$path = $folder . '/' . $_POST['id'] . '.xml';
echo json_encode(['success' => true, 'changed' => $done['changed'], 'record' => $done['view'], 'preview' => ips_describe_preview((string) file_get_contents($path))], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
