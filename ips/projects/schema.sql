-- The Projects module (ips/projects/): a project groups files of the file store (the pages of a manuscript, its description, a PDF) under a title, with a role,
-- a position and a label for each file. Run once against the site's database (after the base schema: project_files points at its `files` table).
-- Deleting a project, or a file from the catalogue, removes only the links, never a file.

CREATE TABLE IF NOT EXISTS `projects` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `title`      VARCHAR(255) NOT NULL,
  `kind`       VARCHAR(40)  NOT NULL DEFAULT 'artefact',
  `status`     VARCHAR(20)  NOT NULL DEFAULT 'active',
  `notes`      TEXT         NULL,
  `created_by` VARCHAR(255) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` VARCHAR(255) NOT NULL DEFAULT '',
  `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_files` (
  `project_id` INT(11)       NOT NULL,
  `file_id`    INT(11)       NOT NULL,
  `role`       VARCHAR(40)   NOT NULL DEFAULT 'supporting',
  `position`   INT(11)       NOT NULL DEFAULT 0,
  `label`      VARCHAR(255)  NOT NULL DEFAULT '',
  `note`       VARCHAR(1000) NOT NULL DEFAULT '',
  `linked_by`  VARCHAR(255)  NOT NULL DEFAULT '',
  `linked_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`project_id`, `file_id`),
  KEY `file_id` (`file_id`),
  KEY `project_position` (`project_id`, `position`),
  CONSTRAINT `project_files_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_files_file` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
