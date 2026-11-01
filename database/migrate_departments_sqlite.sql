CREATE TABLE IF NOT EXISTS `departments` (
  `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS `member_departments` (
  `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
  `member_id` INTEGER NOT NULL,
  `department_id` INTEGER NOT NULL,
  `assigned_date` DATE DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  UNIQUE (`member_id`, `department_id`),
  FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_member_departments_department_id` ON `member_departments` (`department_id`);