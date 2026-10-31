<?php
// The Articles page: the articles on the left (newest update first), the open one on the right: its description, the body in CKEditor (a plain text box when CKEditor is not installed), the
// status and schedule (administrators), the versions, and a preview of what the sanitiser kept. Data from article_list.php and article_get.php; writes post to article_save.php,
// article_status.php, article_restore.php and article_remove.php (same-origin, the user's CSRF token in X-CSRF). It uses the file editor module's stylesheet for the app bar, buttons and table.
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
$is_ready = ips_articles_ready();
$has_ckeditor = is_file(PROGRAM_BASE . 'file_editor/ckeditor/ckeditor.js');
$is_css = ['file_editor/file-editor.css', 'articles/articles.css'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Articles · <?php echo htmlspecialchars(SITE_NAME) ?></title>
<?php include HTML_TEMPLATES.'ui-head.php'; ?>
<?php foreach ($is_css as $css) { if (is_file(PROGRAM_BASE . $css)) { ?>
<link rel="stylesheet" href="<?php echo PROGRAM_WEB_BASE . $css ?>?v=<?php echo filemtime(PROGRAM_BASE . $css) ?>">
<?php } } ?>
<?php if ($is_ready && $has_ckeditor) { ?>
<script src="<?php echo PROGRAM_WEB_BASE ?>file_editor/ckeditor/ckeditor.js"></script>
<?php } ?>
</head>
<body class="fe">
<?php include __DIR__.'/header.php'; ?>

<?php if (!$is_ready) { ?>
	<main class="fe-page">
		<h1>Articles</h1>
		<p class="fe-error" role="alert" id="articles-not-ready">The articles tables have not been created yet. Ask the site's administrator to run <code>articles/schema.sql</code>.</p>
	</main>
<?php } else { ?>
	<div id="ar-app" v-cloak>
		<main class="fe-page">
			<div class="fe-titlerow">
				<div>
					<h1>Articles</h1>
					<p>Stories with a title, byline and body. Anyone signed in can write a draft; an administrator publishes it.</p>
				</div>
				<button type="button" id="article-add" class="fe-btn fe-btn-primary" @click="openNew">New article</button>
			</div>

			<div v-if="pendingOpen !== null" class="fe-notice is-warning" id="article-dirty" role="alert"><strong>Unsaved changes</strong>
				<span class="fe-muted">Save this article before opening another, or leave the changes unsaved.</span>
				<button type="button" class="fe-btn fe-btn-sm" id="dirty-save-go" @click="save(true)">Save and go on</button>
				<button type="button" class="fe-btn fe-btn-sm" id="dirty-discard" @click="discardAndGo">Discard the changes</button>
				<button type="button" class="fe-btn fe-btn-sm" id="dirty-stay" @click="pendingOpen = null">Stay</button>
			</div>

			<div class="ar-layout">
				<section class="ar-list" aria-label="Articles">
					<div class="fe-filter">
						<input type="search" class="fe-input" id="article-filter" v-model="query" placeholder="Filter by title, author or section" aria-label="Filter the articles" autocomplete="off" spellcheck="false">
					</div>
					<div class="fe-table" id="all-articles">
						<button type="button" class="ar-item" v-for="a in shown" :key="a.id" :class="{ current: form && form.id === a.id }" :data-id="a.id" @click="go(a.id)">
							<span class="ar-title">{{ a.title || 'Untitled' }}</span>
							<span class="ar-meta"><span class="ar-state" :class="a.effective_status">{{ stateLabel(a) }}</span>
								<template v-if="a.section"> · {{ a.section }}</template><template v-if="a.authors"> · {{ a.authors }}</template></span>
						</button>
						<div v-if="!shown.length" class="fe-empty" id="no-articles">{{ loaded ? (articles.length ? 'No articles match.' : 'No articles yet.') : 'Loading…' }}</div>
					</div>
					<p v-if="listError" class="fe-error" role="alert">{{ listError }}</p>
				</section>

				<section class="ar-detail" aria-label="Article" id="article-detail">
					<p v-if="!form" class="fe-empty">Choose an article, or write a new one.</p>
					<form v-show="form" id="article-form" @submit.prevent="save(false)" autocomplete="off">
						<template v-if="form">
							<p class="ar-line" id="article-state-line">
								<span class="ar-state" :class="form.effective_status" id="article-state">{{ form.id ? stateLabel(form) : 'New draft' }}</span>
								<span v-if="form.id" class="fe-muted">by {{ form.created_by }}, changed {{ form.updated_at }} by {{ form.updated_by }}</span>
							</p>
							<label class="l" for="article-title">Title</label>
							<input type="text" class="fe-input ar-wide" id="article-title" v-model="form.title" maxlength="255" :disabled="!canEdit">
							<label class="l" for="article-summary">Summary</label>
							<textarea class="fe-input ar-wide" id="article-summary" v-model="form.summary" rows="2" maxlength="1000" :disabled="!canEdit"></textarea>
							<div class="ar-two">
								<div><label class="l" for="article-authors">Authors (separate with ;)</label><input type="text" class="fe-input ar-wide" id="article-authors" v-model="form.authors" maxlength="500" :disabled="!canEdit"></div>
								<div><label class="l" for="article-section">Section</label><input type="text" class="fe-input ar-wide" id="article-section" v-model="form.section" maxlength="80" list="article-sections" :disabled="!canEdit">
									<datalist id="article-sections"><option v-for="s in sections" :key="s" :value="s"></option></datalist></div>
							</div>
							<label class="l" for="article-body">Body</label>
							<div class="ar-body"><textarea id="article-body" class="fe-input ar-wide" rows="16" v-model="bodyText" :readonly="!canEdit" spellcheck="true"></textarea></div>
							<p v-if="!hasEditor" class="fe-muted ar-hint" id="article-plain">CKEditor is not installed, so the body is HTML in a plain box. Whatever you write is cleaned when you save.</p>
							<label class="l" for="article-terms">Keywords (separate with commas)</label>
							<input type="text" class="fe-input ar-wide" id="article-terms" v-model="termsText" maxlength="1200" :disabled="!canEdit">
							<div class="ar-two">
								<div><label class="l" for="article-issue">Issue</label>
									<select class="fe-input ar-wide" id="article-issue" v-model="form.issue_id" :disabled="!canEdit || !issues.length"><option :value="null">None</option><option v-for="i in issues" :key="i.id" :value="i.id">{{ i.label || ('Issue ' + i.id) }}<template v-if="i.issue_date"> · {{ i.issue_date }}</template></option></select></div>
								<div><label class="l" for="article-issue-page">Page in the issue</label><input type="number" min="1" max="9999" class="fe-input ar-wide" id="article-issue-page" v-model="form.issue_page" :disabled="!canEdit || !form.issue_id"></div>
							</div>
							<details class="ar-more"><summary>Address, image and search engines</summary>
								<label class="l" for="article-slug">Address (the end of the article's web address)</label>
								<input type="text" class="fe-input ar-wide" id="article-slug" v-model="form.slug" maxlength="100" :disabled="!canEdit || !form.id" :placeholder="form.id ? '' : 'made from the title when you save'">
								<label class="l" for="article-image">Image address</label>
								<input type="text" class="fe-input ar-wide" id="article-image" v-model="form.image" maxlength="1000" placeholder="https://… or /ips/upload/files/…" :disabled="!canEdit">
								<label class="l" for="article-seo-title">Title for search engines</label>
								<input type="text" class="fe-input ar-wide" id="article-seo-title" v-model="form.seo_title" maxlength="255" :disabled="!canEdit">
								<label class="l" for="article-seo-description">Description for search engines</label>
								<textarea class="fe-input ar-wide" id="article-seo-description" v-model="form.seo_description" rows="2" maxlength="320" :disabled="!canEdit"></textarea>
							</details>
							<p v-if="!canEdit" class="fe-muted ar-hint" id="article-locked">This article is {{ form.status }}: only an administrator can change it.</p>
							<p class="fe-error" v-if="formError" role="alert" id="article-error">{{ formError }}</p>
							<div class="ar-actions">
								<button type="submit" class="fe-btn fe-btn-primary" id="article-save" :disabled="busy || !canEdit">Save</button>
								<button type="button" class="fe-btn" id="article-preview-toggle" :aria-expanded="preview ? 'true' : 'false'" :disabled="!form.id || dirty" @click="preview = !preview">{{ preview ? 'Close the preview' : 'Preview' }}</button>
								<span v-if="dirty" class="fe-muted" id="article-unsaved">Unsaved changes</span>
								<span v-else-if="saved" class="fe-muted" id="article-saved">Saved.</span>
								<button v-if="form.id && canRemove" type="button" class="fe-del ar-delete" :class="{ armed: armed }" id="article-remove" @click="remove">{{ armed ? 'Sure? The versions go too.' : 'Remove article' }}</button>
							</div>
						</template>
					</form>

					<template v-if="form && form.id">
						<section v-if="preview" class="ar-preview" id="article-preview" aria-label="Preview">
							<h2>{{ form.title }}</h2>
							<p class="fe-muted" v-if="form.authors">{{ form.authors }}</p>
							<div class="ar-text" id="article-preview-body" v-html="form.body"></div>
							<p class="fe-muted ar-hint">This is the saved body as the sanitiser kept it; it is what readers get.</p>
						</section>

						<section class="ar-status" id="article-status" aria-label="Status" v-if="admin">
							<h2>Publishing</h2>
							<div class="ar-two">
								<div><label class="l" for="status-select">Status</label>
									<select class="fe-input ar-wide" id="status-select" v-model="statusChoice"><option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.label }}</option></select></div>
								<div v-if="statusChoice === 'scheduled'"><label class="l" for="status-at">Appears on (UTC)</label>
									<input type="datetime-local" class="fe-input ar-wide" id="status-at" v-model="publishAt"></div>
							</div>
							<label class="l" for="article-access">Who may read it</label>
							<select class="fe-input ar-wide" id="article-access" v-model="form.access"><option v-for="a in accessLevels" :key="a.id" :value="a.id">{{ a.label }}</option></select>
							<p class="fe-muted ar-hint">The reading level is saved with the article (Save). A scheduled article is read as published once its time has passed.</p>
							<p class="fe-error" v-if="statusError" role="alert" id="status-error">{{ statusError }}</p>
							<div class="ar-actions"><button type="button" class="fe-btn fe-btn-primary" id="status-apply" :disabled="busy || dirty" @click="applyStatus">Apply the status</button>
								<span v-if="dirty" class="fe-muted">Save your changes first.</span></div>
						</section>
						<p v-else class="fe-muted ar-hint" id="article-admin-hint">Only an administrator can publish an article or choose who may read it.</p>

						<section class="ar-versions" id="article-versions" aria-label="Versions">
							<h2>Versions <span class="fe-muted">({{ revisions.length }})</span></h2>
							<div v-for="r in revisions" :key="r.revision" class="ar-version" :data-revision="r.revision">
								<span><strong>{{ r.revision }}</strong> · {{ r.saved_at }} · {{ r.saved_by }}<template v-if="r.note"> · {{ r.note }}</template></span>
								<span class="ar-version-actions">
									<button type="button" class="fe-btn fe-btn-sm" :data-view="r.revision" @click="viewRevision(r.revision)">View</button>
									<button type="button" class="fe-btn fe-btn-sm" :data-restore="r.revision" :disabled="!canEdit || busy || dirty" @click="restore(r.revision)">Restore</button>
								</span>
							</div>
							<div v-if="viewing" class="ar-viewing" id="revision-view"><h3>{{ viewing.title }} <span class="fe-muted">(version {{ viewing.revision }})</span></h3><div class="ar-text" v-html="viewing.body"></div>
								<button type="button" class="fe-btn fe-btn-sm" @click="viewing = null">Close</button></div>
						</section>
					</template>
				</section>
			</div>
		</main>
	</div>

	<script>window.IPS_ARTICLES = <?php echo json_encode(['csrf' => $login_cookie->CsrfToken(), 'user' => $is_user, 'admin' => $is_admin, 'ckeditor' => $has_ckeditor], JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
	<script src="articles-app.js?v=<?php echo filemtime(__DIR__.'/articles-app.js') ?>"></script>
<?php } ?>
</body>
</html>
