<?php
/* The CSV file type of the file editor (see types/html.php for what a type is): CSV files open in a spreadsheet grid (grid.php) instead of a text editor, with
 * Excel-style copy, paste and fill, so a RAD or AtoM CSV can be edited like a sheet. A new file starts as a header row; a save is refused unless the text is
 * UTF-8, has a header row and has no quote left open (the grid could not read it back). */
return [
	'id' => 'csv',
	'order' => 30,
	'label' => 'CSV',
	'group' => 'CSV spreadsheets',
	'ext' => ['csv'],
	'folder' => 'csv_templates',
	'blurb' => 'Spreadsheet grid',
	'view' => 'grid',
	'skeleton' => function ($title) {
		return "title\n";
	},
	'validate' => function ($text) {
		if (strlen($text) > 5 * 1024 * 1024) {
			return 'the file is larger than 5 MB';
		}
		if (strpos($text, "\0") !== false || !preg_match('//u', $text)) {
			return 'the text is not UTF-8';
		}
		if (trim(preg_split('/\r\n|\r|\n/', preg_replace('/^\xEF\xBB\xBF/', '', $text), 2)[0]) === '') { // strtok would skip blank lines and find the next row
			return 'the first row (the column names) is empty';
		}
		$open = false;
		for ($i = 0, $n = strlen($text); $i < $n; $i++) {
			if ($text[$i] === '"') {
				$open = !$open; // "" inside a quoted cell closes and reopens, so the count still ends right
			}
		}
		return $open ? 'a quote is left open' : null;
	},
];
