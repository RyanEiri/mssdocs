<?php
// The Links page: every link by group with its group's order, Add, Edit, Move up / down and a two-click Delete. The list comes from list.php; writes post to
// save.php, move.php and delete.php (same-origin, the user's CSRF token in X-CSRF). It uses the file editor module's stylesheet for the app bar, buttons and dialog.
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
$ln_user = $login_cookie->username;
$result = $db->query("SELECT admin FROM users WHERE username=? LIMIT 1", [['type' => 's', 'value' => $ln_user]])->fetchArray();
$ln_admin = $result && (int) $result[0]['admin'] === 1;
$ln_css = ['file_editor/file-editor.css', 'links/links.css'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Links · <?php echo htmlspecialchars(SITE_NAME) ?></title>
<?php include HTML_TEMPLATES.'ui-head.php'; ?>
<?php foreach ($ln_css as $css) { if (is_file(PROGRAM_BASE . $css)) { ?>
<link rel="stylesheet" href="<?php echo PROGRAM_WEB_BASE . $css ?>?v=<?php echo filemtime(PROGRAM_BASE . $css) ?>">
<?php } } ?>
</head>
<body class="fe">
<?php include __DIR__.'/header.php'; ?>

	<div id="ln-app" v-cloak>
		<main class="fe-page">
			<div class="fe-titlerow">
				<div>
					<h1>Links</h1>
					<p>The web addresses on the site's Links page, in the order shown there. A hidden link stays here but not on the site.</p>
				</div>
				<button type="button" id="link-add" class="fe-btn fe-btn-primary" @click="openForm(null)">Add a link</button>
			</div>

			<div class="fe-filter">
				<input type="search" class="fe-input" id="link-filter" v-model="query" placeholder="Filter by name, description or address" aria-label="Filter the links" autocomplete="off" spellcheck="false">
			</div>

			<div class="fe-table" id="all-links">
				<template v-for="g in shown" :key="g.id">
					<div class="fe-group">{{ g.name }}<span>{{ g.links.length }} {{ g.links.length === 1 ? 'link' : 'links' }}</span></div>
					<div class="ln-row" v-for="(l, i) in g.links" :key="l.id">
						<span class="ln-main">
							<a class="ln-name" :href="href(l.url)" target="_blank" rel="noopener">{{ l.name }}</a><span v-if="l.hidden" class="fe-badge">Hidden</span>
							<span class="ln-desc" v-if="l.description">{{ l.description }}</span>
							<span class="ln-url">{{ l.url }}</span>
						</span>
						<span class="ln-actions">
							<button type="button" class="fe-del ln-move" :disabled="query || i === 0" @click="move(l, 'up')" :aria-label="'Move ' + l.name + ' up'">↑</button>
							<button type="button" class="fe-del ln-move" :disabled="query || i === g.links.length - 1" @click="move(l, 'down')" :aria-label="'Move ' + l.name + ' down'">↓</button>
							<button type="button" class="fe-del ln-edit" @click="openForm(l)">Edit</button>
							<button type="button" class="fe-del ln-delete" :class="{ armed: armed === l.id }" @click="remove(l)">{{ armed === l.id ? 'Sure?' : 'Delete' }}</button>
						</span>
					</div>
				</template>
				<div v-if="!shown.length" class="fe-empty">{{ loaded ? 'No links match.' : 'Loading…' }}</div>
				<p v-if="listError" class="fe-error" role="alert" style="padding:0 20px 12px">{{ listError }}</p>
			</div>
		</main>

		<div v-if="form" class="fe-scrim" @click.self="form = null" @keydown.esc="form = null">
			<form class="fe-dialog ln-dialog" id="link-form" role="dialog" aria-modal="true" aria-labelledby="link-form-title" autocomplete="off" @submit.prevent="save">
				<h2 id="link-form-title">{{ form.id ? 'Edit link' : 'Add a link' }}</h2>
				<label class="l" for="link-name">Name</label>
				<input type="text" class="fe-input ln-wide" id="link-name" ref="nameEl" v-model="form.name" maxlength="255" required>
				<label class="l" for="link-url">Web address</label>
				<input type="url" class="fe-input ln-wide" id="link-url" v-model="form.url" maxlength="2048" placeholder="https://" required>
				<label class="l" for="link-description">Description</label>
				<textarea class="fe-input ln-wide" id="link-description" v-model="form.description" rows="3" maxlength="1000"></textarea>
				<label class="l" for="link-group">Group</label>
				<select class="fe-input ln-wide" id="link-group" v-model.number="form.group_id" required>
					<option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
				</select>
				<label class="l">Other addresses <span class="fe-muted">(shown with the link as “Also: …”)</span></label>
				<div class="ln-also" v-for="(a, i) in form.also" :key="i">
					<input type="text" class="fe-input" v-model="a[0]" placeholder="Label" maxlength="255" :aria-label="'Label of other address ' + (i + 1)">
					<input type="url" class="fe-input" v-model="a[1]" placeholder="https://" maxlength="2048" :aria-label="'Other address ' + (i + 1)">
					<button type="button" class="fe-del" @click="form.also.splice(i, 1)" :aria-label="'Remove other address ' + (i + 1)">Remove</button>
				</div>
				<button type="button" class="fe-btn fe-btn-sm" id="link-add-also" v-if="form.also.length < maxAlso" @click="form.also.push(['', ''])">Add another address</button>
				<label class="ln-check"><input type="checkbox" id="link-hidden" v-model="form.hidden"> Hidden: keep it here but do not show it on the site</label>
				<p class="fe-error" v-if="formError" role="alert">{{ formError }}</p>
				<div class="actions">
					<button type="button" class="fe-btn" @click="form = null">Cancel</button>
					<button type="submit" class="fe-btn fe-btn-primary" id="link-save" :disabled="busy">Save</button>
				</div>
			</form>
		</div>
	</div>

	<script>window.IPS_LINKS = <?php echo json_encode(['csrf' => $login_cookie->CsrfToken(), 'maxAlso' => IPS_LINKS_MAX_ALSO], JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
	<script src="links-app.js?v=<?php echo filemtime(__DIR__.'/links-app.js') ?>"></script>
</body>
</html>
