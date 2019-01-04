ALTER TABLE `users`
ADD `email` varchar(255) NOT NULL,
ADD `oauth2token` text NOT NULL AFTER `email`;

ALTER TABLE `users`
CHANGE `git_hub_id` `git_hub_id` int(11) NULL AFTER `id`,
CHANGE `git_hub_token` `git_hub_token` varchar(255) COLLATE 'utf8mb4_czech_ci' NULL AFTER `git_hub_name`;
