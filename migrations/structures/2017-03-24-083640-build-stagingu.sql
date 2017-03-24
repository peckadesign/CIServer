ALTER TABLE `create_test_servers`
CHANGE `pull_request_number` `pull_request_number` int(11) NULL AFTER `id`,
CHANGE `commit` `commit` varchar(40) COLLATE 'utf8_general_ci' NULL AFTER `branch_name`;
