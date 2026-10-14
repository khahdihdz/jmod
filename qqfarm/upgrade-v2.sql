-- QQFarm schema upgrade v2: add experience points to existing installations.
-- Back up the database first. Run once in the same JohnCMS database.
ALTER TABLE `qqfarm_players`
  ADD COLUMN `experience` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `coins`;
