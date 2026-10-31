# Articles

Stories with a title, byline, sanitised HTML body, section, keywords, versions and an optional place in an issue. Design and reasons: `docs/mssdocs-articles-design.md` in the webserversAC repository.

**Tables:** `schema.sql` (`articles`, `article_terms`, `article_revisions`). DDL is applied by the site's administrator; the endpoints answer 503 until the tables exist. No foreign keys.

**Who may do what:** any signed-in user writes an article as a draft and edits a draft; only an administrator publishes, schedules, unpublishes, sets access, edits an article that is not a draft or
removes one (the maker may remove their own draft).

**Body:** whatever the editor sends is cleaned by `sanitize.php` on every save and on restore: an allow-list of elements and attributes, only `http`, `https` (and `mailto` for links) or site paths for
addresses, links get `rel="noopener noreferrer"`. Cleaning twice gives the same result.

**Versions:** every save that changes the title, summary or body adds one; a restore is saved as a new version; the last 100 are kept.

**Status:** `draft`, `scheduled` (with `publish_at`, UTC, ISO 8601), `published`, `unpublished`. A scheduled article is read as published once its time has passed; no job runs.

**The page** (`index.php`, `articles-app.js`, `articles.css`): the list, the form, the body in CKEditor (from `file_editor/ckeditor`; a plain HTML box when it is not installed), a preview of the saved,
cleaned body, the versions (view, restore), and for an administrator the status, the schedule (a UTC date and time) and who may read it. A change that is not saved is not left without asking. Home card:
`IPS_MODULES` lists `articles`.

**For a site's own pages** (`public.php`, a library, not pages): `ips_articles_public_list/find/search/sections`, `ips_articles_feed_xml`, `ips_articles_sitemap_xml`, `ips_articles_seo` (meta tags and
NewsArticle JSON-LD). Only effectively published articles come back; a signed-in-only article is a pointer (no body, no snippet) for a visitor; the body is cleaned again on the way out. The query syntax,
engine choice and snippets are the Issues module's (`issues/search_lib.php`, which must be present); the database is reached through `php/standalone_db.php`, which reads `php/classes/config.php` as text.

**Endpoints** (JSON, signed-in, writes are same-origin POSTs with the user's token): `article_list.php`, `article_get.php`, `article_save.php`, `article_status.php`, `article_revision.php`,
`article_restore.php`, `article_remove.php`.

Tests: `tests/test_articles_sanitizer.py` (real PHP in the `mssdocs-php-xsl` image), `tests/test_articles_module.py` (endpoints), `tests/test_articles_public.py` (the library on the dev database, clock injected),
`tests/test_articles_public_logic.py` (feed, sitemap, meta tags), `tests/test_ui_articles.py` (headless Chrome; CKEditor on `vesturheimsrit-base`).
