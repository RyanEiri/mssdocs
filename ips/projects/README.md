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
