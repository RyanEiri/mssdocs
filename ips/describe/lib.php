<?php
/* The Describe module (ips/describe/): answering the cataloguing questions of a printed-book record from a form. A record is a TEI file in the file store made by tools/books-to-tei.py: a
 * biblStruct for the publication beside an msDesc for the copy (docs/mssdocs-metadata-mapping.md, section 9). Everything here is about ONE record's text: reading it into a flat structure,
 * working out which questions are still open, and applying a changed structure back to the TEI. The functions need only PHP's DOM, so they run without a request (the endpoints, which
 * need the web bootstrap, are the other files of this folder).
 *
 * What the web application never does: change a photograph or its EXIF data. A record names photographs by address; this file never opens one.
 *
 * Editing is deliberately narrow. ips_describe_apply() rewrites only the parts a cataloguer answers (the monogr's makers, titles, edition, imprint and volume; the notes after it; the language;
 * the catalogue notes; the copy's marks, binding and condition; the facsimile; the status and the decisions) and leaves every other node, comment and attribute as it was. A record with parts it
 * does not edit, a DTD or named entities, or no biblStruct is refused with a reason (the file editor opens those). A question is OPEN until the record holds the data or a recorded decision.
 * A decision is a <change type="decision" subtype="KEY" who="USER" when="DATE"> in revisionDesc; a later one for the same key supersedes, "retracted" in @ana undoes it, nothing is deleted. */

const IPS_DESCRIBE_TEI = 'http://www.tei-c.org/ns/1.0';
const IPS_DESCRIBE_MAX = 1048576;
const IPS_DESCRIBE_ROLES = ['author' => 'Author', 'editor' => 'Editor', 'translator' => 'Translator', 'contributor' => 'Other contributor'];
const IPS_DESCRIBE_PHOTO_ROLES = ['title-page', 'front-cover', 'back-cover', 'spine', 'binding', 'inside-cover', 'table-of-contents', 'leaf', 'accompanying-material', 'inscription', 'text-page', 'other'];
const IPS_DESCRIBE_STATUSES = ['draft', 'published', 'private'];
const IPS_DESCRIBE_DECISIONS = ['author', 'date', 'place', 'publisher', 'edition', 'volume', 'title', 'photographs', 'title-page'];
const IPS_DESCRIBE_KEEP = 30;   // versions of a record kept in .history/

// What each question asks, in words for the cataloguer (the key is also the decision's subtype).
const IPS_DESCRIBE_QUESTIONS = [
	'author' => 'Who made this book? The catalogue gives no author, or one cell holding several names, a role or a pen name: say who is the author, editor and translator, or confirm that it is unknown.',
	'date' => 'When was it published? The record has no date a machine can read: give the year (or the years), an estimate with the evidence, or confirm that it is undated.',
	'place' => 'Where was it published? No place is given: add it, or confirm that the title page names none.',
	'publisher' => 'Who published it? No publisher is given: add it, or confirm that the title page names none.',
	'edition' => 'Which edition? The edition is not a plain number: give the number, or keep the statement and confirm it.',
	'volume' => 'Which volume or issue? The cell could not be split: give the volume and the issue, or confirm it as written.',
	'title' => 'Is the title read from the title page? It is a description, not a transcription: say it is transcribed, or confirm that you supplied it.',
	'photographs' => 'No photograph is placed on this book: place one from the tray, or confirm that there is none.',
	'title-page' => 'No photograph is marked as the title page: mark one, or confirm that none survives.',
];

function ips_describe_t($doc) {
	$x = new DOMXPath($doc);
	$x->registerNamespace('t', IPS_DESCRIBE_TEI);
	return $x;
}

function ips_describe_text($node) {
	return $node === null ? '' : trim(preg_replace('/\s+/u', ' ', $node->textContent));
}

/* Loads a record's text. Returns [DOMDocument, null] or [null, reason]. Refused: too long, not well-formed, a DTD or a named entity (DOMDocument would write them back as numbers), no
 * msDesc, no biblStruct the copy points to, or parts of the publication that this module does not edit. */
