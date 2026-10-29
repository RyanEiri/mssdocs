<?php
// Gives a project a description as a unit (fonds, collection, series or file) or changes it: level, parent, reference-code segment, title, ISO 8601 dates, extent, histories, scope and
// content, notes, visibility. Answers the unit in full (unit_get.php's answer). Any signed-in user writes a unit; only an administrator may set its visibility to published or private.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/unit_lib.php';
[$user, $admin] = ips_units_user(true);

$id = isset($_POST['project_id']) && ctype_digit((string) $_POST['project_id']) ? (int) $_POST['project_id'] : 0;
if (!$id || ips_projects_get($id) === null) {
	ips_projects_refuse(404, 'That project no longer exists.');
}
$all = ips_units_all();
$existing = $all[$id] ?? null;

$level = $_POST['level'] ?? null;
if (!is_string($level) || !isset(IPS_UNIT_LEVELS[$level])) {
	ips_projects_refuse(422, 'Choose a level: ' . implode(', ', array_keys(IPS_UNIT_LEVELS)) . '.');
}
$parent = $_POST['parent_id'] ?? '';
if ($parent === '' || $parent === '0') {
	$parent = null;
} elseif (is_string($parent) && ctype_digit($parent)) {
	$parent = (int) $parent;
} else {
	ips_projects_refuse(422, 'The parent must be the number of a unit, or empty.');
}
if (($problem = ips_units_parent_problem($id, $level, $parent, $all)) !== null) {
	ips_projects_refuse(422, $problem);
}
if ($existing && ($problem = ips_units_children_problem($id, $level, $all)) !== null) {
	ips_projects_refuse(422, $problem);
}

$v = ['level' => $level, 'parent_id' => $parent];
$segment = ips_projects_text($_POST['ref_segment'] ?? '', 60);
if ($segment === null || !preg_match('/^[A-Za-z0-9._-]*$/', $segment)) {
	ips_projects_refuse(422, 'The reference-code segment is letters, digits, dot, hyphen and underscore, at most 60.');
}
$v['ref_segment'] = $segment;
foreach (IPS_UNIT_LINE_FIELDS as $field => $max) {
	$value = ips_projects_text($_POST[$field] ?? '', $max);
	if ($value === null) {
		ips_projects_refuse(422, "The $field is at most $max characters.");
	}
	$v[$field] = $value;
}
foreach (IPS_UNIT_TEXT_FIELDS as $field) {
	$value = ips_projects_long_text($_POST[$field] ?? '', IPS_UNIT_TEXT_MAX);
	if ($value === null) {
		ips_projects_refuse(422, "The $field is at most " . IPS_UNIT_TEXT_MAX . ' characters.');
	}
	$v[$field] = $value;
}
foreach (['date_start', 'date_end'] as $field) {
	$value = $_POST[$field] ?? '';
	if (!is_string($value) || ($value = trim($value)) !== '' && ips_units_date_bounds($value) === null) {
		ips_projects_refuse(422, "The $field must be an ISO 8601 date: YYYY, YYYY-MM or YYYY-MM-DD (empty for none).");
	}
	$v[$field] = $value;
}
if ($v['date_start'] !== '' && $v['date_end'] !== '' && ips_units_date_bounds($v['date_start'])[0] > ips_units_date_bounds($v['date_end'])[1]) {
	ips_projects_refuse(422, 'The unit cannot end before it starts.');
}
$visibility = $_POST['visibility'] ?? ($existing['visibility'] ?? 'draft');
if (!is_string($visibility) || !isset(IPS_UNIT_VISIBILITIES[$visibility])) {
	ips_projects_refuse(422, 'The visibility is draft, published or private.');
}
if (!$admin && $visibility !== ($existing['visibility'] ?? 'draft')) {
	ips_projects_refuse(403, 'Only an administrator sets a unit to ' . $visibility . '.');
}
$v['visibility'] = $visibility;

ips_units_write($id, $v, $user);
echo json_encode(['success' => true] + ips_units_detail($id, ips_units_all()), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
