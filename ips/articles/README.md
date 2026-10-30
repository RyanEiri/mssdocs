# Articles

Stories with a title, byline, sanitised HTML body, section, keywords, versions and an optional place in an issue. Design and reasons: `docs/mssdocs-articles-design.md` in the webserversAC repository.

**Tables:** `schema.sql` (`articles`, `article_terms`, `article_revisions`). DDL is applied by the site's administrator; the endpoints answer 503 until the tables exist. No foreign keys.

**Who may do what:** any signed-in user writes an article as a draft and edits a draft; only an administrator publishes, schedules, unpublishes, sets access, edits an article that is not a draft or
removes one (the maker may remove their own draft).

**Body:** whatever the editor sends is cleaned by `sanitize.php` on every save and on restore: an allow-list of elements and attributes, only `http`, `https` (and `mailto` for links) or site paths for
addresses, links get `rel="noopener noreferrer"`. Cleaning twice gives the same result.

**Versions:** every save that changes the title, summary or body adds one; a restore is saved as a new version; the last 100 are kept.

**Status:** `draft`, `scheduled` (with `publish_at`, UTC, ISO 8601), `published`, `unpublished`. A scheduled article is read as published once its time has passed; no job runs.

**Endpoints** (JSON, signed-in, writes are same-origin POSTs with the user's token): `article_list.php`, `article_get.php`, `article_save.php`, `article_status.php`, `article_revision.php`,
`article_restore.php`, `article_remove.php`.

Tests: `tests/test_articles_sanitizer.py` (real PHP in the `mssdocs-php-xsl` image), `tests/test_articles_module.py` (the mssdocs dev stack).
