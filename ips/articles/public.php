<?php
/* The articles for a site's own public pages: a library, not pages (the base does not decide what a public page looks like). A site includes this file and calls the functions with the path
 * of the IPS's php/classes/config.php (read, not included: php/standalone_db.php). Everything here is for an anonymous visitor or a named viewer, and only articles that are effectively
 * published are ever returned: status published, or scheduled with a publish_at that has passed (UTC, compared in SQL; `$now`, a Unix time, is for tests). A draft or unpublished article is
 * never returned, and a published article for signed-in readers only comes back as a pointer (title, summary, byline, `readable` false, no body) to an anonymous visitor.
 *
 *   ips_articles_public_list($config, $opts = [], $viewer = null)    ['total', 'articles']: opts section, term, limit (50 at most), offset, now
 *   ips_articles_public_find($config, $id_or_slug, $viewer = null)   one article with its body (when readable), keywords and issue, or null
 *   ips_articles_public_search($config, $q, $opts = [], $viewer = null)   the same query syntax as issues (issues/search_lib.php); snippets of at most 30 words
 *   ips_articles_public_sections($config)                            the sections with published articles and how many
 *   ips_articles_feed_xml($articles, $site) / ips_articles_sitemap_xml($articles, $site) / ips_articles_seo($article, $site)   pure: Atom feed, sitemap, meta tags and JSON-LD
 * $site is ['name' => ..., 'base_url' => 'https://example.org', 'article_path' => '/news/{slug}', 'feed_path' => '/news/feed.xml']; {id} and {slug} are replaced in article_path.
 * A function that reads the database returns null when it cannot (every other page of the site keeps working). The body that comes back has been through the sanitiser again.
 */
require_once __DIR__ . '/../php/access.php';
require_once __DIR__ . '/../php/standalone_db.php';
require_once __DIR__ . '/../php/text_fold.php';
require_once __DIR__ . '/../issues/search_lib.php';   // the query syntax, the engine choice and the snippets are the issues' (one implementation)
require_once __DIR__ . '/sanitize.php';

const IPS_ARTICLES_PUBLIC_PAGE = 20;
const IPS_ARTICLES_PUBLIC_MAX = 50;

function ips_articles_public_connect($config) {
	return ips_standalone_connect($config, ['articles', 'article_terms']);
}

// [sql, params] for "effectively published" on the alias `a`; the moment is bound, so the same condition serves every query and a test can set the clock.
function ips_articles_public_visible($now = null) {
	return ["(a.status = 'published' OR (a.status = 'scheduled' AND a.publish_at IS NOT NULL AND a.publish_at <= ?))", [['type' => 's', 'value' => gmdate('Y-m-d H:i:s', $now ?? time())]]];
}

function ips_articles_public_has_issues() {
	global $db;
	static $has = null;
	return $has ?? ($has = $db->query("SHOW TABLES LIKE 'issues'")->size() > 0);
}

// What every query selects: the article without its body, the moment it appeared (publish_at while scheduled, else published_at) and its issue when that issue is published.
function ips_articles_public_columns($body) {
	$issue = ips_articles_public_has_issues() ? 'i.id AS linked_issue_id, i.label AS issue_label, i.issue_date AS issue_date' : 'NULL AS linked_issue_id, NULL AS issue_label, NULL AS issue_date';
	return "a.id, a.slug, a.title, a.summary, " . ($body ? 'a.body' : "'' AS body") . ", a.section, a.authors, a.access, a.image, a.seo_title, a.seo_description, a.issue_page, a.updated_at,
		CASE WHEN a.status = 'scheduled' THEN a.publish_at ELSE a.published_at END AS shown_at, $issue";
}

function ips_articles_public_from() {
	return 'FROM articles a' . (ips_articles_public_has_issues() ? " LEFT JOIN issues i ON i.id = a.issue_id AND i.status = 'published'" : '');
}

function ips_articles_public_utc($datetime) {
	return $datetime === null || $datetime === '' ? null : gmdate('Y-m-d\TH:i:s\Z', strtotime($datetime . ' UTC'));
}

