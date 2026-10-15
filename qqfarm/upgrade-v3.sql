-- QQFarm upgrade v3: daily mission progress/rewards.
-- Back up the database first; run once on existing installations.
CREATE TABLE IF NOT EXISTS `qqfarm_daily_missions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `mission_date` DATE NOT NULL,
  `mission_key` VARCHAR(32) NOT NULL,
  `progress` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `claimed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_qqfarm_daily_mission` (`user_id`, `mission_date`, `mission_key`),
  KEY `idx_qqfarm_mission_date` (`mission_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
