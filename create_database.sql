CREATE TABLE `applications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `secret` varchar(64) DEFAULT NULL,
  `ratelimit` int(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `secret` (`secret`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `application_hits` (
  `hit` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `app` varchar(64) DEFAULT NULL COMMENT 'The unique secret of the originating application.',
  `stamp` int(24) DEFAULT NULL COMMENT 'A timestamp of when the hit occured. Old hits should be purged from the database.',
  `method` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`hit`),
  KEY `app` (`app`),
  KEY `stamp` (`stamp`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

