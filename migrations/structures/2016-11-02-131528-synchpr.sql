CREATE TABLE `synchronized_pull_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pull_request_number` int(11) NOT NULL,
  `repository` int(11) NOT NULL,
  `hook` TEXT NOT NULL,
  `branch_name` varchar(255) NOT NULL,
  `commit` varchar(40) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `repository_id` (`repository`),
  CONSTRAINT `synchronized_pull_requests._ibfk_1` FOREIGN KEY (`repository`) REFERENCES `repositories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
