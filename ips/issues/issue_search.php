<?php
// Searches the text of the issues for the Issues page: GET q (words, "a phrase", a prefix*, -word), offset, limit (at most 50), sort (date, or relevance where the server's full-text index
// can be trusted). For signed-in users (the editors): they search every issue, drafts included, and see snippets of all. A site's own public pages use issues/public.php instead.
// Answers {success, query, engine, total, results: [{issue_id, page, label, issue_date, volume, number, series, status, access, restricted, snippet, snippet_html, score}], error}.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
require_once __DIR__.'/search_lib.php';
[$user] = ips_issues_user(false);
session_write_close();
$result = ips_issues_search($_GET['q'] ?? '', $user, ['offset' => $_GET['offset'] ?? 0, 'limit' => $_GET['limit'] ?? IPS_ISSUES_PAGE_SIZE, 'sort' => $_GET['sort'] ?? 'date']);
if ($result['error'] !== null) {
	ips_issues_refuse(422, $result['error']);
}
echo json_encode(['success' => true] + $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
