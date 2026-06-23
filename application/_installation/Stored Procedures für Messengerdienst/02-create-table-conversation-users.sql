-- Ordnet Benutzer den privaten Unterhaltungen und dem Gruppenchat zu.
CREATE TABLE IF NOT EXISTS `huge`.`conversation_users` (
 `conversation_id` int(11) unsigned NOT NULL,
 `user_id` int(11) NOT NULL,
 PRIMARY KEY (`conversation_id`, `user_id`),
 KEY `idx_conversation_users_user` (`user_id`),
 CONSTRAINT `fk_conversation_users_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `huge`.`conversations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `fk_conversation_users_user` FOREIGN KEY (`user_id`) REFERENCES `huge`.`users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='users assigned to chat conversations';
