-- Erweitert eine bereits vorhandene Installation um die E-Mail-Bestaetigung.
-- Der Merker sorgt dafuer, dass bestehende Anmeldungen nur beim ersten Lauf als bestaetigt uebernommen werden.
SET @confirmation_columns_missing = (
    SELECT COUNT(*) = 0
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'huge'
      AND TABLE_NAME = 'event_registrations'
      AND COLUMN_NAME = 'registration_confirmed'
);

ALTER TABLE `huge`.`event_registrations`
 ADD COLUMN IF NOT EXISTS `registration_confirmed` tinyint(1) NOT NULL DEFAULT '0' AFTER `participant_email`,
 ADD COLUMN IF NOT EXISTS `registration_confirmation_token` char(64) COLLATE utf8_unicode_ci DEFAULT NULL AFTER `registration_confirmed`,
 ADD COLUMN IF NOT EXISTS `confirmation_expires_at` datetime DEFAULT NULL AFTER `registration_confirmation_token`,
 ADD COLUMN IF NOT EXISTS `registration_confirmed_at` datetime DEFAULT NULL AFTER `confirmation_expires_at`;

-- Bereits vorhandene Anmeldungen stammen noch aus dem alten Ablauf und bleiben deshalb gueltig.
UPDATE `huge`.`event_registrations`
SET `registration_confirmed` = 1,
    `registration_confirmed_at` = `registration_created_at`
WHERE @confirmation_columns_missing = 1;

-- Der eindeutige Token verhindert, dass derselbe Bestaetigungslink mehreren Anmeldungen zugeordnet wird.
SET @token_index_missing = (
    SELECT COUNT(*) = 0
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = 'huge'
      AND TABLE_NAME = 'event_registrations'
      AND INDEX_NAME = 'uniq_registration_confirmation_token'
);
SET @token_index_sql = IF(
    @token_index_missing,
    'ALTER TABLE `huge`.`event_registrations` ADD UNIQUE KEY `uniq_registration_confirmation_token` (`registration_confirmation_token`)',
    'SELECT 1'
);
PREPARE token_index_statement FROM @token_index_sql;
EXECUTE token_index_statement;
DEALLOCATE PREPARE token_index_statement;
