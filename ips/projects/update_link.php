<?php
// Changes a link's role, label and note: project_id, file_id, role, label, note. Who may: as for unlink.php. Answers the saved link.
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
	ips_projects_refuse(403, 'You can only change files you linked, or files inside files/' . $user . '.');
}
$role = $_POST['role'] ?? $link['role'];
if (!is_string($role) || !isset(IPS_PROJECT_ROLES[$role])) {
	ips_projects_refuse(422, 'Choose a role for the file.');
}
$label = ips_projects_text($_POST['label'] ?? $link['label'], 255);
if ($label === null) {
	ips_projects_refuse(422, 'The label is at most 255 characters.');
}
$note = ips_projects_text($_POST['note'] ?? $link['note'], 1000);
if ($note === null) {
	ips_projects_refuse(422, 'The note is at most 1000 characters.');
}
$db->query("UPDATE project_files SET role=?, label=?, note=? WHERE project_id=? AND file_id=?", [
	['type' => 's', 'value' => $role], ['type' => 's', 'value' => $label], ['type' => 's', 'value' => $note],
	['type' => 'i', 'value' => $project_id], ['type' => 'i', 'value' => $file_id],
]);
echo json_encode(['success' => true, 'link' => ips_projects_link_get($project_id, $file_id)]);
