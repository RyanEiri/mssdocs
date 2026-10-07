<?php
// The editor page of the file editor module: one file in CKEditor, in its Source view (CodeMirror: highlighting, search and replace, tag completion, comment
// buttons) or, for a type whose view is 'wysiwyg' (HTML), its visual editor (ips_fe_view() in lib.php). Without CKEditor installed (README) it falls back to a
// plain text box that saves the same way. A file outside the user's own folder is shown read-only.
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
$public = array_values(array_filter(ips_fe_public_types(), function ($t) use ($kind) { return $t['id'] === $kind; }))[0];
$may = ips_fe_may_write($real, $fe_scope);
$writable = $may && is_writable($real);
$can_delete = $may && is_writable(dirname($real));
$has_ckeditor = is_file(__DIR__ . '/ckeditor/ckeditor.js');
$content = file_get_contents($real);
[$view, $view_why] = ips_fe_view($type, $content);
$folder = dirname(substr($real, strlen(ips_fe_roots()[$kind]) + 1));
$folder = $folder === '.' ? 'shared' : $folder;
$fe_css = 'file-editor.css?v=' . filemtime(__DIR__ . '/file-editor.css');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title><?php echo $h(basename($real)) ?> · File editor · <?php echo $h(SITE_NAME) ?></title>
<?php include HTML_TEMPLATES.'ui-head.php'; ?>
<link rel="stylesheet" href="<?php echo $fe_css ?>">
<?php if ($has_ckeditor) { ?>
<script src="ckeditor/ckeditor.js"></script>
<?php } ?>
<?php if ($public['js']) { ?>
<script src="<?php echo $h($public['js']) ?>?v=<?php echo filemtime(__DIR__ . '/' . $public['js']) ?>"></script>
<?php } ?>
</head>
<body class="fe">
<?php include __DIR__.'/header.php'; ?>

	<form id="fe-form" action="save.php" method="post">
		<input type="hidden" name="file" value="<?php echo $h(ips_fe_name($real)) ?>">

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
					<button type="submit" id="save-btn" class="fe-btn fe-btn-primary"<?php if (!$writable) echo ' disabled' ?>>Save</button>
				</div>
			</div>
		</div>

		<main class="fe-main">
<?php if (!$may) { ?>
			<div id="fe-not-yours" role="note" class="fe-notice"><strong>Read-only</strong><span class="fe-muted">This file is outside your own folder (<code><?php echo $h(ips_fe_own_folders_text($fe_user)) ?></code>). You can read it here; only administrators can save it. Make a new file to work in your folder.</span></div>
<?php } elseif (!$writable) { ?>
			<div id="fe-readonly" role="alert" class="fe-notice"><strong>Read-only</strong><span class="fe-muted">The web server cannot write this file.</span></div>
<?php } ?>
<?php if ($has_ckeditor && $view_why !== null) { ?>
			<div id="fe-source-only" role="note" class="fe-notice"><strong>Source</strong><span class="fe-muted"><?php echo $h($view_why) ?></span></div>
<?php } ?>
<?php if (!$has_ckeditor) { ?>
			<div id="fe-no-ckeditor" role="note" class="fe-notice"><strong>Plain editor</strong><span class="fe-muted">CKEditor is not installed, so this is a plain text box (see the module's README); saving works the same.</span></div>
<?php } ?>
			<div id="fe-pane">
				<div class="fe-strip" id="fe-strip">
					<span><span id="line-count">0</span> lines</span><span>UTF-8</span><span><?php echo $h($type['label']) ?></span>
					<span class="fe-status"><span id="wf-dot" class="fe-dot"></span><span id="wf-text"><?php echo $h($type['label']) ?></span></span>
				</div>
<?php // The file's text goes in escaped: the editor holds exactly what the file holds and it is saved as posted. Printed raw, a file containing </textarea><script> would run script here for whoever opens it. ?>
				<textarea name="editor1" id="editor1" rows="30" cols="100" spellcheck="false"<?php if (!$writable) echo ' readonly' ?> style="flex:1;border:0;padding:14px;font:13px/21px var(--ips-font-mono);resize:none"><?php echo $h($content) ?></textarea>
			</div>
		</main>
	</form>

	<div id="fe-toast" role="status" aria-live="polite" hidden></div>
	<script>window.IPS_FE_EDIT = <?php echo json_encode(['csrf' => $login_cookie->CsrfToken(), 'kind' => $kind, 'label' => $type['label'], 'mode' => $type['mode'], 'view' => $view, 'writable' => $writable, 'delete' => $can_delete, 'ckeditor' => $has_ckeditor], JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
	<script src="edit-page.js?v=<?php echo filemtime(__DIR__.'/edit-page.js') ?>"></script>
</body>
</html>
