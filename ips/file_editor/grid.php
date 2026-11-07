<?php
// The editor page for a CSV file (a file type whose view is 'grid', types/csv.php): the file as a spreadsheet grid (Jspreadsheet CE, vendored in js/jspreadsheet/)
// with Excel-style copy, paste and a fill handle that continues series (fill-series.js). The sheet's columns are the file's header row and stay as they are; rows can
// be added, removed and edited. Saving posts the CSV text to save.php, which applies the same rules as for any file (sign-in, CSRF, the user's own folder, the
// type's validation). A file outside the user's own folder is shown read-only.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if (!$login_cookie->CheckIt()) {
	header('Location: ' . PROGRAM_WEB_BASE . 'login.php');
	exit;
}
$fe_user = $login_cookie->username;
$fe_admin = ips_fe_is_admin($fe_user);
$fe_scope = ips_fe_scope($fe_user);
$h = function ($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); };
$real = ips_fe_resolve($_GET['file'] ?? '', $fe_scope);
if ($real === false) {
	http_response_code(404);
	die('That file was not found. <a href="' . PROGRAM_WEB_BASE . 'file_editor/">Back to the file list</a>');
}
$kind = ips_fe_kind_of($real);
$type = ips_fe_type($kind);
if (!$type || $type['view'] !== 'grid') {
	header('Location: ' . PROGRAM_WEB_BASE . 'file_editor/edit.php?file=' . rawurlencode(ips_fe_name($real)));
	exit;
}
$may = ips_fe_may_write($real, $fe_scope);
$writable = $may && is_writable($real);
$can_delete = $may && is_writable(dirname($real));
$too_big = filesize($real) > 5 * 1024 * 1024;
$content = $too_big ? '' : (string) file_get_contents($real);
$folder = dirname(substr($real, strlen(ips_fe_roots()[$kind]) + 1));
$folder = $folder === '.' ? 'shared' : $folder;
$fe_css = 'file-editor.css?v=' . filemtime(__DIR__ . '/file-editor.css');
$v = function ($file) { return $file . '?v=' . filemtime(__DIR__ . '/' . $file); };
$vendor = '../js/jspreadsheet/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title><?php echo $h(basename($real)) ?> · File editor · <?php echo $h(SITE_NAME) ?></title>
<?php include HTML_TEMPLATES.'ui-head.php'; ?>
<link rel="stylesheet" href="<?php echo $fe_css ?>">
<link rel="stylesheet" href="<?php echo $vendor ?>jsuites.css">
<link rel="stylesheet" href="<?php echo $vendor ?>jspreadsheet.css">
</head>
<body class="fe">
<?php include __DIR__.'/header.php'; ?>

	<div class="fe-bar">
		<div class="fe-bar-in">
			<a href="<?php echo PROGRAM_WEB_BASE ?>file_editor/" class="fe-muted" style="font-size:14px">&larr; File editor</a>
			<div class="fe-bar-file">
				<span class="fe-tag" data-type="<?php echo $h($kind) ?>"><?php echo $h($kind) ?></span>
				<h1><?php echo $h(basename($real)) ?></h1>
				<span class="fe-muted" style="font-size:14px"><?php echo $h($folder) ?></span>
				<span id="dirty-flag" hidden class="fe-muted" style="font-size:13px">&middot; Unsaved changes</span>
			</div>
			<div class="fe-bar-actions">
<?php if ($can_delete) { ?>
				<button type="button" id="delete-btn" class="fe-btn fe-btn-danger" title="Move this file to the .deleted folder next to it (it is not erased)">Delete</button>
<?php } ?>
				<button type="button" id="save-btn" class="fe-btn fe-btn-primary"<?php if (!$writable) echo ' disabled' ?>>Save</button>
			</div>
		</div>
	</div>

	<main class="fe-main fe-grid-main">
<?php if (!$may) { ?>
		<div id="fe-not-yours" role="note" class="fe-notice"><strong>Read-only</strong><span class="fe-muted">This file is outside your own folder (<code><?php echo $h(ips_fe_own_folders_text($fe_user)) ?></code>). You can read it here but not change it.</span></div>
<?php } elseif (!$writable) { ?>
		<div id="fe-readonly" role="alert" class="fe-notice"><strong>Read-only</strong><span class="fe-muted">The web server cannot write this file.</span></div>
<?php } ?>
<?php if ($too_big) { ?>
		<div id="fe-too-big" role="alert" class="fe-notice"><strong>Too large</strong><span class="fe-muted">This file is larger than 5 MB, which the grid does not open.</span></div>
<?php } else { ?>
		<p class="fe-muted fe-grid-help" id="fe-grid-help">Click a cell and type, or press F2 to edit. Copy and paste with Ctrl+C and Ctrl+V (also from Excel). Select cells and drag the small square at the corner of the selection to fill: <code>1, 2</code> continues as <code>3, 4, 5</code>, <code>Item 1</code> as <code>Item 2</code>, and one cell is copied. Right-click for rows. Ctrl+Z undoes.</p>
		<div id="fe-pane">
			<div class="fe-strip" id="fe-strip">
				<span id="grid-size">0 rows</span><span>UTF-8</span><span><?php echo $h($type['label']) ?></span>
				<span class="fe-status"><span id="wf-dot" class="fe-dot"></span><span id="wf-text">CSV</span></span>
			</div>
			<div id="fe-grid" aria-label="Spreadsheet of <?php echo $h(basename($real)) ?>"></div>
		</div>
<?php } ?>
	</main>

	<div id="fe-toast" role="status" aria-live="polite" hidden></div>
<?php if (!$too_big) { ?>
	<script>window.IPS_FE_GRID = <?php echo json_encode(['csrf' => $login_cookie->CsrfToken(), 'file' => ips_fe_name($real), 'writable' => $writable, 'delete' => $can_delete, 'text' => $content, 'list' => PROGRAM_WEB_BASE . 'file_editor/'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?>;</script>
	<script src="<?php echo $vendor ?>jsuites.js"></script>
	<script src="<?php echo $vendor ?>index.js"></script>
	<script src="<?php echo $v('fill-series.js') ?>"></script>
	<script src="<?php echo $v('grid-page.js') ?>"></script>
<?php } ?>
</body>
</html>
