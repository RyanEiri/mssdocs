<?php
/* What the Describe endpoints share (the pure part is lib.php). Every endpoint starts: chdir('..'); require start_sess.php, boot.php, this file; then calls ips_describe_user().
 *
 * Who: anyone signed in may read; every write is an administrator's (the cataloguers are administrators), a same-origin POST with the user's CSRF token. Where: a records folder under
 * upload/files/ (never the files root, never a hidden or thumbnail folder), resolved with the file browser's own helpers. What a record may name as a photograph: a file inside upload/files/
 * that exists (an address the record already held stays allowed when the file is gone, so an old record can still be saved). What is never done: a photograph is never opened or written here.
 * "No such record" is a 400, not a 404: nginx replaces the body of a PHP 404 with its own page and the form would lose the reason. */
require_once __DIR__ . '/lib.php';

const IPS_DESCRIBE_IMAGES = '/\.(jpe?g|png|gif|webp)$/i';

function ips_describe_refuse($status, $message, $extra = []) {
	http_response_code($status);
	echo json_encode(['success' => false, 'error' => $message] + $extra, JSON_UNESCAPED_UNICODE);
	exit;
}

// The signed-in user for an endpoint ([name, is_admin]); a write also needs the token and an administrator.
function ips_describe_user($write) {
	global $login_cookie;
	header('Content-Type: application/json');
	header('Cache-Control: no-store');
	$login_cookie = new UserCookie();
	if ($login_cookie->Peek() === false) {
		ips_describe_refuse(403, 'Sign in to describe books.');
	}
	if ($write) {
		$login_cookie->RequireCsrf();
	}
	[$user, $admin] = ips_current_user();
	if ($write && !$admin) {
		ips_describe_refuse(403, 'Only administrators change a record.');
	}
	return [$user, $admin];
}

// The real path of a records folder named by `dir` (files/..., relative to upload/), or a refusal.
function ips_describe_folder($dir) {
	$real = is_string($dir) ? ips_resolve_dir($dir, ips_upload_base()) : false;
	if ($real === false || $real === ips_files_root()) {
		ips_describe_refuse(400, 'Choose a records folder inside upload/files/.');
	}
	foreach (explode('/', substr($real, strlen(ips_files_root()) + 1)) as $part) {
		if ($part === '' || $part[0] === '.' || preg_match(IPS_VERSION_DIR, $part)) {
			ips_describe_refuse(400, 'That folder cannot hold records.');
		}
	}
	return $real;
}

// The file of record $id in the folder, or a refusal: a plain id, an existing xml file that is not a link.
function ips_describe_record_file($folder, $id) {
	if (!is_string($id) || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,80}$/', $id)) {
		ips_describe_refuse(400, 'That is not a record id.');
	}
	$path = $folder . '/' . $id . '.xml';
	if (!is_file($path) || is_link($path)) {
		ips_describe_refuse(400, 'There is no record ' . $id . ' in that folder.');
	}
	return $path;
}

// The records in the folder: [id => path], in natural order; hidden files and links are not records.
function ips_describe_record_files($folder) {
	$out = [];
	foreach (@scandir($folder) ?: [] as $name) {
		if ($name[0] !== '.' && preg_match('/^(.+)\.xml$/', $name, $m) && is_file($folder . '/' . $name) && !is_link($folder . '/' . $name)) {
			$out[$m[1]] = $folder . '/' . $name;
		}
	}
	uksort($out, 'strnatcasecmp');
	return $out;
}

// The folder's settings (.describe.json, written by tools/books-to-tei.py): {"photographs": "<folder relative to the records folder>"}.
function ips_describe_settings($folder) {
	$data = is_file($folder . '/.describe.json') ? json_decode((string) @file_get_contents($folder . '/.describe.json'), true) : null;
	return is_array($data) && isset($data['photographs']) && is_string($data['photographs']) ? ['photographs' => $data['photographs']] : ['photographs' => ''];
}

// The photographs' folder (real path) when the settings name one that is a folder inside the store, else null.
function ips_describe_photo_dir($folder) {
	$rel = ips_describe_settings($folder)['photographs'];
	$root = ips_files_root();
	$real = $rel === '' || !ips_describe_plain_address($rel) ? false : realpath($folder . '/' . $rel);
	return ($real !== false && is_dir($real) && strpos($real . '/', $root . '/') === 0 && $real !== $root) ? $real : null;
}

