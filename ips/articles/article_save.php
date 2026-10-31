<?php
// Makes an article (id 0 or missing) or saves one: title, summary, body (cleaned by the sanitiser), section, authors, keywords (terms), issue and page, image, SEO fields, slug. A new
// article is always a draft; its status is set by article_status.php. Every save that changes the title, summary or body adds a version. Any signed-in user writes and edits a draft; only an
// administrator edits an article that is not a draft, or changes its access. Answers the article, its keywords and its versions.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
[$user, $admin] = ips_articles_user(true);

$id = isset($_POST['id']) && ctype_digit((string) $_POST['id']) ? (int) $_POST['id'] : 0;
$existing = $id ? ips_articles_get($id) : null;
if ($id && $existing === null) {
	ips_articles_refuse(404, 'That article no longer exists.');
}
if ($existing && !ips_articles_may_edit($existing, $admin)) {
	ips_articles_refuse(403, 'Only an administrator changes an article that is published, scheduled or unpublished.');
}
[$v, $problem] = ips_articles_fields($_POST, $existing);
if ($problem !== null) {
	ips_articles_refuse(422, $problem);
}
$access = $_POST['access'] ?? ($existing['access'] ?? 'open');
if (!is_string($access) || !isset(IPS_ARTICLE_ACCESS[$access])) {
	ips_articles_refuse(422, 'The access is open or signed-in.');
}
if (!$admin && $access !== ($existing['access'] ?? 'open')) {
	ips_articles_refuse(403, 'Only an administrator sets who may read an article.');
}
$terms = null;
if (isset($_POST['terms'])) {
	[$terms, $problem] = ips_articles_terms($_POST['terms']);
	if ($problem !== null) {
		ips_articles_refuse(422, $problem);
	}
}
$slug = $existing['slug'] ?? null;
if (isset($_POST['slug']) && is_string($_POST['slug']) && trim($_POST['slug']) !== '' && trim($_POST['slug']) !== $slug) {
	$wanted = trim($_POST['slug']);
	if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $wanted) || strlen($wanted) > 100) {
		ips_articles_refuse(422, 'The address ends in lower-case letters, digits and single hyphens, at most 100 characters.');
	}
	if ($db->query("SELECT id FROM articles WHERE slug=? AND id<>? LIMIT 1", [['type' => 's', 'value' => $wanted], ['type' => 'i', 'value' => $id]])->size() > 0) {
		ips_articles_refuse(422, 'Another article has that address.');
	}
	$slug = $wanted;
}
$search = ips_articles_search_text($v['title'], $v['summary'], $v['body']);
$nullable = function ($value, $type) { return ['type' => $type, 'value' => $value]; };

if (!$existing) {
	$slug = $slug ?? ips_articles_slug($v['title']);
	$id = $db->query("INSERT INTO articles (title, slug, summary, body, search_text, section, authors, status, access, issue_id, issue_page, image, seo_title, seo_description, created_by, updated_by)
		VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, ?, ?, ?, ?, ?, ?)", [
		['type' => 's', 'value' => $v['title']], ['type' => 's', 'value' => $slug], ['type' => 's', 'value' => $v['summary']], ['type' => 's', 'value' => $v['body']], ['type' => 's', 'value' => $search],
		['type' => 's', 'value' => $v['section']], ['type' => 's', 'value' => $v['authors']], ['type' => 's', 'value' => $access], $nullable($v['issue_id'], 'i'), $nullable($v['issue_page'], 'i'),
		['type' => 's', 'value' => $v['image']], ['type' => 's', 'value' => $v['seo_title']], ['type' => 's', 'value' => $v['seo_description']], ['type' => 's', 'value' => $user], ['type' => 's', 'value' => $user],
	])->getId();
} else {
	$db->query("UPDATE articles SET title=?, slug=?, summary=?, body=?, search_text=?, section=?, authors=?, access=?, issue_id=?, issue_page=?, image=?, seo_title=?, seo_description=?, updated_by=? WHERE id=?", [
		['type' => 's', 'value' => $v['title']], ['type' => 's', 'value' => $slug], ['type' => 's', 'value' => $v['summary']], ['type' => 's', 'value' => $v['body']], ['type' => 's', 'value' => $search],
		['type' => 's', 'value' => $v['section']], ['type' => 's', 'value' => $v['authors']], ['type' => 's', 'value' => $access], $nullable($v['issue_id'], 'i'), $nullable($v['issue_page'], 'i'),
		['type' => 's', 'value' => $v['image']], ['type' => 's', 'value' => $v['seo_title']], ['type' => 's', 'value' => $v['seo_description']], ['type' => 's', 'value' => $user], ['type' => 'i', 'value' => $id],
	]);
}
if ($terms !== null) {
	ips_articles_set_terms($id, $terms);
}
ips_articles_add_revision($id, $v, $user, is_string($_POST['note'] ?? null) ? (string) $_POST['note'] : '');
echo json_encode(['success' => true, 'article' => ips_articles_get($id), 'terms' => ips_articles_terms_of($id), 'revisions' => ips_articles_revisions($id)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
