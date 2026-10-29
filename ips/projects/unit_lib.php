<?php
/* Units of description (RAD hierarchy), on top of the Projects module: a project with a row in `unit_descriptions` is a fonds, collection, series or file, with its own
 * description, a parent unit, and as its items the TEI files linked to it with the role "description". The design and the reasons are in docs/mssdocs-rad-hierarchy-design.md
 * (webserversAC repository). Who may do what: any signed-in user writes a unit as a draft; only an administrator sets a unit's visibility to published or private.
 *
 * The rules (levels, parents, dates, effective visibility, inheritance) are pure functions of the set of units ($all: project id => row), so they are tested without a database.
 */
require_once __DIR__ . '/lib.php';

const IPS_UNIT_LEVELS = ['fonds' => 'Fonds', 'collection' => 'Collection', 'series' => 'Series', 'file' => 'File'];
const IPS_UNIT_VISIBILITIES = ['draft' => 'Draft', 'published' => 'Published', 'private' => 'Private'];
const IPS_UNIT_MAX_DEPTH = 6; // levels of units from the top, including the unit itself
// what a unit may sit under: fonds and collection are top levels; a series or file sits under a fonds, a collection or a series (a sub-series is a series under a series)
const IPS_UNIT_PARENT_LEVELS = ['fonds' => [], 'collection' => [], 'series' => ['fonds', 'collection', 'series'], 'file' => ['fonds', 'collection', 'series']];
// fields a view shows from the nearest unit above when the unit leaves them empty (RAD 1.0A2d)
const IPS_UNIT_INHERITED = ['custodial_history', 'language', 'repository', 'access_note', 'terms_note'];
const IPS_UNIT_LINE_FIELDS = ['title' => 255, 'extent' => 255, 'language' => 255, 'repository' => 255, 'accruals' => 255];
const IPS_UNIT_TEXT_FIELDS = ['admin_history', 'custodial_history', 'scope_content', 'access_note', 'terms_note', 'general_note']; // each at most IPS_UNIT_TEXT_MAX characters
const IPS_UNIT_TEXT_MAX = 20000;

// ---- pure rules -------------------------------------------------------------------------------------------------------------------------------

// [first day, last day] of an ISO 8601 date as YYYYMMDD numbers (a year covers the whole year, a month the whole month), or null when it is not a real YYYY, YYYY-MM or YYYY-MM-DD.
function ips_units_date_bounds($d) {
	if (!is_string($d) || !preg_match('/^(\d{4})(?:-(\d{2})(?:-(\d{2}))?)?$/', $d, $m)) {
		return null;
	}
	$year = (int) $m[1];
	if (!isset($m[2])) {
		return [$year * 10000 + 101, $year * 10000 + 1231];
	}
	$month = (int) $m[2];
	if ($month < 1 || $month > 12) {
		return null;
	}
	$leap = $year % 4 === 0 && ($year % 100 !== 0 || $year % 400 === 0);
	$days = [31, $leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][$month - 1];
	if (!isset($m[3])) {
		return [$year * 10000 + $month * 100 + 1, $year * 10000 + $month * 100 + $days];
	}
	$day = (int) $m[3];
	if ($day < 1 || $day > $days) {
		return null;
	}
	$n = $year * 10000 + $month * 100 + $day;
	return [$n, $n];
}

// The ancestors of a unit, top first, then the unit itself: a list of rows from $all (id => row). Stops, without looping, at a missing parent or a cycle.
function ips_units_chain($id, $all) {
	$chain = [];
	$seen = [];
	while ($id !== null && isset($all[$id]) && !isset($seen[$id]) && count($chain) < 50) {
		$seen[$id] = true;
		array_unshift($chain, $all[$id]);
		$id = $all[$id]['parent_id'];
	}
	return $chain;
}

