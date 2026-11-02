-- Run once on an existing SQLite database after taking a backup.
CREATE TABLE IF NOT EXISTS `sepa_runs` (
  `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `due_date` DATE NOT NULL
);

ALTER TABLE `membership_fees` ADD COLUMN `sepa_run_id` INTEGER DEFAULT NULL REFERENCES `sepa_runs` (`id`) ON DELETE RESTRICT;
CREATE INDEX `idx_membership_fees_sepa_run_id` ON `membership_fees` (`sepa_run_id`);