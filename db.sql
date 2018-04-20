-- Adminer 4.3.1 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_fbid` varchar(64) NOT NULL,
  `origin` varchar(64) NOT NULL,
  `owner` varchar(1024) NOT NULL,
  `post_url` varchar(1024) NOT NULL,
  `text` text,
  `files` text,
  `files_name` text,
  `scraped_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_fbid` (`post_fbid`),
  KEY `origin` (`origin`),
  KEY `owner` (`owner`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- 2018-04-20 22:55:58
