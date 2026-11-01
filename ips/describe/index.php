<?php
// The Describe page: the printed-book records of a folder on the left, the open one on the right. A record's open questions come first, each with a button to confirm that the truth is unknown,
// then the fields that answer them, the photographs (turn them, say what they show, move them to or from the tray), a live preview of the Dublin Core and RAD description, and Save.
// Data from record_list.php and record_get.php; writes post to record_save.php, record_status.php, photo_assign.php and json/json-photo_rotate.php (same-origin, the user's CSRF token in
// X-CSRF). Administrators only: everyone else is told so. No database: the records are TEI files, which this page changes through the endpoints and never by hand.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/endpoint.php';
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if (!$login_cookie->CheckIt()) {
	header('Location: ' . PROGRAM_WEB_BASE . 'login.php');
	exit;
}
$is_user = $login_cookie->username;
$is_admin = ips_current_user()[1];
$is_css = ['file_editor/file-editor.css', 'describe/describe.css'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Describe · <?php echo htmlspecialchars(SITE_NAME) ?></title>
<?php include HTML_TEMPLATES.'ui-head.php'; ?>
<?php foreach ($is_css as $css) { if (is_file(PROGRAM_BASE . $css)) { ?>
<link rel="stylesheet" href="<?php echo PROGRAM_WEB_BASE . $css ?>?v=<?php echo filemtime(PROGRAM_BASE . $css) ?>">
<?php } } ?>
</head>
<body class="fe">
<?php include __DIR__.'/header.php'; ?>

<?php if (!$is_admin) { ?>
	<main class="fe-page">
		<h1>Describe</h1>
		<p class="fe-error" role="alert" id="describe-admins-only">Describing books is for administrators. Ask one to give you an administrator account.</p>
	</main>
<?php } else { ?>
	<div id="dc-app" v-cloak>
		<main class="fe-page dc-wide">
			<div class="fe-titlerow">
				<div>
					<h1>Describe</h1>
					<p>Answer a book record's open questions here. Everything is saved into the record's own TEI file, with the earlier version kept; no photograph is ever changed.</p>
				</div>
				<label class="dc-folder"><span class="l">Records folder</span>
					<select class="fe-input" id="dc-folder" v-model="dir" @change="changeFolder"><option value="">Choose…</option><option v-for="f in folders" :key="f" :value="f">{{ f.replace(/^files\//, '') }}</option></select></label>
			</div>

			<div v-if="pending !== null" class="fe-notice is-warning" id="dc-dirty" role="alert"><strong>Unsaved changes</strong>
				<span class="fe-muted">Save this record before opening another, or leave the changes unsaved.</span>
				<button type="button" class="fe-btn fe-btn-sm" id="dc-dirty-save" @click="save(true)">Save and go on</button>
				<button type="button" class="fe-btn fe-btn-sm" id="dc-dirty-discard" @click="discardAndGo">Discard the changes</button>
				<button type="button" class="fe-btn fe-btn-sm" @click="pending = null">Stay</button>
			</div>
			<div v-if="conflict" class="fe-notice is-warning" id="dc-conflict" role="alert"><strong>Changed by someone else</strong>
				<span class="fe-muted">{{ conflict }}</span>
				<button type="button" class="fe-btn fe-btn-sm" id="dc-reload" @click="reload">Load their version</button></div>
			<p v-if="listError" class="fe-error" role="alert" id="dc-list-error">{{ listError }}</p>

			<div class="dc-layout" :class="{ 'dc-with-viewer': rec && form }" v-if="dir">
				<section class="dc-list" aria-label="Records">
					<div class="fe-filter"><label><input type="checkbox" id="dc-only" v-model="onlyOpen"> Only records with open questions</label></div>
					<div class="fe-table" id="dc-records">
						<button type="button" class="dc-item" v-for="r in shown" :key="r.id" :class="{ current: rec && rec.id === r.id }" :data-id="r.id" @click="go(r.id)">
							<span class="dc-title">{{ r.title }}</span>
							<span class="dc-meta"><span class="dc-dot" :class="dot(r)" :title="dotText(r)"></span> {{ dotText(r) }}<template v-if="r.reference"> · {{ r.reference }}</template></span>
						</button>
						<div v-if="!shown.length" class="fe-empty" id="dc-no-records">{{ loaded ? (records.length ? 'Every record is done.' : 'No records in this folder.') : 'Loading…' }}</div>
					</div>
					<p class="fe-muted dc-hint" v-if="loaded">{{ records.length }} records, {{ records.filter(isReady).length }} ready, {{ tray.length }} photographs in no book.</p>
				</section>

				<section class="dc-detail" aria-label="Record" id="dc-detail">
					<p v-if="!rec" class="fe-empty">Choose a record.</p>
					<p v-if="rec && rec.error" class="fe-error" role="alert" id="dc-record-error">{{ rec.error }}</p>
					<form v-if="rec && !rec.error && form" id="dc-form" @submit.prevent="save(false)" autocomplete="off">
						<h2 class="dc-h"><span class="dc-ref">{{ form.reference }}</span> {{ form.titles[0] ? form.titles[0].text : rec.id }}</h2>
						<p class="dc-line"><span class="fe-badge" :class="'is-' + form.status" id="dc-status-badge">{{ form.status }}</span>
							<span v-if="form.checked" class="fe-muted" id="dc-checked-by">checked by {{ form.checked.who || 'you' }}</span></p>

						<section class="dc-questions" id="dc-questions" aria-label="Questions to answer">
							<h3>Questions to answer <span class="fe-muted" id="dc-open-count">({{ openQuestions.length }})</span></h3>
							<p v-if="!openQuestions.length" class="dc-ok" id="dc-none-open">Nothing is open on this book.</p>
							<div v-for="q in openQuestions" :key="q.key" class="dc-q" :data-q="q.key">
								<p>{{ q.text }}</p>
								<p class="dc-qactions">
									<a href="#" class="fe-muted" @click.prevent="focusField(q.key)">Go to the field</a>
									<button type="button" class="fe-btn fe-btn-sm" :data-confirm="q.key" @click="confirm(q.key)">{{ confirmWords[q.key] }}</button>
								</p>
							</div>
						</section>

						<section class="dc-block" :class="{ 'dc-open': isOpen('title') }" id="dc-sec-title">
							<h3>Title</h3>
							<div v-for="(t, i) in form.titles" :key="i" class="dc-row">
								<input type="text" class="fe-input dc-grow" v-model="t.text" :aria-label="'Title ' + (i + 1)" :data-title="i">
								<select class="fe-input" v-model="t.type" :aria-label="'Kind of title ' + (i + 1)"><option value="main">Read from the title page</option><option value="desc">Described (supplied by me)</option><option value="translated">Translated (English, supplied)</option></select>
								<input type="text" class="fe-input dc-short" v-model="t.lang" maxlength="3" placeholder="is" :aria-label="'Language of title ' + (i + 1)">
								<button type="button" class="fe-del" :disabled="form.titles.length < 2" @click="form.titles.splice(i, 1)" :aria-label="'Remove title ' + (i + 1)">Remove</button>
							</div>
							<button type="button" class="fe-btn fe-btn-sm" id="dc-add-title" @click="form.titles.push({ text: '', type: 'translated', lang: 'en' })">Add a title</button>
						</section>

						<section class="dc-block" :class="{ 'dc-open': isOpen('author') }" id="dc-sec-author">
							<h3>Who made it</h3>
							<p class="fe-muted dc-hint">The catalogue's own text is shown as it is until you split it. Say who is the author, the editor and the translator.</p>
							<div v-for="(c, i) in form.contributors" :key="i" class="dc-row">
								<input type="text" class="fe-input dc-grow" v-model="c.name" :aria-label="'Name ' + (i + 1)" :data-person="i">
								<select class="fe-input" v-model="c.role" :aria-label="'Role of name ' + (i + 1)"><option v-for="(label, key) in roles" :key="key" :value="key">{{ label }}</option></select>
								<input v-if="c.role === 'contributor'" type="text" class="fe-input dc-short" v-model="c.resp" placeholder="what they did" :aria-label="'What name ' + (i + 1) + ' did'">
								<button type="button" class="fe-del" @click="form.contributors.splice(i, 1)" :aria-label="'Remove name ' + (i + 1)">Remove</button>
							</div>
							<div class="dc-actions">
								<button type="button" class="fe-btn fe-btn-sm" id="dc-add-person" @click="form.contributors.push({ name: '', role: 'author', resp: '' })">Add a name</button>
								<button type="button" class="fe-btn fe-btn-sm" id="dc-split" :disabled="!needsSplit" @click="splitNames">Split the names the catalogue gave</button>
							</div>
							<label class="l" for="dc-pseudonym">Pen name of (the real name, if the title page gives a pen name)</label>
							<input type="text" class="fe-input dc-wide" id="dc-pseudonym" v-model="form.pseudonym">
						</section>

						<section class="dc-block" id="dc-sec-imprint" :class="{ 'dc-open': isOpen('place') || isOpen('publisher') || isOpen('date') }">
							<h3>Where and when it was published</h3>
							<div class="dc-grid">
								<div :class="{ 'dc-open': isOpen('place') }" id="dc-sec-place"><label class="l" for="dc-place">Place</label><input type="text" class="fe-input dc-wide" id="dc-place" v-model="form.place"></div>
								<div :class="{ 'dc-open': isOpen('publisher') }" id="dc-sec-publisher"><label class="l" for="dc-publisher">Publisher</label><input type="text" class="fe-input dc-wide" id="dc-publisher" v-model="form.publisher"></div>
							</div>
							<div :class="{ 'dc-open': isOpen('date') }" id="dc-sec-date">
								<label class="l" for="dc-date-text">Date as printed (or your estimate in words)</label>
								<input type="text" class="fe-input dc-wide" id="dc-date-text" v-model="form.date.text" placeholder="1917, [c. 1892], 1888-92">
								<div class="dc-grid3">
									<div><label class="l" for="dc-when">Year (or full date)</label><input type="text" class="fe-input dc-wide" id="dc-when" v-model="form.date.when" placeholder="1917 or 1917-03-04"></div>
									<div><label class="l" for="dc-from">…or from</label><input type="text" class="fe-input dc-wide" id="dc-from" v-model="form.date.from" placeholder="1888"></div>
									<div><label class="l" for="dc-to">…to</label><input type="text" class="fe-input dc-wide" id="dc-to" v-model="form.date.to" placeholder="1892"></div>
								</div>
								<label class="l" for="dc-cert">How sure is the date</label>
								<select class="fe-input" id="dc-cert" v-model="form.date.cert"><option value="">Printed on the book</option><option value="low">An estimate: not very sure</option><option value="medium">Fairly sure</option><option value="high">Sure, though not printed</option></select>
							</div>
						</section>

						<section class="dc-block" id="dc-sec-edition" :class="{ 'dc-open': isOpen('edition') || isOpen('volume') }">
							<h3>Edition and volumes</h3>
							<label class="l" for="dc-edition">Edition (a number, or the statement as printed)</label>
							<input type="text" class="fe-input dc-wide" id="dc-edition" v-model="form.edition.text" :class="{ 'dc-open-field': isOpen('edition') }">
							<div id="dc-sec-volume" :class="{ 'dc-open': isOpen('volume') }">
								<div v-for="(s, i) in form.scopes" :key="i" class="dc-row">
									<select class="fe-input" v-model="s.unit" :aria-label="'What number ' + (i + 1) + ' counts'"><option value="">Unsplit</option><option value="volume">Volume</option><option value="issue">Issue</option></select>
									<input type="text" class="fe-input dc-grow" v-model="s.text" :aria-label="'Number ' + (i + 1)" :data-scope="i">
									<button type="button" class="fe-del" @click="form.scopes.splice(i, 1)" :aria-label="'Remove number ' + (i + 1)">Remove</button>
								</div>
								<button type="button" class="fe-btn fe-btn-sm" id="dc-add-scope" @click="form.scopes.push({ unit: 'volume', text: '' })">Add a volume or issue</button>
							</div>
							<div class="dc-grid3">
								<div><label class="l" for="dc-lang-main">Main language (code)</label><input type="text" class="fe-input dc-wide" id="dc-lang-main" v-model="form.language.main" maxlength="3"></div>
								<div><label class="l" for="dc-lang-others">Other languages</label><input type="text" class="fe-input dc-wide" id="dc-lang-others" v-model="form.language.others" placeholder="da no"></div>
								<div><label class="l" for="dc-lang-text">Language as named</label><input type="text" class="fe-input dc-wide" id="dc-lang-text" v-model="form.language.text"></div>
							</div>
						</section>

						<section class="dc-block" id="dc-sec-copy">
							<h3>This copy</h3>
							<p class="fe-muted dc-hint">Reference: {{ form.reference || '—' }}. What is true of this copy, not of the book as published.</p>
							<label class="l" for="dc-marks">Marks and inscriptions</label><textarea class="fe-input dc-wide" id="dc-marks" rows="2" v-model="form.marks"></textarea>
							<label class="l" for="dc-binding">Binding</label><textarea class="fe-input dc-wide" id="dc-binding" rows="2" v-model="form.binding"></textarea>
							<label class="l" for="dc-condition">Condition</label><textarea class="fe-input dc-wide" id="dc-condition" rows="2" v-model="form.condition"></textarea>
							<label class="l" for="dc-notes">Catalogue notes (one per line)</label><textarea class="fe-input dc-wide" id="dc-notes" rows="2" v-model="notesText"></textarea>
						</section>

						<section class="dc-block" id="dc-sec-photographs" :class="{ 'dc-open': isOpen('photographs') || isOpen('title-page') }">
							<h3>Photographs <span class="fe-muted">({{ form.photos.length }})</span></h3>
							<p v-if="!form.photos.length" class="fe-muted" id="dc-no-photos">No photograph is placed on this book yet.</p>
							<div class="dc-photos">
								<div class="dc-photo" :class="{ viewing: viewed && viewed.path === p.path }" v-for="(p, i) in recPhotos" :key="p.path" :data-photo="p.name">
									<button type="button" class="dc-picbtn" @click="view(p)" :aria-label="'Look at ' + p.name + ' beside the form'" :aria-pressed="viewed && viewed.path === p.path" data-look><span class="dc-pic" :class="{ 'dc-missing': p.missing }"><img v-if="!p.missing" :src="p.thumb" :alt="p.name" loading="lazy" :style="turnStyle(p)" :data-rot="p.rotation || null"><span v-else>missing</span></span></button>
									<div class="dc-file">{{ p.name }}</div>
									<select class="fe-input" :value="form.photos[i] && form.photos[i].role" @change="setRole(p, $event.target.value)" :aria-label="'What ' + p.name + ' shows'"><option v-for="r in photoRoles" :key="r" :value="r">{{ r.replace(/-/g, ' ') }}</option></select>
									<div class="dc-actions">
										<button type="button" class="fe-btn fe-btn-sm" :disabled="busy" @click="rotate(p, -90)" :aria-label="'Rotate ' + p.name + ' left'" data-rotate="left">⟲</button>
										<button type="button" class="fe-btn fe-btn-sm" :disabled="busy" @click="rotate(p, 90)" :aria-label="'Rotate ' + p.name + ' right'" data-rotate="right">⟳</button>
										<button type="button" class="fe-btn fe-btn-sm" :disabled="busy" @click="toTray(p)" data-totray>Not this book</button>
									</div>
								</div>
							</div>
							<details class="dc-tray" id="dc-tray" :open="trayOpen" @toggle="trayOpen = $event.target.open"><summary>Photographs in no book ({{ tray.length }}): add one to this book</summary>
								<div class="dc-photos">
									<div class="dc-photo" :class="{ viewing: viewed && viewed.path === p.path }" v-for="p in tray" :key="p.path" :data-tray="p.name">
										<button type="button" class="dc-picbtn" @click="view(p)" :aria-label="'Look at ' + p.name + ' beside the form'" data-look><span class="dc-pic"><img :src="p.thumb" :alt="p.name" loading="lazy" :style="turnStyle(p)"></span></button>
										<div class="dc-file">{{ p.name }}</div>
										<select class="fe-input" v-model="trayRole[p.name]" :aria-label="'What ' + p.name + ' shows'"><option v-for="r in photoRoles" :key="r" :value="r">{{ r.replace(/-/g, ' ') }}</option></select>
										<button type="button" class="fe-btn fe-btn-sm" :disabled="busy" @click="fromTray(p)" data-addphoto>Add to this book</button>
									</div>
								</div>
							</details>
						</section>

						<section class="dc-block" id="dc-sec-decisions" v-if="decisionList.length">
							<h3>Confirmed by a cataloguer</h3>
							<p class="dc-decision" v-for="d in decisionList" :key="d.key" :data-decision="d.key"><strong>{{ d.key }}</strong>: {{ d.text || 'confirmed' }} <span class="fe-muted">({{ d.who || 'you' }}{{ d.when ? ', ' + d.when : '' }})</span>
								<button type="button" class="fe-btn fe-btn-sm" @click="unconfirm(d.key)" :data-unconfirm="d.key">Ask the question again</button></p>
						</section>

						<section class="dc-block" id="dc-sec-state">
							<h3>Checking and publishing</h3>
							<label class="dc-check"><input type="checkbox" id="dc-checked" v-model="isChecked"> I have checked this record</label>
							<label class="l" for="dc-note">A note for the next cataloguer</label>
							<textarea class="fe-input dc-wide" id="dc-note" rows="2" v-model="form.cataloguer_note"></textarea>
							<label class="l" for="dc-status">Status</label>
							<div class="dc-row"><select class="fe-input" id="dc-status" v-model="statusChoice"><option value="draft">Draft</option><option value="published">Published</option><option value="private">Private</option></select>
								<button type="button" class="fe-btn" id="dc-apply-status" :disabled="busy || dirty || statusChoice === form.status" @click="applyStatus">Apply the status</button>
								<span v-if="dirty" class="fe-muted">Save your changes first.</span></div>
							<p class="fe-muted dc-hint">A record is published or made private only when no question is open.</p>
						</section>

						<p class="fe-error" v-if="error" role="alert" id="dc-error">{{ error }}</p>
						<div class="dc-actions dc-savebar">
							<button type="submit" class="fe-btn fe-btn-primary" id="dc-save" :disabled="busy || !dirty">Save</button>
							<span v-if="dirty" class="fe-muted" id="dc-unsaved">Unsaved changes</span><span v-else-if="saved" class="fe-muted" id="dc-saved">Saved.</span>
						</div>
					</form>

					<section v-if="rec && !rec.error && preview && preview.dc" class="dc-preview" id="dc-preview" aria-label="What readers will see">
						<h3>What readers will see <span class="fe-muted">(of the saved record)</span></h3>
						<div class="dc-rad" id="dc-rad" v-html="preview.rad"></div>
						<dl class="dc-dc"><template v-for="(row, i) in preview.dc" :key="i"><dt>{{ row[0] }}</dt><dd>{{ row[1] }}</dd></template></dl>
						<p v-if="preview.completeness && preview.completeness.length" class="fe-muted dc-hint">Still missing from the description: {{ preview.completeness.map(function (c) { return c[0]; }).join(', ') }}.</p>
					</section>
				</section>

				<aside class="dc-viewer" id="dc-viewer" aria-label="The photograph being described" v-if="rec && form">
					<template v-if="viewed">
						<div class="dc-vbar">
							<span class="dc-file" id="dc-vname">{{ viewed.name }}</span>
							<span class="fe-muted" id="dc-vwhere">{{ viewedIndex >= 0 ? (viewedIndex + 1) + ' of ' + recPhotos.length + ' on this book' : 'in no book' }}</span>
							<span class="dc-vtools">
								<button type="button" class="fe-btn fe-btn-sm" @click="step(-1)" :disabled="candidates.length < 2" aria-label="Previous photograph" data-vprev>‹</button>
								<button type="button" class="fe-btn fe-btn-sm" @click="step(1)" :disabled="candidates.length < 2" aria-label="Next photograph" data-vnext>›</button>
								<button type="button" class="fe-btn fe-btn-sm" v-for="z in zooms" :key="z.key" :class="{ 'is-on': zoom === z.value }" @click="zoom = z.value" :data-vzoom="z.key">{{ z.label }}</button>
								<button type="button" class="fe-btn fe-btn-sm" :disabled="busy || viewed.missing" @click="rotate(viewed, -90)" aria-label="Turn the view left" data-vrotate="left">⟲</button>
								<button type="button" class="fe-btn fe-btn-sm" :disabled="busy || viewed.missing" @click="rotate(viewed, 90)" aria-label="Turn the view right" data-vrotate="right">⟳</button>
								<a class="fe-btn fe-btn-sm" :href="viewed.full || '#'" target="_blank" rel="noopener" data-vopen>Open</a>
							</span>
						</div>
						<div class="dc-vbar" id="dc-vrole-row">
							<template v-if="viewedIndex >= 0">
								<label class="fe-muted">Shows <select class="fe-input" :value="form.photos[viewedIndex] && form.photos[viewedIndex].role" @change="setRole(viewed, $event.target.value)" aria-label="What the photograph being viewed shows" id="dc-vrole"><option v-for="r in photoRoles" :key="r" :value="r">{{ r.replace(/-/g, ' ') }}</option></select></label>
								<button type="button" class="fe-btn fe-btn-sm" :disabled="busy" @click="toTray(viewed)" data-vtotray>Not this book</button>
							</template>
							<template v-else>
								<label class="fe-muted">Shows <select class="fe-input" v-model="trayRole[viewed.name]" aria-label="What the photograph being viewed shows" id="dc-vrole"><option v-for="r in photoRoles" :key="r" :value="r">{{ r.replace(/-/g, ' ') }}</option></select></label>
								<button type="button" class="fe-btn fe-btn-sm" :disabled="busy" @click="fromTray(viewed)" data-vaddphoto>Add to this book</button>
							</template>
						</div>
						<div class="dc-stage" ref="stage" id="dc-stage">
							<span v-if="viewed.missing" class="dc-missing">This photograph is missing from the file store.</span>
							<div v-else class="dc-sheet" :style="sheetStyle" id="dc-sheet"><img :key="viewed.path" :src="viewed.full" :alt="viewed.name" @load="measure($event)" :style="imgStyle" id="dc-vimg"></div>
						</div>
						<div class="dc-strip" aria-label="This book's photographs and the ones in no book">
							<button type="button" class="dc-thumb" v-for="p in candidates" :key="p.path" :class="{ viewing: p.path === viewed.path, loose: !isOnBook(p) }" @click="view(p)" :title="p.name + (isOnBook(p) ? '' : ' (in no book)')" :data-strip="p.name"><img :src="p.thumb" :alt="p.name" :style="turnStyle(p)"></button>
						</div>
					</template>
					<p v-else class="fe-empty" id="dc-vnone">No photograph to show. Add one from the tray, or confirm that none survives.</p>
				</aside>
			</div>
			<p v-else class="fe-empty" id="dc-choose">{{ folders.length ? 'Choose a records folder.' : 'No records folder yet: tools/books-to-tei.py generate puts one in the file store.' }}</p>
		</main>
	</div>

	<script>window.IPS_DESCRIBE = <?php echo json_encode(['csrf' => $login_cookie->CsrfToken(), 'user' => $is_user, 'dir' => (isset($_GET['dir']) && is_string($_GET['dir'])) ? $_GET['dir'] : '', 'folders' => ips_describe_find_folders()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) ?>;</script>
	<script src="describe-app.js?v=<?php echo filemtime(__DIR__.'/describe-app.js') ?>"></script>
<?php } ?>
</body>
</html>
