<?php
// The records of a folder for the Describe page: GET dir=<records folder, files/...>. Answers {success, dir, photographs (the settings' photograph folder), records [{id, title, status, open, photos, checked,
// etag, error?}], tray (photographs that no record uses), roles, statuses, decisions}. For signed-in users; nothing is changed.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/endpoint.php';
ips_describe_user(false);
$folder = ips_describe_folder($_GET['dir'] ?? '');
$records = [];
$used = [];
foreach (ips_describe_record_files($folder) as $id => $path) {
	$text = (string) file_get_contents($path);
	$view = ips_describe_view($folder, $id, $text);
	if (isset($view['error'])) {
		$records[] = ['id' => $id, 'title' => $id, 'error' => $view['error']];
		continue;
	}
	$s = $view['struct'];
	foreach ($s['photos'] as $p) {
		$real = ips_describe_resolve_photo($folder, $p['path']);
		if ($real !== null) {
			$used[] = $real;
		}
	}
	$records[] = ['id' => $id, 'title' => $s['titles'][0]['text'] ?? $id, 'reference' => $s['reference'], 'status' => $s['status'], 'open' => $view['open'], 'photos' => count($s['photos']),
		'checked' => $s['checked'] !== null, 'etag' => $view['etag']];
}
echo json_encode(['success' => true, 'dir' => trim($_GET['dir'], '/'), 'photographs' => ips_describe_settings($folder)['photographs'], 'records' => $records, 'tray' => ips_describe_tray($folder, $used),
	'roles' => IPS_DESCRIBE_ROLES, 'photo_roles' => IPS_DESCRIBE_PHOTO_ROLES, 'statuses' => IPS_DESCRIBE_STATUSES, 'decisions' => IPS_DESCRIBE_DECISIONS, 'questions' => IPS_DESCRIBE_QUESTIONS], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
