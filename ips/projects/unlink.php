<?php
// Takes a file out of a project (the file itself stays): project_id, file_id. An administrator, whoever linked it, or someone who may write where the file is.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
[$user, $admin] = ips_projects_user(true);

$project_id = isset($_POST['project_id']) && ctype_digit((string) $_POST['project_id']) ? (int) $_POST['project_id'] : 0;
$file_id = isset($_POST['file_id']) && ctype_digit((string) $_POST['file_id']) ? (int) $_POST['file_id'] : 0;
$link = ips_projects_link_get($project_id, $file_id);
if ($link === null) {
	ips_projects_refuse(404, 'That file is not in the project.');
}
if (!ips_projects_may_change($link, $user, $admin)) {
	ips_projects_refuse(403, 'You can only take out files you linked, or files inside files/' . $user . '.');
}
$db->query("DELETE FROM project_files WHERE project_id=? AND file_id=?", [['type' => 'i', 'value' => $project_id], ['type' => 'i', 'value' => $file_id]]);
echo json_encode(['success' => true]);