function ips_describe_load($xml) {
	if (!is_string($xml) || strlen($xml) > IPS_DESCRIBE_MAX) {
		return [null, 'This record is too long for the form: open it in the file editor.'];
	}
	if (preg_match('/<!DOCTYPE|<!ENTITY|&(?!amp;|lt;|gt;|quot;|apos;|#)[A-Za-z_][\w.-]*;/', $xml)) {
		return [null, 'This record has a DTD or named entities (such as &aelig;), which the form would rewrite: open it in the file editor.'];
	}
	$doc = new DOMDocument('1.0', 'UTF-8');
	$doc->preserveWhiteSpace = false;
	$was = libxml_use_internal_errors(true);
	$ok = $doc->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_NOWARNING);
	$errors = libxml_get_errors();
	libxml_clear_errors();
	libxml_use_internal_errors($was);
	if (!$ok) {
		return [null, 'This record is not well-formed XML' . ($errors ? ' (line ' . $errors[0]->line . ')' : '') . ': open it in the file editor.'];
	}
	$x = ips_describe_t($doc);
	$ms = $x->query('//t:sourceDesc/t:msDesc')->item(0);
	if ($ms === null) {
		return [null, 'This file has no manuscript or copy description (msDesc).'];
	}
	$target = $x->query('t:msContents/t:msItem/t:bibl/t:ptr/@target', $ms)->item(0);
	$bibl = null;
	if ($target !== null && $target->nodeValue !== '' && $target->nodeValue[0] === '#') {
		$bibl = $x->query('//t:biblStruct[@xml:id=' . ips_describe_quote(substr($target->nodeValue, 1)) . ']')->item(0);
	}
	if ($bibl === null) {
		return [null, 'This is not a printed-book record (its copy does not point to a publication, a biblStruct): use the file editor for a manuscript.'];
	}
	$mono = $x->query('t:monogr', $bibl);
	if ($mono->length !== 1) {
		return [null, 'The publication has more than one monograph part: open it in the file editor.'];
	}
	foreach ($mono->item(0)->childNodes as $child) {
		if ($child instanceof DOMElement && !in_array($child->localName, ['author', 'editor', 'respStmt', 'title', 'edition', 'imprint', 'biblScope'], true)) {
			return [null, 'The publication has a <' . $child->localName . '> that the form does not edit: open it in the file editor.'];
		}
	}
	return [$doc, null];
}

// An XPath string literal for any text.
function ips_describe_quote($s) {
	if (strpos($s, "'") === false) {
		return "'" . $s . "'";
	}
	return 'concat(' . implode(",\"'\",", array_map(function ($p) { return "'" . $p . "'"; }, explode("'", $s))) . ')';
}

// The decisions in force: [key => ['who', 'when', 'text']]; the last <change type="decision"> of a key wins and one marked ana="retracted" removes it.
function ips_describe_decisions($x) {
	$out = [];
	foreach ($x->query('//t:teiHeader/t:revisionDesc/t:change[@type="decision"]') as $c) {
		$key = $c->getAttribute('subtype');
		if ($c->getAttribute('ana') === 'retracted') {
			unset($out[$key]);
		} elseif ($key !== '') {
			$out[$key] = ['who' => $c->getAttribute('who'), 'when' => $c->getAttribute('when'), 'text' => ips_describe_text($c)];
		}
	}
	return $out;
}

/* A printed-book record as a flat structure: id, bibl_id, kind, level, titles [{text,type,lang}], contributors [{name,role,resp}], pseudonym, place, publisher, date {text,when,from,to,cert},
 * edition {text,n}, scopes [{unit,text}], language {main,others,text}, notes [text], marks, binding, condition, photos [{path,role}], status, decisions, checked, cataloguer_note, reference.
 * Returns [struct, null] or [null, reason]. */
function ips_describe_read($xml) {
	[$doc, $problem] = ips_describe_load($xml);
	if ($doc === null) {
		return [null, $problem];
	}
	return [ips_describe_struct($doc), null];
}

