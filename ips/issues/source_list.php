<?php
// The PDFs of the file store a new issue can be made from: GET q (part of a file name or folder, optional): up to 100 of the newest, as {path: "files/A/b.pdf", name, folder, size, title}.
// For signed-in users. It lists the catalogue (the files table), so a PDF uploaded in the file browser is there; nothing is read from disk.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
ips_issues_user(false);
$q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
$out = [];
$result = $db->query("SELECT name, size, title FROM files WHERE LOWER(name) LIKE '%.pdf' AND name LIKE ? ESCAPE '\\\\' ORDER BY id DESC LIMIT 100", [['type' => 's', 'value' => $like]]);
while ($r = $result->fetch()) {
	$segments = explode('/', $r['name']);
	$hidden = false;
	foreach ($segments as $segment) {
		if ($segment === '' || $segment[0] === '.' || preg_match(IPS_VERSION_DIR, $segment)) {
			$hidden = true;
		}
	}
	if (!$hidden) {
		$out[] = ['path' => 'files/' . $r['name'], 'name' => basename($r['name']), 'folder' => count($segments) > 1 ? dirname($r['name']) : '', 'size' => (int) $r['size'], 'title' => (string) $r['title']];
	}
}
echo json_encode(['success' => true, 'files' => $out], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
