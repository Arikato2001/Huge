-- Speichert Nachrichten und kennzeichnet, ob sie bereits gelesen wurden.
CREATE TABLE IF NOT EXISTS `huge`.`messages` (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `conversation_id` int(11) unsigned NOT NULL,
 `sender_id` int(11) NOT NULL,
 `message` text COLLATE utf8_unicode_ci NOT NULL,
 `message_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 = ungelesen, 1 = gelesen',
 `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`),
 KEY `idx_messages_conversation` (`conversation_id`),
 KEY `idx_messages_sender` (`sender_id`),
 KEY `idx_messages_unread` (`conversation_id`, `message_status`),
 CONSTRAINT `fk_messages_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `huge`.`conversations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `huge`.`users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='chat messages';
