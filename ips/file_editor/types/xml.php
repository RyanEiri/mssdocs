<?php
/* The XML file type of the file editor (see types/html.php for what a type is): XML files, a new one starting from a minimal TEI document, and a save refused unless
 * the text is well-formed XML. */
return [
	'id' => 'xml',
	'label' => 'XML',
	'group' => 'XML files',
	'ext' => ['xml'],
	'folder' => 'xml_templates',
	'blurb' => 'TEI skeleton',
	'mode' => 'htmlmixed',
	'js' => 'xml.js',
	'skeleton' => function ($title) {
		$title = htmlspecialchars($title, ENT_XML1 | ENT_QUOTES, 'UTF-8');
		return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<TEI xmlns=\"http://www.tei-c.org/ns/1.0\">\n  <teiHeader>\n    <fileDesc>\n      <titleStmt>\n        <title>$title</title>\n"
			. "      </titleStmt>\n      <publicationStmt>\n        <p>Unpublished</p>\n      </publicationStmt>\n      <sourceDesc>\n        <p>Created in the file editor</p>\n"
			. "      </sourceDesc>\n    </fileDesc>\n  </teiHeader>\n  <text>\n    <body>\n      <p/>\n    </body>\n  </text>\n</TEI>\n";
	},
	'validate' => function ($text) {
		$previous = libxml_use_internal_errors(true);
		libxml_clear_errors();
		$ok = simplexml_load_string($text) !== false;
		$problem = $ok ? null : (libxml_get_errors()[0] ?? null);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);
		return $ok ? null : 'the XML is not well-formed' . ($problem ? ' (line ' . $problem->line . ': ' . trim($problem->message) . ')' : '');
	},
];
