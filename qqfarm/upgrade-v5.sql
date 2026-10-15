-- QQFarm v5: expandable farm (6 base plots, up to 12).
-- Select the JohnCMS database before running this migration.
SET @qqfarm_db = DATABASE();
SET @qqfarm_has_players = (
  SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = @qqfarm_db AND TABLE_NAME = 'qqfarm_players'
);
SET @qqfarm_has_column = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @qqfarm_db AND TABLE_NAME = 'qqfarm_players'
    AND COLUMN_NAME = 'unlocked_plots'
);
SET @qqfarm_sql = IF(
  @qqfarm_db IS NULL OR @qqfarm_has_players = 0,
  'SELECT ''QQFarm v5: missing database or qqfarm_players table'' AS status',
  IF(
    @qqfarm_has_column = 0,
    'ALTER TABLE qqfarm_players ADD COLUMN unlocked_plots TINYINT UNSIGNED NOT NULL DEFAULT 6 AFTER experience',
    'SELECT ''QQFarm v5: unlocked_plots already exists; no change needed'' AS status'
  )
);
PREPARE qqfarm_stmt FROM @qqfarm_sql;
EXECUTE qqfarm_stmt;
DEALLOCATE PREPARE qqfarm_stmt;
