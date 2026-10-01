# HTML editor (optional module)

A small browser editor for the HTML files that live under `ips/upload/files/` — for example the bibliography pages a
site serves from its upload folder. Sign in, pick a file from the list, edit it in CKEditor, press Save.

The module is optional: the **HTML Editor** entry in the Files menu (`html/navbar.php`) appears only when this folder exists.

## Pages

| File | What it does |
|---|---|
| `index.php` | Lists every `*.html` file under `upload/files/` (via `json-file_info.php`). |
| `edit.php` | Opens one file in CKEditor. `?html_file=files/html_templates/Resources.html` |
| `html_save.php` | Saves the editor's content. POST only. |
| `json-file_info.php` | The file list as JSON; paths are relative to `upload/`. |
| `html_files.php` | `ips_html_path()` / `ips_html_read()`: the one place a request's path becomes a file. |

## Installing CKEditor (not bundled)

CKEditor 4 is third-party, so it is not in this repository (`ips/html_editor/ckeditor/` is git-ignored). Download a CKEditor 4
"standard" build (4.11.4 is what the first site ran) from https://ckeditor.com/ckeditor-4/download/ and unpack it so that
`ips/html_editor/ckeditor/ckeditor.js` exists. Until then `edit.php` shows a notice and disables Save.

## Security model

* Every page needs a signed-in user. Saving also needs that user's CSRF token (`UserCookie::CsrfToken()`) and a POST.
* A request's file path is resolved with `realpath()` and must be an **existing `*.html` file inside `upload/files/`**; the
  editor can neither create files nor reach anything else (`../`, symlinks out of the tree, other extensions are all refused).
* `json-file_info.php` lists only inside `upload/files/` (its `?dir=` goes through the shared confinement helper) and does not
  follow symlinks.
* The module lives here and not under `upload/` because nginx refuses every `.php` under `/ips/upload/`.

The module finds its own URL folder, so it keeps working if a site serves it from a different directory name.
