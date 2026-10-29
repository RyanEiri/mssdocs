# Projects (optional module)

A **project** groups files of the file store under a title: the page images of a manuscript, its description, a PDF. It is a database record (title, kind, status, notes), and a file can be in
several projects. Each link carries a **role** (artefact page, artefact PDF, transcription, description, supporting document, other), a **position**, a **label** (`fol. 12r`) and a note.
The module is optional: its card on the Home page, and everything it adds to the file browser, appear only when this folder exists and its tables have been created.

| File | What it does |
|---|---|
| `schema.sql` | The two tables: `projects` and `project_files` (project, file, role, position, label, note, who linked it). Run it once, after the base schema. Deleting a project removes its links (a cascading key); no file is ever deleted. There is no key to `files` (its `id` is signed in the base schema and unsigned in some site dumps, and MariaDB refuses a key between the two): a link whose file left the catalogue is not listed or counted and is cleared by the next write. |
| `index.php`, `projects-app.js`, `projects.css`, `header.php` | The Projects page: the list, the open project's details, its files with role, label and note, order, removal. Borrows the file editor module's stylesheet (`file_editor/file-editor.css`). |
| `list.php`, `files.php`, `for_file.php` | Read: every project with its file count and the kinds and roles to offer; one project with its files in order; the projects a file is in. |
| `save.php`, `delete.php`, `link.php`, `update_link.php`, `move.php`, `unlink.php` | Write (a same-origin POST with the user's CSRF token in `X-CSRF`, bound parameters only). `link.php` takes `project_id`, `role` and `paths[]` (`files/A/b.png`). |
| `lib.php` | Who may do what, how a path becomes a catalogue row, and the queries. |

**In the file browser** (`js/ui/browser-projects.js`): a dialog before an upload (a project, "New project…" or none, and a role; the files are linked once they have arrived), **Add to project…** on a
selection and in a file's details panel (which also lists the file's projects), and a **Project** filter beside the type filters that shows one project's files in its order (`browser.php?project=ID` opens it).

**Who may do what.** Any signed-in user makes and changes projects; the creator or an administrator deletes one. A file is linked by an administrator, or by a user for files inside `files/<their username>`
(the same rule as moving and removing). A link is changed or removed by an administrator, by whoever linked it, or by someone who may write where the file is. A file with no catalogue row gets one when it is
first linked, as an upload would make it. Renaming or moving a file keeps its links (its id does not change).

**Needs** a database user that may create the tables (every endpoint answers 503 with a message until `schema.sql` has been run). Tests: `tests/test_projects_module.py` (the endpoints, permissions, cascade, move and rename) and
`tests/test_ui_projects.py` (the browser and the page) in webserversAC; they need the mssdocs dev stack with the tables created.

## Units of description (RAD hierarchy, optional)

A project that has a row in `unit_descriptions` (`units.sql`, run once after `schema.sql`) is a **unit of description**: a fonds, collection, series or file, with its own description (title, ISO 8601 dates, extent, administrative history, custodial history, scope and content, language, repository, notes), a parent unit, and as its **items** the files linked to it with the role `description`. The design and its reasons are in `docs/mssdocs-rad-hierarchy-design.md` of the fleet repository (RAD, July 2008: description from the general to the specific, nothing repeated at a lower level).

| File | What it does |
|---|---|
| `units.sql` | The table. Deleting a project deletes its description; there is no key on `parent_id` (the code refuses to delete a unit that has units under it). |
| `unit_lib.php` | The rules, as functions of the set of units: which level may sit under which (a fonds or collection is a top level; a series or file sits under a fonds, collection or series; at most six deep, no cycles), dates (YYYY, YYYY-MM or YYYY-MM-DD, real dates only), the reference code (the segments from the top, joined with `/`), the effective visibility (the least public along the chain), the fields shown from the unit above when empty (custodial history, language, repository, restrictions on access, terms of use), and advice on dates outside the whole (RAD 1.4B2) and repeated segments. |
| `unit_save.php`, `unit_remove.php` | Write. Any signed-in user writes a unit as a draft; **only an administrator sets published or private**. A unit's description is removed by an administrator or the project's creator, and not while other units are under it. |
| `unit_get.php`, `unit_tree.php` | Read: one unit with its chain, reference code, effective visibility, inherited fields, children, items and advice; every unit as a tree. |

`delete.php` refuses a project while units are under it. Every endpoint answers 503 until `units.sql` has been run. A file linked with the role `description` to two units is an item of both (`ips_units_of_file()`); the Metadata panel will report that (a later stage). Tests: `tests/test_units_logic.py` (the rules, in real PHP) and `tests/test_units_module.py` (the endpoints, on the mssdocs dev stack, which gets the table when missing).