function ips_describe_struct($doc) {
	$x = ips_describe_t($doc);
	$ms = $x->query('//t:sourceDesc/t:msDesc')->item(0);
	$target = substr($x->query('t:msContents/t:msItem/t:bibl/t:ptr/@target', $ms)->item(0)->nodeValue, 1);
	$bibl = $x->query('//t:biblStruct[@xml:id=' . ips_describe_quote($target) . ']')->item(0);
	$mono = $x->query('t:monogr', $bibl)->item(0);
	$s = ['id' => $ms->getAttribute('xml:id'), 'bibl_id' => $bibl->getAttribute('xml:id'), 'kind' => $bibl->getAttribute('type'), 'level' => 'm',
		'titles' => [], 'contributors' => [], 'pseudonym' => '', 'place' => '', 'publisher' => '', 'date' => ['text' => '', 'when' => '', 'from' => '', 'to' => '', 'cert' => ''],
		'edition' => ['text' => '', 'n' => ''], 'scopes' => [], 'language' => ['main' => '', 'others' => '', 'text' => ''], 'notes' => [], 'marks' => '', 'binding' => '', 'condition' => '',
		'photos' => [], 'status' => 'draft', 'decisions' => [], 'checked' => null, 'cataloguer_note' => '', 'reference' => ''];
	foreach ($mono->childNodes as $c) {
		if (!($c instanceof DOMElement)) {
			continue;
		}
		switch ($c->localName) {
			case 'author':
			case 'editor':
				$s['contributors'][] = ['name' => ips_describe_text($c), 'role' => $c->localName, 'resp' => ''];
				break;
			case 'respStmt':
				$resp = ips_describe_text($x->query('t:resp', $c)->item(0));
				$s['contributors'][] = ['name' => ips_describe_text($x->query('t:name', $c)->item(0)), 'role' => strtolower($resp) === 'translator' ? 'translator' : 'contributor', 'resp' => strtolower($resp) === 'translator' ? '' : $resp];
				break;
			case 'title':
				$s['level'] = $c->getAttribute('level') ?: 'm';
				$s['titles'][] = ['text' => ips_describe_text($c), 'type' => $c->getAttribute('type') ?: 'main', 'lang' => $c->getAttributeNS('http://www.w3.org/XML/1998/namespace', 'lang')];
				break;
			case 'edition':
				$s['edition'] = ['text' => ips_describe_text($c), 'n' => $c->getAttribute('n')];
				break;
			case 'biblScope':
				$s['scopes'][] = ['unit' => $c->getAttribute('unit'), 'text' => ips_describe_text($c)];
				break;
			case 'imprint':
				foreach ($c->childNodes as $i) {
					if (!($i instanceof DOMElement)) {
						continue;
					}
					if ($i->localName === 'pubPlace') {
						$s['place'] = ips_describe_text($i);
					} elseif ($i->localName === 'publisher') {
						$s['publisher'] = ips_describe_text($i);
					} elseif ($i->localName === 'date') {
						$s['date'] = ['text' => ips_describe_text($i), 'when' => $i->getAttribute('when'), 'from' => $i->getAttribute('from'), 'to' => $i->getAttribute('to'), 'cert' => $i->getAttribute('cert')];
					}
				}
				break;
		}
	}
	$s['pseudonym'] = ips_describe_text($x->query('t:note[@type="pseudonym"]', $bibl)->item(0));
	$lang = $x->query('t:msContents/t:textLang', $ms)->item(0);
	if ($lang !== null) {
		$s['language'] = ['main' => $lang->getAttribute('mainLang'), 'others' => $lang->getAttribute('otherLangs'), 'text' => ips_describe_text($lang)];
	}
	foreach ($x->query('t:msContents/t:msItem/t:note[@type="catalogue"]', $ms) as $n) {
		$s['notes'][] = ips_describe_text($n);
	}
	$s['marks'] = ips_describe_text($x->query('t:physDesc/t:additions/t:p', $ms)->item(0));
	$s['binding'] = ips_describe_text($x->query('t:physDesc/t:bindingDesc/t:binding/t:p', $ms)->item(0));
	$s['condition'] = ips_describe_text($x->query('t:physDesc/t:objectDesc/t:supportDesc/t:condition/t:p', $ms)->item(0));
	$s['reference'] = ips_describe_text($x->query('t:msIdentifier/t:idno[@type="reference-code"]', $ms)->item(0));
	foreach ($x->query('//t:facsimile/t:surface') as $surface) {
		$g = $x->query('t:graphic/@url', $surface)->item(0);
		if ($g !== null) {
			$s['photos'][] = ['path' => $g->nodeValue, 'role' => in_array($surface->getAttribute('n'), IPS_DESCRIBE_PHOTO_ROLES, true) ? $surface->getAttribute('n') : 'other'];
		}
	}
	$rev = $x->query('//t:teiHeader/t:revisionDesc')->item(0);
	if ($rev !== null && in_array($rev->getAttribute('status'), IPS_DESCRIBE_STATUSES, true)) {
		$s['status'] = $rev->getAttribute('status');
	}
	$s['decisions'] = ips_describe_decisions($x);
	foreach ($x->query('//t:teiHeader/t:revisionDesc/t:change[@type="checked"]') as $c) {
		$s['checked'] = $c->getAttribute('ana') === 'retracted' ? null : ['who' => $c->getAttribute('who'), 'when' => $c->getAttribute('when')];
	}
	foreach ($x->query('//t:teiHeader/t:revisionDesc/t:change[@type="note"]') as $c) {
		$s['cataloguer_note'] = $c->getAttribute('ana') === 'retracted' ? '' : ips_describe_text($c);
	}
	$has_note_change = $x->query('//t:teiHeader/t:revisionDesc/t:change[@type="note"]')->length > 0;
	if ($s['cataloguer_note'] === '' && !$has_note_change) {   // the generator's own review comment may carry a reviewer's note: keep it, the comment is dropped on the first save
		foreach ($doc->childNodes as $n) {
			if ($n instanceof DOMComment && preg_match('/reviewer note:\s*(.*?)\s*$/s', $n->data, $m)) {
				$s['cataloguer_note'] = $m[1];
			}
		}
	}
	return $s;
}

