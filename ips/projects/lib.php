<?php
/* The Projects module (ips/projects/): a project is a record (title, kind, status, notes) and groups files of the file store through `project_files`: each link has a
 * role, a position, a label and a note, and a file may be in several projects. Everything the endpoints share is here: who may do what, how a "files/..." path from the
 * browser becomes a catalogue row, and the queries. Any signed-in user may make and change projects (like the Links module); a file may be linked, and a link
 * changed or removed, only by an administrator, by whoever linked it, or by someone who may write where the file is (inside files/<their username>). Every write
 * is a same-origin POST with the user's CSRF token, bound parameters only.
 */

const IPS_PROJECTS_MAX_LINK = 200; // files in one link request

const IPS_PROJECT_KINDS = [
	'artefact' => 'Artefact description',
	'issue'    => 'Issue',
	'series'   => 'Article series',
	'other'    => 'Other',
];
const IPS_PROJECT_STATUSES = ['active' => 'Active', 'archived' => 'Archived'];
const IPS_PROJECT_ROLES = [
	'artefact-page' => 'Artefact page (image)',
	'artefact-pdf'  => 'Artefact PDF',
	'transcription' => 'Transcription',
	'description'   => 'Description',
	'supporting'    => 'Supporting document',
	'other'         => 'Other',
];

function ips_projects_refuse($status, $message) {
	http_response_code($status);
	echo json_encode(['success' => false, 'error' => $message]);
	exit;
}

// The signed-in user for a JSON endpoint (403 otherwise); for a write also a valid same-origin POST with the user's token (RequireCsrf answers 403/405 itself).
// Returns [username, is_admin].
function ips_projects_user($write) {
	global $login_cookie;
	header('Content-Type: application/json');
	header('Cache-Control: no-store');
	$login_cookie = new UserCookie();
	if ($login_cookie->Peek() === false) {
		ips_projects_refuse(403, 'Sign in to use projects.');
	}
	if ($write) {
		$login_cookie->RequireCsrf();
	}
	if (!ips_projects_ready()) {
		ips_projects_refuse(503, 'The projects tables have not been created yet (run projects/schema.sql).');
	}
	return ips_current_user();
}

function ips_projects_ready() {
	global $db;
	return $db->query("SHOW TABLES LIKE 'projects'")->size() > 0 && $db->query("SHOW TABLES LIKE 'project_files'")->size() > 0;
}

