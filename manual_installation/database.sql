DROP TABLE IF EXISTS `auth`;

CREATE TABLE `auth` (
  `username` varchar(99) NOT NULL DEFAULT '',
  `password` varchar(99) NOT NULL DEFAULT '',
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `data`;

CREATE TABLE `data` (
  `shorturl` varchar(8) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `clicks` int(99) NOT NULL DEFAULT '0',
  `ip` varchar(64) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `owner` varchar(99) NOT NULL DEFAULT '',
  `timestamp` datetime NOT NULL,
  UNIQUE KEY `shorturl` (`shorturl`),
  KEY `owner` (`owner`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `data` (`shorturl`, `url`, `clicks`, `ip`, `description`, `owner`, `timestamp`) 
VALUES ('mqfLH','http://www.yahoo.fr/',0,'127.0.0.1','This is a test.','god','1999-12-31 23:59:59');

DROP TABLE IF EXISTS `data_deleted`;

CREATE TABLE `data_deleted` (
  `shorturl` varchar(8) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `clicks` int(99) NOT NULL DEFAULT '0',
  `ip` varchar(64) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `owner` varchar(99) NOT NULL DEFAULT '',
  `timestamp` datetime NOT NULL,
  UNIQUE KEY `shorturl` (`shorturl`),
  KEY `owner` (`owner`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `stats`;

CREATE TABLE `stats` (
  `shorturl` varchar(8) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `ip` varchar(64) NOT NULL DEFAULT '',
  `useragent` varchar(255) NOT NULL DEFAULT '',
  `referer` varchar(255) NOT NULL DEFAULT '',
  `timestamp` datetime NOT NULL,
  `processtime` varchar(99) DEFAULT NULL,
  KEY `shorturl` (`shorturl`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `stats_deleted`;

CREATE TABLE `stats_deleted` (
  `shorturl` varchar(8) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `ip` varchar(64) NOT NULL DEFAULT '',
  `useragent` varchar(255) NOT NULL DEFAULT '',
  `referer` varchar(255) NOT NULL DEFAULT '',
  `timestamp` datetime NOT NULL,
  `processtime` varchar(99) DEFAULT NULL,
  KEY `shorturl` (`shorturl`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