// Names that hold more than one person, a role or a pen name need the cataloguer's eye.
function ips_describe_name_needs_splitting($name) {
	return (bool) preg_match('/[\/,&;]|\(|\bog\b|\band\b|\bpen name\b/iu', $name);
}

/* The questions still open for a structure: [[key, text], ...]. A question is closed by data or by a decision (a key in $s['decisions']). */
function ips_describe_questions($s) {
	$open = [];
	$decided = $s['decisions'] ?? [];
	$ask = function ($key) use (&$open, $decided) {
		if (!isset($decided[$key])) {
			$open[] = [$key, IPS_DESCRIBE_QUESTIONS[$key]];
		}
	};
	$people = array_filter($s['contributors'], function ($c) { return $c['name'] !== ''; });
	$split = false;
	foreach ($people as $c) {
		$split = $split || ips_describe_name_needs_splitting($c['name']);
	}
	if (!$people || $split) {
		$ask('author');
	}
	$d = $s['date'];
	if ($d['when'] === '' && ($d['from'] === '' || $d['to'] === '')) {
		$ask('date');
	}
	if ($s['place'] === '') {
		$ask('place');
	}
	if ($s['publisher'] === '') {
		$ask('publisher');
	}
	if ($s['edition']['text'] !== '' && $s['edition']['n'] === '') {
		$ask('edition');
	}
	foreach ($s['scopes'] as $sc) {
		if ($sc['unit'] === '') {
			$ask('volume');
			break;
		}
	}
	foreach ($s['titles'] as $t) {
		if ($t['type'] === 'desc') {
			$ask('title');
			break;
		}
	}
	if (!$s['photos']) {
		$ask('photographs');
	} elseif (!in_array('title-page', array_column($s['photos'], 'role'), true)) {
		$ask('title-page');
	}
	return $open;
}

// ---- applying a structure back -------------------------------------------------------------------------------------------------------------------

function ips_describe_el($doc, $name, $text = null, $attrs = []) {
	$e = $doc->createElementNS(IPS_DESCRIBE_TEI, $name);
	foreach ($attrs as $k => $v) {
		if ($v === '' || $v === null) {
			continue;
		}
		if ($k === 'xml:lang') {
			$e->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:lang', $v);
		} elseif ($k === 'xml:id') {
			$e->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:id', $v);
		} else {
			$e->setAttribute($k, $v);
		}
	}
	if ($text !== null) {
		$e->appendChild($doc->createTextNode($text));
	}
	return $e;
}

function ips_describe_clean($v, $max = 2000) {
	$v = is_string($v) ? preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', $v)) : '';
	$v = trim($v);
	return function_exists('mb_substr') ? mb_substr($v, 0, $max) : substr($v, 0, $max);
}

/* A photograph's address as a record holds it: relative to the record, so it may climb out of the record's folder with leading ../ (the records and the photographs are sibling trees) but never
 * after a real folder name, never with an empty or . segment, and it is no URL, no absolute path and has no backslash or NUL. Whether it ends inside the file store is the saving endpoint's check. */
function ips_describe_plain_address($path) {
	if ($path === '' || strpbrk($path, "\\\0") !== false || preg_match('~^[a-z][a-z0-9+.-]*:|^/~i', $path)) {
		return false;
	}
	$climbing = true;
	foreach (explode('/', $path) as $part) {
		if ($part === '' || $part === '.') {
			return false;
		}
		if ($part === '..') {
			if (!$climbing) {
				return false;
			}
		} else {
			$climbing = false;
		}
	}
	return !$climbing;
}

/* Checks a structure a form sent: [normalised structure, null] or [null, reason]. Only the fields the form edits are read, each cleaned and limited; a role, a photograph role, a status or a
 * decision key that is not on its list is refused, a date is only ISO 8601 (YYYY, YYYY-MM, YYYY-MM-DD). The photographs' paths are kept as the record has them or as plain relative addresses. */