// The real path of a photograph address (as a record holds it, relative to the records folder) when it is an image file inside the store, else null.
function ips_describe_resolve_photo($folder, $address) {
	if (!ips_describe_plain_address($address) || !preg_match(IPS_DESCRIBE_IMAGES, $address)) {
		return null;
	}
	$root = ips_files_root();
	$real = realpath($folder . '/' . $address);
	if ($real === false || !is_file($real) || strpos($real, $root . '/') !== 0) {
		return null;
	}
	foreach (explode('/', substr($real, strlen($root) + 1)) as $part) {
		if ($part[0] === '.' || preg_match(IPS_VERSION_DIR, $part)) {
			return null;
		}
	}
	return $real;
}

// The address of a file as a record in $folder would hold it: relative to the folder, climbing with ../ where the photographs are in a sibling tree.
function ips_describe_address($folder, $real) {
	$from = explode('/', trim($folder, '/'));
	$to = explode('/', trim($real, '/'));
	while ($from && $to && $from[0] === $to[0] && count($to) > 1) {
		array_shift($from);
		array_shift($to);
	}
	return str_repeat('../', count($from)) . implode('/', $to);
}

// The address a browser uses for a file of the store, optionally of one of its smaller copies when that exists: ["thumbnail-480"].
function ips_describe_url($real, $size = null) {
	$rel = substr($real, strlen(ips_files_root()) + 1);
	if ($size !== null) {
		$copy = dirname($real) . '/' . $size . '/' . basename($real);
		if (is_file($copy)) {
			$rel = substr($copy, strlen(ips_files_root()) + 1);
		}
	}
	return (parse_url(USER_FILES_URL, PHP_URL_PATH) ?: '/ips/upload/files/') . implode('/', array_map('rawurlencode', explode('/', $rel)));   // root-relative: no scheme or host to go wrong behind a proxy
}

// What a page needs to draw one photograph of a record: its address, role, the browser's addresses of its small and large copies and its recorded turn; missing = the file is gone.
function ips_describe_photo_view($folder, $address, $role = '') {
	$real = ips_describe_resolve_photo($folder, $address);
	return ['path' => $address, 'role' => $role, 'name' => basename($address), 'missing' => $real === null,
		'thumb' => $real ? ips_describe_url($real, 'thumbnail-480') : '', 'full' => $real ? ips_describe_url($real, 'thumbnail-2048') : '',
		'rotation' => $real && function_exists('ips_rotation_of') ? ips_rotation_of(dirname($real), basename($real)) : 0];
}

// The photographs in the photographs' folder that no record of the folder uses: [photo view, ...] in natural order.
function ips_describe_tray($folder, $used_reals) {
	$dir = ips_describe_photo_dir($folder);
	$out = [];
	if ($dir === null) {
		return $out;
	}
	$names = @scandir($dir) ?: [];
	usort($names, 'strnatcasecmp');
	foreach ($names as $name) {
		if ($name[0] === '.' || !preg_match(IPS_DESCRIBE_IMAGES, $name) || !is_file($dir . '/' . $name) || is_link($dir . '/' . $name) || in_array(realpath($dir . '/' . $name), $used_reals, true)) {
			continue;
		}
		$out[] = ips_describe_photo_view($folder, ips_describe_address($folder, $dir . '/' . $name));
	}
	return $out;
}

// Which record of the folder holds a photograph (by the real file, whatever address names it): [real path => record id]. A photograph belongs to one book.
function ips_describe_holders($folder) {
	$holders = [];
	foreach (ips_describe_record_files($folder) as $id => $path) {
		[$s] = ips_describe_read((string) file_get_contents($path));
		foreach ($s['photos'] ?? [] as $p) {
			$real = ips_describe_resolve_photo($folder, $p['path']);
			if ($real !== null) {
				$holders[$real] = $id;
			}
		}
	}
	return $holders;
}

// The real paths of every photograph the records of the folder use (those that still exist).
function ips_describe_used($folder) {
	$used = [];
	foreach (ips_describe_record_files($folder) as $path) {
		[$s] = ips_describe_read((string) file_get_contents($path));
		foreach ($s['photos'] ?? [] as $p) {
			$real = ips_describe_resolve_photo($folder, $p['path']);
			if ($real !== null) {
				$used[] = $real;
			}
		}
	}
	return $used;
}

// The Dublin Core, RAD and completeness for a record's text, when PHP has what they need; [] otherwise.
function ips_describe_preview($xml) {
	require_once dirname(__DIR__) . '/file_editor/metadata/lib.php';
	if (!function_exists('ips_fe_metadata_run')) {
		return [];
	}
	$run = ips_fe_metadata_run($xml);
	return empty($run['available']) ? [] : ['dc' => $run['dc'], 'rad' => $run['rad'], 'completeness' => $run['completeness']];
}

