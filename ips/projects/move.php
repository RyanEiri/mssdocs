<?php
// Moves a file one place up or down in its project: project_id, file_id, direction (up|down). Anyone signed in may order a project's files.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
ips_projects_user(true);

$project_id = isset($_POST['project_id']) && ctype_digit((string) $_POST['project_id']) ? (int) $_POST['project_id'] : 0;
$file_id = isset($_POST['file_id']) && ctype_digit((string) $_POST['file_id']) ? (int) $_POST['file_id'] : 0;
$direction = $_POST['direction'] ?? '';
if (!in_array($direction, ['up', 'down'], true)) {
	ips_projects_refuse(422, 'The direction is up or down.');
}
if (ips_projects_link_get($project_id, $file_id) === null) {
	ips_projects_refuse(404, 'That file is not in the project.');
}
ips_projects_move($project_id, $file_id, $direction);
echo json_encode(['success' => true, 'files' => ips_projects_files($project_id)]);
