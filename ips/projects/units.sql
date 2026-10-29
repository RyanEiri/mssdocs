-- Units of description (ips/projects/, RAD hierarchy; design: docs/mssdocs-rad-hierarchy-design.md in the webserversAC repository). A project that has a row here is a
-- fonds, collection, series or file of an archival description; its items are the TEI files linked to it with the role "description". Run once after schema.sql.
-- Deleting the project deletes its description (a cascading key). parent_id has no key on purpose: the code refuses to delete or move a unit that has children, so a whole
-- fonds can never go with one delete. Dates are ISO 8601 (YYYY, YYYY-MM or YYYY-MM-DD); an empty date_end is an open unit (RAD 1.4B4).

CREATE TABLE IF NOT EXISTS `unit_descriptions` (
  `project_id`        INT(11)      NOT NULL,
  `level`             VARCHAR(20)  NOT NULL,
  `parent_id`         INT(11)      NULL,
  `ref_segment`       VARCHAR(60)  NOT NULL DEFAULT '',
  `title`             VARCHAR(255) NOT NULL DEFAULT '',
  `date_start`        VARCHAR(10)  NOT NULL DEFAULT '',
  `date_end`          VARCHAR(10)  NOT NULL DEFAULT '',
  `extent`            VARCHAR(255) NOT NULL DEFAULT '',
  `admin_history`     TEXT         NULL,
  `custodial_history` TEXT         NULL,
  `scope_content`     TEXT         NULL,
  `language`          VARCHAR(255) NOT NULL DEFAULT '',
  `repository`        VARCHAR(255) NOT NULL DEFAULT '',
  `access_note`       TEXT         NULL,
  `terms_note`        TEXT         NULL,
  `general_note`      TEXT         NULL,
  `accruals`          VARCHAR(255) NOT NULL DEFAULT '',
  `visibility`        VARCHAR(10)  NOT NULL DEFAULT 'draft',
  `updated_by`        VARCHAR(255) NOT NULL DEFAULT '',
  `updated_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`project_id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `unit_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
