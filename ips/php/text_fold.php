<?php
/* Text folding for search: the same function folds a page when it is stored and a query when it is asked, so what matches is decided in one place. Needs mbstring (in the hosts' default
 * PHP packages), not intl: Normalizer is not on every host.
 *
 * What it does, in this order (design: docs/mssdocs-issues-design.md, section 3.5, from what three real InDesign PDFs contain):
 *   1. A soft hyphen (U+00AD) at the end of a line joins the word ("Austur", soft hyphen, newline, "bæjarskóli" gives "Austurbæjarskóli"); one inside a line is removed.
 *   2. Private-use glyphs (the bullets of a symbol font, U+E000 to U+F8FF) and the ornaments U+25BA and U+2022 stop being characters: they separate words.
 *   3. Lower case; ligatures expanded (ﬁ ﬂ ﬀ ﬃ ﬄ); Icelandic and Nordic letters made plain (þ th, ð d, æ ae, œ oe, ø o, ö o, å a, ß ss); other Latin accents dropped.
 *   4. An apostrophe or curly quote inside a word is removed (culture's and cultures match); everything else that is not a letter or a digit separates words; runs of space, a line break
 *      included, are one space.
 * The result is lower-case words separated by single spaces. It is idempotent: folding folded text changes nothing.
 */

function ips_fold_map() {
	static $map = null;
	if ($map === null) {
		$groups = [
			'a' => 'àáâãäåāăąǎ', 'c' => 'çćĉċč', 'd' => 'ďđð', 'e' => 'èéêëēĕėęě', 'g' => 'ĝğġģ', 'h' => 'ĥħ', 'i' => 'ìíîïĩīĭįıǐ', 'j' => 'ĵ', 'k' => 'ķ', 'l' => 'ĺļľŀł',
			'n' => 'ñńņň', 'o' => 'òóôõöōŏőøǫǭǒ', 'r' => 'ŕŗř', 's' => 'śŝşš', 't' => 'ţťŧ', 'u' => 'ùúûüũūŭůűųǔ', 'w' => 'ŵ', 'y' => 'ýÿŷ', 'z' => 'źżž',
		];
		$map = ['þ' => 'th', 'æ' => 'ae', 'œ' => 'oe', 'ß' => 'ss', 'ﬁ' => 'fi', 'ﬂ' => 'fl', 'ﬀ' => 'ff', 'ﬃ' => 'ffi', 'ﬄ' => 'ffl', 'ǽ' => 'ae', 'ǣ' => 'ae'];
		foreach ($groups as $base => $letters) {
			foreach (preg_split('//u', $letters, -1, PREG_SPLIT_NO_EMPTY) as $letter) {
				$map[$letter] = $base;
			}
		}
	}
	return $map;
}

// $text folded for search, or '' when it is not valid UTF-8.
function ips_fold($text) {
	if (!is_string($text) || $text === '' || !preg_match('//u', $text)) {
		return '';
	}
	$text = preg_replace("/\\x{00AD}[ \\t]*\\R[ \\t]*/u", '', $text);          // a hyphenated word at a line end
	$text = str_replace("\xc2\xad", '', $text);                                   // a soft hyphen inside a line
	$text = preg_replace('/[\x{E000}-\x{F8FF}\x{25BA}\x{2022}]/u', ' ', $text);   // symbol-font bullets and ornaments
	$text = strtr(mb_strtolower($text, 'UTF-8'), ips_fold_map());
	$text = preg_replace("/(?<=[\\p{L}\\p{N}])['\\x{2019}\\x{2018}\\x{02BC}`](?=[\\p{L}\\p{N}])/u", '', $text);
	$text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);
	return trim($text);
}
