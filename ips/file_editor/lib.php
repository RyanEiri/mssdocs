<?php
/* The file editor module (ips/file_editor/): which files the editor may list, open, create, save and delete, and for whom. Every request's path becomes a file
 * here, through ips_fe_resolve(), and nowhere else.
 *
 * File types are modules. Each file in types/ (html.php, xml.php, ...) describes one type of file (types/html.php says what a descriptor holds) and the editor offers
 * the types that are loaded: a site adds a type by dropping a file in, removes one by deleting its file, and may narrow the list with IPS_FILE_EDITOR_TYPES (a
 * comma-separated list of type ids). Each type has its own templates folder under upload/files/ (html_templates/, xml_templates/, ...) and a folder holds one type.
 *
 * Who may do what ($scope, from ips_fe_scope()): an administrator anything; a regular user works in their own folder of each templates folder
 * (html_templates/<username>/, ...: made on first use) and READS the files at the top of the folder (the shared ones) but not another user's folder, which is not even
 * listed. A site whose editors all share the pages (a bibliography kept by a team) sets IPS_FILE_EDITOR_SHARED_WRITE to true in php/site-config.php: then every
 * signed-in user may also save and delete the files at the top of the folder. Nothing under a dot-folder (.deleted/) is ever listed or opened. The web server serves
 * these files sandboxed (no script), see the nginx notes in the module's README.
 */

// The loaded file types: [id => descriptor], read from types/*.php (sorted by file name). A descriptor that is malformed, or that claims an id, extension or folder
// another has already taken, is ignored, so one bad module cannot take the others down.
function ips_fe_registry() {
	static $registry = null;
	if ($registry !== null) {
		return $registry;
	}
	$registry = [];
	$extensions = [];
	$folders = [];
	$files = glob(__DIR__ . '/types/*.php') ?: [];
	sort($files);
	foreach ($files as $file) {
		$d = require $file;
		if (!is_array($d) || !isset($d['id'], $d['label'], $d['ext'], $d['folder'], $d['skeleton']) || !is_callable($d['skeleton'])) {
			continue;
		}
		$ext = array_values(array_filter((array) $d['ext'], function ($e) { return is_string($e) && preg_match('/^[a-z0-9]+$/', $e); }));
		if (!preg_match('/^[a-z][a-z0-9]*$/', (string) $d['id']) || !preg_match('/^[A-Za-z0-9_-]+$/', (string) $d['folder']) || !$ext
			|| isset($registry[$d['id']]) || isset($folders[$d['folder']]) || array_intersect($ext, array_keys($extensions))) {
			continue;
		}
		$d['ext'] = $ext;
		$d += ['group' => $d['label'] . ' files', 'blurb' => '', 'mode' => 'htmlmixed', 'validate' => null, 'js' => null];
		$registry[$d['id']] = $d;
		$folders[$d['folder']] = $d['id'];
		foreach ($ext as $e) {
			$extensions[$e] = $d['id'];
		}
	}
	return $registry;
}

// The ids of the file types this site offers: the loaded ones, narrowed by IPS_FILE_EDITOR_TYPES when the site defines it.
function ips_fe_types() {
	$ids = array_keys(ips_fe_registry());
	if (defined('IPS_FILE_EDITOR_TYPES')) {
		$wanted = array_map('trim', explode(',', strtolower((string) IPS_FILE_EDITOR_TYPES)));
		$ids = array_values(array_intersect($ids, $wanted));
	}
	return $ids;
}

// The descriptor of an offered type, or null.
function ips_fe_type($id) {
	return is_string($id) && in_array($id, ips_fe_types(), true) ? ips_fe_registry()[$id] : null;
}

// What the pages and scripts need to know about each offered type (no functions): id, label, group, ext, blurb, mode, and the URL (relative to the module) of its script.
function ips_fe_public_types() {
	$out = [];
	foreach (ips_fe_types() as $id) {
		$d = ips_fe_registry()[$id];
		$out[] = ['id' => $id, 'label' => $d['label'], 'group' => $d['group'], 'ext' => $d['ext'], 'blurb' => $d['blurb'], 'mode' => $d['mode'],
			'js' => $d['js'] && is_file(__DIR__ . '/types/' . basename($d['js'])) ? 'types/' . basename($d['js']) : null];
	}
	return $out;
}

