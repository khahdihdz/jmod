-- QQFarm starter schema for JMod / JohnCMS 7.1
-- Back up your database before applying. This file creates only the QQFarm table.
CREATE TABLE IF NOT EXISTS `qqfarm_players` (
  `user_id` INT UNSIGNED NOT NULL,
  `coins` BIGINT UNSIGNED NOT NULL DEFAULT 100,
  `last_daily` DATE DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
