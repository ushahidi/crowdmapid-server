-- --------------------------------------------------------
-- Host:                         10.0.1.3
-- Server version:               5.1.62-0ubuntu0.10.04.1 - (Ubuntu)
-- Server OS:                    debian-linux-gnu
-- HeidiSQL version:             7.0.0.4085
-- Date/time:                    2012-05-22 19:50:35
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET FOREIGN_KEY_CHECKS=0 */;

-- Dumping database structure for riverid
CREATE DATABASE IF NOT EXISTS `riverid` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `riverid`;


-- Dumping structure for table riverid.applications
DROP TABLE IF EXISTS `applications`;
CREATE TABLE IF NOT EXISTS `applications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT '' COMMENT 'Name of the application/website.',
  `url` varchar(255) DEFAULT '' COMMENT 'User-facing URL for the application/website.',
  `secret` char(64) DEFAULT NULL COMMENT 'A secret hash to secure communication.',
  `ratelimit` mediumint(9) DEFAULT '5000' COMMENT 'The maximum number of hits an application can make against the API before it is cut off. 0 turns off this limit for the application.',
  `mail_from` varchar(255) DEFAULT '',
  `note` text NOT NULL COMMENT 'For internal use only.',
  `admin_email` varchar(255) DEFAULT '' COMMENT 'Contact address',
  `admin_identity` varchar(255) DEFAULT '' COMMENT 'Contact name',
  `debug` tinyint(1) DEFAULT '0' COMMENT 'When set to 1 additional benchmarking and debug data will be exposed with API calls.',
  `registered` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `admin_access` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `secret` (`secret`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1 COMMENT='Applicatons that can interact with CrowdmapID.';

-- Data exporting was unselected.


-- Dumping structure for table riverid.application_hits
DROP TABLE IF EXISTS `application_hits`;
CREATE TABLE IF NOT EXISTS `application_hits` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `application` smallint(6) DEFAULT NULL COMMENT 'The id of the originating application.',
  `method` varchar(32) DEFAULT NULL COMMENT 'The API method invoked.',
  `expires` timestamp NULL DEFAULT NULL COMMENT 'Timestamp of when the API hit is intended to expire.',
  PRIMARY KEY (`id`),
  KEY `application` (`application`),
  KEY `stamp` (`expires`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='A log of incoming API calls.';

-- Data exporting was unselected.


-- Dumping structure for event riverid.application_hits_cleanup
DROP EVENT IF EXISTS `application_hits_cleanup`;
DELIMITER //
CREATE EVENT `application_hits_cleanup` ON SCHEDULE EVERY 5 SECOND STARTS '2012-01-01 00:00:00' ON COMPLETION PRESERVE ENABLE COMMENT 'Clears out expired API hits.' DO DELETE FROM application_hits WHERE expires < NOW()//
DELIMITER ;


-- Dumping structure for table riverid.statistics
DROP TABLE IF EXISTS `statistics`;
CREATE TABLE IF NOT EXISTS `statistics` (
  `stat_name` char(32) NOT NULL DEFAULT '',
  `stat_value` int(11) DEFAULT '0',
  PRIMARY KEY (`stat_name`),
  UNIQUE KEY `cache_name` (`stat_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for table riverid.users
DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `hash` char(128) DEFAULT NULL COMMENT 'Identifying hash for the user.',
  `password` char(64) DEFAULT NULL COMMENT 'Partial hash of the user''s password.',
  `password_changed` timestamp NULL DEFAULT NULL COMMENT 'When the user last updated their password.',
  `question` varchar(256) DEFAULT NULL COMMENT 'Lost password question.',
  `answer` char(128) DEFAULT NULL COMMENT 'Lost password answer.',
  `token` char(32) DEFAULT NULL COMMENT 'Authorization token for making modifications to the account.',
  `token_memory` varchar(256) DEFAULT NULL COMMENT 'The pending string alteration assigned to the token.',
  `token_expires` timestamp NULL DEFAULT NULL COMMENT 'When the authorization token expires.',
  `registered` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp of when the user registered with the system.',
  `avatar` varchar(256) DEFAULT NULL COMMENT 'URL to the user''s profile graphic.',
  `admin` tinyint(1) DEFAULT '0' COMMENT 'Is the user an administrator of this RiverID installation?',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1;

-- Data exporting was unselected.


-- Dumping structure for event riverid.users_tokens_cleanup
DROP EVENT IF EXISTS `users_tokens_cleanup`;
DELIMITER //
CREATE EVENT `users_tokens_cleanup` ON SCHEDULE EVERY 120 SECOND STARTS '2012-03-10 01:27:31' ON COMPLETION PRESERVE ENABLE COMMENT 'Nullify expired tokens.' DO UPDATE users 
SET token = '', token_memory = '', token_expires = NULL 
WHERE expires IS NOT NULL AND expires < NOW()//
DELIMITER ;


