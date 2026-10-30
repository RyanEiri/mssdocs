<?php
// The Issues page: the issues on the left (newest first), the open one on the right (its description, the state of its text, its PDF), and a form to make a new one from a PDF in the file
// store or from an external address. Data from issue_list.php, issue_get.php and source_list.php; writes post to issue_create.php, issue_save.php, issue_extract.php and issue_remove.php
// (same-origin, the user's CSRF token in X-CSRF). It uses the file editor module's stylesheet for the app bar, buttons and table.
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
$is_user = $login_cookie->username;
$is_admin = ips_current_user()[1];
$is_ready = ips_issues_ready();
$is_css = ['file_editor/file-editor.css', 'issues/issues.css'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Issues · <?php echo htmlspecialchars(SITE_NAME) ?></title>
<?php include HTML_TEMPLATES.'ui-head.php'; ?>
<?php foreach ($is_css as $css) { if (is_file(PROGRAM_BASE . $css)) { ?>
<link rel="stylesheet" href="<?php echo PROGRAM_WEB_BASE . $css ?>?v=<?php echo filemtime(PROGRAM_BASE . $css) ?>">
<?php } } ?>
</head>
<body class="fe">
<?php include __DIR__.'/header.php'; ?>

<?php if (!$is_ready) { ?>
	<main class="fe-page">
		<h1>Issues</h1>
		<p class="fe-error" role="alert" id="issues-not-ready">The issues tables have not been created yet. Ask the site's administrator to run <code>issues/schema.sql</code>.</p>
	</main>
<?php } else { ?>
	<div id="is-app" v-cloak>
		<main class="fe-page">
			<div class="fe-titlerow">
				<div>
					<h1>Issues</h1>
					<p>A publication's issues as PDFs. Upload the PDF in the Files page, then make an issue from it here; its text is read so that it can be searched.</p>
				</div>
				<button type="button" id="issue-add" class="fe-btn fe-btn-primary" @click="openNew">New issue</button>
			</div>

			<section class="is-search" aria-label="Search the text of the issues">
				<form id="text-search" @submit.prevent="runSearch(true)" role="search">
					<label class="l" for="search-q">Search the text</label>
					<div class="is-search-row">
						<input type="search" class="fe-input" id="search-q" v-model="search.q" placeholder="words, “a phrase”, prefix*, -excluded" autocomplete="off" spellcheck="false" maxlength="200">
						<button type="submit" class="fe-btn fe-btn-primary" id="search-go" :disabled="search.busy">Search</button>
					</div>
				</form>
				<p v-if="search.error" class="fe-error" role="alert" id="search-error">{{ search.error }}</p>
				<div v-if="search.done" id="search-results" aria-live="polite">
					<p class="fe-muted pj-hint" id="search-count">{{ search.total }} {{ search.total === 1 ? 'page' : 'pages' }} found</p>
					<ol class="is-hits">
						<li v-for="r in search.results" :key="r.issue_id + '-' + r.page" class="is-hit" :data-issue="r.issue_id" :data-page="r.page">
							<a :href="'file.php?id=' + r.issue_id + '#page=' + r.page" target="_blank" rel="noopener" class="is-hit-title">{{ r.label || ('Issue ' + r.issue_id) }}<template v-if="r.issue_date"> · {{ r.issue_date }}</template> · page {{ r.page }}</a>
							<span v-if="r.status !== 'published'" class="is-state" :class="r.status">{{ statusLabel(r.status) }}</span>
							<a href="#" class="fe-muted is-open" @click.prevent="open(r.issue_id)">open issue</a>
							<p class="is-snippet"><template v-for="(part, k) in r.snippet_parts" :key="k"><mark v-if="part[1]">{{ part[0] }}</mark><template v-else>{{ part[0] }}</template></template></p>
						</li>
					</ol>
					<button v-if="search.results.length < search.total" type="button" class="fe-btn fe-btn-sm" id="search-more" :disabled="search.busy" @click="runSearch(false)">Show more</button>
				</div>
			</section>

			<div v-if="gateWarning" class="fe-notice is-warning" id="gate-warning" role="alert"><strong>Not private</strong><span class="fe-muted">{{ gateWarning }}</span></div>

			<div class="pj-layout">
				<section class="pj-list" aria-label="Issues">
					<div class="fe-filter">
						<input type="search" class="fe-input" id="issue-filter" v-model="query" placeholder="Filter by label, date or number" aria-label="Filter the issues" autocomplete="off" spellcheck="false">
					</div>
					<div class="fe-table" id="all-issues">
						<button type="button" class="pj-item" v-for="i in shown" :key="i.id" :class="{ current: current && current.id === i.id }" :data-id="i.id" @click="open(i.id)">
							<span class="pj-title">{{ title(i) }}</span>
							<span class="pj-meta">{{ i.issue_date || 'No date' }}<template v-if="i.volume || i.number"> · {{ [i.volume && 'vol. ' + i.volume, i.number && 'no. ' + i.number].filter(Boolean).join(', ') }}</template>
								· <span class="is-state" :class="i.status">{{ statusLabel(i.status) }}</span>
								<span v-if="stateText(i)" class="fe-badge" :class="'is-' + i.extraction">{{ stateText(i) }}</span></span>
						</button>
						<div v-if="!shown.length" class="fe-empty" id="no-issues">{{ loaded ? (issues.length ? 'No issues match.' : 'No issues yet.') : 'Loading…' }}</div>
					</div>
					<p v-if="listError" class="fe-error" role="alert">{{ listError }}</p>
				</section>

				<section class="pj-detail" aria-label="Issue" id="issue-detail">
					<p v-if="!form && !creating" class="fe-empty">Choose an issue, or make a new one.</p>

					<form v-if="creating" id="issue-new" @submit.prevent="create" autocomplete="off">
						<h2>New issue</h2>
						<div class="is-choice" role="radiogroup" aria-label="Where the issue is">
							<label><input type="radio" id="source-pdf" value="pdf" v-model="creating.source"> A PDF in the file store</label>
							<label><input type="radio" id="source-url" value="url" v-model="creating.source"> Hosted elsewhere (an address only)</label>
						</div>
						<template v-if="creating.source === 'pdf'">
							<label class="l" for="source-filter">PDF</label>
							<input type="search" class="fe-input pj-wide" id="source-filter" v-model="creating.q" @input="findSources" placeholder="Part of the file name or folder" autocomplete="off" spellcheck="false">
							<div class="is-sources" id="source-list" role="listbox" aria-label="PDFs in the file store">
								<button type="button" v-for="f in creating.files" :key="f.path" role="option" class="pj-item" :class="{ current: creating.path === f.path }" :aria-selected="creating.path === f.path ? 'true' : 'false'" :data-path="f.path" @click="pick(f)">
									<span class="pj-title">{{ f.name }}</span><span class="pj-meta">{{ f.folder || 'files' }} · {{ size(f.size) }}</span>
								</button>
								<p v-if="creating.searched && !creating.files.length" class="fe-muted" id="no-sources">No PDF found. Upload one in the Files page first.</p>
							</div>
						</template>
						<template v-else>
							<label class="l" for="new-url">Address</label>
							<input type="url" class="fe-input pj-wide" id="new-url" v-model="creating.external_url" maxlength="1000" placeholder="https://timarit.is/…">
						</template>
						<div class="pj-two">
							<div><label class="l" for="new-label">Label</label><input type="text" class="fe-input pj-wide" id="new-label" v-model="creating.label" maxlength="255" placeholder="Lögberg-Heimskringla, 1 November 2026"></div>
							<div><label class="l" for="new-date">Date (ISO 8601)</label><input type="text" class="fe-input pj-wide" id="new-date" v-model="creating.issue_date" maxlength="10" placeholder="2026-11-01"></div>
						</div>
						<div class="pj-two">
							<div><label class="l" for="new-volume">Volume</label><input type="text" class="fe-input pj-wide" id="new-volume" v-model="creating.volume" maxlength="40"></div>
							<div><label class="l" for="new-number">Number</label><input type="text" class="fe-input pj-wide" id="new-number" v-model="creating.number" maxlength="40"></div>
						</div>
						<label class="l" for="new-series">Publication (when the site has more than one)</label>
						<input type="text" class="fe-input pj-wide" id="new-series" v-model="creating.series" maxlength="80">
						<p class="fe-error" v-if="createError" role="alert" id="create-error">{{ createError }}</p>
						<div class="pj-actions">
							<button type="submit" class="fe-btn fe-btn-primary" id="issue-create" :disabled="busy">Make the issue</button>
							<button type="button" class="fe-btn" @click="creating = null">Cancel</button>
						</div>
						<p class="fe-muted pj-hint">The issue starts as a draft, for signed-in readers. {{ admin ? '' : 'An administrator publishes it.' }}</p>
					</form>

					<template v-if="form">
						<form id="issue-form" @submit.prevent="save" autocomplete="off">
							<p class="is-line" id="issue-state-line">
								<span class="fe-badge" :class="'is-' + form.extraction" id="issue-state">{{ stateText(form) || (form.has_pdf ? '' : 'Hosted elsewhere') }}</span>
								<span class="fe-muted" id="issue-state-note">{{ form.extraction_note }}</span>
								<button v-if="form.has_pdf && canRead" type="button" class="fe-btn fe-btn-sm" id="issue-reread" :disabled="reading || form.extraction === 'running' || form.extraction === 'pending'" @click="reread">Read the text again</button>
							</p>
							<p v-if="pages && form.has_pdf" class="fe-muted pj-hint" id="issue-pages">{{ pages.with_text }} of {{ pages.pages }} pages have text<template v-if="pages.corrected"> · {{ pages.corrected }} corrected</template></p>
							<div class="pj-two">
								<div><label class="l" for="issue-label">Label</label><input type="text" class="fe-input pj-wide" id="issue-label" v-model="form.label" maxlength="255" :disabled="!canEdit"></div>
								<div><label class="l" for="issue-date">Date (ISO 8601)</label><input type="text" class="fe-input pj-wide" id="issue-date" v-model="form.issue_date" maxlength="10" placeholder="2026-11-01" :disabled="!canEdit"></div>
							</div>
							<div class="pj-two">
								<div><label class="l" for="issue-volume">Volume</label><input type="text" class="fe-input pj-wide" id="issue-volume" v-model="form.volume" maxlength="40" :disabled="!canEdit"></div>
								<div><label class="l" for="issue-number">Number</label><input type="text" class="fe-input pj-wide" id="issue-number" v-model="form.number" maxlength="40" :disabled="!canEdit"></div>
							</div>
							<label class="l" for="issue-series">Publication</label>
							<input type="text" class="fe-input pj-wide" id="issue-series" v-model="form.series" maxlength="80" :disabled="!canEdit">
							<label class="l" for="issue-url">External address</label>
							<input type="url" class="fe-input pj-wide" id="issue-url" v-model="form.external_url" maxlength="1000" placeholder="https://timarit.is/…" :disabled="!canEdit">
							<label class="l" for="issue-notes">Notes</label>
							<textarea class="fe-input pj-wide" id="issue-notes" v-model="form.notes" rows="3" maxlength="5000" :disabled="!canEdit"></textarea>
							<div class="pj-two">
								<div>
									<label class="l" for="issue-status">Status</label>
									<select class="fe-input pj-wide" id="issue-status" v-model="form.status" :disabled="!admin"><option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.label }}</option></select>
								</div>
								<div>
									<label class="l" for="issue-access">Who may read it</label>
									<select class="fe-input pj-wide" id="issue-access" v-model="form.access" :disabled="!admin"><option v-for="a in accessLevels" :key="a.id" :value="a.id">{{ a.label }}</option></select>
								</div>
							</div>
							<p v-if="!admin" class="fe-muted pj-hint" id="issue-admin-hint">Only an administrator can publish an issue, make it private, or choose who may read it.</p>
							<p v-if="!canEdit" class="fe-muted pj-hint" id="issue-locked">This issue is {{ form.status }}: only an administrator can change it.</p>
							<p class="fe-error" v-if="formError" role="alert" id="issue-error">{{ formError }}</p>
							<div class="pj-actions">
								<button type="submit" class="fe-btn fe-btn-primary" id="issue-save" :disabled="busy || !canEdit">Save changes</button>
								<button v-if="form.has_pdf && pages && pages.pages" type="button" class="fe-btn" id="issue-correct" :aria-expanded="correcting ? 'true' : 'false'" @click="toggleCorrect">{{ correcting ? 'Close the text view' : 'Correct the text' }}</button>
								<a v-if="form.has_pdf" class="fe-btn" id="issue-pdf" :href="pdfHref" target="_blank" rel="noopener">Open the PDF</a>
								<a v-if="form.external_url" class="fe-btn" id="issue-external" :href="form.external_url" target="_blank" rel="noopener noreferrer">Open the external address</a>
								<button v-if="canRemove" type="button" class="fe-del pj-delete" :class="{ armed: armed }" id="issue-remove" @click="remove">{{ armed ? 'Sure? The PDF is removed too.' : 'Remove issue' }}</button>
							</div>
						</form>

						<section v-if="correcting" class="is-correct" id="correct" aria-label="Correct the text of a page">
							<div class="is-correct-bar">
								<button type="button" class="fe-btn fe-btn-sm" id="correct-prev" :disabled="!correcting.pages.length || correcting.page <= 1" @click="goTo(correcting.page - 1)" aria-label="Previous page">&lsaquo;</button>
								<label class="l" for="correct-page" style="margin:0">Page</label>
								<select class="fe-input" id="correct-page" :value="correcting.page" @change="goTo(Number($event.target.value))"><option v-for="p in correcting.pages" :key="p.page" :value="p.page">{{ p.page }}{{ p.corrected ? ' (corrected)' : '' }}{{ p.has_text ? '' : ' (no text)' }}</option></select>
								<button type="button" class="fe-btn fe-btn-sm" id="correct-next" :disabled="correcting.page >= correcting.pages.length" @click="goTo(correcting.page + 1)" aria-label="Next page">&rsaquo;</button>
								<span class="fe-muted" id="correct-status">{{ correctStatus }}</span>
							</div>
							<div v-if="correcting.pending" class="fe-notice" id="correct-dirty" role="alert"><strong>Unsaved changes</strong>
								<span class="fe-muted">Save the correction of page {{ correcting.page }} before going to page {{ correcting.pending }}, or leave it unsaved.</span>
								<button type="button" class="fe-btn fe-btn-sm" id="correct-save-go" @click="saveCorrection(true)">Save and go on</button>
								<button type="button" class="fe-btn fe-btn-sm" id="correct-discard" @click="discardAndGo">Discard the changes</button>
							</div>
							<div class="is-correct-split">
								<iframe id="correct-pdf" class="is-pdf" :src="pdfAt(correcting.page)" title="The PDF page"></iframe>
								<div>
									<textarea class="fe-input pj-wide is-text" id="correct-text" v-model="correcting.text" :readonly="!canEdit" spellcheck="false" aria-label="The text of the page"></textarea>
									<p v-if="!canEdit" class="fe-muted pj-hint" id="correct-locked">This issue is {{ form.status }}: only an administrator can correct its text.</p>
									<p class="fe-error" v-if="correcting.error" role="alert" id="correct-error">{{ correcting.error }}</p>
									<div class="pj-actions">
										<button type="button" class="fe-btn fe-btn-primary" id="correct-save" :disabled="!canEdit || !dirty || correcting.busy" @click="saveCorrection(false)">Save the correction</button>
										<button v-if="correcting.corrected" type="button" class="fe-del" :class="{ armed: correcting.armed }" id="correct-restore" :disabled="!canEdit" @click="restoreCorrection">{{ correcting.armed ? 'Sure? The correction is lost.' : 'Restore the original' }}</button>
										<button v-if="correcting.corrected" type="button" class="fe-btn fe-btn-sm" id="correct-original-toggle" @click="correcting.showOriginal = !correcting.showOriginal">{{ correcting.showOriginal ? 'Hide' : 'Show' }} the original</button>
									</div>
									<pre v-if="correcting.corrected && correcting.showOriginal" class="is-original" id="correct-original">{{ correcting.raw }}</pre>
								</div>
							</div>
						</section>

						<section class="is-toc" id="issue-toc" aria-label="Contents">
							<h2>Contents <span class="fe-muted">({{ toc.length }})</span></h2>
							<p v-if="!toc.length" class="fe-muted pj-hint" id="toc-empty">No contents yet. Add the articles of the issue with their pages.</p>
							<div v-for="(e, i) in toc" :key="i" class="is-toc-row" :data-row="i">
								<select class="fe-input" v-model="e.kind" :disabled="!canEdit" :aria-label="'Kind of entry ' + (i + 1)"><option v-for="k in tocKinds" :key="k.id" :value="k.id">{{ k.label }}</option></select>
								<input type="text" class="fe-input is-toc-title" v-model="e.title" maxlength="500" placeholder="Title" :disabled="!canEdit" :aria-label="'Title of entry ' + (i + 1)">
								<input type="number" min="1" class="fe-input is-toc-page" v-model="e.page_from" placeholder="From" :disabled="!canEdit" :aria-label="'First page of entry ' + (i + 1)">
								<input type="number" min="1" class="fe-input is-toc-page" v-model="e.page_to" placeholder="To" :disabled="!canEdit" :aria-label="'Last page of entry ' + (i + 1)">
								<input type="text" class="fe-input is-toc-author" v-model="e.author" maxlength="255" placeholder="Author" :disabled="!canEdit" :aria-label="'Author of entry ' + (i + 1)">
								<span class="pj-file-actions">
									<a v-if="form.has_pdf && Number(e.page_from) > 0" :href="pdfAt(Number(e.page_from), true)" target="_blank" rel="noopener" class="fe-muted" :aria-label="'Open page ' + e.page_from">open</a>
									<button type="button" class="fe-del" :disabled="!canEdit || i === 0" @click="moveToc(i, -1)" :aria-label="'Move entry ' + (i + 1) + ' up'">↑</button>
									<button type="button" class="fe-del" :disabled="!canEdit || i === toc.length - 1" @click="moveToc(i, 1)" :aria-label="'Move entry ' + (i + 1) + ' down'">↓</button>
									<button type="button" class="fe-del" :disabled="!canEdit" @click="toc.splice(i, 1)" :aria-label="'Remove entry ' + (i + 1)">Remove</button>
								</span>
							</div>
							<p class="fe-error" v-if="tocError" role="alert" id="toc-error">{{ tocError }}</p>
							<div class="pj-actions">
								<button type="button" class="fe-btn" id="toc-add" :disabled="!canEdit" @click="addToc">Add an entry</button>
								<button type="button" class="fe-btn fe-btn-primary" id="toc-save" :disabled="!canEdit || tocBusy" @click="saveToc">Save the contents</button>
								<span v-if="tocSaved" class="fe-muted" id="toc-saved">Saved.</span>
							</div>
						</section>
					</template>
				</section>
			</div>
		</main>
	</div>

	<script>window.IPS_ISSUES = <?php echo json_encode(['csrf' => $login_cookie->CsrfToken(), 'user' => $is_user, 'admin' => $is_admin], JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
	<script src="issues-app.js?v=<?php echo filemtime(__DIR__.'/issues-app.js') ?>"></script>
<?php } ?>
</body>
</html>
