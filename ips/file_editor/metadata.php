<?php
// The Metadata panel's data: the Dublin Core record, the RAD description and the completeness report for the TEI text in the editor (metadata/lib.php), as JSON
// {success, available, stage, message, record, dc, rad, completeness, part_of}; part_of lists the units the file is an item of when the Projects module has units (see ips_fe_part_of). It describes only the text it is given (unsaved changes included) and reads no file, so it needs a signed-in
// user and a same-origin POST with that user's CSRF token, nothing more. "available" is false (and the editor shows no Metadata button) when PHP has no dom or xsl extension.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
require_once __DIR__.'/metadata/lib.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

function ips_fe_refuse($status, $message) {
	http_response_code($status);
	echo json_encode(['success' => false, 'error' => $message]);
	exit;
}

$login_cookie = new UserCookie();
if ($login_cookie->Peek() === false) {
	ips_fe_refuse(403, 'Sign in to see the metadata.');
}
$login_cookie->RequireCsrf();
if (!isset($_POST['text']) || !is_string($_POST['text']) || $_POST['text'] === '') {
	ips_fe_refuse(400, 'No text to describe.');
}

// Where the file sits in an archival description: the units it is an item of (linked to them with the role "description" in the Projects module), each with its chain, reference code,
// effective visibility and the fields it inherits, or null when the module or its table is missing, the file has no catalogue row, or it is in no unit. The file is named as the editor
// names it and resolved for this user like any file the editor opens, so nothing else can be looked up.
function ips_fe_part_of($given, $dc) {
	global $db, $login_cookie;
	$projects = __DIR__ . '/../projects/unit_lib.php';
	if (!is_string($given) || $given === '' || !is_file($projects)) {
		return null;
	}
	require_once $projects;
	if (!ips_units_ready()) {
		return null;
	}
	$real = ips_fe_resolve($given, ips_fe_scope($login_cookie->username ?? ''));
	$root = ips_files_root();
	if ($real === false || $root === false || strpos($real, $root . '/') !== 0) {
		return null;
	}
	$row = $db->query("SELECT id FROM files WHERE name=? LIMIT 1", [['type' => 's', 'value' => substr($real, strlen($root) + 1)]])->fetchArray();
	$ids = $row ? ips_units_of_file((int) $row[0]['id']) : [];
	if (!$ids) {
		return null;
	}
	$all = ips_units_all();
	$identifier = '';
	foreach ($dc as $pair) {
		if ($pair[0] === 'dc:identifier') {
			$identifier = $pair[1];
			break;
		}
	}
	$parents = [];
	foreach ($ids as $id) {
		if (!isset($all[$id])) {
			continue;
		}
		$code = ips_units_reference_code($id, $all);
		// what the item has "as above": for each inheritable field the unit's own value, else the nearest unit above that has one (an item sits below its unit, so the unit counts too)
		$inherited = [];
		$above = ips_units_inherited($id, $all);
		foreach (IPS_UNIT_INHERITED as $field) {
			$found = (string) $all[$id][$field] !== '' ? ['value' => (string) $all[$id][$field], 'from' => $id] : ($above[$field] ?? ['value' => '', 'from' => null]);
			if ($found['from'] !== null) {
				$inherited[$field] = $found + ['from_title' => $all[$found['from']]['title']];
			}
		}
		$parents[] = ['project_id' => $id, 'title' => $all[$id]['title'], 'level' => $all[$id]['level'], 'reference_code' => $code,
			'item_reference_code' => $identifier === '' ? $code : ($code === '' ? $identifier : $code . '/' . $identifier),
			'effective_visibility' => ips_units_effective_visibility($id, $all), 'inherited' => $inherited,
			'chain' => array_map(function ($u) { return ['project_id' => $u['project_id'], 'title' => $u['title'], 'level' => $u['level']]; }, ips_units_chain($id, $all))];
	}
	return $parents ?: null;
}

$result = ips_fe_metadata_run($_POST['text']);
$result['part_of'] = $result['stage'] === null && $result['available'] ? ips_fe_part_of($_POST['file'] ?? null, $result['dc']) : null;
echo json_encode(['success' => true] + $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
