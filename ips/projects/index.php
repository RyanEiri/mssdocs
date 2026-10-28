<?php
// The Projects page: the projects on the left, the open one on the right (its details and its files in their order, each with a role, label and note). The data comes
// from list.php and files.php; writes post to save.php, delete.php, update_link.php, move.php and unlink.php (same-origin, the user's CSRF token in X-CSRF). Files are
// linked from the file browser. It uses the file editor module's stylesheet for the app bar, buttons and dialog.
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
$pj_user = $login_cookie->username;
$result = $db->query("SELECT admin FROM users WHERE username=? LIMIT 1", [['type' => 's', 'value' => $pj_user]])->fetchArray();
$pj_admin = $result && (int) $result[0]['admin'] === 1;
$pj_css = ['file_editor/file-editor.css', 'projects/projects.css'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Projects · <?php echo htmlspecialchars(SITE_NAME) ?></title>
<?php include HTML_TEMPLATES.'ui-head.php'; ?>
<?php foreach ($pj_css as $css) { if (is_file(PROGRAM_BASE . $css)) { ?>
<link rel="stylesheet" href="<?php echo PROGRAM_WEB_BASE . $css ?>?v=<?php echo filemtime(PROGRAM_BASE . $css) ?>">
<?php } } ?>
</head>
<body class="fe">
<?php include __DIR__.'/header.php'; ?>

	<div id="pj-app" v-cloak>
		<main class="fe-page">
			<div class="fe-titlerow">
				<div>
					<h1>Projects</h1>
					<p>A project groups files of the file store: the pages of a manuscript, its description, a PDF. Link files from the Files page.</p>
				</div>
				<button type="button" id="project-add" class="fe-btn fe-btn-primary" @click="openNew">New project</button>
			</div>

			<div class="pj-layout">
				<section class="pj-list" aria-label="Projects">
					<div class="fe-filter">
						<input type="search" class="fe-input" id="project-filter" v-model="query" placeholder="Filter by title" aria-label="Filter the projects" autocomplete="off" spellcheck="false">
					</div>
					<div class="fe-table" id="all-projects">
						<button type="button" class="pj-item" v-for="p in shown" :key="p.id" :class="{ current: current && current.id === p.id }" :data-id="p.id" @click="open(p.id)">
							<span class="pj-title">{{ p.title }}</span>
							<span class="pj-meta">{{ kindLabel(p.kind) }} · {{ p.files }} {{ p.files === 1 ? 'file' : 'files' }}<span v-if="p.status === 'archived'" class="fe-badge">Archived</span></span>
						</button>
						<div v-if="!shown.length" class="fe-empty">{{ loaded ? (projects.length ? 'No projects match.' : 'No projects yet.') : 'Loading…' }}</div>
					</div>
					<label class="pj-check"><input type="checkbox" id="project-archived" v-model="showArchived"> Show archived projects</label>
					<p v-if="listError" class="fe-error" role="alert">{{ listError }}</p>
				</section>

				<section class="pj-detail" aria-label="Project" id="project-detail">
					<p v-if="!form" class="fe-empty">Choose a project, or make a new one.</p>
					<template v-else>
						<form id="project-form" @submit.prevent="save" autocomplete="off">
							<label class="l" for="project-title">Title</label>
							<input type="text" class="fe-input pj-wide" id="project-title" ref="titleEl" v-model="form.title" maxlength="255" required>
							<div class="pj-two">
								<div>
									<label class="l" for="project-kind">Kind</label>
									<select class="fe-input pj-wide" id="project-kind" v-model="form.kind"><option v-for="k in kinds" :key="k.id" :value="k.id">{{ k.label }}</option></select>
								</div>
								<div>
									<label class="l" for="project-status">Status</label>
									<select class="fe-input pj-wide" id="project-status" v-model="form.status"><option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.label }}</option></select>
								</div>
							</div>
							<label class="l" for="project-notes">Notes</label>
							<textarea class="fe-input pj-wide" id="project-notes" v-model="form.notes" rows="3" maxlength="5000"></textarea>
							<p class="fe-error" v-if="formError" role="alert">{{ formError }}</p>
							<div class="pj-actions">
								<button type="submit" class="fe-btn fe-btn-primary" id="project-save" :disabled="busy">{{ form.id ? 'Save changes' : 'Create project' }}</button>
								<button type="button" class="fe-btn" @click="cancelNew" v-if="!form.id">Cancel</button>
								<a v-if="form.id" class="fe-btn" id="project-in-files" :href="filesHref(form.id)">Show in Files</a>
								<button v-if="form.id && canDelete" type="button" class="fe-del pj-delete" :class="{ armed: armed === 'project' }" id="project-delete" @click="removeProject">{{ armed === 'project' ? 'Sure? The files stay.' : 'Delete project' }}</button>
							</div>
						</form>

						<div v-if="form.id" class="pj-files">
							<h2>Files <span class="fe-muted">({{ files.length }})</span></h2>
							<p v-if="filesLoaded && !files.length" class="fe-empty" id="project-no-files">No files yet. In the Files page, select files and choose “Add to project…”.</p>
							<div class="pj-file" v-for="(f, i) in files" :key="f.file_id" :data-file="f.file_id">
								<div class="pj-file-main">
									<span class="pj-file-name">{{ f.title || f.name }}</span><span v-if="f.missing" class="fe-badge">Missing</span>
									<a class="pj-file-path" :href="folderHref(f.path)">{{ f.path }}</a>
								</div>
								<select class="fe-input pj-role" v-model="f.role" @change="saveLink(f)" :aria-label="'Role of ' + f.name"><option v-for="r in roles" :key="r.id" :value="r.id">{{ r.label }}</option></select>
								<input type="text" class="fe-input pj-label" v-model="f.label" @change="saveLink(f)" placeholder="Label (fol. 12r)" maxlength="255" :aria-label="'Label of ' + f.name">
								<input type="text" class="fe-input pj-note" v-model="f.note" @change="saveLink(f)" placeholder="Note" maxlength="1000" :aria-label="'Note on ' + f.name">
								<span class="pj-file-actions">
									<button type="button" class="fe-del pj-move" :disabled="i === 0" @click="move(f, 'up')" :aria-label="'Move ' + f.name + ' up'">↑</button>
									<button type="button" class="fe-del pj-move" :disabled="i === files.length - 1" @click="move(f, 'down')" :aria-label="'Move ' + f.name + ' down'">↓</button>
									<button type="button" class="fe-del pj-unlink" :class="{ armed: armed === f.file_id }" @click="unlink(f)">{{ armed === f.file_id ? 'Sure?' : 'Remove' }}</button>
								</span>
							</div>
							<p v-if="filesError" class="fe-error" role="alert">{{ filesError }}</p>
						</div>
					</template>
				</section>
			</div>
		</main>
	</div>

	<script>window.IPS_PROJECTS = <?php echo json_encode(['csrf' => $login_cookie->CsrfToken(), 'user' => $pj_user, 'admin' => $pj_admin], JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
	<script src="projects-app.js?v=<?php echo filemtime(__DIR__.'/projects-app.js') ?>"></script>
</body>
</html>
