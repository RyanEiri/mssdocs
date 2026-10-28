<?php
// One project and its files in their order: GET files.php?id=N -> {project, files: [{file_id, path, name, title, role, position, label, note, linked_by, missing}]}.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
ips_projects_user(false);

$id = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$project = $id ? ips_projects_get($id) : null;
if ($project === null) {
	ips_projects_refuse(404, 'That project no longer exists.');
}
echo json_encode(['success' => true, 'project' => $project, 'files' => ips_projects_files($id)]);
