CREATE TABLE IF NOT EXISTS `club_functions` (
  `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS `member_functions` (
  `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
  `member_id` INTEGER NOT NULL,
  `function_id` INTEGER NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`function_id`) REFERENCES `club_functions` (`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_member_functions_member_id` ON `member_functions` (`member_id`);
CREATE INDEX IF NOT EXISTS `idx_member_functions_function_id` ON `member_functions` (`function_id`);