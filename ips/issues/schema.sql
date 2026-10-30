-- The Issues module (ips/issues/): a publication's issues as PDFs, with their text extracted page by page for search. Run once. No foreign keys, as in the other optional modules: the code
-- keeps the three tables together (removing an issue removes its pages and contents). All utf8mb4_unicode_ci. Design: docs/mssdocs-issues-design.md in the webserversAC repository.
-- search_text is the accent-folded corrected-or-raw text (php/text_fold.php); its FULLTEXT index is for the search stage. InnoDB's defaults (innodb_ft_min_token_size 3, a stopword list)
-- are the server's, not this file's: the search code checks them and falls back to LIKE when they do not suit.

CREATE TABLE IF NOT EXISTS `issues` (
  `id`              INT(11)       NOT NULL AUTO_INCREMENT,
  `series`          VARCHAR(80)   NOT NULL DEFAULT '',
  `label`           VARCHAR(255)  NOT NULL DEFAULT '',
  `issue_date`      VARCHAR(10)   NOT NULL DEFAULT '',
  `volume`          VARCHAR(40)   NOT NULL DEFAULT '',
  `number`          VARCHAR(40)   NOT NULL DEFAULT '',
  `page_count`      INT(11)       NOT NULL DEFAULT 0,
  `pdf_sha256`      CHAR(64)      NOT NULL DEFAULT '',
  `pdf_size`        BIGINT        NOT NULL DEFAULT 0,
  `original_name`   VARCHAR(255)  NOT NULL DEFAULT '',
  `external_url`    VARCHAR(1000) NOT NULL DEFAULT '',
  `status`          VARCHAR(10)   NOT NULL DEFAULT 'draft',
  `access`          VARCHAR(10)   NOT NULL DEFAULT 'signed-in',
  `extraction`      VARCHAR(10)   NOT NULL DEFAULT 'none',
  `extraction_note` VARCHAR(1000) NOT NULL DEFAULT '',
  `extraction_at`   TIMESTAMP     NULL DEFAULT NULL,
  `notes`           TEXT          NULL,
  `created_by`      VARCHAR(255)  NOT NULL DEFAULT '',
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by`      VARCHAR(255)  NOT NULL DEFAULT '',
  `updated_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `issue_date` (`issue_date`),
  KEY `series_date` (`series`, `issue_date`),
  KEY `pdf_sha256` (`pdf_sha256`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `issue_pages` (
  `issue_id`       INT(11)       NOT NULL,
  `page`           INT(11)       NOT NULL,
  `text_raw`       MEDIUMTEXT    NULL,
  `text_corrected` MEDIUMTEXT    NULL,
  `search_text`    MEDIUMTEXT    NULL,
  `corrected_by`   VARCHAR(255)  NOT NULL DEFAULT '',
  `corrected_at`   TIMESTAMP     NULL DEFAULT NULL,
  PRIMARY KEY (`issue_id`, `page`),
  FULLTEXT KEY `search_text` (`search_text`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `issue_toc` (
  `id`        INT(11)      NOT NULL AUTO_INCREMENT,
  `issue_id`  INT(11)      NOT NULL,
  `position`  INT(11)      NOT NULL DEFAULT 0,
  `title`     VARCHAR(500) NOT NULL DEFAULT '',
  `page_from` INT(11)      NOT NULL DEFAULT 0,
  `page_to`   INT(11)      NOT NULL DEFAULT 0,
  `author`    VARCHAR(255) NOT NULL DEFAULT '',
  `kind`      VARCHAR(40)  NOT NULL DEFAULT 'article',
  PRIMARY KEY (`id`),
  KEY `issue_position` (`issue_id`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