function ips_describe_check($in, $current) {
	if (!is_array($in)) {
		return [null, 'The form sent nothing readable.'];
	}
	$s = $current;
	$iso = '/^\d{4}(-(0[1-9]|1[0-2])(-(0[1-9]|[12]\d|3[01]))?)?$/';
	$titles = [];
	foreach ($in['titles'] ?? [] as $t) {
		$text = ips_describe_clean($t['text'] ?? '', 500);
		$type = $t['type'] ?? 'main';
		if ($text === '') {
			continue;
		}
		if (!in_array($type, ['main', 'desc', 'translated'], true)) {
			return [null, 'A title is main, descriptive or translated.'];
		}
		$titles[] = ['text' => $text, 'type' => $type, 'lang' => preg_match('/^[a-z]{2,3}$/', $t['lang'] ?? '') ? $t['lang'] : ''];
	}
	if (!$titles) {
		return [null, 'A book needs a title.'];
	}
	$s['titles'] = $titles;
	$people = [];
	foreach ($in['contributors'] ?? [] as $c) {
		$name = ips_describe_clean($c['name'] ?? '', 300);
		if ($name === '') {
			continue;
		}
		if (!isset(IPS_DESCRIBE_ROLES[$c['role'] ?? ''])) {
			return [null, 'A role is author, editor, translator or other contributor.'];
		}
		$people[] = ['name' => $name, 'role' => $c['role'], 'resp' => $c['role'] === 'contributor' ? ips_describe_clean($c['resp'] ?? '', 100) : ''];
	}
	$s['contributors'] = $people;
	$s['pseudonym'] = ips_describe_clean($in['pseudonym'] ?? '', 300);
	$s['place'] = ips_describe_clean($in['place'] ?? '', 200);
	$s['publisher'] = ips_describe_clean($in['publisher'] ?? '', 300);
	$d = $in['date'] ?? [];
	$date = ['text' => ips_describe_clean($d['text'] ?? '', 100), 'when' => trim((string) ($d['when'] ?? '')), 'from' => trim((string) ($d['from'] ?? '')), 'to' => trim((string) ($d['to'] ?? '')),
		'cert' => in_array($d['cert'] ?? '', ['', 'low', 'medium', 'high'], true) ? ($d['cert'] ?? '') : ''];
	foreach (['when', 'from', 'to'] as $k) {
		if ($date[$k] !== '' && !preg_match($iso, $date[$k])) {
			return [null, 'A date is a year, a year and month, or a full date (2026, 2026-10, 2026-10-31).'];
		}
	}
	if ($date['when'] !== '' && ($date['from'] !== '' || $date['to'] !== '')) {
		return [null, 'A date is one date or a range, not both.'];
	}
	if ($date['from'] !== '' && $date['to'] !== '' && strcmp($date['from'], $date['to']) > 0) {
		return [null, 'The range ends before it starts.'];
	}
	$s['date'] = $date;
	$e = $in['edition'] ?? [];
	$etext = ips_describe_clean($e['text'] ?? '', 100);
	$s['edition'] = ['text' => $etext, 'n' => ctype_digit($etext) ? $etext : ''];
	$scopes = [];
	foreach ($in['scopes'] ?? [] as $sc) {
		$text = ips_describe_clean($sc['text'] ?? '', 60);
		$unit = $sc['unit'] ?? '';
		if ($text === '') {
			continue;
		}
		if (!in_array($unit, ['', 'volume', 'issue'], true)) {
			return [null, 'A volume or issue number has the unit volume or issue.'];
		}
		$scopes[] = ['unit' => $unit, 'text' => $text];
	}
	$s['scopes'] = $scopes;
	$l = $in['language'] ?? [];
	$s['language'] = ['main' => preg_match('/^[a-z]{2,3}$/', $l['main'] ?? '') ? $l['main'] : '', 'others' => preg_match('/^([a-z]{2,3})( [a-z]{2,3})*$/', $l['others'] ?? '') ? $l['others'] : '', 'text' => ips_describe_clean($l['text'] ?? '', 100)];
	$s['notes'] = array_values(array_filter(array_map(function ($n) { return ips_describe_clean($n, 1000); }, $in['notes'] ?? [])));
	foreach (['marks' => 2000, 'binding' => 1000, 'condition' => 1000] as $k => $max) {
		$s[$k] = ips_describe_clean($in[$k] ?? '', $max);
	}
	$photos = [];
	foreach ($in['photos'] ?? [] as $p) {
		$path = trim((string) ($p['path'] ?? ''));
		if (!ips_describe_plain_address($path)) {
			return [null, 'A photograph is a plain relative address.'];
		}
		if (!in_array($p['role'] ?? '', IPS_DESCRIBE_PHOTO_ROLES, true)) {
			return [null, 'A photograph shows a title page, a cover, a binding, a leaf, an inscription or the like (see the list).'];
		}
		$photos[] = ['path' => $path, 'role' => $p['role']];
	}
	$s['photos'] = $photos;
	if (isset($in['status'])) {
		if (!in_array($in['status'], IPS_DESCRIBE_STATUSES, true)) {
			return [null, 'The status is draft, published or private.'];
		}
		$s['status'] = $in['status'];
	}
	$decisions = [];
	foreach ($in['decisions'] ?? [] as $key => $d) {
		if (!in_array($key, IPS_DESCRIBE_DECISIONS, true)) {
			return [null, 'That is not something that can be confirmed.'];
		}
		$decisions[$key] = ['who' => $current['decisions'][$key]['who'] ?? '', 'when' => $current['decisions'][$key]['when'] ?? '', 'text' => ips_describe_clean(is_array($d) ? ($d['text'] ?? '') : '', 300)];
	}
	$s['decisions'] = $decisions;
	$s['checked'] = !empty($in['checked']) ? ($current['checked'] ?: ['who' => '', 'when' => '']) : null;
	$s['cataloguer_note'] = ips_describe_clean($in['cataloguer_note'] ?? '', 2000);
	return [$s, null];
}

