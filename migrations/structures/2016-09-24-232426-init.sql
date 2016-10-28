CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `git_hub_id` int(11) NOT NULL,
  `git_hub_name` varchar(255) NOT NULL,
  `git_hub_token` varchar(255) NOT NULL,
  `system_user` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `repositories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `opened_pull_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pull_request_number` int(11) NOT NULL,
  `repository` int(11) NOT NULL,
  `hook` TEXT NOT NULL,
  `branch_name` varchar(255) NOT NULL,
  `commit` varchar(40) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `repository_id` (`repository`),
  CONSTRAINT `opened_pull_requests._ibfk_1` FOREIGN KEY (`repository`) REFERENCES `repositories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `create_test_servers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pull_request_number` int(11) NOT NULL,
  `branch_name` varchar(255) NOT NULL,
  `commit` varchar(40) NOT NULL,
  `repository` int(11) NOT NULL,
  `succeeded` int(11) NULL,
  `failed` int(11) NULL,
  `output` TEXT NULL,
  `start` DATETIME NULL,
  `finish` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `repository_id` (`repository`),
  CONSTRAINT `create_test_servers._ibfk_1` FOREIGN KEY (`repository`) REFERENCES `repositories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `pull_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `number` int(11) NOT NULL,
  `repository` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `repository_id` (`repository`),
  CONSTRAINT `pull_requests._ibfk_1` FOREIGN KEY (`repository`) REFERENCES `repositories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `build_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `commit` varchar(40) NOT NULL,
  `branch_name` varchar(255) NOT NULL,
  `repository` int(11) NOT NULL,
  `succeeded` int(11) NULL,
  `failed` int(11) NULL,
  `output` TEXT NULL,
  `start` DATETIME NULL,
  `finish` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `repository_id` (`repository`),
  CONSTRAINT `build_requests._ibfk_1` FOREIGN KEY (`repository`) REFERENCES `repositories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



