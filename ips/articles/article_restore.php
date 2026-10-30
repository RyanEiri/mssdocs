<?php
// Puts an earlier version back: POST id and revision. The version's body is cleaned again (what an older sanitiser let through is not trusted) and saved as a NEW version, so the history is never
// rewritten. Same rule for who may as article_save.php. Answers the article and its versions.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
[$user, $admin] = ips_articles_user(true);
$id = isset($_POST['id']) && ctype_digit((string) $_POST['id']) ? (int) $_POST['id'] : 0;
$revision = isset($_POST['revision']) && ctype_digit((string) $_POST['revision']) ? (int) $_POST['revision'] : 0;
$article = $id ? ips_articles_get($id) : null;
$old = $article && $revision ? ips_articles_revision($id, $revision) : null;
if ($article === null || $old === null) {
	ips_articles_refuse(404, 'That version does not exist.');
}
if (!ips_articles_may_edit($article, $admin)) {
	ips_articles_refuse(403, 'Only an administrator changes an article that is published, scheduled or unpublished.');
}
$v = ['title' => $old['title'], 'summary' => $old['summary'], 'body' => ips_articles_clean($old['body'])];
$db->query("UPDATE articles SET title=?, summary=?, body=?, search_text=?, updated_by=? WHERE id=?", [['type' => 's', 'value' => $v['title']], ['type' => 's', 'value' => $v['summary']], ['type' => 's', 'value' => $v['body']],
	['type' => 's', 'value' => ips_articles_search_text($v['title'], $v['summary'], $v['body'])], ['type' => 's', 'value' => $user], ['type' => 'i', 'value' => $id]]);
ips_articles_add_revision($id, $v, $user, 'Restored version ' . $revision);
echo json_encode(['success' => true, 'article' => ips_articles_get($id), 'terms' => ips_articles_terms_of($id), 'revisions' => ips_articles_revisions($id)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