/* Applies a checked structure to the record's text. Returns [text, changed, null] or [null, false, reason]; `changed` is false when the canonical XML (comments left out) equals the original, so a
 * save that changes nothing writes nothing. $user and $date (Y-m-d) stamp the decisions, the tick and the note that are new. */
function ips_describe_apply($xml, $s, $user, $date) {
	[$doc, $problem] = ips_describe_load($xml);
	if ($doc === null) {
		return [null, false, $problem];
	}
	$before = $doc->C14N();
	$x = ips_describe_t($doc);
	$ms = $x->query('//t:sourceDesc/t:msDesc')->item(0);
	$id = $ms->getAttribute('xml:id');
	$target = substr($x->query('t:msContents/t:msItem/t:bibl/t:ptr/@target', $ms)->item(0)->nodeValue, 1);
	$bibl = $x->query('//t:biblStruct[@xml:id=' . ips_describe_quote($target) . ']')->item(0);
	$mono = $x->query('t:monogr', $bibl)->item(0);

	// the publication: makers, titles, edition, imprint, volumes, in the order the schema wants
	while ($mono->firstChild) {
		$mono->removeChild($mono->firstChild);
	}
	foreach ($s['contributors'] as $c) {
		if ($c['role'] === 'author' || $c['role'] === 'editor') {
			$mono->appendChild(ips_describe_el($doc, $c['role'], $c['name']));
		} else {
			$r = ips_describe_el($doc, 'respStmt');
			$r->appendChild(ips_describe_el($doc, 'resp', $c['role'] === 'translator' ? 'Translator' : ($c['resp'] !== '' ? $c['resp'] : 'Contributor')));
			$r->appendChild(ips_describe_el($doc, 'name', $c['name']));
			$mono->appendChild($r);
		}
	}
	foreach ($s['titles'] as $t) {
		$mono->appendChild(ips_describe_el($doc, 'title', $t['text'], ['level' => $s['level'], 'type' => $t['type'], 'xml:lang' => $t['lang']]));
	}
	if ($s['edition']['text'] !== '') {
		$mono->appendChild(ips_describe_el($doc, 'edition', $s['edition']['text'], ['n' => $s['edition']['n']]));
	}
	$imprint = ips_describe_el($doc, 'imprint');
	$d = $s['date'];
	$has_date = $d['text'] !== '' || $d['when'] !== '' || $d['from'] !== '';
	if ($s['place'] !== '') {
		$imprint->appendChild(ips_describe_el($doc, 'pubPlace', $s['place']));
	}
	if ($s['publisher'] !== '') {
		$imprint->appendChild(ips_describe_el($doc, 'publisher', $s['publisher']));
	}
	if ($has_date) {
		$show = $d['text'] !== '' ? $d['text'] : ($d['when'] !== '' ? $d['when'] : $d['from'] . ($d['to'] !== '' ? '-' . $d['to'] : ''));
		$imprint->appendChild(ips_describe_el($doc, 'date', $show, ['when' => $d['when'], 'from' => $d['from'], 'to' => $d['to'], 'cert' => ($d['when'] !== '' || $d['from'] !== '') ? $d['cert'] : '']));
	} elseif (!$imprint->hasChildNodes()) {
		$imprint->appendChild(ips_describe_el($doc, 'date', 'n.d.'));   // a monogr must have an imprint and an imprint may not be empty: n.d. is the catalogue's own convention
	}
	$mono->appendChild($imprint);
	foreach ($s['scopes'] as $sc) {
		$mono->appendChild(ips_describe_el($doc, 'biblScope', $sc['text'], ['unit' => $sc['unit']]));
	}
	foreach (iterator_to_array($x->query('t:note[@type="pseudonym"]', $bibl)) as $old) {
		$bibl->removeChild($old);
	}
	if ($s['pseudonym'] !== '') {
		$bibl->appendChild(ips_describe_el($doc, 'note', $s['pseudonym'], ['type' => 'pseudonym']));
	}

	// the copy: language, catalogue notes, marks, binding, condition
	$contents = $x->query('t:msContents', $ms)->item(0);
	foreach (iterator_to_array($x->query('t:textLang', $contents)) as $old) {
		$contents->removeChild($old);
	}
	$lang = $s['language'];
	if ($lang['main'] !== '' || $lang['text'] !== '') {
		$el = ips_describe_el($doc, 'textLang', $lang['text'], ['mainLang' => $lang['main'], 'otherLangs' => $lang['others']]);
		$contents->insertBefore($el, $x->query('t:msItem', $contents)->item(0));
	}
	$item = $x->query('t:msItem', $contents)->item(0);
	foreach (iterator_to_array($x->query('t:note[@type="catalogue"]', $item)) as $old) {
		$item->removeChild($old);
	}
	foreach ($s['notes'] as $n) {
		$item->appendChild(ips_describe_el($doc, 'note', $n, ['type' => 'catalogue']));
	}
	$phys = $x->query('t:physDesc', $ms)->item(0);
	if ($phys !== null) {
		foreach (iterator_to_array($x->query('t:additions | t:bindingDesc', $phys)) as $old) {
			$phys->removeChild($old);
		}
		$cond = $x->query('t:objectDesc/t:supportDesc/t:condition', $phys)->item(0);
		if ($cond !== null) {
			$cond->parentNode->removeChild($cond);
		}
		if ($s['condition'] !== '') {
			$sd = $x->query('t:objectDesc/t:supportDesc', $phys)->item(0);
			if ($sd !== null) {
				$c = ips_describe_el($doc, 'condition');
				$c->appendChild(ips_describe_el($doc, 'p', $s['condition']));
				$sd->appendChild($c);
			}
		}
		if ($s['marks'] !== '') {
			$a = ips_describe_el($doc, 'additions');
			$a->appendChild(ips_describe_el($doc, 'p', $s['marks']));
			$phys->appendChild($a);
		}
		if ($s['binding'] !== '') {
			$b = ips_describe_el($doc, 'bindingDesc');
			$bb = ips_describe_el($doc, 'binding');
			$bb->appendChild(ips_describe_el($doc, 'p', $s['binding']));
			$b->appendChild($bb);
			$phys->appendChild($b);
		}
	}

	// the photographs
	$root = $doc->documentElement;
	foreach (iterator_to_array($x->query('t:facsimile', $root)) as $old) {
		$root->removeChild($old);
	}
	if ($s['photos']) {
		$fac = ips_describe_el($doc, 'facsimile');
		foreach ($s['photos'] as $i => $p) {
			$surface = ips_describe_el($doc, 'surface', null, ['xml:id' => $id . '-s' . ($i + 1), 'n' => $p['role']]);
			$surface->appendChild(ips_describe_el($doc, 'graphic', null, ['url' => $p['path']]));
			$fac->appendChild($surface);
		}
		$root->insertBefore($fac, $x->query('t:text', $root)->item(0));
	}

	// the status, decisions, the tick and the note
	$rev = $x->query('//t:teiHeader/t:revisionDesc')->item(0);
	if ($rev === null) {
		return [null, false, 'The record has no revision description to hold the status.'];
	}
	$rev->setAttribute('status', $s['status']);
	$now = ips_describe_decisions($x);
	foreach ($s['decisions'] as $key => $d) {
		if (!isset($now[$key])) {
			$rev->appendChild(ips_describe_el($doc, 'change', $d['text'] !== '' ? $d['text'] : 'Confirmed by the cataloguer.', ['type' => 'decision', 'subtype' => $key, 'who' => $user, 'when' => $date, 'ana' => 'confirmed']));
		} elseif ($d['text'] !== '' && $d['text'] !== $now[$key]['text']) {
			$rev->appendChild(ips_describe_el($doc, 'change', $d['text'], ['type' => 'decision', 'subtype' => $key, 'who' => $user, 'when' => $date, 'ana' => 'confirmed']));
		}
	}
	foreach (array_diff_key($now, $s['decisions']) as $key => $d) {
		$rev->appendChild(ips_describe_el($doc, 'change', 'Retracted: the question is open again.', ['type' => 'decision', 'subtype' => $key, 'who' => $user, 'when' => $date, 'ana' => 'retracted']));
	}
	$was = null;
	foreach ($x->query('t:change[@type="checked"]', $rev) as $c) {
		$was = $c->getAttribute('ana') === 'retracted' ? null : $c;
	}
	if ($s['checked'] !== null && $was === null) {
		$rev->appendChild(ips_describe_el($doc, 'change', 'Checked by the cataloguer.', ['type' => 'checked', 'who' => $user, 'when' => $date]));
	} elseif ($s['checked'] === null && $was !== null) {
		$rev->appendChild(ips_describe_el($doc, 'change', 'The check was withdrawn.', ['type' => 'checked', 'who' => $user, 'when' => $date, 'ana' => 'retracted']));
	}
	$prior = '';
	foreach ($x->query('t:change[@type="note"]', $rev) as $c) {
		$prior = $c->getAttribute('ana') === 'retracted' ? '' : ips_describe_text($c);
	}
	if ($s['cataloguer_note'] !== $prior) {   // a note read from the generator's comment is new here: it is written down before the comment goes
		$attrs = ['type' => 'note', 'who' => $user, 'when' => $date];
		if ($s['cataloguer_note'] === '') {
			$attrs['ana'] = 'retracted';
		}
		$rev->appendChild(ips_describe_el($doc, 'change', $s['cataloguer_note'] !== '' ? $s['cataloguer_note'] : 'The note was removed.', $attrs));
	}

	// the generator's review comment is out of date the moment a person works on the record: the questions are worked out live
	foreach (iterator_to_array($doc->childNodes) as $n) {
		if ($n instanceof DOMComment && preg_match('/^\s*review:/', $n->data)) {
			$doc->removeChild($n);
		}
	}
	$doc->formatOutput = true;
	$out = $doc->saveXML();
	$changed = $before !== $doc->C14N();
	return [$out, $changed, null];
}