function ips_articles_public_row($r, $viewer) {
	$readable = ips_access_can_read($viewer, 'published', $r['access']);
	$authors = array_values(array_filter(array_map('trim', preg_split('/\s*(?:;|\band\b|\bog\b)\s*/u', (string) $r['authors'])), 'strlen'));
	return ['id' => (int) $r['id'], 'slug' => $r['slug'], 'title' => $r['title'], 'summary' => $r['summary'], 'section' => $r['section'], 'authors' => $r['authors'], 'author_list' => $authors,
		'access' => $r['access'], 'readable' => $readable, 'image' => $r['image'], 'seo_title' => $r['seo_title'], 'seo_description' => $r['seo_description'],
		'published_at' => ips_articles_public_utc($r['shown_at']), 'updated_at' => ips_articles_public_utc($r['updated_at']),
		'issue' => $r['issue_label'] === null ? null : ['id' => (int) $r['linked_issue_id'], 'label' => $r['issue_label'], 'issue_date' => $r['issue_date'], 'page' => $r['issue_page'] === null ? null : (int) $r['issue_page']],
		'body' => $readable ? ips_articles_clean((string) $r['body']) : ''];
}

function ips_articles_public_terms($id) {
	global $db;
	$out = [];
	$result = $db->query("SELECT term FROM article_terms WHERE article_id = ? ORDER BY term_key", [['type' => 'i', 'value' => $id]]);
	while ($r = $result->fetch()) {
		$out[] = $r['term'];
	}
	return $out;
}

function ips_articles_public_list($config, $opts = [], $viewer = null) {
	if (!ips_articles_public_connect($config)) {
		return null;
	}
	global $db;
	[$where, $params] = ips_articles_public_visible($opts['now'] ?? null);
	$from = ips_articles_public_from();
	if (isset($opts['section']) && is_string($opts['section']) && $opts['section'] !== '') {
		$where .= ' AND a.section = ?';
		$params[] = ['type' => 's', 'value' => $opts['section']];
	}
	if (isset($opts['term']) && is_string($opts['term']) && ips_fold($opts['term']) !== '') {
		$where .= ' AND EXISTS (SELECT 1 FROM article_terms t WHERE t.article_id = a.id AND t.term_key = ?)';
		$params[] = ['type' => 's', 'value' => ips_fold($opts['term'])];
	}
	$total = (int) $db->query("SELECT COUNT(*) AS n $from WHERE $where", $params)->fetchArray()[0]['n'];
	$params[] = ['type' => 'i', 'value' => max(1, min((int) ($opts['limit'] ?? IPS_ARTICLES_PUBLIC_PAGE), IPS_ARTICLES_PUBLIC_MAX))];
	$params[] = ['type' => 'i', 'value' => max(0, (int) ($opts['offset'] ?? 0))];
	$out = [];
	$result = $db->query("SELECT " . ips_articles_public_columns(false) . " $from WHERE $where ORDER BY shown_at DESC, a.id DESC LIMIT ? OFFSET ?", $params);
	while ($r = $result->fetch()) {
		$out[] = ips_articles_public_row($r, $viewer);
	}
	return ['total' => $total, 'articles' => $out];
}

function ips_articles_public_find($config, $key, $viewer = null, $now = null) {
	if (!ips_articles_public_connect($config)) {
		return null;
	}
	global $db;
	[$where, $params] = ips_articles_public_visible($now);
	$by_id = (is_int($key) || (is_string($key) && ctype_digit($key)));
	$where .= $by_id ? ' AND a.id = ?' : ' AND a.slug = ?';
	$params[] = $by_id ? ['type' => 'i', 'value' => (int) $key] : ['type' => 's', 'value' => (string) $key];
	$rows = $db->query("SELECT " . ips_articles_public_columns(true) . " " . ips_articles_public_from() . " WHERE $where LIMIT 1", $params)->fetchArray();
	if (!$rows || (!$by_id && $rows[0]['slug'] !== (string) $key)) { // the collation ignores case and trailing spaces; an address is exact
		return null;
	}
	$article = ips_articles_public_row($rows[0], $viewer);
	$article['terms'] = $article['readable'] ? ips_articles_public_terms($article['id']) : [];
	return $article;
}

function ips_articles_public_sections($config, $now = null) {
	if (!ips_articles_public_connect($config)) {
		return null;
	}
	global $db;
	[$where, $params] = ips_articles_public_visible($now);
	$out = [];
	$result = $db->query("SELECT a.section, COUNT(*) AS n FROM articles a WHERE $where AND a.section <> '' GROUP BY a.section ORDER BY a.section", $params);
	while ($r = $result->fetch()) {
		$out[$r['section']] = (int) $r['n'];
	}
	return $out;
}

/* Searches the published articles (title, summary and body, accent-folded). $opts: offset, limit, engine ('fulltext' or 'like', for tests), now, section. Returns what ips_issues_search() does:
 * query, error, engine, total and results; each result is the article (without body) with `snippet`, `snippet_html` and `snippet_parts` (null when the viewer may not read it: a pointer). */