// "html_templates/ann or xml_templates/ann": where this user's own folders are, for the messages.
function ips_fe_own_folders_text($username) {
	return implode(' or ', array_map(function ($rel) use ($username) { return basename($rel) . '/' . $username; }, ips_fe_kinds())) ?: 'your own folder';
}

// Whether every signed-in user may change the shared files at the top of a templates folder (the site's choice, see above).
function ips_fe_shared_write() {
	return defined('IPS_FILE_EDITOR_SHARED_WRITE') && IPS_FILE_EDITOR_SHARED_WRITE === true;
}

function ips_fe_is_admin($username) {
	return is_string($username) && $username !== '' && !empty((new UserGrab($username))->admin);
}

// '' for an administrator (all of it), the user's own folder name for anyone else (a plain folder name: no leading dot, no slash), null when there is none.
function ips_fe_scope($username) {
	if (!is_string($username) || $username === '') {
		return null;
	}
	if (ips_fe_is_admin($username)) {
		return '';
	}
	return preg_match('/^[^.\/\\\\\0][^\/\\\\\0]{0,99}$/', $username) ? $username : null;
}

// [type id => path of its templates folder under ips/] for the offered types.
function ips_fe_kinds() {
	$out = [];
	foreach (ips_fe_types() as $id) {
		$out[$id] = 'upload/files/' . ips_fe_registry()[$id]['folder'];
	}
	return $out;
}

// [type id => real directory] of the templates folders that exist.
function ips_fe_roots() {
	$roots = [];
	foreach (ips_fe_kinds() as $kind => $rel) {
		$real = realpath(PROGRAM_BASE . $rel);
		if ($real !== false && is_dir($real)) {
			$roots[$kind] = $real;
		}
	}
	return $roots;
}

// The offered type that owns the file's extension, or null.
function ips_fe_kind_of($path) {
	$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
	foreach (ips_fe_types() as $id) {
		if (in_array($ext, ips_fe_registry()[$id]['ext'], true)) {
			return $id;
		}
	}
	return null;
}

// Whether the user with $scope may see $real inside the templates folder $root: nothing under a dot-folder; a regular user sees the top of the folder and
// their own folder, not another user's.
function ips_fe_visible($real, $root, $scope) {
	$inside = substr($real, strlen($root) + 1);
	if (preg_match('#(^|/)\.#', $inside)) {
		return false;
	}
	if ($scope !== '') {
		$dir = dirname($inside);
		return $dir === '.' || ($scope !== null && ($dir === $scope || strpos($dir . '/', $scope . '/') === 0));
	}
	return true;
}

// Whether the user with $scope may save or delete $real (a file in a templates folder): an administrator any; anyone else their own folder, and, where the site
// shares its pages, the files at the top of the folder; nobody without a scope.
function ips_fe_may_write($real, $scope) {
	if ($scope === '') {
		return true;
	}
	if ($scope === null) {
		return false;
	}
	foreach (ips_fe_roots() as $root) {
		if (strpos($real, $root . '/') !== 0) {
			continue;
		}
		if (ips_fe_shared_write() && dirname($real) === $root) {
			return true;
		}
		$own = realpath($root . '/' . $scope);
		if ($own !== false && strpos($own, $root . '/') === 0 && strpos($real, $own . '/') === 0) {
			return true;
		}
	}
	return false;
}

// The real path of the existing editable file $given names ("upload/files/html_templates/x.html", a leading "../" or "/ips/" is ignored), or false.
// realpath() resolves symlinks and "..", so nothing outside the templates folders can be reached.
function ips_fe_resolve($given, $scope) {
	if (!is_string($given) || $given === '' || strpos($given, "\0") !== false) {
		return false;
	}
	$rel = preg_replace('#^(?:\.\./|/ips/)#', '', $given);
	if ($rel === '' || $rel[0] === '/') {
		return false;
	}
	$real = realpath(PROGRAM_BASE . $rel);
	if ($real === false || !is_file($real)) {
		return false;
	}
	$kind = ips_fe_kind_of($real);
	$roots = ips_fe_roots();
	if ($kind === null || !isset($roots[$kind])) {
		return false;
	}
	if (strpos($real, $roots[$kind] . '/') === 0 && ips_fe_visible($real, $roots[$kind], $scope)) {
		return $real;
	}
	return false;
}

