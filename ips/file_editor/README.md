# File editor (optional module)

Create, edit and delete the files a site keeps in its file store, in the browser: a **File editor** list with a filter, a *New file* dialog, and an editor that is
CKEditor's Source view with CodeMirror (highlighting, search and replace, tag completion, comment buttons). The module is optional: its card on the Home page appears
only when this folder exists.

| File | What it does |
|---|---|
| `index.php`, `files-app.js` | The list: the files the user may open, filter, New file, two-click Delete. |
| `edit.php`, `edit-page.js` | One file: status strip (lines and the file type's own check), unsaved flag, Ctrl/Cmd+S, Save, two-click Delete. |
| `list.php`, `new.php`, `save.php`, `delete.php` | The JSON endpoints behind them. |
| `schema.php`, `validate.php`, `validate_xml.py`, `schemas/` | **The XML validator** (below): the Validate button's server side, the checker, and where the Menota schema goes. |
| `metadata.php`, `metadata-panel.js`, `metadata/` | **The Metadata panel** (below): Dublin Core, a RAD description and the gaps for the XML in the editor, from stylesheets in `metadata/`. |
| `artefact.php`, `artefact-pane.js` | **The artefact pane**: the page images or PDF a document describes, beside the editor (below). |
| `lib.php` | The one place a request's path becomes a file, the file-type registry, and who may do what. |
| `types/*.php` (and `types/*.js`) | **The file types**: one module per type of file (below). |
| `grid.php`, `grid-page.js`, `fill-series.js` | **The CSV grid**: the page a CSV file opens in, and its fill handle's series logic (below). The grid library is vendored in `js/jspreadsheet/`. |
| `header.php`, `file-editor.css` | The app bar and the styles (on the admin UI's `--ips-*` tokens, so `css/site-theme.css` re-themes them). |

## File types are modules

A type of file is one file in `types/` that returns a descriptor; the editor offers the types that are loaded. `types/html.php` and `types/xml.php` are the two that come
with the module. **Add a type by dropping a file in `types/`, take one away by deleting its file**; a site can also narrow the list without touching the folder:

    define('IPS_FILE_EDITOR_TYPES', 'html');     // php/site-config.php: offer only these ids (comma-separated)

A descriptor (`types/html.php` documents it):

| Key | |
|---|---|
| `id`, `label`, `group`, `blurb` | The type's name in requests, the name people see, the heading of its files in the list, the line under it in the New file dialog. |
| `ext` | The extensions it owns (lowercase, no dot); the first is given to a new file. |
| `folder` | Its templates folder under `upload/files/`: its files are listed, opened, created, saved and deleted there and nowhere else (one type per folder). |
| `mode` | The CodeMirror mode of the Source view (default `htmlmixed`). |
| `view` | How its files open: `source` (the default: CodeMirror only), `grid` (a spreadsheet, `grid.php`: `edit.php` sends the file there; see below) or `wysiwyg` (CKEditor's visual editor, with a Source button). A file that holds script, a form, a frame or a whole HTML document still opens in Source, so the visual editor never removes anything unseen (`ips_fe_view()` in `lib.php`). |
| `order` | Optional number (default 100): where the type comes in the list and the New file dialog, which offers the first one. `html` 10, `xml` 20, `csv` 30. |
| `skeleton` | `function ($title)`: the text of a new file. |
| `validate` | Optional `function ($text)`: `null` when the text may be saved, else the reason it may not (XML: it must be well-formed). |
| `js` | Optional script in `types/`, loaded by the editor page, registering `window.IPS_FE_TYPES[id] = { check: function (text) { return {ok, text}; } }`, the live status beside the line count (XML: "Well-formed"). |

A descriptor that is malformed, or that claims an id, extension or folder another has already taken, is ignored, so one bad module cannot take the others down (`tests/test_file_editor_types.py`
in webserversAC). Security of a new type is the site's call: the save path, the folder rules and the escaped output are the module's, but a type that holds script needs the nginx rule below.

## CSV files open in a spreadsheet grid

`types/csv.php` is the third file type that comes with the module (folder `upload/files/csv_templates/`, `view` `grid`). A CSV opens in `grid.php` (a spreadsheet, Jspreadsheet CE) instead of a text editor, so a RAD or AtoM CSV
can be edited like a sheet. Saving goes through `save.php` like any file: sign-in, CSRF, the user's own folder, and the type's rules (UTF-8, a first row of column names, no quote left open, at most 5 MB).

- **The sheet:** the first row of the file is the column names and stays as it is: columns cannot be added, removed, renamed, moved or sorted, so a file keeps its layout. Rows can be added and removed (right-click), and every cell is edited as plain text.
  A cell is never evaluated: `=1+1` is the text `=1+1`, and markup in a header or a cell is shown as the text it is (tested in a browser with `<img onerror>`, `<script>` and `javascript:`).
- **Copy and paste** with Ctrl+C and Ctrl+V, also to and from Excel. **Ctrl+Z / Ctrl+Y** undo and redo; a whole fill is one step.
- **Fill handle** (`fill-series.js`): select cells and drag the small square at the corner of the selection, up, down, left or right. It continues what Excel continues: `1, 2` as `3, 4, 5`; `Item 1, Item 2` as `Item 3, Item 4`; one `FH-B01-0009`
  as `FH-B01-0010` (zero padding kept); ISO dates by the same number of days (one date by a day); an uneven or mixed selection is repeated. One number is copied, as in Excel, and one plain text is copied. Each selected column fills on its own.
  The library only repeats the selection, so the series comes from the `onbeforechange` hook while the corner is dragged; a paste, typing or undo never counts as a fill. Not done: Excel's fill options menu, double-click to fill down, Ctrl-drag to copy instead.
- **Saving** writes the sheet back as CSV (RFC 4180: a cell is quoted only when it holds a comma, a quote or a line break), with the file's own line endings, its byte order mark if it has one and its closing line break; blank rows at the end are dropped.
  A file opened and saved without a change is not marked as changed. Quoting of cells nobody touched may differ from how the file was first written (a quoted cell that did not need quotes is written without them).
- **Not there:** frozen columns, search, sorting, formulas (the Pro edition), a CSV with a different separator (semicolon, tab), files over 5 MB. Not tested: screen readers (the library's markup has little ARIA, so this is a mouse and keyboard tool),
  touch screens, and a pasted block that is wider than the sheet.
- **The library** is vendored by `tools/vendor-grid-assets.sh` (pinned Jspreadsheet CE 5.0.4 and jsuites 6.6.1, both MIT, licence texts beside them, about 1.2 MB, no CDN and no bundler). The fill relies on that version's `onbeforechange` hook and the `.jss_corner` square:
  after changing the version, run `tests/test_ui_csv_grid.py` and `tests/test_fill_series.py` (webserversAC).

## The artefact beside a document

An XML or HTML file can have an **artefact** shown beside it: page images (one at a time, with zoom) or a PDF in a frame, from the file store. `artefact.php` looks in this order and stops at the first that has something:

1. **The Projects module** (if installed): the document is linked to a project; the project's files with the role *Artefact page* (images, in the project's order, each with its label such as `fol. 12r`) or *Artefact PDF*. A document in several projects has a source for each, with a switcher; archived projects are left out.
2. **The TEI's own facsimile** (XML only): `<graphic url>` (inside a `<surface n>`) and `<pb n facs>`, for an address that names a file inside the store (a path relative to the document, or a full address containing `/upload/files/`). Any other address is ignored.
3. **A folder `<xml:id>-pages`** anywhere in the store (to depth 4) holding images, in natural order; the id is the `<msDesc>`'s `xml:id`, else the file's name.
4. **A PDF `<xml:id>.pdf`**, found the same way.

With no artefact there is no pane; the strip says "No artefact linked". The pane has a page list, previous and next, zoom (`Fit`, 125 to 300%), a PDF link, width steps and Hide (the strip's *Artefact* button brings it back); width, visibility, zoom and Follow cursor are remembered in the browser.
**Follow cursor** (Source view): the nearest `<pb n="...">` or `<locus from="...">` before the cursor names a page, and the page whose label or file name is the same is shown (`fol. 12r`, `12r` and `0012r` are one label; a PDF with no page list takes a number). Nothing needs an XML extension: the TEI is read with regular expressions, since not every host has `dom`. Tests: `tests/test_artefact_pane.py`, `tests/test_ui_artefact_pane.py` (on the vesturheimsrit base dev stack, with the Projects tables).

## Validating XML against the Menota schema

`validate_xml.py` checks a document against the Menota schema (RELAX NG, Menota Handbook 3.0, a customisation of TEI P5) and lists every problem with its line. It is a **command-line tool**:
`python3 validate_xml.py --schema menotaP5.rng --file record.xml` (the fleet's `tools/validate-tei.py` in webserversAC wraps it for files and folders), and what **the editor's Validate button** runs on a host
that has set one up. The button (XML only) lists the problems (a click on a line goes there), and after each save a note says Valid or how many problems; it is advice and never stops a save.

- **The schema is not in the repository** (menota.org states no licence): `tools/fetch-menota-schema.sh <folder>` downloads it with its source, date and checksums; see `schemas/README.md`.
- **Named entities** (`&aelig; &mdash; ...`, the list in `menota-entities.txt`) are understood: replaced with their characters before parsing, nothing external is ever loaded. This also lets a Menota document with such entities be saved (`types/xml.php`).
- **Why not PHP's own validator, and why no button on the fleet yet.** PHP's `relaxNGValidate` recompiles the 800 KB schema on every call. Measured: **libxml2 2.15 or newer takes about 4 seconds; 2.14.6 (the PyPI wheels of lxml), 2.9.14 (Debian 12) and Debian 13's packaged lxml take minutes.** So the checker needs an lxml built on libxml2 2.15+ and refuses an older one, and a site names the interpreter with `IPS_FE_PYTHON` in `php/site-config.php`; without it there is no button. PHP runs the checker as a fixed command (no shell), feeds it the text on stdin, stops it after `IPS_FE_VALIDATE_TIMEOUT` seconds (60), and lets at most `IPS_FE_VALIDATE_SLOTS` (2) run at once. The button appears by itself once a host has such an interpreter.
- Tests: `tests/test_menota_validation.py` (the checker with this computer's lxml; the PHP side with fake and real interpreters; the endpoint on the dev stack) and `tests/test_ui_menota_validation.py` (the button, on the dev stack, with a clearly fake checker) in webserversAC.

## Metadata from the TEI header

For an XML file the strip has a **Metadata** button (when PHP has the `dom` and `xsl` extensions: `php8.2-xml` on every host). It opens a panel above the text with three views of the record's `msDesc`, made from the text in the editor, unsaved changes included, and made again after every save: **Dublin Core** (a table of terms), **RAD** (a description of the manuscript as a discrete item, with each paragraph's rule in its tooltip) and **Gaps** (what the record lacks that the views want). A badge shows the record's status (`revisionDesc/@status`: `draft`, `published` or `private`; none is a draft) and whether it may be shown in public zones; only `published` may. The panel is a tool for the cataloguer and does not publish anything.

When the Projects module has units of description (`projects/units.sql`) and the file is an item of one (linked to it with the role `description`), the panel also says **where it sits** (*Part of: fonds › series*, each a link to the Projects page, with the item's reference code: its unit's code, then its own shelfmark), **limits the status** by the units above (the least public wins: a published item under a draft series shows as a draft), shows under the RAD view **what the item has as above** (the unit's own custodial history, language, repository, restrictions and terms, else the nearest unit above's), and reports in **Gaps** an item linked to more than one unit (an item has one parent; the first is used). The file is named in the request and resolved for the user like any file the editor opens.

`metadata.php` takes the text as a same-origin POST with the user's CSRF token (a signed-in user, nothing more), reads no file, and answers JSON; `metadata/lib.php` parses the text with no network, no DTD and no entity expansion and runs the stylesheets of `metadata/` (see its README) with file and network access denied. The RAD view comes back as XHTML from the stylesheet, and the panel copies it node by node, keeping only `div`, `p` and `span` with their class and rule, so no markup from a record reaches the page. Tests: `tests/test_metadata_*.py` in the fleet repository.

## Where the files are, and who may do what

Each type's files live in its own folder under `upload/files/` (`html_templates/`, `xml_templates/`, ...). An **administrator** may do anything in them. A **regular user** has their own
folder in each (`html_templates/<username>/`, made when they first create a file) where they create, save and delete; they can *read* the files at the top of a folder (the shared
pages) but not change them, and another user's folder is not even listed. A site whose editors share its pages (a bibliography kept by a team) lets every signed-in user also save
and delete the shared pages by defining, in `php/site-config.php` (or `config.php`):

    define('IPS_FILE_EDITOR_SHARED_WRITE', true);

Nothing is erased: Delete moves a file to `.deleted/` in its folder, and nothing under a dot-folder is ever listed or opened.

## Installing CKEditor (not bundled)

CKEditor 4 and its CodeMirror plugin are third-party, so they are not in this repository (`file_editor/ckeditor/` is not tracked). Unpack a CKEditor 4 build
that includes the `codemirror` plugin (4.11.x with CodeMirror plugin 1.17.x is what the first sites ran) so that `file_editor/ckeditor/ckeditor.js` exists.
Without it the editor page shows a notice and is a plain text box that saves the same way. The module sets every CKEditor option itself (`customConfig` off).

## Security model

* Every page and endpoint needs a signed-in user; saving, creating and deleting also need that user's CSRF token (`X-CSRF`) and a same-origin POST.
* A request's path is resolved with `realpath()` and must be an **existing file of a loaded type inside that type's folder** that the user may see; `..`,
  symlinks out of the tree, other extensions and dot-folders are refused. A user's own folder that is a link out of the templates folder is not used.
* The editor page prints a file's text **escaped** (printed raw, a file holding `</textarea><script>` would run script for whoever opens it) and the server saves
  exactly what is posted; XML must be well-formed.
* The web server must not run script in these pages when they are viewed directly: serve `.html`, `.htm`, `.xhtml` and `.xml` (and any type that is markup a browser would run) under `/ips/upload/files/` with
  `Content-Security-Policy: sandbox` (see `templates/nginx/ips-site.conf.j2` in webserversAC). A site whose pages need JavaScript can leave `.html` out of that rule,
  at the price that a regular user's HTML then runs as the site.
* The module lives here and not under `upload/` because nginx refuses every `.php` under `/ips/upload/`.
