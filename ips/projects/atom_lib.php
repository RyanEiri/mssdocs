<?php
/* AtoM CSV export of an archival description (RAD hierarchy, stage H4): the rows of a unit of description, of the units below it and of their items (TEI files linked with the role
 * "description"), in the column layout of AtoM's RAD CSV import template (the University of Manitoba Icelandic Collection's "AtoM CSV Template_v2.csv": its 90 columns, minus the
 * template's blank column). The conventions follow that collection's own ingest files: a byte order mark, lowercase level names (fonds, series, subseries, file, item), "|" between
 * several values, dates as written in eventDates (ISO 8601 intervals such as 1916/2016, start and end columns left empty), and legacy ids to link a row to its parent.
 *
 * A unit's row is made here from its description. An item's row is made from the RAD description the file editor's metadata stylesheets give for its TEI (tei-to-rad.xsl): each
 * paragraph carries its RAD rule in data-rule, and the table below says which AtoM column a rule fills. Nothing is invented: a column the record does not hold is empty.
 * Needs dom for items. The rules are pure functions, tested without a database.
 */

const IPS_ATOM_COLUMNS = ['legacyId', 'parentId', 'qubitParentSlug', 'title', 'radGeneralMaterialDesignation', 'alternateTitle', 'radOtherTitleInformation', 'radTitleStatementOfResponsibility',
	'radTitleStatementOfResponsibilityNote', 'radTitleAttributionsAndConjectures', 'radTitleContinues', 'radTitleSourceOfTitleProper', 'radTitleVariationsInTitle', 'radTitleParallelTitles',
	'levelOfDescription', 'repository', 'identifier', 'radEdition', 'radEditionStatementOfResponsibility', 'radStatementOfScaleCartographic', 'radStatementOfProjection', 'radStatementOfCoordinates',
	'radStatementOfScaleArchitectural', 'radIssuingJurisdiction', 'extentAndMedium', 'radTitleProperOfPublishersSeries', 'radParallelTitlesOfPublishersSeries',
	'radOtherTitleInformationOfPublishersSeries', 'radStatementOfResponsibilityRelatingToPublishersSeries', 'radNumberingWithinPublishersSeries', 'radPublishersSeriesNote', 'archivalHistory',
	'scopeAndContent', 'physicalCharacteristics', 'acquisition', 'arrangement', 'language', 'script', 'languageNote', 'locationOfOriginals', 'locationOfCopies', 'accessConditions',
	'reproductionConditions', 'findingAids', 'relatedUnitsOfDescription', 'accruals', 'radNoteAccompanyingMaterial', 'radNoteAlphaNumericDesignation', 'radNoteConservation', 'radNoteEdition',
	'radNotePhysicalDescription', 'radNotePublishersSeries', 'radNoteRights', 'radNoteCast', 'radNoteCredits', 'radNoteSignaturesInscriptions', 'generalNote', 'radStandardNumber',
	'subjectAccessPoints', 'placeAccessPoints', 'nameAccessPoints', 'genreAccessPoints', 'descriptionIdentifier', 'institutionIdentifier', 'rules', 'descriptionStatus', 'levelOfDetail',
	'revisionHistory', 'languageOfDescription', 'scriptOfDescription', 'sources', 'publicationStatus', 'physicalObjectName', 'physicalObjectLocation', 'physicalObjectType',
	'alternativeIdentifiers', 'alternativeIdentifierLabels', 'eventActors', 'eventActorHistories', 'eventTypes', 'eventDates', 'eventStartDates', 'eventEndDates', 'eventDescriptions',
	'eventPlaces', 'digitalObjectPath', 'digitalObjectURI', 'digitalObjectChecksum', 'culture'];

