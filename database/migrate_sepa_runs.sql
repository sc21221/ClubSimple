-- Run once on an existing MariaDB/MySQL database after taking a backup.
CREATE TABLE IF NOT EXISTS `sepa_runs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `due_date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `membership_fees`
  ADD COLUMN `sepa_run_id` int(11) DEFAULT NULL,
  ADD KEY `idx_membership_fees_sepa_run_id` (`sepa_run_id`),
  ADD CONSTRAINT `fk_membership_fees_sepa_run` FOREIGN KEY (`sepa_run_id`) REFERENCES `sepa_runs` (`id`) ON DELETE RESTRICT;