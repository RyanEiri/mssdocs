# File editor (optional module)

Create, edit and delete the HTML (and XML) pages a site keeps in its file store, in the browser: a **Pages** list with a filter, a *New file* dialog,
and an editor that is CKEditor's Source view with CodeMirror (highlighting, search and replace, tag completion, comment buttons). The module is optional:
its card on the Home page appears only when this folder exists.

| File | What it does |
|---|---|
| `index.php`, `files-app.js` | The Pages list: the files the user may open, filter, New file, two-click Delete. |
| `edit.php`, `edit-page.js` | One file: status strip (lines, XML well-formed), unsaved flag, Ctrl/Cmd+S, Save, two-click Delete. |
| `list.php`, `new.php`, `save.php`, `delete.php` | The JSON endpoints behind them. |
| `lib.php` | The one place a request's path becomes a file, and who may do what. |
| `header.php`, `file-editor.css` | The app bar and the styles (on the admin UI's `--ips-*` tokens, so `css/site-theme.css` re-themes them). |

## Where the files are, and who may do what

HTML pages live in `upload/files/html_templates/`, XML in `upload/files/xml_templates/` (a folder holds one kind). An **administrator** may do anything in them.
A **regular user** has their own folder in each (`html_templates/<username>/`, made when they first create a file) where they create, save and delete; they can
*read* the files at the top of a folder (the shared pages) but not change them, and another user's folder is not even listed. A site whose editors share its pages
(a bibliography kept by a team) lets every signed-in user also save and delete the shared pages by defining, in `php/site-config.php` (or `config.php`):

    define('IPS_FILE_EDITOR_SHARED_WRITE', true);

`define('IPS_FILE_EDITOR_TYPES', 'html');` offers HTML only (default `html,xml`). Nothing is erased: Delete moves a file to `.deleted/` in its templates folder, and
nothing under a dot-folder is ever listed or opened.

## Installing CKEditor (not bundled)

CKEditor 4 and its CodeMirror plugin are third-party, so they are not in this repository (`file_editor/ckeditor/` is not tracked). Unpack a CKEditor 4 build
that includes the `codemirror` plugin (4.11.x with CodeMirror plugin 1.17.x is what the first sites ran) so that `file_editor/ckeditor/ckeditor.js` exists.
Without it the editor page shows a notice and is a plain text box that saves the same way. The module sets every CKEditor option itself (`customConfig` off).

## Security model

* Every page and endpoint needs a signed-in user; saving, creating and deleting also need that user's CSRF token (`X-CSRF`) and a same-origin POST.
* A request's path is resolved with `realpath()` and must be an **existing `*.html` / `*.xml` file inside its templates folder** that the user may see; `..`,
  symlinks out of the tree, other extensions and dot-folders are refused. A user's own folder that is a link out of the templates folder is not used.
* The editor page prints a file's text **escaped** (printed raw, a file holding `</textarea><script>` would run script for whoever opens it) and the server saves
  exactly what is posted; XML must be well-formed.
* The web server must not run script in these pages when they are viewed directly: serve `.html`, `.htm`, `.xhtml` and `.xml` under `/ips/upload/files/` with
  `Content-Security-Policy: sandbox` (see `templates/nginx/ips-site.conf.j2` in webserversAC). A site whose pages need JavaScript can leave `.html` out of that rule,
  at the price that a regular user's HTML then runs as the site.
* The module lives here and not under `upload/` because nginx refuses every `.php` under `/ips/upload/`.
