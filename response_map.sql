--
-- Table structure for table `resource`
--

SET foreign_key_checks = 0;

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `userId` VARCHAR(36) NOT NULL,
  `username` VARCHAR(36) DEFAULT NULL,
  `firstname` VARCHAR(80) DEFAULT NULL,
  `lastname` VARCHAR(80) DEFAULT NULL,
-- The maximum length for an email address is 254 chars by RFC3696 errata
  `email` VARCHAR(254) DEFAULT NULL,
  `create_time` TIMESTAMP NOT NULL DEFAULT 0,
  `update_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE (`userId`),
  UNIQUE (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `resource`;
CREATE TABLE IF NOT EXISTS `resource` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `course_id` VARCHAR(255) NOT NULL,
  `map_id` VARCHAR(36) NOT NULL,
  `head_label` VARCHAR(80) DEFAULT NULL,
  `description_label` VARCHAR(80) DEFAULT NULL,
  `location_label` VARCHAR(80) DEFAULT NULL,
  `url_label` VARCHAR(80) DEFAULT NULL,
  `create_time` TIMESTAMP NOT NULL DEFAULT 0,
  `update_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE no_duplicates (`course_id`, `map_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `response`;
CREATE TABLE IF NOT EXISTS `response` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `resource_id` INT(11) NOT NULL,
  `head` VARCHAR(255) DEFAULT NULL,
  `description` TEXT,
  `location` VARCHAR(255) NOT NULL,
  `latitude` DECIMAL(10,8) NOT NULL,
  `longitude` DECIMAL(11,8) NOT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `thumbnail_url` VARCHAR(255) DEFAULT NULL,
  `url` VARCHAR(2000) DEFAULT NULL,
  `vote_count` INT(11) NOT NULL DEFAULT 0,
  `deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `create_time` TIMESTAMP NOT NULL DEFAULT 0,
  `update_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`resource_id`) REFERENCES `resource` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `feedback`;
CREATE TABLE IF NOT EXISTS `feedback` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `response_id` INT(11) NOT NULL,
  `vote_count` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `create_time` TIMESTAMP NOT NULL DEFAULT 0,
  `update_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`response_id`) REFERENCES `response` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SET foreign_key_checks = 1;