// A record as a page needs it: the structure, the open questions with their words, the etag, the photographs drawn, the decisions' key list.
function ips_describe_view($folder, $id, $text) {
	[$struct, $problem] = ips_describe_read($text);
	if ($struct === null) {
		return ['id' => $id, 'error' => $problem];
	}
	$questions = array_map(function ($q) { return ['key' => $q[0], 'text' => $q[1]]; }, ips_describe_questions($struct));
	return ['id' => $id, 'struct' => $struct, 'questions' => $questions, 'open' => array_column($questions, 'key'), 'etag' => hash('sha256', $text),
		'photos' => array_map(function ($p) use ($folder) { return ips_describe_photo_view($folder, $p['path'], $p['role']); }, $struct['photos'])];
}

// One lock for every writer of a records folder (the lock file is hidden, so it is never listed or served).
function ips_describe_lock($folder) {
	$handle = @fopen($folder . '/.describe.lock', 'c');
	if ($handle === false || !flock($handle, LOCK_EX)) {
		ips_describe_refuse(500, 'The records folder could not be locked for writing (is it writable?).');
	}
	return $handle;
}

function ips_describe_unlock($handle) {
	flock($handle, LOCK_UN);
	fclose($handle);
}

/* Changes one record under the caller's lock. $etag (or null to skip the check) must still be the file's; $mutate($struct) returns the structure to save ($input form) or a string reason.
 * The input is checked and applied (lib.php), a photograph address that is new must resolve inside the store, the old file is copied to .history/, the new text must read again as a
 * record, and the write is atomic. Returns ['ok' => true, 'changed', 'view', 'backup'] or ['ok' => false, 'status', 'error', 'extra']. Nothing is written when nothing changed. */
function ips_describe_update($folder, $id, $etag, $mutate, $user, $leaving_from = null) {
	$path = $folder . '/' . $id . '.xml';
	$text = @file_get_contents($path);
	if ($text === false) {
		return ['ok' => false, 'status' => 400, 'error' => 'There is no record ' . $id . ' in that folder.', 'extra' => []];
	}
	if ($etag !== null && !hash_equals(hash('sha256', $text), (string) $etag)) {
		return ['ok' => false, 'status' => 409, 'error' => 'Someone else changed this record since you opened it. Reload it to see their version.', 'extra' => ['current_etag' => hash('sha256', $text)]];
	}
	[$current, $problem] = ips_describe_read($text);
	if ($current === null) {
		return ['ok' => false, 'status' => 422, 'error' => $problem, 'extra' => []];
	}
	$wanted = $mutate($current);
	if (is_string($wanted)) {
		return ['ok' => false, 'status' => 422, 'error' => $wanted, 'extra' => []];
	}
	[$checked, $why] = ips_describe_check($wanted, $current);
	if ($checked === null) {
		return ['ok' => false, 'status' => 422, 'error' => $why, 'extra' => []];
	}
	$had = array_column($current['photos'], 'path');
	$holders = null;
	foreach ($checked['photos'] as $p) {
		if (in_array($p['path'], $had, true)) {
			continue;
		}
		$real = ips_describe_resolve_photo($folder, $p['path']);
		if ($real === null) {
			return ['ok' => false, 'status' => 422, 'error' => 'The photograph ' . basename($p['path']) . ' is not an image inside the file store.', 'extra' => []];
		}
		$holders = $holders ?? ips_describe_holders($folder);
		if (isset($holders[$real]) && $holders[$real] !== $id && $holders[$real] !== $leaving_from) {
			return ['ok' => false, 'status' => 422, 'error' => 'The photograph ' . basename($p['path']) . ' is already on book ' . $holders[$real] . ': move it from there.', 'extra' => []];
		}
	}
	[$out, $changed, $err] = ips_describe_apply($text, $checked, $user, gmdate('Y-m-d'));
	if ($out === null) {
		return ['ok' => false, 'status' => 422, 'error' => $err, 'extra' => []];
	}
	if (!$changed) {   // the canonical XML is the same: nothing is written (the record keeps its own comment and layout until a real change)
		return ['ok' => true, 'changed' => false, 'view' => ips_describe_view($folder, $id, $text), 'backup' => null];
	}
	if (ips_describe_read($out)[0] === null) {
		return ['ok' => false, 'status' => 500, 'error' => 'The changed record would not read back; nothing was saved.', 'extra' => []];
	}
	$backup = ips_describe_backup($path, $user);
	if ($backup === false) {
		return ['ok' => false, 'status' => 500, 'error' => 'The earlier version could not be kept in .history/, so nothing was saved (is the folder writable?).', 'extra' => []];
	}
	if (!ips_describe_write($path, $out)) {
		return ['ok' => false, 'status' => 500, 'error' => 'The record could not be written.', 'extra' => []];
	}
	return ['ok' => true, 'changed' => true, 'view' => ips_describe_view($folder, $id, $out), 'backup' => $backup];
}
