-- QQFarm native PHP/HTML starter schema for JMod / JohnCMS 7.1
-- Back up the database before applying. These are module-owned tables only.
CREATE TABLE IF NOT EXISTS `qqfarm_players` (
  `user_id` INT UNSIGNED NOT NULL,
  `coins` BIGINT UNSIGNED NOT NULL DEFAULT 100,
  `experience` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `last_daily` DATE DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `qqfarm_plots` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `plot_no` TINYINT UNSIGNED NOT NULL,
  `crop_key` VARCHAR(24) DEFAULT NULL,
  `planted_at` DATETIME DEFAULT NULL,
  `ready_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_qqfarm_user_plot` (`user_id`, `plot_no`),
  KEY `idx_qqfarm_ready` (`user_id`, `ready_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

CREATE TABLE IF NOT EXISTS `qqfarm_pets` (
  `user_id` INT UNSIGNED NOT NULL,
  `pet_key` VARCHAR(32) NOT NULL DEFAULT 'farm_cat',
  `last_gift` DATE DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
