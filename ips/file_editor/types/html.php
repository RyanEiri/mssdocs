<?php
/* A file type of the file editor (see lib.php and the README): HTML pages. A file type is a module: one file in types/ that returns this array, and the editor
 * offers the types whose files are there (remove a file to turn its type off; IPS_FILE_EDITOR_TYPES, a comma-separated list of ids, can also narrow them).
 *
 *   id        letters and digits, unique: the type's name in requests and in the New file dialog
 *   label     the name people see ("HTML")
 *   group     the heading of its files on the Files page
 *   ext       the file extensions it owns (lowercase, no dot); the first one is given to a new file
 *   folder    its templates folder under upload/files/: files of this type are listed, opened, created, saved and deleted there and nowhere else
 *   blurb     one line under the label in the New file dialog
 *   mode      the CodeMirror mode of its Source view (default htmlmixed)
 *   skeleton  function ($title): the text of a new file
 *   validate  optional function ($text): null when the text may be saved, else the reason it may not
 *   js        optional script in types/, loaded by the editor page: registers window.IPS_FE_TYPES[id] = { check: function (text) { return {ok, text} } }, the live
 *             status next to the line count
 */
return [
	'id' => 'html',
	'label' => 'HTML',
	'group' => 'HTML pages',
	'ext' => ['html'],
	'folder' => 'html_templates',
	'blurb' => 'Blank page',
	'mode' => 'htmlmixed',
	'skeleton' => function ($title) {
		$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
		return "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n  <meta charset=\"utf-8\">\n  <title>$title</title>\n</head>\n<body>\n  <p></p>\n</body>\n</html>\n";
	},
];