function ips_articles_public_search($config, $q, $opts = [], $viewer = null) {
	if (!ips_articles_public_connect($config)) {
		return null;
	}
	global $db;
	$out = ['query' => is_string($q) ? $q : '', 'error' => null, 'engine' => null, 'total' => 0, 'results' => []];
	$parsed = ips_issues_parse_query($q);
	if ($parsed['error'] !== null) {
		$out['error'] = $parsed['error'];
		return $out;
	}
	$clauses = $parsed['clauses'];
	if (!ips_issues_positive($clauses)) {
		return $out;
	}
	$engine = $opts['engine'] ?? ips_issues_choose_engine($clauses, ips_issues_ft_settings());
	$out['engine'] = $engine;
	[$where, $params] = ips_articles_public_visible($opts['now'] ?? null);
	$match = [];
	$match_params = [];
	if ($engine === 'fulltext') {
		$match[] = 'MATCH(a.search_text) AGAINST (? IN BOOLEAN MODE)';
		$match_params[] = ['type' => 's', 'value' => ips_issues_boolean($clauses)];
		[$check, $checks] = ips_issues_like(array_values(array_filter($clauses, function ($c) { return $c['not'] || count($c['words']) > 1; })));
	} else {
		[$check, $checks] = ips_issues_like($clauses);
	}
	if ($check !== '') {
		$match[] = $check;
		$match_params = array_merge($match_params, $checks);
	}
	// ips_issues_like() names the column p.search_text; here it is the article's
	$match = array_map(function ($m) { return str_replace('p.search_text', 'a.search_text', $m); }, $match);
	$where .= ' AND ' . implode(' AND ', $match);
	$params = array_merge($params, $match_params);
	if (isset($opts['section']) && is_string($opts['section']) && $opts['section'] !== '') {
		$where .= ' AND a.section = ?';
		$params[] = ['type' => 's', 'value' => $opts['section']];
	}
	$from = ips_articles_public_from();
	$out['total'] = (int) $db->query("SELECT COUNT(*) AS n $from WHERE $where", $params)->fetchArray()[0]['n'];
	$params[] = ['type' => 'i', 'value' => max(1, min((int) ($opts['limit'] ?? IPS_ARTICLES_PUBLIC_PAGE), IPS_ARTICLES_PUBLIC_MAX))];
	$params[] = ['type' => 'i', 'value' => max(0, (int) ($opts['offset'] ?? 0))];
	$result = $db->query("SELECT " . ips_articles_public_columns(true) . " $from WHERE $where ORDER BY shown_at DESC, a.id DESC LIMIT ? OFFSET ?", $params);
	while ($r = $result->fetch()) {
		$article = ips_articles_public_row($r, $viewer);
		$snippet = null;
		if ($article['readable']) {
			foreach ([ips_articles_text($article['body']), $article['summary'], $article['title']] as $text) {
				if (($snippet = ips_issues_snippet($text, $clauses)) !== null) {
					break;
				}
			}
		}
		unset($article['body']);
		$article['snippet'] = $snippet ? $snippet['text'] : null;
		$article['snippet_html'] = $snippet ? $snippet['html'] : null;
		$article['snippet_parts'] = $snippet ? $snippet['parts'] : null;
		$out['results'][] = $article;
	}
	return $out;
}

// ---- feed, sitemap, meta tags (pure) ---------------------------------------------------------------------------------------------------------

