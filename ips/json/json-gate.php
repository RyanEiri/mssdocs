<?php
// The access check behind nginx's auth_request for everything under /ips/upload/files/ and /ips/upload/archives/ (the site's nginx asks here, then serves the file itself).
// Answers 204 (let nginx serve it) or 403; nginx maps any other status to a 500, so a path that does not exist is a 204 for a signed-in visitor (nginx then
// answers 404) and a 403 for anyone else. A file is allowed when it is in a public folder (a folder with the marker file, or below one: see ips_dir_is_public)
// or the visitor is signed in; the archives (uploaded zips) are never public, only for a signed-in visitor. It reads only the request's path and never writes. The answer carries X-Gate-Cache-Control, which nginx sends as the file's
// Cache-Control: a public file may be cached anywhere; a file served to a signed-in visitor is `private` (no CDN or shared cache may keep it, or one would hand it
// to the next visitor) but the visitor's own browser may keep it for a day, so the scan pane's page images are not fetched again on every visit; a refusal is
// never stored.
chdir('..');
include getcwd().'/php/boot.php';

const GATE_PUBLIC = 'public, max-age=3600';
const GATE_SIGNED_IN = 'private, max-age=86400';
const GATE_REFUSED = 'private, no-store';

function gate_answer($status, $cache) {
	http_response_code($status);
	header('X-Gate-Cache-Control: '.$cache);
	exit;
}

// The area (files or archives) and the path the visitor asked for, relative to it (nginx's own IPS_ORIGINAL_URI parameter: a client cannot set it the way it can
// a header); null when it is under neither.
function gate_requested_path() {
	$uri = $_SERVER['IPS_ORIGINAL_URI'] ?? '';
	$path = is_string($uri) ? parse_url($uri, PHP_URL_PATH) : null;
	if (!is_string($path)) {
		return null;
	}
	// The marker that starts the path decides the area: a name inside an archive path that merely contains another marker must not change it.
	$found = null;
	foreach (['files' => ['/upload/files/'], 'archives' => ['/upload/archives/']] as $area => $markers) {
		foreach ($markers as $marker) {
			$at = strpos($path, $marker);
			if ($at !== false && ($found === null || $at < $found[0])) {
				$found = [$at, $area, $marker];
			}
		}
	}
	if ($found === null) {
		return null;
	}
	$rel = rawurldecode(substr($path, $found[0] + strlen($found[2])));
	return ($rel === '' || strpos($rel, "\0") !== false) ? null : [$found[1], $rel];
}

$asked = gate_requested_path();
$root = $asked === null ? false : realpath(ips_upload_base().$asked[0]);
if ($asked === null || $root === false) {
	gate_answer(403, GATE_REFUSED);
}
$rel = $asked[1];
// No hidden entry is ever served (the marker, .deleted/, backups), whatever the file system says.
foreach (explode('/', $rel) as $part) {
	if ($part !== '' && $part[0] === '.') {
		gate_answer(403, GATE_REFUSED);
	}
}
$real = realpath($root.'/'.$rel);
if ($real !== false && strpos($real.'/', $root.'/') !== 0) {
	gate_answer(403, GATE_REFUSED); // a link out of upload/files/
}
$folder = $real === false ? false : (is_dir($real) ? $real : dirname($real));
if ($asked[0] === 'files' && $folder !== false && ips_dir_is_public($folder)) {
	gate_answer(204, GATE_PUBLIC);
}
// Peek(), not CheckIt(): CheckIt() redirects a visitor who is not signed in to the login page, and nginx cannot take a redirect as an answer.
$login_cookie = new UserCookie();
if ($login_cookie->Peek() !== false) {
	gate_answer(204, GATE_SIGNED_IN);
}
gate_answer(403, GATE_REFUSED);
