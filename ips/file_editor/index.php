<?php
// The Files page of the file editor module: the HTML (and XML) files this user may open, a filter, New file, and Delete.
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
$fe_types = ips_fe_types();
// where a new file of each kind goes for this user, for the dialog's hint (null: the user has no folder)
$fe_dirs = null;
if ($fe_scope !== null) {
	$fe_dirs = [];
	foreach (ips_fe_kinds() as $kind => $rel) {
		$fe_dirs[$kind] = substr($rel, strlen('upload/files/')) . ($fe_scope === '' ? '' : '/' . $fe_scope);
	}
}
$fe_css = 'file-editor.css?v=' . filemtime(__DIR__ . '/file-editor.css');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Pages · <?php echo htmlspecialchars(SITE_NAME) ?></title>
<?php include HTML_TEMPLATES.'ui-head.php'; ?>
<link rel="stylesheet" href="<?php echo $fe_css ?>">
</head>
<body class="fe">
<?php include __DIR__.'/header.php'; ?>

	<div id="fe-app" v-cloak>
		<main class="fe-page">
			<div class="fe-titlerow">
				<div>
					<h1>Pages</h1>
					<p><?php echo implode(' and ', array_map(function ($t) { return strtoupper($t); }, $fe_types)) === 'HTML AND XML' ? 'HTML and XML' : strtoupper(implode('', $fe_types)) ?> files kept in the file store. Open one to edit it in place.</p>
				</div>
				<button type="button" id="new-file-open" class="fe-btn fe-btn-primary" @click="openNew">New file</button>
			</div>

			<div class="fe-filter">
				<input type="search" class="fe-input" id="file-filter" v-model="query" placeholder="Filter by name or folder" aria-label="Filter by name or folder" autocomplete="off" spellcheck="false">
<?php if (count($fe_types) > 1) { ?>
				<div class="fe-seg" role="group" aria-label="File type">
					<button type="button" v-for="t in types" :key="t.id" :aria-pressed="type === t.id ? 'true' : 'false'" @click="type = t.id">{{ t.label }}</button>
				</div>
<?php } ?>
			</div>

			<div class="fe-table" id="allfiles">
				<template v-for="g in shown" :key="g.name">
					<div class="fe-group">{{ g.name }}<span v-if="g.note">{{ g.note }}</span></div>
					<div class="fe-file files" v-for="f in g.files" :key="f.path" role="link" tabindex="0" @click="open(f)" @keydown.enter="open(f)">
						<span><span class="fe-tag" :class="{ 'fe-tag-xml': f.ext === 'xml' }">{{ f.ext }}</span></span>
						<span class="name"><a :href="editUrl(f)" :title="f.path" @click.stop>{{ f.name }}</a><span v-if="!f.writable" class="fe-badge">Read-only</span></span>
						<span class="dir">{{ f.dir }}</span>
						<span class="size">{{ f.sizeText }}</span>
						<span class="del"><button v-if="f.deletable" type="button" class="fe-del" :class="{ armed: armed === f.path }" @click.stop="remove(f)">{{ armed === f.path ? 'Sure?' : 'Delete' }}</button></span>
					</div>
				</template>
				<div v-if="!shown.length" class="fe-empty">No files match.</div>
				<p v-if="deleteError" class="fe-error" role="alert" style="padding:0 20px 12px">{{ deleteError }}</p>
			</div>
		</main>

		<div v-if="isNew" class="fe-scrim" @click.self="isNew = false" @keydown.esc="isNew = false">
			<form class="fe-dialog" id="new-file-form" role="dialog" aria-modal="true" aria-labelledby="new-file-title" autocomplete="off" @submit.prevent="create">
				<h2 id="new-file-title">New file</h2>
<?php if (count($fe_types) > 1) { ?>
				<label class="l">Type</label>
				<div class="fe-tiles">
					<button type="button" class="fe-tile" :aria-pressed="newType === 'html' ? 'true' : 'false'" @click="newType = 'html'"><strong>HTML</strong><span>Blank page</span></button>
					<button type="button" class="fe-tile" :aria-pressed="newType === 'xml' ? 'true' : 'false'" @click="newType = 'xml'"><strong>XML</strong><span>TEI skeleton</span></button>
				</div>
<?php } ?>
				<label class="l" for="new-file-name">File name</label>
				<div class="fe-name">
					<input type="text" class="fe-input" id="new-file-name" ref="nameEl" v-model="newName" maxlength="100" required>
					<span class="suffix">.{{ newType }}</span>
				</div>
				<p class="fe-hint">Created in <code>{{ dirs ? dirs[newType] : '' }}</code> and opened in the editor.</p>
				<p class="fe-error" v-if="newError" role="alert">{{ newError }}</p>
				<div class="actions">
					<button type="button" class="fe-btn" @click="isNew = false">Cancel</button>
					<button type="submit" class="fe-btn fe-btn-primary" id="new-file-create" :disabled="busy">Create and edit</button>
				</div>
			</form>
		</div>
	</div>

	<script>window.IPS_FE = <?php echo json_encode(['csrf' => $login_cookie->CsrfToken(), 'types' => $fe_types, 'dirs' => $fe_dirs], JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
	<script src="files-app.js?v=<?php echo filemtime(__DIR__.'/files-app.js') ?>"></script>
</body>
</html>