function ips_articles_h($text) {
	return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Text for an XML element: the XML 1.0 forbidden control characters are dropped, the rest escaped.
function ips_articles_x($text) {
	return htmlspecialchars(preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', (string) $text), ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function ips_articles_url_of($article, $site) {
	return rtrim($site['base_url'], '/') . str_replace(['{id}', '{slug}'], [(string) $article['id'], rawurlencode($article['slug'])], $site['article_path']);
}

/* An Atom feed of the articles given (the caller passes the open ones from ips_articles_public_list: a feed is public, so an article for signed-in readers is left out here as well). Summaries
 * only, no bodies. */
function ips_articles_feed_xml($articles, $site) {
	$open = array_values(array_filter($articles, function ($a) { return $a['access'] === 'open'; }));
	$base = rtrim($site['base_url'], '/');
	$updated = '1970-01-01T00:00:00Z';
	foreach ($open as $a) {
		$updated = max($updated, $a['updated_at'] ?? '', $a['published_at'] ?? '');
	}
	$self = $base . ($site['feed_path'] ?? '/feed.xml');
	$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<feed xmlns="http://www.w3.org/2005/Atom">' . "\n"
		. '  <title>' . ips_articles_x($site['name']) . '</title>' . "\n" . '  <id>' . ips_articles_x($self) . '</id>' . "\n"
		. '  <link rel="self" href="' . ips_articles_x($self) . '"/>' . "\n" . '  <link rel="alternate" href="' . ips_articles_x($base . '/') . '"/>' . "\n"
		. '  <updated>' . $updated . '</updated>' . "\n";
	foreach ($open as $a) {
		$url = ips_articles_url_of($a, $site);
		$xml .= "  <entry>\n    <title>" . ips_articles_x($a['title']) . "</title>\n    <id>" . ips_articles_x($url) . "</id>\n    <link rel=\"alternate\" href=\"" . ips_articles_x($url) . "\"/>\n"
			. '    <published>' . ($a['published_at'] ?? $updated) . "</published>\n    <updated>" . ($a['updated_at'] ?? $a['published_at'] ?? $updated) . "</updated>\n";
		foreach ($a['author_list'] ?? [] as $name) {
			$xml .= '    <author><name>' . ips_articles_x($name) . "</name></author>\n";
		}
		if ($a['section'] !== '') {
			$xml .= '    <category term="' . ips_articles_x($a['section']) . "\"/>\n";
		}
		if ($a['summary'] !== '') {
			$xml .= '    <summary>' . ips_articles_x($a['summary']) . "</summary>\n";
		}
		$xml .= "  </entry>\n";
	}
	return $xml . "</feed>\n";
}

// A sitemap of the open articles given.
function ips_articles_sitemap_xml($articles, $site) {
	$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
	foreach ($articles as $a) {
		if ($a['access'] !== 'open') {
			continue;
		}
		$xml .= '  <url><loc>' . ips_articles_x(ips_articles_url_of($a, $site)) . '</loc>' . ($a['updated_at'] ? '<lastmod>' . substr($a['updated_at'], 0, 10) . '</lastmod>' : '') . "</url>\n";
	}
	return $xml . "</urlset>\n";
}

/* The head of an article's page: ['title', 'description', 'canonical', 'meta' (tags, escaped), 'json_ld' (a script element holding NewsArticle), 'robots']. An article for signed-in readers
 * gets noindex and no description, so the page does not tell a search engine what it hides. The JSON is encoded with the HEX flags, so no title can close the script element. */
function ips_articles_seo($article, $site) {
	$open = $article['access'] === 'open';
	$title = $article['seo_title'] !== '' ? $article['seo_title'] : $article['title'];
	$description = $open ? ($article['seo_description'] !== '' ? $article['seo_description'] : $article['summary']) : '';
	$canonical = ips_articles_url_of($article, $site);
	$meta = '<title>' . ips_articles_h($title . ' – ' . $site['name']) . "</title>\n" . '<link rel="canonical" href="' . ips_articles_h($canonical) . "\">\n";
	if ($description !== '') {
		$meta .= '<meta name="description" content="' . ips_articles_h($description) . "\">\n";
	}
	$meta .= '<meta property="og:type" content="article">' . "\n" . '<meta property="og:title" content="' . ips_articles_h($title) . "\">\n" . '<meta property="og:url" content="' . ips_articles_h($canonical) . "\">\n";
	if ($description !== '') {
		$meta .= '<meta property="og:description" content="' . ips_articles_h($description) . "\">\n";
	}
	$image = $open ? (string) $article['image'] : '';
	if ($image !== '') {
		$image = preg_match('~^https?://~i', $image) ? $image : rtrim($site['base_url'], '/') . $image;
		$meta .= '<meta property="og:image" content="' . ips_articles_h($image) . "\">\n";
	}
	$robots = $open ? null : 'noindex';
	if ($robots !== null) {
		$meta .= '<meta name="robots" content="noindex">' . "\n";
	}
	$ld = null;
	if ($open) {
		$data = ['@context' => 'https://schema.org', '@type' => 'NewsArticle', 'headline' => mb_substr($title, 0, 110), 'mainEntityOfPage' => $canonical,
			'author' => array_map(function ($n) { return ['@type' => 'Person', 'name' => $n]; }, $article['author_list'] ?? []), 'publisher' => ['@type' => 'Organization', 'name' => $site['name']]];
		foreach (['datePublished' => 'published_at', 'dateModified' => 'updated_at'] as $key => $field) {
			if (!empty($article[$field])) {
				$data[$key] = $article[$field];
			}
		}
		if ($description !== '') {
			$data['description'] = $description;
		}
		if ($image !== '') {
			$data['image'] = $image;
		}
		if ($article['section'] !== '') {
			$data['articleSection'] = $article['section'];
		}
		$ld = '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script>';
	}
	return ['title' => $title, 'description' => $description, 'canonical' => $canonical, 'meta' => $meta, 'json_ld' => $ld, 'robots' => $robots];
}
