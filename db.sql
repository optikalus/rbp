CREATE TABLE `posts` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `t` int unsigned NOT NULL,
  `parent` smallint unsigned NOT NULL DEFAULT '0',
  `message_author` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `message_author_email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message_subject` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `message_body` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` smallint DEFAULT NULL,
  `ip` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `thread` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `link` enum('n','y') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'n',
  `video` enum('n','y') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'n',
  `image` enum('n','y') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'n',
  `banned` enum('n','y') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'n',
  `score` decimal(3,2) DEFAULT NULL,
  `type` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transient` enum('n','y') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'n',
  PRIMARY KEY (`id`,`t`),
  KEY `message_author` (`message_author`),
  KEY `date` (`date`),
  KEY `keyword` (`message_author`,`message_subject`,`message_body`(255)),
  KEY `parent` (`parent`,`t`),
  KEY `t_date` (`t`,`date`),
  KEY `author_date` (`message_author`,`date`)
) ENGINE=InnoDB AUTO_INCREMENT=10446 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE `links` (
  `id` int NOT NULL DEFAULT '0',
  `t` int NOT NULL DEFAULT '0',
  `link_url` text NOT NULL,
  `link_title` varchar(150) DEFAULT NULL,
  KEY `idandt` (`id`,`t`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `images` (
  `id` int NOT NULL DEFAULT '0',
  `t` int NOT NULL DEFAULT '0',
  `image_url` text NOT NULL,
  KEY `images_index` (`t`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `flags` (
  `id` smallint unsigned NOT NULL DEFAULT '0',
  `t` int unsigned NOT NULL DEFAULT '0',
  `votes` int unsigned NOT NULL DEFAULT '0',
  `score` decimal(3,2) NOT NULL DEFAULT '0.00',
  `type` enum('','stupid','blog','funny','informative','interesting','warn-g','warn-n','troll','nsfw') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
