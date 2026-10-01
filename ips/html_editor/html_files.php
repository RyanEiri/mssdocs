<?php
/*
 * Access to the files the HTML editor works on: existing *.html files under upload/files/, and nothing else.
 *
 * $rel is a path from the browser, relative to upload/ (files/html_templates/Resources.html) or in the
 * ../upload/files/... form the file browser uses; realpath() resolves both, symlinks included, and the result
 * must be inside upload/files/.
 *
 * These helpers live in html_editor/ rather than upload/ because nginx refuses every .php under /ips/upload/.
 */
function ips_html_path($rel) {
	if (!is_string($rel) || $rel === '' || strpos($rel, "\0") !== false) {
		return false;
	}
	$root = ips_files_root();
	$path = realpath(ips_upload_base() . $rel);
	if ($root === false || $path === false || strpos($path, $root . DIRECTORY_SEPARATOR) !== 0) {
		return false;
	}
	if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'html' || !is_file($path)) {
		return false;
	}
	return $path;
}

function ips_html_read($rel) {
	$path = ips_html_path($rel);
	return $path === false ? '' : (string)file_get_contents($path);
}
