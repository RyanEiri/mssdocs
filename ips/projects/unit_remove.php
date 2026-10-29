<?php
// Takes a project's description as a unit away (the project, its files and its links stay). Refused while other units are under it. Only an administrator or the person who made
// the project may.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/unit_lib.php';
[$user, $admin] = ips_units_user(true);
$id = isset($_POST['project_id']) && ctype_digit((string) $_POST['project_id']) ? (int) $_POST['project_id'] : 0;
$all = ips_units_all();
$project = $id ? ips_projects_get($id) : null;
if ($project === null || !isset($all[$id])) {
	ips_projects_refuse(404, 'That project has no description as a unit.');
}
if (!$admin && $project['created_by'] !== $user) {
	ips_projects_refuse(403, 'Only an administrator or the person who made the project can remove its description.');
}
if (ips_units_children($id, $all)) {
	ips_projects_refuse(409, 'Other units are under this one; move or remove them first.');
}
$db->query("DELETE FROM unit_descriptions WHERE project_id=?", [['type' => 'i', 'value' => $id]]);
echo json_encode(['success' => true]);