// Text from a form field: one line of plain text, whitespace collapsed, at most $max characters, or null when it is not a string or too long.
function ips_projects_text($value, $max) {
	if (!is_string($value)) {
		return null;
	}
	$value = trim(preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', $value)));
	return $value === '' || mb_strlen($value, 'UTF-8') <= $max ? $value : null;
}

// Notes: several lines allowed (line endings made "\n", trailing space trimmed), at most $max characters, or null.
function ips_projects_long_text($value, $max) {
	if (!is_string($value)) {
		return null;
	}
	$value = trim(preg_replace("/[ \t]+\n/", "\n", str_replace(["\r\n", "\r"], "\n", $value)));
	return mb_strlen($value, 'UTF-8') <= $max ? $value : null;
}

function ips_projects_row($r) {
	return ['id' => (int) $r['id'], 'title' => $r['title'], 'kind' => $r['kind'], 'status' => $r['status'], 'notes' => (string) $r['notes'],
		'created_by' => $r['created_by'], 'created_at' => $r['created_at'], 'updated_by' => $r['updated_by'], 'updated_at' => $r['updated_at'],
		'files' => isset($r['files']) ? (int) $r['files'] : null];
}

function ips_projects_get($id) {
	global $db;
	$rows = $db->query("SELECT p.*, (SELECT COUNT(*) FROM project_files f WHERE f.project_id = p.id) AS files FROM projects p WHERE p.id=? LIMIT 1", [['type' => 'i', 'value' => $id]])->fetchArray();
	return $rows ? ips_projects_row($rows[0]) : null;
}

// Every project, active ones first, each with its number of files.
function ips_projects_all() {
	global $db;
	$out = [];
	$result = $db->query("SELECT p.*, (SELECT COUNT(*) FROM project_files f WHERE f.project_id = p.id) AS files FROM projects p ORDER BY p.status = 'archived', p.title, p.id");
	while ($r = $result->fetch()) {
		$out[] = ips_projects_row($r);
	}
	return $out;
}

function ips_projects_options($list) {
	$out = [];
	foreach ($list as $id => $label) {
		$out[] = ['id' => $id, 'label' => $label];
	}
	return $out;
}

// ---- files ----------------------------------------------------------------------------------------------------------------------------------

// A "files/A/b.png" path from the browser: the file's name as the catalogue keeps it ("A/b.png"), its real path and its folder's real path, or a string with the
// problem. The file must exist inside upload/files/ (links out of it are refused by realpath), no segment may be hidden or a thumbnail folder, and with $write a
// regular user must have the file inside files/<their username>.
function ips_projects_resolve($path, $user, $admin, $write) {
	if (!is_string($path) || strncmp($path, 'files/', 6) !== 0 || strpbrk($path, "\\\0") !== false) {
		return 'That is not a file in the file store.';
	}
	$rel = substr($path, 6);
	foreach (explode('/', $rel) as $segment) {
		if ($segment === '' || $segment[0] === '.' || preg_match(IPS_VERSION_DIR, $segment)) {
			return 'That is not a file in the file store.';
		}
	}
	$root = ips_files_root();
	$real = $root === false ? false : realpath($root . '/' . $rel);
	if ($real === false || !is_file($real) || strpos($real, $root . '/') !== 0) {
		return 'That file is not in the file store.';
	}
	$dir = dirname($real);
	if ($write && !$admin && !ips_in_users_tree($dir, $user)) {
		return 'You can only link files inside files/' . $user . '.';
	}
	return ['rel' => $rel, 'real' => $real, 'dir' => $dir];
}

function ips_projects_mime($real, $rel) {
	$type = function_exists('mime_content_type') ? @mime_content_type($real) : false;
	return is_string($type) && $type !== '' ? $type : 'application/octet-stream';
}

// The id of the file's catalogue row (the files table, named without "files/"), or null. With $create a file that has no row yet gets one, as an upload would
// make it, and its folder's row (the catalogue groups files by folder row).
function ips_projects_file_id($found, $create) {
	global $db;
	$rel = $found['rel'];
	$rows = $db->query("SELECT id FROM files WHERE name=? LIMIT 1", [['type' => 's', 'value' => $rel]])->fetchArray();
	if ($rows) {
		return (int) $rows[0]['id'];
	}
	if (!$create) {
		return null;
	}
	$id = $db->query("INSERT INTO files (name, size, type, url, title, description) VALUES (?, ?, ?, ?, '', '')", [
		['type' => 's', 'value' => $rel], ['type' => 'i', 'value' => (int) filesize($found['real'])], ['type' => 's', 'value' => ips_projects_mime($found['real'], $rel)],
		['type' => 's', 'value' => USER_FILES_URL . $rel],
	])->getId();
	$folder = dirname($rel);
	if ($folder !== '.' && $id) {
		$f = $db->query("SELECT folder_id FROM folders WHERE folder_name=? LIMIT 1", [['type' => 's', 'value' => $folder]])->fetchArray();
		$folder_id = $f ? (int) $f[0]['folder_id'] : $db->query("INSERT INTO folders (folder_name) VALUES (?)", [['type' => 's', 'value' => $folder]])->getId();
		$db->query("UPDATE files SET folder_id=? WHERE id=?", [['type' => 'i', 'value' => $folder_id], ['type' => 'i', 'value' => $id]]);
	}
	return (int) $id;
}

// ---- links ----------------------------------------------------------------------------------------------------------------------------------

function ips_projects_link_row($r) {
	$real = ips_files_root() === false ? false : realpath(ips_files_root() . '/' . $r['name']);
	return ['file_id' => (int) $r['file_id'], 'path' => 'files/' . $r['name'], 'name' => basename($r['name']), 'title' => (string) $r['title'], 'role' => $r['role'],
		'position' => (int) $r['position'], 'label' => $r['label'], 'note' => $r['note'], 'linked_by' => $r['linked_by'], 'missing' => $real === false || !is_file($real)];
}

// A project's files in its order (position, then the order they were linked in).
function ips_projects_files($project_id) {
	global $db;
	$out = [];
	$result = $db->query("SELECT l.file_id, l.role, l.position, l.label, l.note, l.linked_by, f.name, f.title FROM project_files l JOIN files f ON f.id = l.file_id
		WHERE l.project_id=? ORDER BY l.position, l.file_id", [['type' => 'i', 'value' => $project_id]]);
	while ($r = $result->fetch()) {
		$out[] = ips_projects_link_row($r);
	}
	return $out;
}

function ips_projects_link_get($project_id, $file_id) {
	global $db;
	$rows = $db->query("SELECT l.file_id, l.role, l.position, l.label, l.note, l.linked_by, f.name, f.title FROM project_files l JOIN files f ON f.id = l.file_id
		WHERE l.project_id=? AND l.file_id=? LIMIT 1", [['type' => 'i', 'value' => $project_id], ['type' => 'i', 'value' => $file_id]])->fetchArray();
	return $rows ? ips_projects_link_row($rows[0]) : null;
}

// Links the file at the end of the project, or, if it is linked already, changes its role (its position, label and note stay).
function ips_projects_link($project_id, $file_id, $role, $by) {
	global $db;
	$next = $db->query("SELECT COALESCE(MAX(position), 0) + 1 AS p FROM project_files WHERE project_id=?", [['type' => 'i', 'value' => $project_id]])->fetchArray();
	$db->query("INSERT INTO project_files (project_id, file_id, role, position, linked_by) VALUES (?, ?, ?, ?, ?)
		ON DUPLICATE KEY UPDATE role = VALUES(role)", [
		['type' => 'i', 'value' => $project_id], ['type' => 'i', 'value' => $file_id], ['type' => 's', 'value' => $role],
		['type' => 'i', 'value' => (int) $next[0]['p']], ['type' => 's', 'value' => $by],
	]);
}

// May this user change or remove an existing link: an administrator, whoever linked it, or someone who may write where the file is.
function ips_projects_may_change($link, $user, $admin) {
	if ($admin || $link['linked_by'] === $user) {
		return true;
	}
	$found = ips_projects_resolve($link['path'], $user, $admin, true);
	return is_array($found);
}

// Moves a link one place up or down (the positions of the whole project are renumbered 1..n first, so equal or gapped positions cannot trap it).
function ips_projects_move($project_id, $file_id, $direction) {
	global $db;
	$ids = [];
	$result = $db->query("SELECT file_id FROM project_files WHERE project_id=? ORDER BY position, file_id", [['type' => 'i', 'value' => $project_id]]);
	while ($r = $result->fetch()) {
		$ids[] = (int) $r['file_id'];
	}
	$at = array_search($file_id, $ids, true);
	$to = $direction === 'up' ? $at - 1 : $at + 1;
	if ($at === false || $to < 0 || $to >= count($ids)) {
		return false;
	}
	[$ids[$at], $ids[$to]] = [$ids[$to], $ids[$at]];
	foreach ($ids as $i => $id) {
		$db->query("UPDATE project_files SET position=? WHERE project_id=? AND file_id=?", [['type' => 'i', 'value' => $i + 1], ['type' => 'i', 'value' => $project_id], ['type' => 'i', 'value' => $id]]);
	}
	return true;
}

// The projects a file is in, with the link's own details.
function ips_projects_of_file($file_id) {
	global $db;
	$out = [];
	$result = $db->query("SELECT p.id, p.title, p.kind, p.status, l.role, l.position, l.label, l.note FROM project_files l JOIN projects p ON p.id = l.project_id
		WHERE l.file_id=? ORDER BY p.status = 'archived', p.title, p.id", [['type' => 'i', 'value' => $file_id]]);
	while ($r = $result->fetch()) {
		$out[] = ['project_id' => (int) $r['id'], 'title' => $r['title'], 'kind' => $r['kind'], 'status' => $r['status'], 'role' => $r['role'],
			'position' => (int) $r['position'], 'label' => $r['label'], 'note' => $r['note']];
	}
	return $out;
}
