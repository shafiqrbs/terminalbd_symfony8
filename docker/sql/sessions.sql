CREATE TABLE IF NOT EXISTS `sessions` (
    `sess_id` VARBINARY(128) NOT NULL,
    `sess_data` MEDIUMBLOB NOT NULL,
    `sess_lifetime` INT UNSIGNED NOT NULL,
    `sess_time` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`sess_id`),
    KEY `sessions_sess_lifetime_idx` (`sess_lifetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
