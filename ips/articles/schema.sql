-- The Articles module (ips/articles/): stories with a title, byline, body, section, keywords, versions and an optional place in an issue. Run once. No foreign keys, as in the other optional
-- modules: the code keeps the tables together (removing an article removes its keywords and versions). All utf8mb4_unicode_ci. Design: docs/mssdocs-articles-design.md in the webserversAC repository.
-- body is HTML that the sanitiser (articles/sanitize.php) has cleaned on every save; search_text is the accent-folded plain text of title, summary and body (php/text_fold.php).
-- publish_at is UTC. A scheduled article whose publish_at has passed is effectively published: that is decided when it is read, not by a job.

CREATE TABLE IF NOT EXISTS `articles` (
  `id`              INT(11)       NOT NULL AUTO_INCREMENT,
  `title`           VARCHAR(255)  NOT NULL,
  `slug`            VARCHAR(100)  NOT NULL,
  `summary`         VARCHAR(1000) NOT NULL DEFAULT '',
  `body`            MEDIUMTEXT    NULL,
  `search_text`     MEDIUMTEXT    NULL,
  `section`         VARCHAR(80)   NOT NULL DEFAULT '',
  `authors`         VARCHAR(500)  NOT NULL DEFAULT '',
  `status`          VARCHAR(12)   NOT NULL DEFAULT 'draft',
  `access`          VARCHAR(10)   NOT NULL DEFAULT 'open',
  `publish_at`      DATETIME      NULL DEFAULT NULL,
  `published_at`    DATETIME      NULL DEFAULT NULL,
  `issue_id`        INT(11)       NULL DEFAULT NULL,
  `issue_page`      INT(11)       NULL DEFAULT NULL,
  `image`           VARCHAR(1000) NOT NULL DEFAULT '',
  `seo_title`       VARCHAR(255)  NOT NULL DEFAULT '',
  `seo_description` VARCHAR(320)  NOT NULL DEFAULT '',
  `created_by`      VARCHAR(255)  NOT NULL DEFAULT '',
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by`      VARCHAR(255)  NOT NULL DEFAULT '',
  `updated_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `status_publish` (`status`, `publish_at`),
  KEY `section` (`section`),
  KEY `issue` (`issue_id`, `issue_page`),
  FULLTEXT KEY `search_text` (`search_text`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `article_terms` (
  `article_id` INT(11)      NOT NULL,
  `term`       VARCHAR(60)  NOT NULL,
  `term_key`   VARCHAR(80)  NOT NULL,
  PRIMARY KEY (`article_id`, `term_key`),
  KEY `term_key` (`term_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `article_revisions` (
  `id`         INT(11)       NOT NULL AUTO_INCREMENT,
  `article_id` INT(11)       NOT NULL,
  `revision`   INT(11)       NOT NULL,
  `title`      VARCHAR(255)  NOT NULL DEFAULT '',
  `summary`    VARCHAR(1000) NOT NULL DEFAULT '',
  `body`       MEDIUMTEXT    NULL,
  `saved_by`   VARCHAR(255)  NOT NULL DEFAULT '',
  `saved_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `note`       VARCHAR(255)  NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `article_revision` (`article_id`, `revision`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
