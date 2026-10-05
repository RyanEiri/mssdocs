<?php
/* The file editor module (ips/file_editor/): which files the editor may list, open, create, save and delete, and for whom. Every request's path becomes a file
 * here, through ips_fe_resolve(), and nowhere else.
 *
 * Two kinds of file, each in its own templates folder under upload/files/: *.html in html_templates/ and *.xml in xml_templates/ (a site may offer only one of the
 * kinds: IPS_FILE_EDITOR_TYPES, a comma-separated list, default "html,xml"). A folder holds one kind: XML is not opened from html_templates, nor HTML from xml_templates.
 *
 * Who may do what ($scope, from ips_fe_scope()): an administrator anything; a regular user works in their own folder of each templates folder
 * (html_templates/<username>/, xml_templates/<username>/: made on first use) and READS the files at the top of the folder (the shared ones) but not another
 * user's folder, which is not even listed. A site whose editors all share the pages (a bibliography kept by a team) sets IPS_FILE_EDITOR_SHARED_WRITE to true
 * in php/site-config.php: then every signed-in user may also save and delete the files at the top of the folder. Nothing under a dot-folder (.deleted/) is
 * ever listed or opened. The web server serves these pages sandboxed (no script), see the nginx notes in the module's README.
 */

const IPS_FE_HTML_ROOT = 'upload/files/html_templates';
const IPS_FE_XML_ROOT = 'upload/files/xml_templates';

// The kinds of file this site offers: ['html', 'xml'] by default.
function ips_fe_types() {
	$wanted = defined('IPS_FILE_EDITOR_TYPES') ? IPS_FILE_EDITOR_TYPES : 'html,xml';
	$types = array_values(array_intersect(['html', 'xml'], array_map('trim', explode(',', strtolower($wanted)))));
	return $types ?: ['html', 'xml'];
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

// ['html'|'xml' => path of its templates folder under ips/] for the kinds this site offers.
function ips_fe_kinds() {
	$all = ['html' => IPS_FE_HTML_ROOT, 'xml' => IPS_FE_XML_ROOT];
	return array_intersect_key($all, array_flip(ips_fe_types()));
}

// ['html'|'xml' => real directory] of the templates folders that exist.
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

// The folder's kind by the file's extension, or null.
function ips_fe_kind_of($path) {
	$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
	return in_array($ext, ips_fe_types(), true) ? $ext : null;
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
		$scan = function ($dir, $top) use (&$scan, $kind, $root, $scope) {
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
				} elseif (strtolower(pathinfo($f, PATHINFO_EXTENSION)) === $kind) {
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
		$tree[] = ['name' => basename($root), 'kind' => $kind, 'items' => $scan($root, true)];
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

// "My page", "my page.html" -> "My page.html"; false for a name that is empty, too long, or has anything but letters, digits, spaces, "_" and "-".
function ips_fe_new_filename($given, $kind) {
	if (!is_string($given) || !in_array($kind, ['html', 'xml'], true)) {
		return false;
	}
	$base = trim(preg_replace('/\.' . $kind . '$/i', '', trim($given)));
	if (!preg_match('/^[\p{L}\p{N}][\p{L}\p{N} _-]{0,99}$/u', $base)) {
		return false;
	}
	return $base . '.' . $kind;
}

function ips_fe_skeleton($kind, $title) {
	if ($kind === 'html') {
		$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
		return "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n  <meta charset=\"utf-8\">\n  <title>$title</title>\n</head>\n<body>\n  <p></p>\n</body>\n</html>\n";
	}
	$title = htmlspecialchars($title, ENT_XML1 | ENT_QUOTES, 'UTF-8');
	return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<TEI xmlns=\"http://www.tei-c.org/ns/1.0\">\n  <teiHeader>\n    <fileDesc>\n      <titleStmt>\n        <title>$title</title>\n"
		. "      </titleStmt>\n      <publicationStmt>\n        <p>Unpublished</p>\n      </publicationStmt>\n      <sourceDesc>\n        <p>Created in the file editor</p>\n"
		. "      </sourceDesc>\n    </fileDesc>\n  </teiHeader>\n  <text>\n    <body>\n      <p/>\n    </body>\n  </text>\n</TEI>\n";
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
