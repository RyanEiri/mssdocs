<?php
// The AtoM CSV of a unit of description, the units below it and their items (atom_lib.php): GET id (the unit to start from; a top level exports the whole fonds), items=0 to leave the
// items out, published=1 to include only what is public (a unit or item whose own status, or a unit above it, is not published is left out; 409 if nothing is). Any signed-in user, as
// for reading a unit. An item is a TEI .xml file linked to a unit with the role "description"; it needs the file editor module and PHP's dom and xsl, and without them only the units
// are exported. IPS_ATOM_INSTITUTION (the institution identifier) and IPS_ATOM_RULES (default "Rules for Archival Description (RAD), 2008") may be defined in php/site-config.php.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/unit_lib.php';
require_once __DIR__.'/atom_lib.php';
ips_units_user(false);
$id = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$all = ips_units_all();
if (!$id || !isset($all[$id])) {
	ips_projects_refuse(404, 'That project has no description as a unit.');
}
$only_published = ($_GET['published'] ?? '') === '1';
$with_items = ($_GET['items'] ?? '1') !== '0';
$site = ['institution' => defined('IPS_ATOM_INSTITUTION') ? IPS_ATOM_INSTITUTION : '', 'rules' => defined('IPS_ATOM_RULES') ? IPS_ATOM_RULES : 'Rules for Archival Description (RAD), 2008'];

$lib = getcwd().'/file_editor/metadata/lib.php';
$items_ok = $with_items && is_file($lib);
if ($items_ok) {
	require_once $lib;
	$items_ok = ips_fe_metadata_available();
}

$rows = [];
$skipped = 0;
foreach (ips_atom_subtree($id, $all) as $unit_id) {
	$effective = ips_units_effective_visibility($unit_id, $all);
	if ($only_published && $effective !== 'published') {
		$skipped++;
		continue;
	}
	$row = ips_atom_unit_row($unit_id, $all, $site);
	if ($unit_id === $id) {
		$row['parentId'] = ''; // the export starts here: this unit is its top, whatever is above it
	}
	$rows[] = $row;
	if (!$items_ok) {
		continue;
	}
	foreach (ips_units_items($unit_id) as $link) {
		$real = ips_files_root() === false ? false : realpath(ips_files_root() . '/' . substr($link['path'], 6));
		if ($real === false || strtolower(pathinfo($real, PATHINFO_EXTENSION)) !== 'xml' || strpos($real, ips_files_root() . '/') !== 0) {
			continue;
		}
		$view = ips_fe_metadata_run((string) file_get_contents($real));
		if ($view['stage'] !== null || !$view['rad']) {
			continue;
		}
		$own = $view['record']['status'];
		$status = ($own === 'published' && $effective === 'published') ? 'published' : (($own === 'private' || $effective === 'private') ? 'private' : 'draft');
		if ($only_published && $status !== 'published') {
			$skipped++;
			continue;
		}
		$identifier = '';
		foreach ($view['dc'] as $pair) {
			if ($pair[0] === 'dc:identifier') {
				$identifier = $pair[1];
				break;
			}
		}
		$rows[] = ips_atom_item_row($view['rad'], 'i' . $link['file_id'], 'u' . $unit_id, $identifier, $status);
	}
}
if (!$rows) {
	ips_projects_refuse(409, 'Nothing is published in this description yet.');
}
$name = preg_replace('/[^A-Za-z0-9._-]+/', '-', ips_units_reference_code($id, $all) ?: $all[$id]['title']);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="atom-' . trim($name, '-') . '.csv"');
header('X-Skipped-Rows: ' . $skipped);
echo ips_atom_csv($rows);
