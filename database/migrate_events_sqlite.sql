CREATE TABLE IF NOT EXISTS `events` (
  `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS `member_events` (
  `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
  `member_id` INTEGER NOT NULL,
  `event_id` INTEGER NOT NULL,
  `event_date` DATE NOT NULL,
  `description` TEXT DEFAULT NULL,
  FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_member_events_member_id` ON `member_events` (`member_id`);
CREATE INDEX IF NOT EXISTS `idx_member_events_event_id` ON `member_events` (`event_id`);