-- Dumping structure for table riverid.user_addresses
DROP TABLE IF EXISTS `user_addresses`;
CREATE TABLE IF NOT EXISTS `user_addresses` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `master` int(1) DEFAULT '0',
  `confirmed` int(1) DEFAULT '0',
  `registered` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `user` (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for table riverid.user_badges
DROP TABLE IF EXISTS `user_badges`;
CREATE TABLE IF NOT EXISTS `user_badges` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `application` int(11) DEFAULT NULL,
  `user` int(11) DEFAULT NULL,
  `badge` varchar(128) DEFAULT NULL,
  `title` varchar(128) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `graphic` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `category` varchar(128) DEFAULT NULL,
  `awarded` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `application` (`application`),
  KEY `user` (`user`),
  KEY `badge` (`badge`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for table riverid.user_sessions
DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user` int(10) DEFAULT NULL COMMENT 'Row id of the represented user from the users table.',
  `application` int(10) DEFAULT NULL COMMENT 'Row id representing the assigned application.',
  `session` char(64) DEFAULT NULL COMMENT '64-character alphanumeric unique session identifier.',
  `expire` timestamp NULL DEFAULT NULL COMMENT 'When this session should expire if unused.',
  PRIMARY KEY (`id`),
  KEY `user` (`user`),
  KEY `application` (`application`),
  KEY `session` (`session`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for event riverid.user_sessions_cleanup
DROP EVENT IF EXISTS `user_sessions_cleanup`;
DELIMITER //
CREATE EVENT `user_sessions_cleanup` ON SCHEDULE EVERY 5 MINUTE STARTS '2012-01-01 00:00:00' ON COMPLETION PRESERVE ENABLE COMMENT 'Clear out expired sessions.' DO DELETE FROM user_sessions WHERE expires IS NOT NULL AND expire < NOW()//
DELIMITER ;


-- Dumping structure for table riverid.user_sites
DROP TABLE IF EXISTS `user_sites`;
CREATE TABLE IF NOT EXISTS `user_sites` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user` int(10) DEFAULT '0' COMMENT 'Assigned user.',
  `application` int(10) DEFAULT '0' COMMENT 'Assigned application.',
  `url` varchar(256) DEFAULT '0' COMMENT 'Associated website address.',
  PRIMARY KEY (`id`),
  KEY `url` (`url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for table riverid.user_storage
DROP TABLE IF EXISTS `user_storage`;
CREATE TABLE IF NOT EXISTS `user_storage` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `application` int(11) DEFAULT NULL,
  `user` int(11) DEFAULT NULL,
  `storage_public` int(1) DEFAULT '0',
  `storage_key` varchar(255) DEFAULT NULL,
  `storage_value` varchar(255) DEFAULT NULL,
  `storage_expires` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `application` (`application`),
  KEY `user` (`user`),
  KEY `storage_key` (`storage_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for event riverid.user_storage_cleanup
DROP EVENT IF EXISTS `user_storage_cleanup`;
DELIMITER //
CREATE EVENT `user_storage_cleanup` ON SCHEDULE EVERY 1 MINUTE STARTS '2012-01-01 12:00:00' ON COMPLETION PRESERVE ENABLE COMMENT 'Clear out expired storage data.' DO DELETE FROM user_storage WHERE expires IS NOT NULL AND expires < NOW()//
DELIMITER ;


-- Dumping structure for trigger riverid.application_hits_expiring
DROP TRIGGER IF EXISTS `application_hits_expiring`;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';
DELIMITER //
CREATE TRIGGER `application_hits_expiring` AFTER DELETE ON `application_hits` FOR EACH ROW BEGIN
	SET @appid = OLD.application;
	INSERT INTO statistics (stat_name, stat_value) VALUES (CONCAT('application_api_hits_', @appid), 0) ON DUPLICATE KEY UPDATE stat_value = stat_value - 1;
END//
DELIMITER ;
SET SQL_MODE=@OLD_SQL_MODE;


-- Dumping structure for trigger riverid.application_hits_incoming
DROP TRIGGER IF EXISTS `application_hits_incoming`;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';
DELIMITER //
CREATE TRIGGER `application_hits_incoming` AFTER INSERT ON `application_hits` FOR EACH ROW BEGIN
	SET @appid = NEW.application;
	INSERT INTO statistics (stat_name, stat_value) VALUES (CONCAT('application_api_hits_', @appid), 1) ON DUPLICATE KEY UPDATE stat_value = stat_value + 1;
END//
DELIMITER ;
SET SQL_MODE=@OLD_SQL_MODE;


-- Dumping structure for trigger riverid.users_cleanup
DROP TRIGGER IF EXISTS `users_cleanup`;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';
DELIMITER //
CREATE TRIGGER `users_cleanup` AFTER DELETE ON `users` FOR EACH ROW BEGIN
	SET @userid = OLD.id;
	DELETE FROM user_addresses WHERE user=@userid;
	DELETE FROM user_sessions WHERE user=@userid;
	DELETE FROM user_sites WHERE user=@userid;
	DELETE FROM user_storage WHERE user=@userid;
END//
DELIMITER ;
SET SQL_MODE=@OLD_SQL_MODE;
/*!40014 SET FOREIGN_KEY_CHECKS=1 */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
