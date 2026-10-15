-- QQFarm upgrade v4: companion pets and daily pet gift.
-- Safe to re-run: CREATE TABLE IF NOT EXISTS avoids duplicate-table errors.
-- Select the JohnCMS database before importing this file.
CREATE TABLE IF NOT EXISTS `qqfarm_pets` (
  `user_id` INT UNSIGNED NOT NULL,
  `pet_key` VARCHAR(32) NOT NULL DEFAULT 'farm_cat',
  `last_gift` DATE DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
