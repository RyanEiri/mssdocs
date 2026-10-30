# Issues (optional module)

A publication's **issues as PDFs**, with the text read page by page for search. Design, reasons and the plan for testing the search: `docs/mssdocs-issues-design.md` in the fleet repository. This is stage J1 (the data, the PDF store, the text extraction and the endpoints); the Issues page, the search and the page-correction screen are the next stages.

| File | What it does |
|---|---|
| `schema.sql` | `issues`, `issue_pages` (raw, corrected and accent-folded text, with a FULLTEXT index) and `issue_toc`. Run it once. No foreign keys; the code keeps the three together. |
| `lib.php` | Who may do what, the checks on a form, the PDF store (a copy named by its SHA-256 in `upload/archives/issues/xx/`), the queries. |
| `issue_create.php` | Makes an issue from a PDF already in the file store (`path=files/A/b.pdf`, copied; the same file twice is stored once) or, with no path, from an external address only (an issue hosted elsewhere, such as timarit.is). A draft, for signed-in readers. |
| `issue_save.php`, `issue_remove.php`, `issue_extract.php` | Change an issue's description, remove it (its PDF too when no other issue uses the file), read its text again (a correction is kept). |
| `issue_get.php`, `issue_list.php` | Read: one issue with how much of its text was read; every issue, newest first (an undated one last). |
| `file.php` | The PDF, by `id`, with byte ranges, an ETag and `nosniff`; allowed by `ips_access_can_read()`. A visitor who may not read it gets 404 (a draft's existence is not told). |
| `extract.php` | `pdfinfo` and `pdftotext` as fixed commands with no shell, a time and a size limit; judges a run of pages (`done`, or `no-text` for a scan). |
| `../php/issue_worker.php` | The background worker (CLI only), started by the endpoints, reporting through the issue's `extraction` state (`pending`, `running`, `done`, `no-text`, `failed`, with a note). |
| `../php/text_fold.php`, `../php/access.php`, `../php/iso_date.php` | Folding for search (Þ, ð, æ, ö, accents, soft-hyphen joins, symbol-font bullets, curly quotes), the one access function a subscription system would replace, and ISO 8601 dates. |

**Who may do what** (Ryan, 2026-10-30): any signed-in user makes an issue and edits a draft; only an administrator sets `published` or `private`, sets the access, or edits or removes a published issue; the maker of a draft may remove it. **Access:** `open` (anyone, once published) or `signed-in`; a draft or private issue is for signed-in editors only.

**Needs:** `poppler-utils` on the host (`pdfinfo`, `pdftotext`; `php_extra_packages`), PHP `mbstring`, `exec` allowed (as for the folder zip), and the DDL applied by the site's administrator. **The nginx gate must be deployed** (the three IPS sites have it): it is what keeps the direct address of a PDF under `upload/archives/` from visitors; a site without it must not hold a restricted issue (the base's own dev stack has no gate). Settings in `php/site-config.php`: `IPS_ISSUES_MAX_BYTES` (200 MB), `IPS_ISSUES_MAX_PAGES` (400), `IPS_ISSUES_EXTRACT_TIMEOUT` (180 s).

Tests (fleet repository): `tests/test_issues_logic.py` (folding, access and extraction in real PHP on LaTeX-made PDFs), `tests/test_issues_module.py` (the endpoints, the worker and the PDF's address on the mssdocs dev stack) and `tests/test_issues_gate.py` (the direct address behind the gate, on the vesturheimsrit base stack).
