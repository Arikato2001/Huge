CREATE TABLE IF NOT EXISTS `huge`.`files` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(255) NOT NULL,
 `size` int(11) NOT NULL,
 `downloads` int(11) NOT NULL DEFAULT 0,
 `OwnerID` int(11) NOT NULL,
 `Shared` tinyint(1) NOT NULL DEFAULT 0,
 PRIMARY KEY (`id`),
 KEY `idx_files_owner` (`OwnerID`),
 KEY `idx_files_shared` (`Shared`),
 CONSTRAINT `fk_files_owner`
   FOREIGN KEY (`OwnerID`) REFERENCES `huge`.`users` (`user_id`)
   ON DELETE CASCADE
   ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
