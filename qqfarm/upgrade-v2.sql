-- QQFarm schema upgrade v2: add experience points safely.
-- MySQL 5.5+/MariaDB compatible; safe to run more than once.
-- Select the JohnCMS database before importing this file.
SET @qqfarm_db := DATABASE();

SET @qqfarm_sql := IF(
  @qqfarm_db IS NULL,
  'SELECT ''ERROR: Select the JohnCMS database first'' AS qqfarm_upgrade_status',
  IF(
    NOT EXISTS (
      SELECT 1 FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = @qqfarm_db AND TABLE_NAME = 'qqfarm_players'
    ),
    'SELECT ''ERROR: qqfarm_players table is missing; install the base QQFarm schema first'' AS qqfarm_upgrade_status',
    IF(
      EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @qqfarm_db
          AND TABLE_NAME = 'qqfarm_players'
          AND COLUMN_NAME = 'experience'
      ),
      'SELECT ''OK: experience column already exists; nothing to do'' AS qqfarm_upgrade_status',
      'ALTER TABLE `qqfarm_players` ADD COLUMN `experience` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `coins`'
    )
  )
);

PREPARE qqfarm_upgrade_stmt FROM @qqfarm_sql;
EXECUTE qqfarm_upgrade_stmt;
DEALLOCATE PREPARE qqfarm_upgrade_stmt;
