<?php
// Moves a photograph between records, or between a record and the tray, or changes what it shows: POST dir=<records folder>&address=<the photograph as a record holds it>&from=<record id or empty>
// &to=<record id or empty>&role=<what it shows>. Administrators only, same-origin, with the CSRF token. from empty = out of the tray, to empty = back to the tray, from = to = a change of role. Both
// records are changed under one lock: the target first, and if the source then fails the target is put back from its history. The photograph itself is never opened or written (turning it is
// json/json-photo_rotate.php). Answers {success, records (the views of the records changed), tray}.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/endpoint.php';
[$user] = ips_describe_user(true);
$folder = ips_describe_folder($_POST['dir'] ?? '');
$address = $_POST['address'] ?? '';
$from = (string) ($_POST['from'] ?? '');
$to = (string) ($_POST['to'] ?? '');
$role = (string) ($_POST['role'] ?? '');
if (!is_string($address) || !ips_describe_plain_address($address)) {
	ips_describe_refuse(400, 'That is not a photograph address.');
}
if ($from === '' && $to === '') {
	ips_describe_refuse(400, 'Say which record the photograph comes from or goes to.');
}
if ($to !== '' && !in_array($role, IPS_DESCRIBE_PHOTO_ROLES, true)) {
	ips_describe_refuse(400, 'Say what the photograph shows.');
}
foreach ([$from, $to] as $id) {
	if ($id !== '') {
		ips_describe_record_file($folder, $id);
	}
}
if ($from === '' && ips_describe_resolve_photo($folder, $address) === null) {
	ips_describe_refuse(400, 'That photograph is not an image inside the file store.');
}
$lock = ips_describe_lock($folder);
$views = [];
$undo = null;
if ($to !== '') {
	$done = ips_describe_update($folder, $to, null, function ($current) use ($address, $role, $from, $to) {
		$photos = $current['photos'];
		foreach ($photos as $i => $p) {
			if ($p['path'] === $address) {
				if ($from !== $to) {
					return 'That photograph is already on this book.';
				}
				$photos[$i]['role'] = $role;
				return array_replace($current, ['photos' => $photos]);
			}
		}
		if ($from === $to) {
			return 'That photograph is not on this book.';
		}
		$photos[] = ['path' => $address, 'role' => $role];
		return array_replace($current, ['photos' => $photos]);
	}, $user, $from !== '' ? $from : null);
	if (!$done['ok']) {
		ips_describe_unlock($lock);
		ips_describe_refuse($done['status'], $done['error'], $done['extra']);
	}
	$views[] = $done['view'];
	$undo = $done['backup'];
}
if ($from !== '' && $from !== $to) {
	$gone = ips_describe_update($folder, $from, null, function ($current) use ($address) {
		$photos = array_values(array_filter($current['photos'], function ($p) use ($address) { return $p['path'] !== $address; }));
		return count($photos) === count($current['photos']) ? 'That photograph is not on that book.' : array_replace($current, ['photos' => $photos]);
	}, $user);
	if (!$gone['ok']) {
		if ($undo !== null && is_file($undo)) {   // put the target back: the photograph must not be on two books
			ips_describe_write($folder . '/' . $to . '.xml', (string) file_get_contents($undo));
		}
		ips_describe_unlock($lock);
		ips_describe_refuse($gone['status'], $gone['error'], $gone['extra']);
	}
	$views[] = $gone['view'];
}
ips_describe_unlock($lock);
echo json_encode(['success' => true, 'records' => $views, 'tray' => ips_describe_tray($folder, ips_describe_used($folder))], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
