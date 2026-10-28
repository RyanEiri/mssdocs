<?php
// Deletes a project and its links (never the files). Only an administrator or the person who made the project may.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
[$user, $admin] = ips_projects_user(true);

$id = isset($_POST['id']) && ctype_digit((string) $_POST['id']) ? (int) $_POST['id'] : 0;
$project = $id ? ips_projects_get($id) : null;
if ($project === null) {
	ips_projects_refuse(404, 'That project no longer exists.');
}
if (!$admin && $project['created_by'] !== $user) {
	ips_projects_refuse(403, 'Only an administrator or the person who made the project can delete it.');
}
$db->query("DELETE FROM projects WHERE id=?", [['type' => 'i', 'value' => $id]]);
echo json_encode(['success' => true]);
