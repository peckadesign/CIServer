ALTER TABLE `build_requests`
  CHANGE `commit` `commit` varchar(40) COLLATE 'utf8mb4_czech_ci' NOT NULL AFTER `id`,
  CHANGE `branch_name` `branch_name` varchar(255) COLLATE 'utf8mb4_czech_ci' NOT NULL AFTER `commit`,
  CHANGE `output` `output` text COLLATE 'utf8mb4_czech_ci' NULL AFTER `failed`;

ALTER TABLE `create_test_servers`
  CHANGE `branch_name` `branch_name` varchar(255) COLLATE 'utf8mb4_czech_ci' NOT NULL AFTER `pull_request_number`,
  CHANGE `commit` `commit` varchar(40) COLLATE 'utf8mb4_czech_ci' NULL AFTER `branch_name`,
  CHANGE `output` `output` text COLLATE 'utf8mb4_czech_ci' NULL AFTER `failed`;

ALTER TABLE `pull_requests_hooks`
  CHANGE `hook` `hook` text COLLATE 'utf8mb4_czech_ci' NOT NULL AFTER `repository`,
  CHANGE `branch_name` `branch_name` varchar(255) COLLATE 'utf8mb4_czech_ci' NOT NULL AFTER `hook`,
  CHANGE `commit` `commit` varchar(40) COLLATE 'utf8mb4_czech_ci' NOT NULL AFTER `branch_name`;

ALTER TABLE `repositories`
  CHANGE `name` `name` varchar(255) COLLATE 'utf8mb4_czech_ci' NULL AFTER `id`;

ALTER TABLE `users`
  CHANGE `git_hub_name` `git_hub_name` varchar(255) COLLATE 'utf8mb4_czech_ci' NOT NULL AFTER `git_hub_id`,
  CHANGE `git_hub_token` `git_hub_token` varchar(255) COLLATE 'utf8mb4_czech_ci' NOT NULL AFTER `git_hub_name`;
