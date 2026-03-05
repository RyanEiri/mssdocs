-- mssdocs IPS database schema
-- Run this against your configured database to create the required tables.

CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(255) NOT NULL,
  `email`      VARCHAR(255) NOT NULL DEFAULT '',
  `password`   VARCHAR(255) NOT NULL,
  `secretword` VARCHAR(60)  NOT NULL,
  `admin`      TINYINT(1)   NOT NULL DEFAULT 0,
  `date`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `folders` (
  `folder_id`   INT(11)      NOT NULL AUTO_INCREMENT,
  `folder_name` VARCHAR(500) NOT NULL,
  PRIMARY KEY (`folder_id`),
  UNIQUE KEY `folder_name` (`folder_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `files` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(500) NOT NULL,
  `size`        BIGINT       NOT NULL DEFAULT 0,
  `type`        VARCHAR(100) NOT NULL DEFAULT '',
  `url`         VARCHAR(500) NOT NULL DEFAULT '',
  `title`       VARCHAR(500) NOT NULL DEFAULT '',
  `description` TEXT,
  `date`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `folder_id`   INT(11)               DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `folder_id` (`folder_id`),
  CONSTRAINT `files_folder` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`folder_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ziparchives` (
  `folder_id` INT(11)      NOT NULL,
  `zip_name`  VARCHAR(500) NOT NULL DEFAULT '',
  `zip_url`   VARCHAR(500) NOT NULL DEFAULT '',
  `zip_date`  DATETIME              DEFAULT NULL,
  PRIMARY KEY (`folder_id`),
  CONSTRAINT `zip_folder` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`folder_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
