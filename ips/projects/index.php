<?php
// The Projects page: the projects on the left, the open one on the right (its details and its files in their order, each with a role, label and note). The data comes
// from list.php and files.php; writes post to save.php, delete.php, update_link.php, move.php and unlink.php (same-origin, the user's CSRF token in X-CSRF). Files are
// linked from the file browser. It uses the file editor module's stylesheet for the app bar, buttons and dialog.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/unit_lib.php';
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if (!$login_cookie->CheckIt()) {
	header('Location: ' . PROGRAM_WEB_BASE . 'login.php');
	exit;
}
$pj_user = $login_cookie->username;
$result = $db->query("SELECT admin FROM users WHERE username=? LIMIT 1", [['type' => 's', 'value' => $pj_user]])->fetchArray();
$pj_admin = $result && (int) $result[0]['admin'] === 1;
$pj_units = ips_units_ready(); // units of description (units.sql): the Description section and the Hierarchy list
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
<?php if ($pj_units) { ?>
					<div class="pj-views" role="tablist" aria-label="List">
						<button type="button" role="tab" id="view-projects" class="pj-view" :class="{ on: view === 'projects' }" :aria-selected="view === 'projects' ? 'true' : 'false'" @click="view = 'projects'">Projects</button>
						<button type="button" role="tab" id="view-hierarchy" class="pj-view" :class="{ on: view === 'hierarchy' }" :aria-selected="view === 'hierarchy' ? 'true' : 'false'" @click="view = 'hierarchy'">Hierarchy</button>
					</div>
					<div v-if="view === 'hierarchy'" class="fe-table" id="all-units">
						<button type="button" class="pj-item pj-node" v-for="n in flat" :key="n.project_id" :class="{ current: current && current.id === n.project_id }" :data-id="n.project_id" :style="{ paddingLeft: (20 + n.depth * 18) + 'px' }" @click="open(n.project_id)">
							<span class="pj-title">{{ n.title }}</span>
							<span class="pj-meta">{{ levelLabel(n.level) }}<template v-if="n.reference_code"> · {{ n.reference_code }}</template> · <span class="pj-vis" :class="n.effective_visibility">{{ visLabel(n.effective_visibility) }}</span><template v-if="n.items"> · {{ n.items }} {{ n.items === 1 ? 'item' : 'items' }}</template></span>
						</button>
						<div v-if="!flat.length" class="fe-empty" id="no-units">No units yet. Open a project and choose “Describe as a unit…”.</div>
					</div>
<?php } ?>
					<div class="fe-filter" v-show="view === 'projects'">
						<input type="search" class="fe-input" id="project-filter" v-model="query" placeholder="Filter by title" aria-label="Filter the projects" autocomplete="off" spellcheck="false">
					</div>
					<div class="fe-table" id="all-projects" v-show="view === 'projects'">
						<button type="button" class="pj-item" v-for="p in shown" :key="p.id" :class="{ current: current && current.id === p.id }" :data-id="p.id" @click="open(p.id)">
							<span class="pj-title">{{ p.title }}</span>
							<span class="pj-meta"><template v-if="unitOf(p.id)">{{ levelLabel(unitOf(p.id).level) }} · </template>{{ kindLabel(p.kind) }} · {{ p.files }} {{ p.files === 1 ? 'file' : 'files' }}<span v-if="p.status === 'archived'" class="fe-badge">Archived</span></span>
						</button>
						<div v-if="!shown.length" class="fe-empty">{{ loaded ? (projects.length ? 'No projects match.' : 'No projects yet.') : 'Loading…' }}</div>
					</div>
					<label class="pj-check" v-show="view === 'projects'"><input type="checkbox" id="project-archived" v-model="showArchived"> Show archived projects</label>
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

