# Links (optional module)

The entries of a site's **Links** page, kept in the database and edited through a form: a list by group, **Add a link**, **Edit**, **Move up / down**, a two-click **Delete**, and a
**Hidden** box that keeps a link here but off the site. The module is optional: its card on the Home page appears only when this folder exists.

| File | What it does |
|---|---|
| `schema.sql` | The two tables: `link_groups` (name, position) and `links` (group, name, description, address, other addresses as JSON, position, hidden, who changed it last). Run it once. |
| `index.php`, `links-app.js`, `links.css`, `header.php` | The page (Vue, the base shell's own copy). It borrows the file editor module's stylesheet (`file_editor/file-editor.css`: app bar, buttons, table, dialog), so install that module too. |
| `list.php`, `save.php`, `move.php`, `delete.php` | The JSON endpoints behind it. A write is a same-origin POST with the signed-in user's CSRF token in `X-CSRF`, bound parameters only. |
| `lib.php` | Who may edit (any signed-in user), what a link may hold, and the queries. |

A link holds a name (255 characters), a description (1000), an `http://` or `https://` address (no spaces) and up to ten other addresses with labels. An address is listed once: saving one that another entry
has is refused. Groups are rows of `link_groups`: the form picks among them; add one with `INSERT INTO link_groups (name, position) VALUES ('Name', 6);`.

**Needs** `mbstring` and a database user that may create the tables (the module answers 503 with a message until `schema.sql` has been run). **A site reads the links** from the two tables with a plain query;
`links` ordered by its group's `position`, then its own, with `hidden = 0`, is the page's order. Tests: `tests/test_links_module.py` and `tests/test_ui_links_module.py` in webserversAC.