// which AtoM column the paragraph of a RAD rule fills in an item's row (several paragraphs of one rule, such as the general notes of 1.8B21, are joined with a blank line)
const IPS_ATOM_ITEM_RULES = [
	'1.8B1' => 'radTitleVariationsInTitle', '1.8B2' => 'radTitleSourceOfTitleProper', '1.8B5' => 'radTitleStatementOfResponsibilityNote', '1.8B6' => 'radTitleAttributionsAndConjectures',
	'1.8B8' => 'eventDescriptions', '1.8B9' => 'radNotePhysicalDescription', '1.8B9a' => 'physicalCharacteristics', '1.8B9b' => 'radNoteConservation', '1.8B9c' => 'radNoteAccompanyingMaterial',
	'1.8B11' => 'radNoteAlphaNumericDesignation', '1.8B12' => 'acquisition', '1.8B14' => 'languageNote', '1.8B15b' => 'locationOfCopies', '1.8B16a' => 'accessConditions',
	'1.8B16c' => 'reproductionConditions', '1.8B17' => 'findingAids', '1.8B21' => 'generalNote', '13.7C' => 'archivalHistory', '13.7D' => 'scopeAndContent',
];

// A row with every column, empty, with the given values filled in (an unknown column is a programming error and is refused).
function ips_atom_row($values) {
	$row = array_fill_keys(IPS_ATOM_COLUMNS, '');
	foreach ($values as $column => $value) {
		if (!array_key_exists($column, $row)) {
			throw new InvalidArgumentException("Not an AtoM column: $column");
		}
		$row[$column] = (string) $value;
	}
	return $row;
}

// The publication status AtoM knows for a visibility: only a published unit is Published; a draft or private one is a Draft.
function ips_atom_publication($visibility) {
	return $visibility === 'published' ? 'Published' : 'Draft';
}

// eventDates for a unit: its ISO 8601 date or interval as the collection's own ingests write it ("1916/2016"); an open unit ends in "/.." (EDTF), a unit with only an end begins "../".
function ips_atom_unit_dates($unit) {
	$start = $unit['date_start'];
	$end = $unit['date_end'];
	if ($start !== '' && $end !== '') {
		return $start === $end ? $start : $start . '/' . $end;
	}
	return $start !== '' ? $start . '/..' : ($end !== '' ? '../' . $end : '');
}

// The level name AtoM is given: a series under a series is a subseries.
function ips_atom_level($unit, $all) {
	if ($unit['level'] === 'series' && $unit['parent_id'] !== null && isset($all[$unit['parent_id']]) && $all[$unit['parent_id']]['level'] === 'series') {
		return 'subseries';
	}
	return $unit['level'];
}

/* The row of unit $id. $site is ['institution' => institutionIdentifier, 'rules' => the rules the description follows]. The repository and the description-control columns (status,
 * rules, institution) are given on a top-level unit only, as the collection's exports do: the units below inherit them in AtoM. The administrative history and the custodial history go into
 * archivalHistory under headings, as the collection writes them ("Custodial History"), because AtoM keeps a biography with an authority record this description does not have. */
function ips_atom_unit_row($id, $all, $site = []) {
	$u = $all[$id];
	$top = $u['parent_id'] === null || !isset($all[$u['parent_id']]);
	$repository = $u['repository'] !== '' ? $u['repository'] : (ips_units_inherited($id, $all)['repository']['value'] ?? '');
	$history = [];
	if ($u['admin_history'] !== '') {
		$history[] = "Administrative history\n\n" . $u['admin_history'];
	}
	if ($u['custodial_history'] !== '') {
		$history[] = "Custodial History\n\n" . $u['custodial_history'];
	}
	$effective = ips_units_effective_visibility($id, $all);
	$dates = ips_atom_unit_dates($u);
	return ips_atom_row([
		'legacyId' => 'u' . $id, 'parentId' => $top ? '' : 'u' . $u['parent_id'], 'title' => $u['title'], 'levelOfDescription' => ips_atom_level($u, $all),
		'repository' => $top ? $repository : '', 'identifier' => $u['ref_segment'], 'extentAndMedium' => $u['extent'], 'archivalHistory' => implode("\n\n", $history),
		'scopeAndContent' => $u['scope_content'], 'languageNote' => $u['language'], 'accessConditions' => $u['access_note'], 'reproductionConditions' => $u['terms_note'],
		'accruals' => $u['accruals'], 'generalNote' => $u['general_note'],
		'institutionIdentifier' => $top ? ($site['institution'] ?? '') : '', 'rules' => $top ? ($site['rules'] ?? '') : '',
		'descriptionStatus' => $top ? ($effective === 'published' ? 'Final' : 'Draft') : '', 'publicationStatus' => ips_atom_publication($effective),
		'eventTypes' => $dates !== '' ? 'Creation' : '', 'eventDates' => $dates, 'culture' => 'en',
	]);
}

