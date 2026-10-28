<?php
// Links files of the file store to a project: project_id, role, paths[] ("files/A/b.png", in the order they are to be added at the end of the project). A file with no
// catalogue row yet gets one. A file already in the project keeps its place and takes the new role. Each path is judged on its own: the answer lists what was
// linked and what was refused, with the reason.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
[$user, $admin] = ips_projects_user(true);

$project_id = isset($_POST['project_id']) && ctype_digit((string) $_POST['project_id']) ? (int) $_POST['project_id'] : 0;
if (!$project_id || ips_projects_get($project_id) === null) {
	ips_projects_refuse(404, 'That project no longer exists.');
}
$role = $_POST['role'] ?? 'supporting';
if (!is_string($role) || !isset(IPS_PROJECT_ROLES[$role])) {
	ips_projects_refuse(422, 'Choose a role for the files.');
}
$paths = $_POST['paths'] ?? [];
if (!is_array($paths) || !$paths || count($paths) > IPS_PROJECTS_MAX_LINK) {
	ips_projects_refuse(422, 'Name between 1 and ' . IPS_PROJECTS_MAX_LINK . ' files to link.');
}

$linked = [];
$failed = [];
foreach (array_values(array_unique($paths)) as $path) {
	$found = ips_projects_resolve($path, $user, $admin, true);
	if (is_string($found)) {
		$failed[] = ['path' => is_string($path) ? $path : '', 'error' => $found];
		continue;
	}
	$file_id = ips_projects_file_id($found, true);
	if (!$file_id) {
		$failed[] = ['path' => $path, 'error' => 'The file could not be added to the catalogue.'];
		continue;
	}
	ips_projects_link($project_id, $file_id, $role, $user);
	$linked[] = ['path' => $path, 'file_id' => $file_id];
}
echo json_encode(['success' => true, 'linked' => $linked, 'failed' => $failed]);
