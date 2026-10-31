<?php
// One version of an article with its body: GET id and revision. For signed-in users (the editors). The body is cleaned again here: a version saved under an older sanitiser is shown on the Articles
// page as HTML, and article_restore.php cleans it again before it becomes the article.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
ips_articles_user(false);
$id = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$revision = isset($_GET['revision']) && ctype_digit((string) $_GET['revision']) ? (int) $_GET['revision'] : 0;
$found = $id && $revision ? ips_articles_revision($id, $revision) : null;
if ($found === null) {
	ips_articles_refuse(404, 'That version does not exist.');
}
$found['body'] = ips_articles_clean($found['body']);
echo json_encode(['success' => true, 'revision' => $found], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
