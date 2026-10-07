-- The Links module (ips/links/): a list of web addresses in groups, kept in two tables. Run once against the site's database (no foreign keys, like the other tables).
-- Groups are rows here, not code: add one with INSERT INTO link_groups (name, position) VALUES ('Name', 6);

CREATE TABLE IF NOT EXISTS `link_groups` (
  `id`       INT(11)      NOT NULL AUTO_INCREMENT,
  `name`     VARCHAR(120) NOT NULL,
  `position` INT(11)      NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `links` (
  `id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `group_id`    INT(11)       NOT NULL,
  `name`        VARCHAR(255)  NOT NULL,
  `description` VARCHAR(1000) NOT NULL DEFAULT '',
  `url`         VARCHAR(2048) NOT NULL,
  `also`        TEXT          NULL COMMENT 'JSON list of [label, url]: the entry''s other addresses',
  `position`    INT(11)       NOT NULL DEFAULT 0,
  `hidden`      TINYINT(1)    NOT NULL DEFAULT 0,
  `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by`  VARCHAR(255)  NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `group_position` (`group_id`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
