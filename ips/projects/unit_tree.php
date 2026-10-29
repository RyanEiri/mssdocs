<?php
// Every unit as a tree (top levels first, each with its children), each node with its level, reference code, visibility, effective visibility and number of items, and the levels and
// visibilities a form may offer. A unit whose parent has gone is listed at the top with "orphan": true.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/unit_lib.php';
ips_units_user(false);
$all = ips_units_all();
$counts = [];
$result = $db->query("SELECT l.project_id, COUNT(*) AS n FROM project_files l JOIN files f ON f.id = l.file_id WHERE l.role='description' GROUP BY l.project_id");
while ($r = $result->fetch()) {
	$counts[(int) $r['project_id']] = (int) $r['n'];
}
$node = function ($u) use (&$node, $all, $counts) {
	$kids = [];
	foreach (ips_units_children($u['project_id'], $all) as $child) {
		$kids[] = $node($child);
	}
	return ['project_id' => $u['project_id'], 'title' => $u['title'], 'level' => $u['level'], 'ref_segment' => $u['ref_segment'], 'reference_code' => ips_units_reference_code($u['project_id'], $all),
		'visibility' => $u['visibility'], 'effective_visibility' => ips_units_effective_visibility($u['project_id'], $all), 'items' => $counts[$u['project_id']] ?? 0, 'children' => $kids,
		'orphan' => $u['parent_id'] !== null && !isset($all[$u['parent_id']])];
};
$tree = [];
foreach ($all as $u) {
	if ($u['parent_id'] === null || !isset($all[$u['parent_id']])) {
		$tree[] = $node($u);
	}
}
echo json_encode(['success' => true, 'units' => $tree, 'levels' => ips_projects_options(IPS_UNIT_LEVELS), 'visibilities' => ips_projects_options(IPS_UNIT_VISIBILITIES)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
