<?php
/* Searching the text of issues (design: docs/mssdocs-issues-design.md, sections 3.5 and 10). The page text is stored folded (php/text_fold.php) and the query is folded the same way, so
 * `Bjorg` finds `Björg` and `Thor` finds `Þór`. It works with the IPS's own database object ($db: query($sql, $typed_params) with fetch(), fetchArray(), size()) and with the small adapter
 * issues/public.php makes for a site's own pages, so one implementation serves both.
 *
 * Query syntax: words (all must be on the page), "a phrase", a word* (a prefix), and -word or -"a phrase" (must not be). Everything is folded and only the folded words reach the SQL, as bound
 * values: no operator of MariaDB's boolean mode can come from a query. Two ways to match, with the same meaning (whole words, folded):
 *   fulltext  MATCH ... AGAINST in boolean mode on the required words: fast; used only when it is safe, that is when no word is shorter than innodb_ft_min_token_size (default 3, so
 *             `ás` and `og` would be lost) and none is on the server's stopword list (`the`, `de`, ...), which the index ignores. The index only NARROWS the pages: a phrase is then
 *             checked as adjacent words with LIKE, and an excluded word or phrase with NOT LIKE. (Measured on the real L-H issues: InnoDB's own phrase search missed pages that LIKE found,
 *             a different few on each run, so it is not used. Whole words and prefixes were found every time.)
 *   like      the same words as LIKE on the folded text: always right, slower; used otherwise.
 * Who sees what: an anonymous visitor only searches published issues; a draft or private issue is for signed-in editors. For an issue the viewer may not read (published for signed-in readers,
 * searched by a visitor) a page is still listed, as a pointer, with no snippet and no text.
 */
require_once __DIR__ . '/../php/text_fold.php';
require_once __DIR__ . '/../php/access.php';

const IPS_ISSUES_QUERY_MAX = 200;   // characters
const IPS_ISSUES_CLAUSES_MAX = 10;
const IPS_ISSUES_TERM_MAX = 50;     // characters in one word
const IPS_ISSUES_PAGE_SIZE = 20;
const IPS_ISSUES_PAGE_SIZE_MAX = 50;
const IPS_ISSUES_SNIPPET_WORDS = 30;

// ---- the query ----------------------------------------------------------------------------------------------------------------------------

/* The clauses of a query: ['clauses' => [['words' => [folded words], 'prefix' => bool, 'not' => bool], ...], 'error' => null | message]. A clause of one word is a word, of several a phrase
 * (a quoted phrase, or a word the folding splits: vín-arterta). A clause that folds to nothing (punctuation only) is dropped. */
function ips_issues_parse_query($q) {
	if (!is_string($q)) {
		return ['clauses' => [], 'error' => 'The query must be text.'];
	}
	if (mb_strlen($q, 'UTF-8') > IPS_ISSUES_QUERY_MAX) {
		return ['clauses' => [], 'error' => 'The query is longer than ' . IPS_ISSUES_QUERY_MAX . ' characters.'];
	}
	if (!preg_match('//u', $q)) {
		return ['clauses' => [], 'error' => 'The query is not valid text.'];
	}
	preg_match_all('/(-?)"([^"]*)"|(-?)([^\s"]+)/u', $q, $found, PREG_SET_ORDER);
	$clauses = [];
	$seen = [];
	foreach ($found as $m) {
		$is_word = isset($m[4]) && $m[4] !== ''; // the second alternative of the pattern matched (a bare word) rather than the first (a quoted phrase)
		$not = $is_word ? $m[3] === '-' : $m[1] === '-';
		$text = $is_word ? $m[4] : $m[2];
		$prefix = $is_word && substr($text, -1) === '*';
		$words = array_values(array_filter(explode(' ', ips_fold($text)), 'strlen'));
		if (!$words) {
			continue;
		}
		foreach ($words as $word) {
			if (mb_strlen($word, 'UTF-8') > IPS_ISSUES_TERM_MAX) {
				return ['clauses' => [], 'error' => 'A word in the query is longer than ' . IPS_ISSUES_TERM_MAX . ' characters.'];
			}
		}
		$key = ($not ? '-' : '+') . implode(' ', $words) . ($prefix ? '*' : '');
		if (isset($seen[$key])) {
			continue;
		}
		$seen[$key] = true;
		$clauses[] = ['words' => $words, 'prefix' => $prefix, 'not' => $not];
		if (count($clauses) > IPS_ISSUES_CLAUSES_MAX) {
			return ['clauses' => [], 'error' => 'The query has more than ' . IPS_ISSUES_CLAUSES_MAX . ' parts.'];
		}
	}
	return ['clauses' => $clauses, 'error' => null];
}

