<?php
/* The metadata views of the file editor: the Dublin Core record, the RAD description and the completeness report for the TEI manuscript description (msDesc) in a text, made by the
 * XSLT 1.0 stylesheets next to this file (see README.md there and docs/mssdocs-metadata-mapping.md in the webserversAC repository). Needs PHP's dom and xsl extensions (php8.2-xml
 * on the hosts); without them ips_fe_metadata_available() is false and the editor shows no Metadata button.
 *
 * The text is parsed with no network, no DTD load and no entity expansion; the Menota character names (&ae; ...) are replaced by numeric references first, as for the Validate button
 * (schema.php). Nothing is read from or written to a file but the stylesheets.
 */
require_once __DIR__ . '/../schema.php';

const IPS_FE_METADATA_MAX_BYTES = 4194304; // 4 MB, as for validation

function ips_fe_metadata_available() {
	return class_exists('DOMDocument') && class_exists('XSLTProcessor');
}

// A stylesheet of this folder as a processor, or null. The sheet is loaded from its file so that its xsl:include resolves.
function ips_fe_metadata_processor($file) {
	$sheet = new DOMDocument();
	if (!@$sheet->load(__DIR__ . '/' . $file, LIBXML_NONET)) {
		return null;
	}
	$processor = new XSLTProcessor();
	if (!@$processor->importStylesheet($sheet)) {
		return null;
	}
	// from here the stylesheet may read no file and use no network (it has no need to: its xsl:include was resolved above)
	$processor->setSecurityPrefs(XSL_SECPREF_READ_FILE | XSL_SECPREF_WRITE_FILE | XSL_SECPREF_CREATE_DIRECTORY | XSL_SECPREF_READ_NETWORK | XSL_SECPREF_WRITE_NETWORK);
	return $processor;
}

/* The three views of $text (the TEI document as a string):
 *   ['available' => bool, 'stage' => null | 'size' | 'parse' | 'none' | 'engine', 'message' => string|null,
 *    'record' => null | ['id', 'status' (draft, published or private), 'lang'],
 *    'dc' => [[term, value], ...], 'rad' => XHTML string | null, 'completeness' => [['field', 'message'], ...]]
 * Stage 'parse' means the text is not well-formed XML, 'none' that it holds no msDesc, 'size' that it is too long, 'engine' that a stylesheet could not be run. */
function ips_fe_metadata_run($text) {
	$out = ['available' => ips_fe_metadata_available(), 'stage' => null, 'message' => null, 'record' => null, 'dc' => [], 'rad' => null, 'completeness' => []];
	if (!$out['available']) {
		return $out;
	}
	if (strlen($text) > IPS_FE_METADATA_MAX_BYTES) {
		return ['stage' => 'size', 'message' => 'The document is larger than ' . (IPS_FE_METADATA_MAX_BYTES >> 20) . ' MB, too large to describe here.'] + $out;
	}
	$text = ips_fe_substitute_entities($text, ips_fe_entities());
	$previous = libxml_use_internal_errors(true);
	libxml_clear_errors();
	$document = new DOMDocument();
	$loaded = $document->loadXML($text, LIBXML_NONET | LIBXML_NOWARNING);
	$problem = $loaded ? null : (libxml_get_errors()[0] ?? null);
	libxml_clear_errors();
	libxml_use_internal_errors($previous);
	if (!$loaded) {
		return ['stage' => 'parse', 'message' => 'The text is not well-formed XML' . ($problem ? ' (line ' . $problem->line . ': ' . trim($problem->message) . ')' : '') . '.'] + $out;
	}
	$sheets = [];
	foreach (['dc' => 'tei-to-dc.xsl', 'rad' => 'tei-to-rad.xsl', 'completeness' => 'tei-completeness.xsl'] as $view => $file) {
		$processor = ips_fe_metadata_processor($file);
		if ($processor === null) {
			return ['stage' => 'engine', 'message' => 'The stylesheet ' . $file . ' could not be loaded.'] + $out;
		}
		$sheets[$view] = $processor;
	}
	$dc = $sheets['dc']->transformToDoc($document);
	if ($dc === false) {
		return ['stage' => 'engine', 'message' => 'The Dublin Core stylesheet failed.'] + $out;
	}
	if ($dc->documentElement === null) {
		return ['stage' => 'none', 'message' => 'This file holds no manuscript description (a TEI msDesc in the sourceDesc), so there is nothing to describe.'] + $out;
	}
	$out['record'] = [
		'id' => $dc->documentElement->getAttribute('id'),
		'status' => $dc->documentElement->getAttribute('status'),
		'lang' => $dc->documentElement->getAttribute('lang'),
	];
	foreach ($dc->documentElement->childNodes as $node) {
		if ($node instanceof DOMElement) {
			$out['dc'][] = [$node->prefix . ':' . $node->localName, $node->textContent];
		}
	}
	$rad = $sheets['rad']->transformToDoc($document);
	$out['rad'] = $rad && $rad->documentElement ? $rad->saveXML($rad->documentElement) : null;
	$gaps = $sheets['completeness']->transformToDoc($document);
	if ($gaps && $gaps->documentElement) {
		foreach ($gaps->documentElement->childNodes as $node) {
			if ($node instanceof DOMElement) {
				$out['completeness'][] = [$node->getAttribute('field'), $node->textContent];
			}
		}
	}
	return $out;
}

/* The views of a unit of description (a fonds, collection, series or file), from the unit XML of projects/unit_lib.php (ips_units_xml): ['available' => bool, 'record' => {id, status},
 * 'dc' => [[term, value], ...], 'rad' => XHTML string]. $repeat prints the values the unit inherits as well (IPS_RAD_REPEAT_INHERITED, RAD footnote 1a). */
function ips_fe_metadata_unit_views($xml, $repeat = false) {
	$out = ['available' => ips_fe_metadata_available(), 'record' => null, 'dc' => [], 'rad' => null];
	if (!$out['available']) {
		return $out;
	}
	$document = new DOMDocument();
	if (!@$document->loadXML($xml, LIBXML_NONET)) {
		return $out;
	}
	$dc = ips_fe_metadata_processor('unit-to-dc.xsl');
	$rad = ips_fe_metadata_processor('unit-to-rad.xsl');
	if ($dc === null || $rad === null) {
		return $out;
	}
	$record = $dc->transformToDoc($document);
	if ($record && $record->documentElement) {
		$out['record'] = ['id' => $record->documentElement->getAttribute('id'), 'status' => $record->documentElement->getAttribute('status')];
		foreach ($record->documentElement->childNodes as $node) {
			if ($node instanceof DOMElement) {
				$out['dc'][] = [$node->prefix . ':' . $node->localName, $node->textContent];
			}
		}
	}
	$rad->setParameter('', 'repeat', $repeat ? 'yes' : 'no');
	$view = $rad->transformToDoc($document);
	$out['rad'] = $view && $view->documentElement ? $view->saveXML($view->documentElement) : null;
	return $out;
}
