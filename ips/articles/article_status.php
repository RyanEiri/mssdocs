<?php
// Sets an article's status: draft, scheduled (with publish_at, a UTC date and time in ISO 8601: 2026-11-05T14:30), published or unpublished. Only an administrator. A scheduled article is read as
// published once its time has passed (no job runs). published_at is set the first time it is published; publish_at is kept only while scheduled. Answers the article.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
[$user, $admin] = ips_articles_user(true);
if (!$admin) {
	ips_articles_refuse(403, 'Only an administrator publishes, schedules or unpublishes an article.');
}
$id = isset($_POST['id']) && ctype_digit((string) $_POST['id']) ? (int) $_POST['id'] : 0;
$article = $id ? ips_articles_get($id) : null;
if ($article === null) {
	ips_articles_refuse(404, 'That article no longer exists.');
}
$status = $_POST['status'] ?? '';
if (!is_string($status) || !isset(IPS_ARTICLE_STATUSES[$status])) {
	ips_articles_refuse(422, 'The status is draft, scheduled, published or unpublished.');
}
$at = null;
if ($status === 'scheduled') {
	$at = ips_articles_datetime($_POST['publish_at'] ?? null);
	if ($at === null) {
		ips_articles_refuse(422, 'A scheduled article needs the date and time it appears, as 2026-11-05T14:30 (UTC).');
	}
}
if (in_array($status, ['scheduled', 'published'], true) && $article['title'] === '') {
	ips_articles_refuse(422, 'An article needs a title before it is published.');
}
$db->query("UPDATE articles SET status=?, publish_at=?, published_at=CASE WHEN ? = 'published' AND published_at IS NULL THEN NOW() ELSE published_at END, updated_by=? WHERE id=?", [
	['type' => 's', 'value' => $status], ['type' => 's', 'value' => $at], ['type' => 's', 'value' => $status], ['type' => 's', 'value' => $user], ['type' => 'i', 'value' => $id],
]);
echo json_encode(['success' => true, 'article' => ips_articles_get($id)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
