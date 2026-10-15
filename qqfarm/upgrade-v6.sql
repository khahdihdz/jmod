-- QQFarm v6: seed inventory / seed shop.
-- Select the JohnCMS database before running this migration.
SET @qqfarm_db = DATABASE();
SET @qqfarm_has_players = (
  SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = @qqfarm_db AND TABLE_NAME = 'qqfarm_players'
);
SET @qqfarm_has_inventory = (
  SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = @qqfarm_db AND TABLE_NAME = 'qqfarm_inventory'
);
SET @qqfarm_sql = IF(
  @qqfarm_db IS NULL OR @qqfarm_has_players = 0,
  'SELECT ''QQFarm v6: missing database or qqfarm_players table'' AS status',
  IF(
    @qqfarm_has_inventory = 0,
    'CREATE TABLE qqfarm_inventory (user_id INT UNSIGNED NOT NULL, item_key VARCHAR(24) NOT NULL, quantity INT UNSIGNED NOT NULL DEFAULT 0, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (user_id, item_key), KEY idx_qqfarm_inventory_item (item_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
    'SELECT ''QQFarm v6: qqfarm_inventory already exists; no change needed'' AS status'
  )
);
PREPARE qqfarm_stmt FROM @qqfarm_sql;
EXECUTE qqfarm_stmt;
DEALLOCATE PREPARE qqfarm_stmt;
