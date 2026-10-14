# QQFarm module for JMod / JohnCMS 7.1

This directory provides an initial JohnCMS-native integration scaffold. It does **not** claim to port the original QQFarm Flash client or all its game mechanics.

## Install

1. Back up the site files and database.
2. Upload this `qqfarm/` directory to the JohnCMS web root.
3. Import `install.sql` into the same database used by JohnCMS (for example via phpMyAdmin).
4. Visit `/qqfarm/` while logged in to JohnCMS.
5. Verify the player row is created once and that daily reward can only be claimed once per server date.

## What is implemented

- Uses the JMod bootstrap and the JohnCMS `UserInterface` session.
- Requires an authenticated member.
- Creates a player row on first visit (after schema installation).
- Uses prepared statements and an anti-CSRF session token.
- Includes a minimal daily-reward example with a conditional database update.

## Limitations and next steps

- Original QQFarm API adapters (Discuz X / vBulletin) are not ported.
- The original SWF client will not run in most modern browsers. A modern HTML5 client or separately tested Flash-compatible environment is required.
- The original game's SQL schema has not been imported; this scaffold uses only `qqfarm_players`.
- Before production use, test on a staging copy and review authorization, rate limits, economy logic and database migrations.
- The project bootstrap requires PHP 5.6–7.x; do not change the site's PHP version just for this module without testing the entire JMod installation.

## Uninstall

Back up/export data first. Remove the module directory and, if you are sure the data is no longer needed, drop only the `qqfarm_players` table manually.
