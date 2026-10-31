<?php
// One article for the editor: {success, article, terms, revisions (no bodies), sections (in use), issues (the Issues module's, when installed: id, label, issue_date, page_count)}, and the statuses and
// access levels a form may offer. For signed-in users (the editors).
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
ips_articles_user(false);
$id = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$article = $id ? ips_articles_get($id) : null;
if ($article === null) {
	ips_articles_refuse(404, 'That article does not exist.');
}
echo json_encode(['success' => true, 'article' => $article, 'terms' => ips_articles_terms_of($id), 'revisions' => ips_articles_revisions($id)] + ips_articles_form_options(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
