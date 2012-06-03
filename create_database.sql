SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


DROP TABLE IF EXISTS `applications`;
CREATE TABLE IF NOT EXISTS `applications` (
  `id` tinyint(4) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `secret` char(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `ratelimit` smallint(6) unsigned NOT NULL DEFAULT '5000',
  `mail_from` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `note` text COLLATE utf8_unicode_ci NOT NULL,
  `admin_email` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `admin_identity` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `debug` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `registered` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `admin_access` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `secret` (`secret`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci CHECKSUM=1 COMMENT='Registry of applications that are allowed access to this ser';

DROP TABLE IF EXISTS `application_hits`;
CREATE TABLE IF NOT EXISTS `application_hits` (
  `application` tinyint(4) unsigned NOT NULL,
  `expires` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `application` (`application`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='A log of incoming API calls. Used to determine when an appli';
DROP TRIGGER IF EXISTS `application_hits_expiring`;
DELIMITER //
CREATE TRIGGER `application_hits_expiring` AFTER DELETE ON `application_hits`
 FOR EACH ROW BEGIN
	SET @appid = OLD.application;
	INSERT INTO statistics (stat_name, stat_value) VALUES (CONCAT('application_api_hits_', @appid), 0) ON DUPLICATE KEY UPDATE stat_value = stat_value - 1;
END
//
DELIMITER ;
DROP TRIGGER IF EXISTS `application_hits_incoming`;
DELIMITER //
CREATE TRIGGER `application_hits_incoming` AFTER INSERT ON `application_hits`
 FOR EACH ROW BEGIN
	SET @appid = NEW.application;
	INSERT INTO statistics (stat_name, stat_value) VALUES (CONCAT('application_api_hits_', @appid), 1) ON DUPLICATE KEY UPDATE stat_value = stat_value + 1;
END
//
DELIMITER ;

DROP TABLE IF EXISTS `statistics`;
CREATE TABLE IF NOT EXISTS `statistics` (
  `stat_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `stat_value` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`stat_name`),
  KEY `stat_name` (`stat_name`),
  KEY `stat_value` (`stat_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='A collection of various statistics about the service.';

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `hash` char(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `password` char(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `password_changed` timestamp NULL DEFAULT NULL,
  `question` varchar(256) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `answer` char(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `token` char(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `token_memory` varchar(256) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `token_expires` timestamp NULL DEFAULT NULL,
  `registered` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `avatar` varchar(256) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `admin` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci CHECKSUM=1 COMMENT='Registry of user accounts.';

DROP TABLE IF EXISTS `user_addresses`;
CREATE TABLE IF NOT EXISTS `user_addresses` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(11) unsigned NOT NULL,
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `master` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `confirmed` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `registered` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `user` (`user`),
  KEY `master` (`master`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Registry of user email addresses.';

DROP TABLE IF EXISTS `user_aliases`;
CREATE TABLE IF NOT EXISTS `user_aliases` (
  `hash` char(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `user` char(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `note` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`hash`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=FIXED COMMENT='A registry of accounts that have been merged. Applications';

DROP TABLE IF EXISTS `user_badges`;
CREATE TABLE IF NOT EXISTS `user_badges` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(11) unsigned NOT NULL,
  `application` tinyint(4) unsigned NOT NULL,
  `badge` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `graphic` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `category` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `awarded` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `application` (`application`),
  KEY `user` (`user`),
  KEY `badge` (`badge`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Registry of user badges.';

DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(11) unsigned NOT NULL,
  `application` tinyint(4) unsigned NOT NULL,
  `session` char(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `expire` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user` (`user`),
  KEY `application` (`application`),
  KEY `session` (`session`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Registry of user sessions.';

DROP TABLE IF EXISTS `user_sites`;
CREATE TABLE IF NOT EXISTS `user_sites` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(11) unsigned NOT NULL,
  `application` tinyint(4) unsigned NOT NULL,
  `url` varchar(256) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `url` (`url`(255)),
  KEY `application` (`application`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Registry of sites associated with user accounts.';

DROP TABLE IF EXISTS `user_storage`;
CREATE TABLE IF NOT EXISTS `user_storage` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(11) unsigned NOT NULL,
  `application` tinyint(4) unsigned NOT NULL,
  `storage_public` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `storage_key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `storage_value` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `storage_expires` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `application` (`application`),
  KEY `user` (`user`),
  KEY `storage_key` (`storage_key`),
  KEY `storage_expires` (`storage_expires`),
  KEY `storage_public` (`storage_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='A simple key-value store for user accounts.';


ALTER TABLE `application_hits`
  ADD CONSTRAINT `application_hits_ibfk_1` FOREIGN KEY (`application`) REFERENCES `applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_2` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `user_aliases`
  ADD CONSTRAINT `user_aliases_ibfk_3` FOREIGN KEY (`hash`) REFERENCES `users` (`hash`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_aliases_ibfk_4` FOREIGN KEY (`user`) REFERENCES `users` (`hash`) ON UPDATE CASCADE;

ALTER TABLE `user_badges`
  ADD CONSTRAINT `user_badges_ibfk_3` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_badges_ibfk_4` FOREIGN KEY (`application`) REFERENCES `applications` (`id`) ON UPDATE CASCADE;

ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_3` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_sessions_ibfk_4` FOREIGN KEY (`application`) REFERENCES `applications` (`id`) ON UPDATE CASCADE;

ALTER TABLE `user_sites`
  ADD CONSTRAINT `user_sites_ibfk_3` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_sites_ibfk_4` FOREIGN KEY (`application`) REFERENCES `applications` (`id`) ON UPDATE CASCADE;

ALTER TABLE `user_storage`
  ADD CONSTRAINT `user_storage_ibfk_3` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_storage_ibfk_5` FOREIGN KEY (`application`) REFERENCES `applications` (`id`) ON UPDATE CASCADE;

DELIMITER $$
DROP EVENT `application_hits_cleanup`$$
CREATE EVENT `application_hits_cleanup` ON SCHEDULE EVERY 1 MINUTE STARTS '2012-01-01 00:00:00' ON COMPLETION PRESERVE ENABLE DO DELETE FROM application_hits WHERE expires < NOW()$$

DROP EVENT `users_tokens_cleanup`$$
CREATE EVENT `users_tokens_cleanup` ON SCHEDULE EVERY 5 MINUTE STARTS '2012-01-01 00:00:00' ON COMPLETION PRESERVE ENABLE DO UPDATE users 
SET token = '', token_memory = '', token_expires = NULL 
WHERE expires IS NOT NULL AND expires < NOW()$$

DROP EVENT `user_sessions_cleanup`$$
CREATE EVENT `user_sessions_cleanup` ON SCHEDULE EVERY 5 MINUTE STARTS '2012-01-01 00:00:00' ON COMPLETION PRESERVE ENABLE DO DELETE FROM user_sessions WHERE expires IS NOT NULL AND expire < NOW()$$

DROP EVENT `user_storage_cleanup`$$
CREATE EVENT `user_storage_cleanup` ON SCHEDULE EVERY 5 MINUTE STARTS '2012-01-01 00:00:00' ON COMPLETION PRESERVE ENABLE DO DELETE FROM user_storage WHERE expires IS NOT NULL AND expires < NOW()$$

DELIMITER ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
