-- Speichert die Grunddaten eines Events fuer das Event-Management-System.
CREATE TABLE IF NOT EXISTS `huge`.`events` (
 `event_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `event_title` varchar(120) COLLATE utf8_unicode_ci NOT NULL,
 `event_description` text COLLATE utf8_unicode_ci NOT NULL,
 `event_date` datetime NOT NULL,
 `event_location` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
 `event_max_participants` int(11) unsigned NOT NULL,
 `event_created_by` int(11) NOT NULL,
 `event_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`event_id`),
 KEY `idx_events_date` (`event_date`),
 KEY `fk_events_created_by` (`event_created_by`),
 CONSTRAINT `fk_events_created_by` FOREIGN KEY (`event_created_by`) REFERENCES `huge`.`users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='event data';

-- Speichert die Beziehung zwischen Benutzern und Events.
CREATE TABLE IF NOT EXISTS `huge`.`event_registrations` (
 `registration_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `event_id` int(11) unsigned NOT NULL,
 `user_id` int(11) DEFAULT NULL,
 `participant_name` varchar(120) COLLATE utf8_unicode_ci NOT NULL,
 `participant_email` varchar(254) COLLATE utf8_unicode_ci NOT NULL,
 `registration_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`registration_id`),
 UNIQUE KEY `uniq_event_user` (`event_id`, `user_id`),
 UNIQUE KEY `uniq_event_email` (`event_id`, `participant_email`),
 KEY `idx_event_registrations_user` (`user_id`),
 KEY `idx_event_registrations_email` (`participant_email`),
 CONSTRAINT `fk_event_registrations_event` FOREIGN KEY (`event_id`) REFERENCES `huge`.`events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `fk_event_registrations_user` FOREIGN KEY (`user_id`) REFERENCES `huge`.`users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='event registrations';
