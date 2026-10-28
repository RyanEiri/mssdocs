# File editor (optional module)

Create, edit and delete the files a site keeps in its file store, in the browser: a **File editor** list with a filter, a *New file* dialog, and an editor that is
CKEditor's Source view with CodeMirror (highlighting, search and replace, tag completion, comment buttons). The module is optional: its card on the Home page appears
only when this folder exists.

| File | What it does |
|---|---|
| `index.php`, `files-app.js` | The list: the files the user may open, filter, New file, two-click Delete. |
| `edit.php`, `edit-page.js` | One file: status strip (lines and the file type's own check), unsaved flag, Ctrl/Cmd+S, Save, two-click Delete. |
| `list.php`, `new.php`, `save.php`, `delete.php` | The JSON endpoints behind them. |
| `artefact.php`, `artefact-pane.js` | **The artefact pane**: the page images or PDF a document describes, beside the editor (below). |
| `lib.php` | The one place a request's path becomes a file, the file-type registry, and who may do what. |
| `types/*.php` (and `types/*.js`) | **The file types**: one module per type of file (below). |
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
| `view` | How its files open: `source` (the default: CodeMirror only) or `wysiwyg` (CKEditor's visual editor, with a Source button). A file that holds script, a form, a frame or a whole HTML document still opens in Source, so the visual editor never removes anything unseen (`ips_fe_view()` in `lib.php`). |
| `skeleton` | `function ($title)`: the text of a new file. |
| `validate` | Optional `function ($text)`: `null` when the text may be saved, else the reason it may not (XML: it must be well-formed). |
| `js` | Optional script in `types/`, loaded by the editor page, registering `window.IPS_FE_TYPES[id] = { check: function (text) { return {ok, text}; } }`, the live status beside the line count (XML: "Well-formed"). |

A descriptor that is malformed, or that claims an id, extension or folder another has already taken, is ignored, so one bad module cannot take the others down (`tests/test_file_editor_types.py`
in webserversAC). Security of a new type is the site's call: the save path, the folder rules and the escaped output are the module's, but a type that holds script needs the nginx rule below.

## The artefact beside a document

An XML or HTML file can have an **artefact** shown beside it: page images (one at a time, with zoom) or a PDF in a frame, from the file store. `artefact.php` looks in this order and stops at the first that has something:

1. **The Projects module** (if installed): the document is linked to a project; the project's files with the role *Artefact page* (images, in the project's order, each with its label such as `fol. 12r`) or *Artefact PDF*. A document in several projects has a source for each, with a switcher; archived projects are left out.
2. **The TEI's own facsimile** (XML only): `<graphic url>` (inside a `<surface n>`) and `<pb n facs>`, for an address that names a file inside the store (a path relative to the document, or a full address containing `/upload/files/`). Any other address is ignored.
3. **A folder `<xml:id>-pages`** anywhere in the store (to depth 4) holding images, in natural order; the id is the `<msDesc>`'s `xml:id`, else the file's name.
4. **A PDF `<xml:id>.pdf`**, found the same way.

With no artefact there is no pane; the strip says "No artefact linked". The pane has a page list, previous and next, zoom (`Fit`, 125 to 300%), a PDF link, width steps and Hide (the strip's *Artefact* button brings it back); width, visibility, zoom and Follow cursor are remembered in the browser.
**Follow cursor** (Source view): the nearest `<pb n="...">` or `<locus from="...">` before the cursor names a page, and the page whose label or file name is the same is shown (`fol. 12r`, `12r` and `0012r` are one label; a PDF with no page list takes a number). Nothing needs an XML extension: the TEI is read with regular expressions, since not every host has `dom`. Tests: `tests/test_artefact_pane.py`, `tests/test_ui_artefact_pane.py` (on the vesturheimsrit base dev stack, with the Projects tables).

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
