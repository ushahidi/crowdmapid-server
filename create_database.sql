-- --------------------------------------------------------
-- Host:                         10.0.1.9
-- Server version:               5.1.41-3ubuntu12.10 - (Ubuntu)
-- Server OS:                    debian-linux-gnu
-- HeidiSQL version:             7.0.0.4085
-- Date/time:                    2012-03-09 17:23:11
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET FOREIGN_KEY_CHECKS=0 */;

-- Dumping database structure for riverid
CREATE DATABASE IF NOT EXISTS `riverid` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `riverid`;


-- Dumping structure for table riverid.applications
CREATE TABLE IF NOT EXISTS `applications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT '' COMMENT 'Name of the application/website.',
  `url` varchar(255) DEFAULT '' COMMENT 'User-facing URL for the application/website.',
  `secret` char(64) DEFAULT NULL COMMENT 'A secret hash to secure communication.',
  `ratelimit` mediumint(9) DEFAULT '5000' COMMENT 'The maximum number of hits an application can make against the API before it is cut off. 0 turns off this limit for the application.',
  `mail_from` varchar(255) DEFAULT '',
  `note` text COMMENT 'For internal use only.',
  `admin_email` varchar(255) DEFAULT '' COMMENT 'Contact address',
  `admin_identity` varchar(255) DEFAULT '' COMMENT 'Contact name',
  `debug` tinyint(1) DEFAULT '0' COMMENT 'When set to 1 additional benchmarking and debug data will be exposed with API calls.',
  `registered` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `secret` (`secret`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for table riverid.application_hits
CREATE TABLE IF NOT EXISTS `application_hits` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `application` smallint(6) DEFAULT NULL COMMENT 'The id of the originating application.',
  `method` varchar(32) DEFAULT NULL COMMENT 'The API method invoked.',
  `expires` timestamp NULL DEFAULT NULL COMMENT 'Timestamp of when the API hit is intended to expire.',
  PRIMARY KEY (`id`),
  KEY `application` (`application`),
  KEY `stamp` (`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for table riverid.cache
CREATE TABLE IF NOT EXISTS `cache` (
  `cache_name` char(32) NOT NULL DEFAULT '',
  `cache_value` int(11) DEFAULT '0',
  PRIMARY KEY (`cache_name`),
  UNIQUE KEY `cache_name` (`cache_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for event riverid.cache_hits_expire
DELIMITER //
CREATE EVENT `cache_hits_expire` ON SCHEDULE EVERY 5 SECOND STARTS '2012-01-01 00:00:00' ON COMPLETION PRESERVE ENABLE COMMENT 'Clears out expired API hits.' DO DELETE FROM application_hits WHERE expires < NOW()//
DELIMITER ;


-- Dumping structure for table riverid.users
CREATE TABLE IF NOT EXISTS `users` (
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

-- Data exporting was unselected.


-- Dumping structure for table riverid.user_sessions
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user` int(10) DEFAULT NULL COMMENT 'Row id of the represented user from the users table.',
  `application` int(10) DEFAULT NULL COMMENT 'Row id representing the assigned application.',
  `session` char(64) DEFAULT NULL COMMENT '64-character alphanumeric unique session identifier.',
  `expire` timestamp NULL DEFAULT NULL COMMENT 'When this session should expire if unused.',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for event riverid.user_sessions_expire
DELIMITER //
CREATE EVENT `user_sessions_expire` ON SCHEDULE EVERY 5 MINUTE STARTS '2012-01-01 00:00:00' ON COMPLETION NOT PRESERVE ENABLE COMMENT 'Clear out expired sessions.' DO DELETE FROM user_sessions WHERE expire < NOW()//
DELIMITER ;


-- Dumping structure for table riverid.user_sites
CREATE TABLE IF NOT EXISTS `user_sites` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user` int(10) DEFAULT '0' COMMENT 'Assigned user.',
  `application` int(10) DEFAULT '0' COMMENT 'Assigned application.',
  `url` varchar(256) DEFAULT '0' COMMENT 'Associated website address.',
  PRIMARY KEY (`id`),
  KEY `url` (`url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for trigger riverid.application_hits_add
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';
DELIMITER //
CREATE TRIGGER `application_hits_add` AFTER INSERT ON `application_hits` FOR EACH ROW BEGIN
	SET @appid = NEW.application;
	INSERT INTO cache (cache_name, cache_value) VALUES (CONCAT('app_hits_count_', @appid), 1) ON DUPLICATE KEY UPDATE cache_value = cache_value + 1;
END//
DELIMITER ;
SET SQL_MODE=@OLD_SQL_MODE;


-- Dumping structure for trigger riverid.application_hits_expire
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';
DELIMITER //
CREATE TRIGGER `application_hits_expire` AFTER DELETE ON `application_hits` FOR EACH ROW BEGIN
	SET @appid = OLD.application;
	INSERT INTO cache (cache_name, cache_value) VALUES (CONCAT('app_hits_count_', @appid), 0) ON DUPLICATE KEY UPDATE cache_value = cache_value - 1;
END//
DELIMITER ;
SET SQL_MODE=@OLD_SQL_MODE;
/*!40014 SET FOREIGN_KEY_CHECKS=1 */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
