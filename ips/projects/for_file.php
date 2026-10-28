<?php
// The projects a file is in: GET for_file.php?path=files/A/b.png -> {file_id (null when the file has no catalogue row yet, or is not a file in the store), projects: [{project_id, title, kind, status, role, position, label, note}]}.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
[$user, $admin] = ips_projects_user(false);

$found = ips_projects_resolve($_GET['path'] ?? null, $user, $admin, false);
if (is_string($found)) { // a file that went (moved, removed) between the page's list and this call is in no project, not an error
	echo json_encode(['success' => true, 'file_id' => null, 'projects' => []]);
	exit;
}
$file_id = ips_projects_file_id($found, false);
echo json_encode(['success' => true, 'file_id' => $file_id, 'projects' => $file_id ? ips_projects_of_file($file_id) : []]);