// What is wrong with putting a unit of $level (project $id; null for a new one) under $parent_id, or null when it is fine: the parent must be a unit of a level that may hold it,
// the unit may not be under itself or its descendants, and the depth is limited. $all is the set of units as it is now.
function ips_units_parent_problem($id, $level, $parent_id, $all) {
	$allowed = IPS_UNIT_PARENT_LEVELS[$level] ?? null;
	if ($allowed === null) {
		return 'Choose a level: ' . implode(', ', array_keys(IPS_UNIT_LEVELS)) . '.';
	}
	if ($parent_id === null) {
		return $allowed ? 'A ' . $level . ' belongs under a ' . implode(', ', $allowed) . '.' : null;
	}
	if (!$allowed) {
		return 'A ' . $level . ' is a top level and has no parent.';
	}
	if (!isset($all[$parent_id])) {
		return 'The parent must be a unit of description.';
	}
	if ($id !== null && $parent_id === $id) {
		return 'A unit cannot be under itself.';
	}
	if (!in_array($all[$parent_id]['level'], $allowed, true)) {
		return 'A ' . $level . ' belongs under a ' . implode(', ', $allowed) . ', not a ' . $all[$parent_id]['level'] . '.';
	}
	$chain = ips_units_chain($parent_id, $all);
	foreach ($chain as $above) {
		if ($id !== null && $above['project_id'] === $id) {
			return 'A unit cannot be under one of its own parts.';
		}
	}
	if (count($chain) + 1 > IPS_UNIT_MAX_DEPTH) {
		return 'Units can be nested at most ' . IPS_UNIT_MAX_DEPTH . ' deep.';
	}
	return null;
}

// What is wrong with giving unit $id the level $level while it keeps its children: a child must still be allowed under it. Null when fine.
function ips_units_children_problem($id, $level, $all) {
	foreach ($all as $unit) {
		if ($unit['parent_id'] === $id && !in_array($level, IPS_UNIT_PARENT_LEVELS[$unit['level']] ?? [], true)) {
			return 'It holds a ' . $unit['level'] . ', which cannot sit under a ' . $level . '.';
		}
	}
	return null;
}

function ips_units_children($id, $all) {
	$out = [];
	foreach ($all as $unit) {
		if ($unit['parent_id'] === $id) {
			$out[] = $unit;
		}
	}
	return $out;
}

// The reference code of a unit: the segments of the chain from the top, joined with "/" (empty segments left out).
function ips_units_reference_code($id, $all) {
	$parts = [];
	foreach (ips_units_chain($id, $all) as $unit) {
		if ($unit['ref_segment'] !== '') {
			$parts[] = $unit['ref_segment'];
		}
	}
	return implode('/', $parts);
}

// The visibility a unit has in public zones: the least public along its chain. Any private unit above (or itself) makes it private, else any draft makes it draft; published only if all are.
function ips_units_effective_visibility($id, $all) {
	$worst = 'published';
	foreach (ips_units_chain($id, $all) as $unit) {
		if ($unit['visibility'] === 'private') {
			return 'private';
		}
		if ($unit['visibility'] !== 'published') {
			$worst = 'draft';
		}
	}
	return $worst;
}

// For each inherited field the unit leaves empty: {value, from} with the nearest unit above that has it ({value: '', from: null} when none has).
function ips_units_inherited($id, $all) {
	$chain = ips_units_chain($id, $all);
	array_pop($chain); // the unit itself
	$out = [];
	foreach (IPS_UNIT_INHERITED as $field) {
		if (($all[$id][$field] ?? '') !== '') {
			continue;
		}
		$found = ['value' => '', 'from' => null];
		foreach (array_reverse($chain) as $above) {
			if ((string) $above[$field] !== '') {
				$found = ['value' => (string) $above[$field], 'from' => $above['project_id']];
				break;
			}
		}
		$out[$field] = $found;
	}
	return $out;
}

// Advice about the dates of a unit and its neighbours (RAD 1.4B2: the dates of a part fall within the dates of the whole). Never blocks a save. Each warning is a sentence.
function ips_units_date_warnings($id, $all) {
	$out = [];
	$within = function ($child, $parent) {
		$c = ips_units_date_bounds($child['date_start']);
		$p = ips_units_date_bounds($parent['date_start']);
		if ($c && $p && $c[0] < $p[0]) {
			return false;
		}
		$parent_end = ips_units_date_bounds($parent['date_end']);
		if ($parent_end) {
			if ($child['date_end'] === '') {
				return $child['date_start'] === '' ? true : false;
			}
			$child_end = ips_units_date_bounds($child['date_end']);
			if ($child_end && $child_end[1] > $parent_end[1]) {
				return false;
			}
		}
		return true;
	};
	$unit = $all[$id];
	if ($unit['parent_id'] !== null && isset($all[$unit['parent_id']]) && !$within($unit, $all[$unit['parent_id']])) {
		$out[] = 'Its dates are not within those of ' . $all[$unit['parent_id']]['title'] . ' (RAD 1.4B2).';
	}
	foreach (ips_units_children($id, $all) as $child) {
		if (!$within($child, $unit)) {
			$out[] = 'The dates of ' . $child['title'] . ' are not within those of this unit (RAD 1.4B2).';
		}
	}
	return $out;
}

