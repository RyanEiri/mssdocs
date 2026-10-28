<?php
// Adds a project (id 0 or missing) or changes one: title, kind, status, notes. Answers the saved project.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
[$user] = ips_projects_user(true);

$id = isset($_POST['id']) && ctype_digit((string) $_POST['id']) ? (int) $_POST['id'] : 0;
if ($id && ips_projects_get($id) === null) {
	ips_projects_refuse(404, 'That project no longer exists.');
}
$title = ips_projects_text($_POST['title'] ?? null, 255);
if ($title === null || $title === '') {
	ips_projects_refuse(422, 'The title is required (at most 255 characters).');
}
$kind = $_POST['kind'] ?? 'artefact';
if (!is_string($kind) || !isset(IPS_PROJECT_KINDS[$kind])) {
	ips_projects_refuse(422, 'Choose a kind of project: ' . implode(', ', array_keys(IPS_PROJECT_KINDS)) . '.');
}
$status = $_POST['status'] ?? 'active';
if (!is_string($status) || !isset(IPS_PROJECT_STATUSES[$status])) {
	ips_projects_refuse(422, 'The status is active or archived.');
}
$notes = ips_projects_long_text($_POST['notes'] ?? '', 5000);
if ($notes === null) {
	ips_projects_refuse(422, 'The notes are at most 5000 characters.');
}

if (!$id) {
	$id = $db->query("INSERT INTO projects (title, kind, status, notes, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?)", [
		['type' => 's', 'value' => $title], ['type' => 's', 'value' => $kind], ['type' => 's', 'value' => $status], ['type' => 's', 'value' => $notes],
		['type' => 's', 'value' => $user], ['type' => 's', 'value' => $user],
	])->getId();
} else {
	$db->query("UPDATE projects SET title=?, kind=?, status=?, notes=?, updated_by=? WHERE id=?", [
		['type' => 's', 'value' => $title], ['type' => 's', 'value' => $kind], ['type' => 's', 'value' => $status], ['type' => 's', 'value' => $notes],
		['type' => 's', 'value' => $user], ['type' => 'i', 'value' => $id],
	]);
}
echo json_encode(['success' => true, 'project' => ips_projects_get($id)]);