// The text of a RAD paragraph without its label ("Language: ").
function ips_atom_paragraph_text($p) {
	$text = '';
	foreach ($p->childNodes as $node) {
		if (!($node instanceof DOMElement && $node->getAttribute('class') === 'rad-label')) {
			$text .= $node->textContent;
		}
	}
	return trim(preg_replace('/\s+/u', ' ', $text));
}

/* The row of an item from the RAD XHTML of its TEI (ips_fe_metadata_run()['rad']): the title, date and physical description of the first line, the paragraph of each rule in
 * IPS_ATOM_ITEM_RULES, and the name and place access points (the authority key after a heading, "(AndDav001)", is dropped). $identifier is the item's shelfmark (its idno), $status the
 * item's effective visibility; $legacy and $parent link it to its unit. */
function ips_atom_item_row($rad, $legacy, $parent, $identifier, $status) {
	$document = new DOMDocument();
	$document->loadXML($rad, LIBXML_NONET);
	$xpath = new DOMXPath($document);
	$first = function ($query) use ($xpath) {
		$found = $xpath->query($query);
		return $found->length ? trim(preg_replace('/\s+/u', ' ', $found->item(0)->textContent)) : '';
	};
	$values = ['legacyId' => $legacy, 'parentId' => $parent, 'title' => $first('//span[@class="rad-title"]'), 'levelOfDescription' => 'item', 'identifier' => $identifier,
		'extentAndMedium' => $first('//span[@class="rad-physical"]'), 'radTitleStatementOfResponsibility' => $first('//span[@class="rad-responsibility"]'),
		'publicationStatus' => ips_atom_publication($status), 'culture' => 'en'];
	$date = $first('//span[@class="rad-date"]');
	if ($date !== '') {
		$values['eventTypes'] = 'Creation';
		$values['eventDates'] = $date;
	}
	$joined = [];
	foreach ($xpath->query('//p[@data-rule]') as $p) {
		$column = IPS_ATOM_ITEM_RULES[$p->getAttribute('data-rule')] ?? null;
		if ($column !== null && ($text = ips_atom_paragraph_text($p)) !== '') {
			$joined[$column][] = $text;
		}
	}
	foreach ($joined as $column => $parts) {
		$values[$column] = implode("\n\n", $parts);
	}
	foreach (['nameAccessPoints' => 'rad-access-names', 'placeAccessPoints' => 'rad-access-places', 'subjectAccessPoints' => 'rad-access-subjects'] as $column => $class) {
		$line = $first('//p[@class="' . $class . '"]');
		if ($line !== '') {
			$line = preg_replace('/^(Name|Place|Subject)\s+/u', '', $line);
			$values[$column] = implode('|', array_map(function ($entry) { return trim(preg_replace('/\s*\([^()]*\)$/u', '', $entry)); }, explode(';', $line)));
		}
	}
	return ips_atom_row($values);
}

// The CSV text for the rows: a byte order mark (the collection's files have one), the header, then each row; every field quoted as needed.
function ips_atom_csv($rows) {
	$out = fopen('php://temp', 'w+');
	fputcsv($out, IPS_ATOM_COLUMNS, ',', '"', '');
	foreach ($rows as $row) {
		fputcsv($out, array_values($row), ',', '"', '');
	}
	rewind($out);
	$text = stream_get_contents($out);
	fclose($out);
	return "\xEF\xBB\xBF" . $text;
}

// Unit $root and everything below it, in tree order (each unit before its children, siblings by title).
function ips_atom_subtree($root, $all) {
	$out = [];
	$walk = function ($id) use (&$walk, &$out, $all) {
		$out[] = $id;
		foreach (ips_units_children($id, $all) as $child) {
			$walk($child['project_id']);
		}
	};
	if (isset($all[$root])) {
		$walk($root);
	}
	return $out;
}