// Advice about the reference-code segment: another unit under the same parent with the same one. Never blocks a save.
function ips_units_segment_warnings($id, $all) {
	$out = [];
	$unit = $all[$id];
	if ($unit['ref_segment'] === '') {
		return $out;
	}
	foreach ($all as $other) {
		if ($other['project_id'] !== $id && $other['parent_id'] === $unit['parent_id'] && $other['ref_segment'] === $unit['ref_segment']) {
			$out[] = 'Another unit under the same parent (' . $other['title'] . ') has the same reference-code segment, ' . $unit['ref_segment'] . '.';
		}
	}
	return $out;
}

// ---- database ---------------------------------------------------------------------------------------------------------------------------------

function ips_units_ready() {
	global $db;
	return ips_projects_ready() && $db->query("SHOW TABLES LIKE 'unit_descriptions'")->size() > 0;
}

// The signed-in user for a unit endpoint (see ips_projects_user), and 503 until units.sql has been run. Returns [username, is_admin].
function ips_units_user($write) {
	$who = ips_projects_user($write);
	if (!ips_units_ready()) {
		ips_projects_refuse(503, 'The units table has not been created yet (run projects/units.sql).');
	}
	return $who;
}

function ips_units_row($r) {
	$out = ['project_id' => (int) $r['project_id'], 'level' => $r['level'], 'parent_id' => $r['parent_id'] === null ? null : (int) $r['parent_id'],
		'ref_segment' => $r['ref_segment'], 'project_title' => $r['project_title'], 'title' => $r['title'] !== '' ? $r['title'] : $r['project_title'],
		'own_title' => $r['title'], 'date_start' => $r['date_start'], 'date_end' => $r['date_end'], 'extent' => $r['extent'],
		'accruals' => $r['accruals'], 'visibility' => $r['visibility'], 'updated_by' => $r['updated_by'], 'updated_at' => $r['updated_at'], 'created_by' => $r['created_by']];
	foreach (array_merge(IPS_UNIT_TEXT_FIELDS, ['language', 'repository']) as $field) {
		$out[$field] = (string) $r[$field];
	}
	return $out;
}

// Every unit, keyed by project id (title is the unit's own title, else the project's).
function ips_units_all() {
	global $db;
	$out = [];
	$result = $db->query("SELECT u.*, p.title AS project_title, p.created_by FROM unit_descriptions u JOIN projects p ON p.id = u.project_id ORDER BY p.title, u.project_id");
	while ($r = $result->fetch()) {
		$row = ips_units_row($r);
		$out[$row['project_id']] = $row;
	}
	return $out;
}

// The TEI files (or any files) linked to a unit with the role "description": its items, in the project's order.
function ips_units_items($project_id) {
	return array_values(array_filter(ips_projects_files($project_id), function ($link) { return $link['role'] === 'description'; }));
}

// The units a file is an item of (linked with the role "description"): a list of project ids. One is right; more than one is reported to the cataloguer.
function ips_units_of_file($file_id) {
	global $db;
	$out = [];
	$result = $db->query("SELECT l.project_id FROM project_files l JOIN unit_descriptions u ON u.project_id = l.project_id WHERE l.file_id=? AND l.role='description' ORDER BY l.position, l.project_id",
		[['type' => 'i', 'value' => $file_id]]);
	while ($r = $result->fetch()) {
		$out[] = (int) $r['project_id'];
	}
	return $out;
}

// One unit with everything a view needs: the row, its chain, reference code, effective visibility, inherited fields, children, items and date warnings.
function ips_units_detail($id, $all) {
	if (!isset($all[$id])) {
		return null;
	}
	$brief = function ($u) { return ['project_id' => $u['project_id'], 'title' => $u['title'], 'level' => $u['level'], 'ref_segment' => $u['ref_segment'], 'visibility' => $u['visibility']]; };
	return ['unit' => $all[$id],
		'chain' => array_map($brief, ips_units_chain($id, $all)),
		'reference_code' => ips_units_reference_code($id, $all),
		'effective_visibility' => ips_units_effective_visibility($id, $all),
		'inherited' => ips_units_inherited($id, $all),
		'children' => array_map($brief, ips_units_children($id, $all)),
		'items' => ips_units_items($id),
		'warnings' => array_merge(ips_units_date_warnings($id, $all), ips_units_segment_warnings($id, $all))];
}

