<?php
// Removes an article, its keywords and its versions. An administrator may remove any article; the person who made a draft may remove it.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
[$user, $admin] = ips_articles_user(true);
$id = isset($_POST['id']) && ctype_digit((string) $_POST['id']) ? (int) $_POST['id'] : 0;
$article = $id ? ips_articles_get($id) : null;
if ($article === null) {
	ips_articles_refuse(404, 'That article no longer exists.');
}
if (!$admin && !($article['created_by'] === $user && $article['status'] === 'draft')) {
	ips_articles_refuse(403, 'Only an administrator, or the person who made a draft, can remove an article.');
}
foreach (['article_terms', 'article_revisions'] as $table) {
	$db->query("DELETE FROM $table WHERE article_id=?", [['type' => 'i', 'value' => $id]]);
}
$db->query("DELETE FROM articles WHERE id=?", [['type' => 'i', 'value' => $id]]);
echo json_encode(['success' => true]);
