-- Speichert private Unterhaltungen und den gemeinsamen Gruppenchat.
CREATE TABLE IF NOT EXISTS `huge`.`conversations` (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 = privater Chat, 1 = Gruppenchat',
 `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`),
 KEY `idx_conversations_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='chat conversations';
