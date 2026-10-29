<?php
/* Text extraction for issues: pdfinfo and pdftotext (poppler-utils) run as fixed commands with no shell, a time limit and a size limit, and the result stored page by page. Used by the
 * background worker (php/issue_worker.php) and by the tests; it needs a database connection ($db) only in ips_issues_run_extraction().
 *
 * What was measured on real InDesign PDFs (docs/mssdocs-issues-design.md, section 9): the default pdftotext mode keeps articles in reading order (-layout would interleave the columns),
 * pages come apart at the form feed it writes, and poppler prints dozens of warnings ("Invalid Font Weight") on stderr that are not failures: only the exit status and an empty result are.
 */

if (!defined('IPS_ISSUES_MAX_PAGES')) {
	define('IPS_ISSUES_MAX_PAGES', 400);
}
if (!defined('IPS_ISSUES_EXTRACT_TIMEOUT')) {
	define('IPS_ISSUES_EXTRACT_TIMEOUT', 180); // seconds for each poppler command
}
const IPS_ISSUES_MAX_TEXT_BYTES = 134217728; // 128 MB of text from one PDF
const IPS_ISSUES_EMPTY_PAGE_CHARS = 3;       // a page with fewer letters than this has no text layer

// The path of a poppler program, or null when it is not installed.
function ips_issues_bin($name) {
	foreach (['/usr/bin/', '/usr/local/bin/'] as $dir) {
		if (is_executable($dir . $name)) {
			return $dir . $name;
		}
	}
	return null;
}

// Runs a command (an array: no shell) and returns ['status' => exit code, 'out' => stdout, 'timed_out' => bool, 'too_long' => bool]. stderr is discarded.
function ips_issues_run($command, $timeout, $max_bytes) {
	$process = @proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'a']], $pipes, null, ['PATH' => '/usr/bin:/bin', 'LC_ALL' => 'C.UTF-8']);
	if (!is_resource($process)) {
		return ['status' => -1, 'out' => '', 'timed_out' => false, 'too_long' => false];
	}
	stream_set_blocking($pipes[1], false);
	$deadline = microtime(true) + $timeout;
	$out = '';
	$timed_out = $too_long = false;
	while (true) {
		$read = [$pipes[1]];
		$write = $except = null;
		$left = $deadline - microtime(true);
		if ($left <= 0) {
			$timed_out = true;
			break;
		}
		if (stream_select($read, $write, $except, 0, (int) min(200000, $left * 1000000)) === false) {
			break;
		}
		if ($read) {
			$chunk = fread($pipes[1], 262144);
			if ($chunk !== false) {
				$out .= $chunk;
			}
			if (strlen($out) > $max_bytes) {
				$too_long = true;
				break;
			}
		}
		if (feof($pipes[1])) {
			break;
		}
	}
	fclose($pipes[1]);
	if ($timed_out || $too_long) {
		proc_terminate($process, 9);
	}
	$status = proc_close($process);
	return ['status' => $status, 'out' => $out, 'timed_out' => $timed_out, 'too_long' => $too_long];
}

// The number of pages of the PDF, or a string saying why it cannot be read.
function ips_issues_page_count($path) {
	$bin = ips_issues_bin('pdfinfo');
	if ($bin === null) {
		return 'poppler-utils is not installed on this server (pdfinfo is missing).';
	}
	$run = ips_issues_run([$bin, '-enc', 'UTF-8', $path], IPS_ISSUES_EXTRACT_TIMEOUT, 1048576);
	if ($run['timed_out']) {
		return 'The PDF took too long to read.';
	}
	if ($run['status'] !== 0 || !preg_match('/^Pages:\s+(\d+)/m', $run['out'], $m)) {
		return 'This is not a PDF that can be read (it may be damaged or encrypted).';
	}
	return (int) $m[1];
}

/* The text of each page of the PDF as a list (page 1 first), or a string saying why not. Refuses a PDF of more than IPS_ISSUES_MAX_PAGES pages. A PDF with a text layer
 * gives text; a scan gives empty pages (counted by the caller). */