<?php if ($pj_units) { ?>
						<section v-if="form.id" class="pj-unit" id="unit-section" aria-label="Archival description">
							<h2>Archival description</h2>
							<template v-if="!unit">
								<p class="fe-muted">Describe this project as a unit of an archival description (RAD): a fonds, collection, series or file. The files linked to it with the role “Description” are its items.</p>
								<div class="pj-actions"><button type="button" class="fe-btn" id="unit-describe" @click="describe">Describe as a unit…</button></div>
							</template>
							<form v-else id="unit-form" @submit.prevent="saveUnit" autocomplete="off">
								<p v-if="info" class="pj-unit-line" id="unit-line">
									<template v-if="info.chain.length > 1"><span id="unit-chain"><template v-for="(c, i) in info.chain.slice(0, -1)"><a href="#" @click.prevent="open(c.project_id)">{{ c.title }}</a> › </template></span></template>
									Reference code <code id="unit-code">{{ info.reference_code || '—' }}</code> ·
									<span class="pj-vis" :class="info.effective_visibility" id="unit-effective">{{ info.effective_visibility === 'published' ? 'Public' : 'Not shown in public zones' }}</span>
									<template v-if="info.effective_visibility !== unit.visibility"> (a unit above it is {{ visLabel(info.effective_visibility).toLowerCase() }})</template>
								</p>
								<div class="pj-two">
									<div>
										<label class="l" for="unit-level">Level</label>
										<select class="fe-input pj-wide" id="unit-level" v-model="unit.level"><option v-for="l in levels" :key="l.id" :value="l.id">{{ l.label }}</option></select>
									</div>
									<div>
										<label class="l" for="unit-parent">Parent</label>
										<select class="fe-input pj-wide" id="unit-parent" v-model="unit.parent_id">
											<option :value="null" :disabled="needsParent">{{ needsParent ? 'Choose a parent' : 'None (a top level)' }}</option>
											<option v-for="o in parentOptions" :key="o.project_id" :value="o.project_id">{{ o.title }} ({{ levelLabel(o.level) }})</option>
										</select>
									</div>
								</div>
								<div class="pj-two">
									<div><label class="l" for="unit-segment">Reference-code segment</label><input type="text" class="fe-input pj-wide" id="unit-segment" v-model="unit.ref_segment" maxlength="60" placeholder="F1"></div>
									<div><label class="l" for="unit-title">Title</label><input type="text" class="fe-input pj-wide" id="unit-title" v-model="unit.title" maxlength="255" :placeholder="form.title"></div>
								</div>
								<div class="pj-two">
									<div><label class="l" for="unit-start">Dates: from (ISO 8601)</label><input type="text" class="fe-input pj-wide" id="unit-start" v-model="unit.date_start" maxlength="10" placeholder="1884 or 1884-03-04"></div>
									<div><label class="l" for="unit-end">to (empty while the unit is open)</label><input type="text" class="fe-input pj-wide" id="unit-end" v-model="unit.date_end" maxlength="10" placeholder="1950"></div>
								</div>
								<div class="pj-two">
									<div><label class="l" for="unit-extent">Extent</label><input type="text" class="fe-input pj-wide" id="unit-extent" v-model="unit.extent" maxlength="255" placeholder="0.3 m of textual records"></div>
									<div><label class="l" for="unit-accruals">Accruals</label><input type="text" class="fe-input pj-wide" id="unit-accruals" v-model="unit.accruals" maxlength="255" placeholder="Further accruals are expected"></div>
								</div>
								<div class="pj-two">
									<div><label class="l" for="unit-language">Language</label><input type="text" class="fe-input pj-wide" id="unit-language" v-model="unit.language" maxlength="255" :placeholder="hint('language')"></div>
									<div><label class="l" for="unit-repository">Repository</label><input type="text" class="fe-input pj-wide" id="unit-repository" v-model="unit.repository" maxlength="255" :placeholder="hint('repository')"></div>
								</div>
								<label class="l" for="unit-admin">Administrative history / biographical sketch</label>
								<textarea class="fe-input pj-wide" id="unit-admin" v-model="unit.admin_history" rows="3"></textarea>
								<label class="l" for="unit-custodial">Custodial history</label>
								<textarea class="fe-input pj-wide" id="unit-custodial" v-model="unit.custodial_history" rows="2" :placeholder="hint('custodial_history')"></textarea>
								<label class="l" for="unit-scope">Scope and content</label>
								<textarea class="fe-input pj-wide" id="unit-scope" v-model="unit.scope_content" rows="4"></textarea>
								<div class="pj-two">
									<div><label class="l" for="unit-access">Restrictions on access</label><textarea class="fe-input pj-wide" id="unit-access" v-model="unit.access_note" rows="2" :placeholder="hint('access_note')"></textarea></div>
									<div><label class="l" for="unit-terms">Terms governing use</label><textarea class="fe-input pj-wide" id="unit-terms" v-model="unit.terms_note" rows="2" :placeholder="hint('terms_note')"></textarea></div>
								</div>
								<label class="l" for="unit-note">General note</label>
								<textarea class="fe-input pj-wide" id="unit-note" v-model="unit.general_note" rows="2"></textarea>
								<div class="pj-two">
									<div>
										<label class="l" for="unit-visibility">Visibility</label>
										<select class="fe-input pj-wide" id="unit-visibility" v-model="unit.visibility" :disabled="!admin"><option v-for="v in visibilities" :key="v.id" :value="v.id">{{ v.label }}</option></select>
										<p v-if="!admin" class="fe-muted pj-hint" id="unit-visibility-hint">Only an administrator can publish a unit or make it private.</p>
									</div>
								</div>
								<ul v-if="info && info.warnings.length" class="pj-warnings" id="unit-warnings"><li v-for="w in info.warnings" :key="w">{{ w }}</li></ul>
								<p class="fe-error" v-if="unitError" role="alert" id="unit-error">{{ unitError }}</p>
								<div class="pj-actions">
									<button type="submit" class="fe-btn fe-btn-primary" id="unit-save" :disabled="unitBusy">{{ info ? 'Save description' : 'Create description' }}</button>
									<button type="button" class="fe-btn" v-if="!info" @click="unit = null">Cancel</button>
									<button v-if="info && canRemoveUnit" type="button" class="fe-del pj-delete" :class="{ armed: armed === 'unit' }" id="unit-remove" @click="removeUnit">{{ armed === 'unit' ? 'Sure? The project stays.' : 'Remove description' }}</button>
								</div>
								<div v-if="info && info.children.length" class="pj-parts" id="unit-children">
									<h3>Units below</h3>
									<button type="button" class="pj-part" v-for="c in info.children" :key="c.project_id" @click="open(c.project_id)">{{ c.title }} <span class="fe-muted">({{ levelLabel(c.level) }}<template v-if="c.ref_segment">, {{ c.ref_segment }}</template>)</span></button>
								</div>
								<div v-if="info" class="pj-parts" id="unit-preview">
									<h3>Preview</h3>
									<button type="button" class="fe-btn fe-btn-sm" id="preview-rad" :class="{ on: previewKind === 'rad' }" @click="preview('rad')">RAD description</button>
									<button type="button" class="fe-btn fe-btn-sm" id="preview-dc" :class="{ on: previewKind === 'dc' }" @click="preview('dc')">Dublin Core</button>
									<button type="button" class="fe-btn fe-btn-sm" v-if="previewKind" @click="preview('')">Close</button>
									<p v-if="previewNote" class="fe-muted" id="preview-note">{{ previewNote }}</p>
									<div ref="previewBody" id="preview-body" class="pj-preview" v-show="previewKind"></div>
								</div>
								<div v-if="info" class="pj-parts" id="unit-export">
									<h3>Export</h3>
									<a class="fe-btn fe-btn-sm" id="export-atom" :href="exportHref" download>AtoM CSV</a>
									<label class="pj-check pj-inline"><input type="checkbox" id="export-published" v-model="exportPublished"> only what is published</label>
									<p class="fe-muted pj-hint">This unit, the units below it and their TEI items, in the column layout of AtoM’s RAD CSV import.</p>
								</div>
								<div v-if="info && info.items.length" class="pj-parts" id="unit-items">
									<h3>Items <span class="fe-muted">({{ info.items.length }})</span></h3>
									<p class="pj-part-file" v-for="i in info.items" :key="i.file_id">{{ i.title || i.name }} <span class="fe-muted">{{ i.path }}</span></p>
								</div>
							</form>
						</section>
<?php } ?>

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

	<script>window.IPS_PROJECTS = <?php echo json_encode(['csrf' => $login_cookie->CsrfToken(), 'user' => $pj_user, 'admin' => $pj_admin, 'units' => $pj_units], JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
	<script src="projects-app.js?v=<?php echo filemtime(__DIR__.'/projects-app.js') ?>"></script>
</body>
</html>
