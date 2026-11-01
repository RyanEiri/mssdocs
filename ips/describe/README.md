# Describe

Administrators answer a printed-book record's open cataloguing questions in the browser. The records are TEI files (a `biblStruct` for the publication beside an `msDesc` for the copy; the profile is
section 9 of `docs/mssdocs-metadata-mapping.md` in the webserversAC repository). No database tables, no DDL.

**Records folder.** Any folder under `upload/files/` that holds `*.xml` printed-book records. An optional hidden `.describe.json`, `{"photographs": "<folder relative to this one>"}` (written by
`tools/books-to-tei.py generate --photographs-dir`), names the folder of photographs; the ones no record uses are the *tray*. A manuscript record, a record with a DTD or named entities, more than one
`monogr` or more than 1 MB is listed with the reason and never edited here (open it in the file editor).

**Who:** every write is administrator-only, a same-origin POST with the user's CSRF token. Others get a message that the page is for administrators.

**Questions** (computed live by `ips_describe_questions()`, never stored): author, date, place, publisher, edition, volume, title, photographs, title-page. A question is open until the record holds the
data or a decision for its key. **Confirmed unknown** is a decision: `<change type="decision" subtype="<key>" who="<user>" when="<date>" ana="confirmed|retracted">` in `revisionDesc`, kept in the
history of the record and never printed by RAD. Checked and notes are `<change type="checked">` and `<change type="note">`.

**Saving** rewrites only the answered parts of the DOM. An etag (sha256 of the file) is checked first (409 when the file changed), the old file goes to `.history/<id>/<UTC>-<user>.xml` (last 30 kept,
hidden, never served), and the new one is written through a temporary file and a rename. A save that changes nothing writes nothing.

**Status:** `draft`, `published`, `private`; Publish is refused, with the open questions listed, while any are open.

**Photographs:** moving one between books or the tray is `photo_assign.php` (one book per photograph, under a lock). **No photograph is ever changed**: turning is the file browser's `.rotation.json`
sidecar through `json/json-photo_rotate.php`.

**The viewer:** beside the form (below 1300 px the list moves above and the form and viewer share a row; below 900 px the viewer comes first, unpinned) the photograph being described is shown large and stays in view while the form scrolls. It opens on the title page (else the first photograph, else the first one in no book); the strip under it holds this book's photographs and, dashed, the ones in no book; Fit, 100% and 200% zoom (the stage scrolls), the turn buttons (the same sidecar), previous/next (also the left and right arrow keys outside a text box), a role menu, and Not this book or Add to this book. Clicking a card's picture shows that photograph. It loads the `thumbnail-2048` copy when there is one, else the original.

**Endpoints** (JSON; 400 where a missing record would be a 404, because nginx replaces a PHP 404 body): `record_list.php`, `record_get.php`, `record_save.php`, `record_status.php`,
`photo_assign.php`. Home card: `IPS_MODULES` lists `describe`. Tests: `tests/test_describe_logic.py`, `test_describe_module.py`, `test_ui_describe.py`.