function ips_issues_positive($clauses) {
	return array_values(array_filter($clauses, function ($c) { return !$c['not']; }));
}

// ---- the engine ---------------------------------------------------------------------------------------------------------------------------

/* The server's full-text settings: ['min_token' => int, 'stopwords' => bool, 'stoplist' => [word => true], 'custom_stoplist' => bool], or null when they cannot be read. Read once. */
function ips_issues_ft_settings() {
	global $db;
	static $cache = false;
	if ($cache !== false) {
		return $cache;
	}
	$cache = null;
	$vars = [];
	$result = @$db->query("SHOW VARIABLES LIKE 'innodb_ft_%'");
	if (!$result) {
		return $cache;
	}
	while ($r = $result->fetch()) {
		$vars[$r['Variable_name']] = $r['Value'];
	}
	if (!isset($vars['innodb_ft_min_token_size'])) {
		return $cache;
	}
	$stoplist = [];
	$stops = @$db->query("SELECT value FROM INFORMATION_SCHEMA.INNODB_FT_DEFAULT_STOPWORD");
	if ($stops) {
		while ($r = $stops->fetch()) {
			$stoplist[strtolower($r['value'])] = true;
		}
	}
	$cache = ['min_token' => (int) $vars['innodb_ft_min_token_size'], 'stopwords' => ($vars['innodb_ft_enable_stopword'] ?? 'ON') !== 'OFF', 'stoplist' => $stoplist,
		'custom_stoplist' => ($vars['innodb_ft_server_stopword_table'] ?? '') !== '' || ($vars['innodb_ft_user_stopword_table'] ?? '') !== ''];
	return $cache;
}

// 'fulltext' when MATCH ... AGAINST would give the same pages as LIKE for these clauses on a server with these settings, else 'like' (see the top of this file). Pure.
function ips_issues_choose_engine($clauses, $settings) {
	if ($settings === null || !$clauses || $settings['custom_stoplist']) {
		return 'like';
	}
	foreach ($clauses as $clause) {
		foreach ($clause['words'] as $word) {
			if (mb_strlen($word, 'UTF-8') < $settings['min_token'] || ($settings['stopwords'] && isset($settings['stoplist'][$word]))) {
				return 'like';
			}
		}
	}
	return 'fulltext';
}

// The boolean-mode string for the index: every word of every required clause as a required word (+word), the last of a prefix clause with a * (+word*). A phrase contributes its words (the
// index finds the pages that have them all; ips_issues_like() then checks they are adjacent) and an excluded clause contributes nothing (NOT LIKE excludes it). Only folded words are in it.
function ips_issues_boolean($clauses) {
	$parts = [];
	foreach (ips_issues_positive($clauses) as $c) {
		foreach ($c['words'] as $i => $word) {
			$parts[] = '+' . $word . ($c['prefix'] && $i === count($c['words']) - 1 ? '*' : '');
		}
	}
	return implode(' ', array_unique($parts));
}

// [sql condition, bound parameters] on issue_pages.search_text for the clauses, as LIKE on whole folded words (the text is single-space separated, so padding it with a space on each side makes
// " word " mean a whole word). The words hold only letters and digits; the wildcards are escaped anyway.
function ips_issues_like($clauses) {
	$conditions = [];
	$params = [];
	foreach ($clauses as $c) {
		$text = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], implode(' ', $c['words']));
		$conditions[] = "CONCAT(' ', p.search_text, ' ') " . ($c['not'] ? 'NOT LIKE' : 'LIKE') . " ? ESCAPE '\\\\'";
		$params[] = ['type' => 's', 'value' => '% ' . $text . ($c['prefix'] ? '%' : ' %')];
	}
	return [implode(' AND ', $conditions), $params];
}

