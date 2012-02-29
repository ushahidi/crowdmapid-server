CREATE TABLE `application_hits` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `application` smallint(6) DEFAULT NULL COMMENT 'The id of the originating application.',
  `method` varchar(32) DEFAULT NULL COMMENT 'The API method invoked.',
  `stamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp of when the API hit occured.',
  PRIMARY KEY (`id`),
  KEY `application` (`application`),
  KEY `stamp` (`stamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `applications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT '' COMMENT 'Name of the application/website.',
  `url` varchar(255) DEFAULT '' COMMENT 'User-facing URL for the application/website.',
  `secret` char(64) DEFAULT NULL COMMENT 'A secret hash to secure communication.',
  `ratelimit` mediumint(9) DEFAULT '5000' COMMENT 'The maximum number of hits an application can make against the API before it is cut off. 0 turns off this limit for the application.',
  `note` text COMMENT 'For internal use only.',
  `admin_email` varchar(255) DEFAULT '' COMMENT 'Contact address',
  `admin_identity` varchar(255) DEFAULT '' COMMENT 'Contact name',
  `debug` tinyint(1) DEFAULT '0' COMMENT 'When set to 1 additional benchmarking and debug data will be exposed with API calls.',
  `registered` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `secret` (`secret`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `cache` (
  `cache_name` char(32) NOT NULL DEFAULT '',
  `cache_value` int(11) DEFAULT '0',
  PRIMARY KEY (`cache_name`),
  UNIQUE KEY `cache_name` (`cache_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `hash` char(128) DEFAULT NULL COMMENT 'Identifying hash for the user.',
  `email` varchar(256) DEFAULT NULL COMMENT 'User''s email address.',
  `password` char(64) DEFAULT NULL COMMENT 'Partial hash of the user''s password.',
  `question` varchar(256) DEFAULT NULL COMMENT 'Lost password question.',
  `answer` char(128) DEFAULT NULL COMMENT 'Lost password answer.',
  `registered` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp of when the user registered with the system.',
  `accessed` timestamp NULL DEFAULT NULL COMMENT 'Timestamp of when the user last accessed their account.',
  `admin` tinyint(1) DEFAULT '0' COMMENT 'Is the user an administrator of this RiverID installation?',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

################################################################################

LOCK TABLES `cache` WRITE;
INSERT INTO `cache` (`cache_name`, `cache_value`)
VALUES
  ('app_count',0),
  ('app_hits_count',0),
  ('users_count',0);
UNLOCK TABLES;

################################################################################

set delimiter //

CREATE TRIGGER app_hit_register AFTER INSERT ON `application_hits`
FOR EACH ROW BEGIN
  UPDATE cache SET cache_value = cache_value + 1 WHERE cache_name = `app_hits_count` LIMIT 1;
END//

CREATE TRIGGER app_hit_expire AFTER DELETE ON `application_hits`
FOR EACH ROW BEGIN
  UPDATE cache SET cache_value = cache_value - 1 WHERE cache_name = `app_hits_count` LIMIT 1;
END//

CREATE TRIGGER app_register AFTER INSERT ON `applications`
FOR EACH ROW BEGIN
  UPDATE cache SET cache_value = cache_value + 1 WHERE cache_name = `app_count` LIMIT 1;
END//

CREATE TRIGGER app_unregister AFTER DELETE ON `applications`
FOR EACH ROW BEGIN
  UPDATE cache SET cache_value = cache_value - 1 WHERE cache_name = `app_count` LIMIT 1;
END//

CREATE TRIGGER user_register AFTER INSERT ON `users`
FOR EACH ROW BEGIN
  UPDATE cache SET cache_value = cache_value + 1 WHERE cache_name = `users_count` LIMIT 1;
END//

CREATE TRIGGER user_unregister AFTER DELETE ON `users`
FOR EACH ROW BEGIN
  UPDATE cache SET cache_value = cache_value - 1 WHERE cache_name = `users_count` LIMIT 1;
END//