// The name the editor's links and forms use for a real path: relative to ips/ ("upload/files/html_templates/x.html").
function ips_fe_name($real) {
	return ltrim(substr($real, strlen(rtrim(realpath(PROGRAM_BASE), '/'))), '/');
}

// The files and the folders holding them, for the file list: one group per kind, each item with whether this user may change/delete it. Symlinks are not
// followed; a regular user is not shown another user's folder.
function ips_fe_tree($scope) {
	$tree = [];
	foreach (ips_fe_roots() as $kind => $root) {
		$exts = ips_fe_registry()[$kind]['ext'];
		$scan = function ($dir, $top) use (&$scan, $kind, $exts, $scope) {
			$items = [];
			$names = scandir($dir);
			natcasesort($names);
			foreach ($names as $f) {
				if ($f === '' || $f[0] === '.' || is_link("$dir/$f")) {
					continue;
				}
				if (is_dir("$dir/$f")) {
					if ($f !== 'thumbnail' && !($top && $scope !== '' && $f !== $scope)) {
						$items[] = ['name' => $f, 'type' => 'folder', 'items' => $scan("$dir/$f", false)];
					}
				} elseif (in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), $exts, true)) {
					$real = realpath("$dir/$f");
					$may = ips_fe_may_write($real, $scope);
					$items[] = [
						'name' => $f, 'type' => 'file', 'kind' => $kind, 'path' => ips_fe_name($real), 'size' => filesize("$dir/$f"),
						'writable' => is_writable("$dir/$f") && $may,
						'deletable' => is_writable($dir) && $may,
					];
				}
			}
			return $items;
		};
		$tree[] = ['name' => basename($root), 'kind' => $kind, 'label' => ips_fe_registry()[$kind]['group'], 'items' => $scan($root, true)];
	}
	return $tree;
}

// The folder new files of $kind are created in (made on first use): the whole templates folder for an administrator, the user's own folder in it for anyone
// else. False when there is no scope, it cannot be made, or it ends up outside the templates folder (a link).
function ips_fe_new_dir($kind, $scope) {
	$kinds = ips_fe_kinds();
	if (!is_string($scope) || !isset($kinds[$kind])) {
		return false;
	}
	$dir = PROGRAM_BASE . $kinds[$kind] . ($scope === '' ? '' : '/' . $scope);
	if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
		return false;
	}
	$real = realpath($dir);
	$top = realpath(PROGRAM_BASE . $kinds[$kind]);
	if ($real === false || $top === false || ($real !== $top && strpos($real, $top . '/') !== 0)) {
		return false;
	}
	return $real;
}

// "My page", "my page.html" -> "My page.html" (the type's first extension); false for a name that is empty, too long, or has anything but letters, digits, spaces,
// "_" and "-", or for a type this site does not offer.
function ips_fe_new_filename($given, $kind) {
	$type = ips_fe_type($kind);
	if (!is_string($given) || $type === null) {
		return false;
	}
	$base = trim($given);
	foreach ($type['ext'] as $ext) {
		$base = preg_replace('/\.' . preg_quote($ext, '/') . '$/i', '', $base);
	}
	$base = trim($base);
	if (!preg_match('/^[\p{L}\p{N}][\p{L}\p{N} _-]{0,99}$/u', $base)) {
		return false;
	}
	return $base . '.' . $type['ext'][0];
}

// The text a new file of the type starts with.
function ips_fe_skeleton($kind, $title) {
	$type = ips_fe_type($kind);
	return $type === null ? '' : (string) call_user_func($type['skeleton'], $title);
}

// null when the text may be saved as a file of the type, else the reason it may not (a type without a validator accepts anything).
function ips_fe_validate($kind, $text) {
	$type = ips_fe_type($kind);
	if ($type === null) {
		return 'that kind of file is not offered here';
	}
	return is_callable($type['validate']) ? call_user_func($type['validate'], $text) : null;
}

// Deletes nothing for good: moves the file into <templates folder>/.deleted/ under a name with the time. True when it moved.
function ips_fe_trash($real) {
	foreach (ips_fe_roots() as $root) {
		if (strpos($real, $root . '/') !== 0) {
			continue;
		}
		$dir = $root . '/.deleted';
		if (!is_dir($dir) && !@mkdir($dir, 0775) && !is_dir($dir)) {
			return false;
		}
		return @rename($real, $dir . '/' . date('Ymd-His') . '-' . str_replace('/', '__', substr($real, strlen($root) + 1)));
	}
	return false;
}
