<?php
/* The HTML of an article body, cleaned: what CKEditor sends is never trusted. An allow-list of elements and, for each, of attributes; only web addresses (http, https, and mailto for links) or a
 * path on the site; everything else is removed. It runs on every save and again on a restored revision, and what is stored is what a public page prints, so the public side never has to
 * clean anything. Pure (needs dom). Design: docs/mssdocs-articles-design.md, section 3.2.
 *
 *   ips_articles_clean($html)  the clean HTML (idempotent: cleaning clean HTML changes nothing)
 *   ips_articles_text($html)   its plain text, with a space between blocks, for search and for summaries
 */

// element => the attributes it may keep
const IPS_ARTICLE_TAGS = [
	'p' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [], 'strong' => [], 'em' => [], 'b' => [], 'i' => [], 'u' => [], 's' => [], 'sub' => [], 'sup' => [],
	'br' => [], 'hr' => [], 'a' => ['href', 'title'], 'img' => ['src', 'alt', 'title'], 'figure' => [], 'figcaption' => [], 'table' => [], 'thead' => [], 'tbody' => [], 'tfoot' => [],
	'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'], 'caption' => [], 'code' => [], 'pre' => [],
];
// elements whose content is dropped with them (their text is code or data, not prose)
const IPS_ARTICLE_DROP = ['script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet', 'noscript', 'template', 'svg', 'math', 'head', 'title', 'link', 'meta', 'base', 'form', 'select',
	'textarea', 'button', 'input', 'audio', 'video', 'canvas', 'xml', 'plaintext', 'noembed', 'noframes', 'datalist', 'option', 'optgroup', 'dialog'];

// The address if it is one an article may carry, else null. A link may be http, https, mailto, a path on the site ("/x", not "//host") or an anchor ("#x"); an image http, https or a path.
function ips_articles_url($value, $link) {
	$value = trim((string) $value);
	$stripped = preg_replace('/[\x00-\x20\x7f-\x9f\x{200B}-\x{200F}\x{2028}\x{2029}\x{FEFF}]+/u', '', $value); // "java\tscript:" and the like
	if ($stripped === null || $stripped === '' || strlen($value) > 2000) {
		return null;
	}
	if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $stripped, $m)) {
		$scheme = strtolower(rtrim($m[0], ':'));
		if ($scheme === 'http' || $scheme === 'https') {
			return preg_match('#^https?://[^/\s]+#i', $stripped) ? $value : null;
		}
		return $link && $scheme === 'mailto' && preg_match('/^mailto:[^\s<>"\']+$/i', $stripped) ? $stripped : null;
	}
	if ($stripped[0] === '/') {
		return substr($stripped, 0, 2) === '//' || strpos($stripped, '\\') !== false ? null : $value;
	}
	return $link && $stripped[0] === '#' ? $stripped : null;
}

function ips_articles_clean_node($node, $into, $doc) {
	foreach (iterator_to_array($node->childNodes) as $child) {
		if ($child instanceof DOMText || $child instanceof DOMCdataSection) {
			$into->appendChild($doc->createTextNode($child->nodeValue));
			continue;
		}
		if (!($child instanceof DOMElement)) {
			continue; // comments, processing instructions
		}
		$name = strtolower($child->localName);
		if (in_array($name, IPS_ARTICLE_DROP, true)) {
			continue;
		}
		if (!isset(IPS_ARTICLE_TAGS[$name])) { // an unknown element: its content stays, the element goes
			ips_articles_clean_node($child, $into, $doc);
			continue;
		}
		if ($name === 'img') {
			$src = ips_articles_url($child->getAttribute('src'), false);
			if ($src === null) {
				continue;
			}
		}
		$made = $doc->createElement($name);
		foreach (IPS_ARTICLE_TAGS[$name] as $attribute) {
			if (!$child->hasAttribute($attribute)) {
				continue;
			}
			$value = $child->getAttribute($attribute);
			if ($attribute === 'href') {
				$value = ips_articles_url($value, true);
				if ($value === null) {
					continue;
				}
			} elseif ($attribute === 'src') {
				$value = ips_articles_url($value, false);
			} elseif ($attribute === 'colspan' || $attribute === 'rowspan') {
				if (!ctype_digit($value) || (int) $value < 1 || (int) $value > 50) {
					continue;
				}
			}
			$made->setAttribute($attribute, $value);
		}
		if ($name === 'a') {
			if (!$made->hasAttribute('href')) { // a link with no usable address is only its text
				ips_articles_clean_node($child, $into, $doc);
				continue;
			}
			$made->setAttribute('rel', 'noopener noreferrer');
		}
		$into->appendChild($made);
		if ($name !== 'br' && $name !== 'hr' && $name !== 'img') {
			ips_articles_clean_node($child, $made, $doc);
		}
	}
}

function ips_articles_clean($html) {
	$html = (string) $html;
	if ($html === '' || !preg_match('//u', $html)) {
		return '';
	}
	$previous = libxml_use_internal_errors(true);
	$source = new DOMDocument();
	$source->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
	libxml_clear_errors();
	libxml_use_internal_errors($previous);
	$out = new DOMDocument('1.0', 'UTF-8');
	$root = $out->createElement('div');
	$out->appendChild($root);
	$body = $source->getElementsByTagName('body')->item(0);
	if ($body) {
		ips_articles_clean_node($body, $root, $out);
	}
	$clean = '';
	foreach ($root->childNodes as $node) {
		$clean .= $out->saveHTML($node);
	}
	return trim($clean);
}

function ips_articles_text($html) {
	$text = preg_replace('#</?(p|h[1-6]|li|ul|ol|blockquote|tr|td|th|table|figure|figcaption|div|pre|caption)\b[^>]*>|<br\s*/?>|<hr\s*/?>#i', ' ', (string) $html);
	$text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
	return trim(preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', $text)));
}