// ---- files: etag, history, atomic write -----------------------------------------------------------------------------------------------------------

function ips_describe_etag($path) {
	return is_file($path) ? hash_file('sha256', $path) : '';
}

function ips_describe_user_part($user) {
	return preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) $user) ?: 'user';
}

/* Copies the record to <folder>/.history/<id>/<UTC time>-<user>.xml before it is replaced and keeps the newest IPS_DESCRIBE_KEEP. The hidden folder is never listed and the gate refuses
 * hidden names. Returns the copy's path, or false when it could not be made (the caller then does not write). */
function ips_describe_backup($path, $user) {
	$dir = dirname($path) . '/.history/' . basename($path, '.xml');
	if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
		return false;
	}
	$copy = $dir . '/' . gmdate('Ymd-His') . 'Z-' . ips_describe_user_part($user) . '.xml';
	$n = 1;
	while (file_exists($copy)) {
		$copy = $dir . '/' . gmdate('Ymd-His') . 'Z-' . ips_describe_user_part($user) . '-' . (++$n) . '.xml';
	}
	if (!@copy($path, $copy)) {
		return false;
	}
	$all = glob($dir . '/*.xml') ?: [];
	sort($all);
	foreach (array_slice($all, 0, max(0, count($all) - IPS_DESCRIBE_KEEP)) as $old) {
		@unlink($old);
	}
	return $copy;
}

// Writes the text beside the record and renames it over it: a reader never sees half a file.
function ips_describe_write($path, $text) {
	$tmp = dirname($path) . '/.' . basename($path) . '.' . bin2hex(random_bytes(6)) . '.part';
	if (file_put_contents($tmp, $text, LOCK_EX) === false || !@rename($tmp, $path)) {
		@unlink($tmp);
		return false;
	}
	@chmod($path, 0664);
	return true;
}