// ---- snippets -----------------------------------------------------------------------------------------------------------------------------

/* The words of a page as they were written, with the joins the folding makes (a soft hyphen at a line end joins the word) and the symbol-font bullets dropped: a list of tokens. */
function ips_issues_tokens($text) {
	if (!is_string($text) || !preg_match('//u', $text)) {
		return [];
	}
	$text = preg_replace("/\\x{00AD}[ \\t]*\\R[ \\t]*/u", '', $text);
	$text = str_replace("\xc2\xad", '', $text);
	$text = preg_replace('/[\x{E000}-\x{F8FF}\x{25BA}\x{2022}]/u', ' ', $text);
	return preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
}

/* A snippet of at most $max words around the first match of a positive clause, in the page's own spelling: ['text' => ..., 'html' => ... with the matching words in <mark> and everything
 * escaped, 'parts' => [[text, matched], ...] (the same as segments), 'words' => n], or null when nothing matches (a page found by a corrected text whose words are not in the shown text, say). */
function ips_issues_snippet($text, $clauses, $max = IPS_ISSUES_SNIPPET_WORDS) {
	$tokens = ips_issues_tokens($text);
	if (!$tokens) {
		return null;
	}
	// each token as its folded words, in one flat list that remembers the token each came from
	$words = [];
	$owner = [];
	foreach ($tokens as $i => $token) {
		foreach (array_filter(explode(' ', ips_fold($token)), 'strlen') as $word) {
			$words[] = $word;
			$owner[] = $i;
		}
	}
	$hits = []; // [first token, last token] of every match of a positive clause
	foreach (ips_issues_positive($clauses) as $c) {
		$n = count($c['words']);
		for ($at = 0; $at + $n <= count($words); $at++) {
			$ok = true;
			foreach ($c['words'] as $k => $word) {
				$last = $k === $n - 1;
				if ($last && $c['prefix'] ? strncmp($words[$at + $k], $word, strlen($word)) !== 0 : $words[$at + $k] !== $word) {
					$ok = false;
					break;
				}
			}
			if ($ok) {
				$hits[] = [$owner[$at], $owner[$at + $n - 1]];
			}
		}
	}
	if (!$hits) {
		return null;
	}
	usort($hits, function ($a, $b) { return $a[0] <=> $b[0]; });
	[$from, $to] = $hits[0];
	$start = max(0, min($from - 8, count($tokens) - $max));
	if ($to - $start >= $max) {
		$start = $from;
	}
	$end = min(count($tokens), $start + $max);
	$marked = [];
	foreach ($hits as [$a, $b]) {
		for ($i = $a; $i <= $b; $i++) {
			$marked[$i] = true;
		}
	}
	$plain = [];
	$html = [];
	$parts = [];   // [[text, marked], ...]: the same snippet as segments, for a page that builds its own markup instead of trusting a string
	for ($i = $start; $i < $end; $i++) {
		$plain[] = $tokens[$i];
		$safe = htmlspecialchars($tokens[$i], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		$on = isset($marked[$i]);
		$html[] = $on ? '<mark>' . $safe . '</mark>' : $safe;
		// the space between two words belongs to the plain side, so a highlighted segment is exactly its word or words
		$last = count($parts) - 1;
		if (!$parts) {
			$parts[] = [$tokens[$i], $on];
		} elseif ($parts[$last][1] === $on) {
			$parts[$last][0] .= ' ' . $tokens[$i];
		} elseif ($on) {
			$parts[$last][0] .= ' ';
			$parts[] = [$tokens[$i], true];
		} else {
			$parts[] = [' ' . $tokens[$i], false];
		}
	}
	$pre = $start > 0 ? '… ' : '';
	$post = $end < count($tokens) ? ' …' : '';
	if ($pre !== '') {
		array_unshift($parts, [$pre, false]);
	}
	if ($post !== '') {
		$parts[] = [$post, false];
	}
	return ['text' => $pre . implode(' ', $plain) . $post, 'html' => $pre . implode(' ', $html) . $post, 'parts' => $parts, 'words' => count($plain)];
}

// ---- the search ---------------------------------------------------------------------------------------------------------------------------

/* Searches the pages. $viewer is null (anonymous) or the signed-in user's name. $opts: offset, limit (at most 50), sort ('date', newest first, or 'relevance', which needs fulltext and
 * otherwise falls back to date), engine (force 'fulltext' or 'like': for tests). Returns ['query' => ..., 'error' => null|message, 'engine' => ..., 'total' => n, 'results' => [...]]; each result:
 * issue_id, page, label, issue_date, volume, number, series, access, status, restricted (the viewer may not read the issue), snippet, snippet_html and snippet_parts (null when restricted), score. */
function ips_issues_search($q, $viewer, $opts = []) {
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
	$limit = max(1, min((int) ($opts['limit'] ?? IPS_ISSUES_PAGE_SIZE), IPS_ISSUES_PAGE_SIZE_MAX));
	$offset = max(0, (int) ($opts['offset'] ?? 0));
	$engine = $opts['engine'] ?? ips_issues_choose_engine($clauses, ips_issues_ft_settings());
	$out['engine'] = $engine;
	$params = [];
	if ($engine === 'fulltext') {
		$where = 'MATCH(p.search_text) AGAINST (? IN BOOLEAN MODE)';
		$params[] = ['type' => 's', 'value' => ips_issues_boolean($clauses)];
	} else {
		[$where, $like] = ips_issues_like($clauses);
		$params = $like;
	}
	if ($engine === 'fulltext') { // the index narrows; what it cannot be trusted with is checked on the pages it finds
		[$check, $checks] = ips_issues_like(array_values(array_filter($clauses, function ($c) { return $c['not'] || count($c['words']) > 1; })));
		if ($check !== '') {
			$where .= ' AND ' . $check;
			$params = array_merge($params, $checks);
		}
	}
	if (!(is_string($viewer) && $viewer !== '')) {
		$where .= " AND i.status = 'published'";
	}
	$count = $db->query("SELECT COUNT(*) AS n FROM issue_pages p JOIN issues i ON i.id = p.issue_id WHERE $where", $params)->fetchArray();
	$out['total'] = (int) $count[0]['n'];
	$score = $engine === 'fulltext' ? 'MATCH(p.search_text) AGAINST (? IN BOOLEAN MODE)' : '0';
	$order = ($engine === 'fulltext' && ($opts['sort'] ?? 'date') === 'relevance' ? 'score DESC, ' : '') . "i.issue_date = '', i.issue_date DESC, i.id DESC, p.page ASC";
	$select = $params;
	if ($engine === 'fulltext') {
		array_unshift($select, ['type' => 's', 'value' => ips_issues_boolean($clauses)]);
	}
	$select[] = ['type' => 'i', 'value' => $limit];
	$select[] = ['type' => 'i', 'value' => $offset];
	$result = $db->query("SELECT p.issue_id, p.page, COALESCE(p.text_corrected, p.text_raw) AS body, $score AS score, i.label, i.issue_date, i.volume, i.number, i.series, i.status, i.access
		FROM issue_pages p JOIN issues i ON i.id = p.issue_id WHERE $where ORDER BY $order LIMIT ? OFFSET ?", $select);
	while ($r = $result->fetch()) {
		$readable = ips_access_can_read($viewer, $r['status'], $r['access']);
		$snippet = $readable ? ips_issues_snippet((string) $r['body'], $clauses) : null;
		$out['results'][] = ['issue_id' => (int) $r['issue_id'], 'page' => (int) $r['page'], 'label' => $r['label'], 'issue_date' => $r['issue_date'], 'volume' => $r['volume'], 'number' => $r['number'],
			'series' => $r['series'], 'status' => $r['status'], 'access' => $r['access'], 'restricted' => !$readable, 'snippet' => $snippet ? $snippet['text'] : null,
			'snippet_html' => $snippet ? $snippet['html'] : null, 'snippet_parts' => $snippet ? $snippet['parts'] : null, 'score' => (float) $r['score']];
	}
	return $out;
}