function ips_issues_extract_pages($path) {
	$count = ips_issues_page_count($path);
	if (is_string($count)) {
		return $count;
	}
	if ($count < 1) {
		return 'The PDF has no pages.';
	}
	if ($count > IPS_ISSUES_MAX_PAGES) {
		return "The PDF has $count pages; at most " . IPS_ISSUES_MAX_PAGES . ' are accepted.';
	}
	$bin = ips_issues_bin('pdftotext');
	if ($bin === null) {
		return 'poppler-utils is not installed on this server (pdftotext is missing).';
	}
	$run = ips_issues_run([$bin, '-enc', 'UTF-8', $path, '-'], IPS_ISSUES_EXTRACT_TIMEOUT, IPS_ISSUES_MAX_TEXT_BYTES);
	if ($run['timed_out']) {
		return 'Extracting the text took too long.';
	}
	if ($run['too_long']) {
		return 'The text of this PDF is larger than ' . (IPS_ISSUES_MAX_TEXT_BYTES >> 20) . ' MB.';
	}
	if ($run['status'] !== 0) {
		return 'The text could not be read from this PDF (it may forbid copying text).';
	}
	$pages = explode("\f", $run['out']);
	if (count($pages) > $count) { // a form feed ends each page, so the last element is empty
		array_pop($pages);
	}
	while (count($pages) < $count) {
		$pages[] = '';
	}
	return array_slice($pages, 0, $count);
}

// Whether a page has a text layer worth searching.
function ips_issues_page_has_text($text) {
	return mb_strlen(preg_replace('/[^\p{L}]/u', '', (string) $text), 'UTF-8') >= IPS_ISSUES_EMPTY_PAGE_CHARS;
}

// The extraction state for the text of a list of pages: ['state' => done|no-text, 'note' => ..., 'empty' => number of pages without text]. Most pages empty means a scan.
function ips_issues_judge($pages) {
	$empty = 0;
	foreach ($pages as $text) {
		if (!ips_issues_page_has_text($text)) {
			$empty++;
		}
	}
	$total = count($pages);
	$scanned = $total > 0 && $empty * 5 >= $total * 4; // four fifths or more
	$note = $total . ($total === 1 ? ' page' : ' pages') . ($empty ? ', ' . $empty . ' without a text layer' : '');
	return ['state' => $scanned ? 'no-text' : 'done', 'note' => $scanned ? 'Scanned: no text layer (' . $note . '). The PDF can still be read; its text cannot be searched.' : $note, 'empty' => $empty];
}

function ips_issues_set_state($id, $state, $note, $page_count = null) {
	global $db;
	$params = [['type' => 's', 'value' => $state], ['type' => 's', 'value' => mb_substr($note, 0, 1000)]];
	$sql = "UPDATE issues SET extraction=?, extraction_note=?, extraction_at=NOW()";
	if ($page_count !== null) {
		$sql .= ", page_count=?";
		$params[] = ['type' => 'i', 'value' => $page_count];
	}
	$params[] = ['type' => 'i', 'value' => $id];
	$db->query($sql . " WHERE id=?", $params);
}

/* Extracts the text of issue $id's PDF and stores it: the raw text of each page, and the folded search text of the corrected text where there is one, else of the raw text. A correction
 * made by an editor is kept (a page is replaced by number, its correction untouched). Returns true when the issue ends 'done' or 'no-text', false when it failed (the message is on the issue). */
function ips_issues_run_extraction($id, $pdf_path_for) {
	global $db;
	$rows = $db->query("SELECT pdf_sha256 FROM issues WHERE id=? LIMIT 1", [['type' => 'i', 'value' => $id]])->fetchArray();
	if (!$rows || $rows[0]['pdf_sha256'] === '') {
		return false;
	}
	ips_issues_set_state($id, 'running', 'Reading the text…');
	$path = $pdf_path_for($rows[0]['pdf_sha256']);
	if (!is_file($path)) {
		ips_issues_set_state($id, 'failed', 'The PDF file is missing from the server.');
		return false;
	}
	$pages = ips_issues_extract_pages($path);
	if (is_string($pages)) {
		ips_issues_set_state($id, 'failed', $pages);
		return false;
	}
	foreach ($pages as $i => $text) {
		$page = $i + 1;
		$existing = $db->query("SELECT text_corrected FROM issue_pages WHERE issue_id=? AND page=? LIMIT 1", [['type' => 'i', 'value' => $id], ['type' => 'i', 'value' => $page]])->fetchArray();
		$corrected = $existing && $existing[0]['text_corrected'] !== null ? $existing[0]['text_corrected'] : null;
		$search = ips_fold($corrected ?? $text);
		$db->query("INSERT INTO issue_pages (issue_id, page, text_raw, search_text) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE text_raw=VALUES(text_raw), search_text=VALUES(search_text)",
			[['type' => 'i', 'value' => $id], ['type' => 'i', 'value' => $page], ['type' => 's', 'value' => $text], ['type' => 's', 'value' => $search]]);
	}
	$db->query("DELETE FROM issue_pages WHERE issue_id=? AND page>?", [['type' => 'i', 'value' => $id], ['type' => 'i', 'value' => count($pages)]]);
	$judged = ips_issues_judge($pages);
	ips_issues_set_state($id, $judged['state'], $judged['note'], count($pages));
	return true;
}