// Inserts or replaces the description of project $id with $v (the validated values of unit_save.php).
function ips_units_write($id, $v, $user) {
	global $db;
	$columns = ['level', 'parent_id', 'ref_segment', 'title', 'date_start', 'date_end', 'extent', 'admin_history', 'custodial_history', 'scope_content', 'language', 'repository',
		'access_note', 'terms_note', 'general_note', 'accruals', 'visibility'];
	$params = [['type' => 'i', 'value' => $id]];
	foreach ($columns as $c) {
		$params[] = ['type' => $c === 'parent_id' ? 'i' : 's', 'value' => $v[$c]];
	}
	$params[] = ['type' => 's', 'value' => $user];
	$names = implode(', ', $columns);
	$marks = implode(', ', array_fill(0, count($columns), '?'));
	$updates = implode(', ', array_map(function ($c) { return "$c = VALUES($c)"; }, array_merge($columns, ['updated_by'])));
	$db->query("INSERT INTO unit_descriptions (project_id, $names, updated_by) VALUES (?, $marks, ?) ON DUPLICATE KEY UPDATE $updates", $params);
}

// ---- views ------------------------------------------------------------------------------------------------------------------------------------

// The XML the unit stylesheets (file_editor/metadata/unit-to-rad.xsl, unit-to-dc.xsl) read: the unit's own description, its chain, its children and the values it inherits. Needs dom.
// <unit id level visibility effective> <reference-code/> <title/> <dates start end/> <extent/> <accruals/> <admin-history/> <custodial-history/> <scope-content/> <language/>
// <repository/> <access/> <terms/> <general-note/> <inherited field from from-title>value</inherited>* <chain><unit id level title/>*</chain> <children><unit .../>*</children>
function ips_units_xml($id, $all) {
	if (!isset($all[$id])) {
		return null;
	}
	$u = $all[$id];
	$doc = new DOMDocument('1.0', 'UTF-8');
	$root = $doc->createElement('unit');
	$doc->appendChild($root);
	foreach (['id' => $id, 'level' => $u['level'], 'visibility' => $u['visibility'], 'effective' => ips_units_effective_visibility($id, $all)] as $name => $value) {
		$root->setAttribute($name, (string) $value);
	}
	$text = function ($name, $value) use ($doc, $root) {
		$root->appendChild($doc->createElement($name))->appendChild($doc->createTextNode((string) $value));
	};
	$text('reference-code', ips_units_reference_code($id, $all));
	$text('title', $u['title']);
	$dates = $root->appendChild($doc->createElement('dates'));
	$dates->setAttribute('start', $u['date_start']);
	$dates->setAttribute('end', $u['date_end']);
	foreach (['extent' => 'extent', 'accruals' => 'accruals', 'admin-history' => 'admin_history', 'custodial-history' => 'custodial_history', 'scope-content' => 'scope_content',
		'language' => 'language', 'repository' => 'repository', 'access' => 'access_note', 'terms' => 'terms_note', 'general-note' => 'general_note'] as $element => $field) {
		$text($element, $u[$field]);
	}
	$names = ['custodial_history' => 'custodial-history', 'language' => 'language', 'repository' => 'repository', 'access_note' => 'access', 'terms_note' => 'terms'];
	foreach (ips_units_inherited($id, $all) as $field => $found) {
		if ($found['from'] === null) {
			continue;
		}
		$e = $root->appendChild($doc->createElement('inherited'));
		$e->setAttribute('field', $names[$field]);
		$e->setAttribute('from', (string) $found['from']);
		$e->setAttribute('from-title', $all[$found['from']]['title']);
		$e->appendChild($doc->createTextNode($found['value']));
	}
	$list = function ($name, $units) use ($doc, $root) {
		$box = $root->appendChild($doc->createElement($name));
		foreach ($units as $unit) {
			$e = $box->appendChild($doc->createElement('unit'));
			$e->setAttribute('id', (string) $unit['project_id']);
			$e->setAttribute('level', $unit['level']);
			$e->setAttribute('title', $unit['title']);
		}
	};
	$chain = ips_units_chain($id, $all);
	array_pop($chain);
	$list('chain', $chain);
	$list('children', ips_units_children($id, $all));
	return $doc->saveXML();
}
