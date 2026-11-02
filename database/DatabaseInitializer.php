<?php

declare(strict_types=1);

namespace Clubmanager\Database;

use PDO;
use Clubmanager\Core\Logger;

/**
 * Database Schema Initializer & Seeder
 *
 * Automatically provisions database tables for both SQLite and MariaDB/MySQL
 * and seeds realistic demonstration data for "Musikverein Denkendorf".
 *
 * @package Clubmanager\Database
 * @author  Reinhard Schiessl
 */
class DatabaseInitializer
{
    /**
     * Run schema creation and seed data.
     *
     * @param PDO $pdo
     * @param string $driver
     * @return void
     */
    public static function initialize(PDO $pdo, string $driver = 'sqlite'): void
    {
        Logger::info("Initializing database schema for driver: {$driver}");

        if ($driver === 'sqlite') {
            static::createSqliteSchema($pdo);
        } else {
            static::createMysqlSchema($pdo);
        }

        static::seedData($pdo);
        Logger::info("Database schema and seed data created successfully.");
    }

    /**
     * Create SQLite schema.
     *
     * @param PDO $pdo
     * @return void
     */
    protected static function createSqliteSchema(PDO $pdo): void
    {

        $pdo->exec('PRAGMA foreign_keys = ON');
        // language=SQLite
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `clubs` (
              `Id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `name` VARCHAR(100) NOT NULL,
              `name2` VARCHAR(100) DEFAULT NULL,
              `boss` VARCHAR(40) DEFAULT NULL,
              `street` VARCHAR(60) DEFAULT NULL,
              `zip` VARCHAR(20) DEFAULT NULL,
              `city` VARCHAR(60) DEFAULT NULL,
              `country` VARCHAR(20) DEFAULT NULL,
              `email` VARCHAR(80) DEFAULT NULL,
              `phone` VARCHAR(20) DEFAULT NULL,
              `bank_name` VARCHAR(100) DEFAULT NULL,
              `bank_creditor_id` VARCHAR(32) NOT NULL,
              `bank_bic` VARCHAR(32) NOT NULL,
              `bank_iban` VARCHAR(32) NOT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_by` VARCHAR(80) DEFAULT NULL,
              `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS `fee_classes` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `name` VARCHAR(100) NOT NULL UNIQUE,
              `description` TEXT DEFAULT NULL,
              `base_amount` DECIMAL(10,2) NOT NULL,
              `currency` VARCHAR(3) DEFAULT 'EUR',
              `billing_cycle` VARCHAR(20) DEFAULT 'Jährlich' CHECK (`billing_cycle` IS NULL OR `billing_cycle` IN ('Jährlich', 'Halbjährlich', 'Vierteljährlich', 'Monatlich')),
              `is_active` INTEGER DEFAULT 1,
              `min_age` INTEGER DEFAULT 0,
              `max_age` INTEGER DEFAULT 999,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS `members` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `club_id` INTEGER NOT NULL DEFAULT 1,
              `salutation` VARCHAR(10) DEFAULT 'Herr' CHECK (`salutation` IS NULL OR `salutation` IN ('Herr', 'Frau', 'Divers')),
              `sex` VARCHAR(10) DEFAULT 'Männlich' CHECK (`sex` IS NULL OR `sex` IN ('Männlich', 'Weiblich', 'Divers')),
              `first_name` VARCHAR(100) NOT NULL,
              `last_name` VARCHAR(100) NOT NULL,
              `email` VARCHAR(255) DEFAULT NULL,
              `phone` VARCHAR(50) DEFAULT NULL,
              `membership_type` VARCHAR(30) DEFAULT 'Vollmitglied' CHECK (`membership_type` IS NULL OR `membership_type` IN ('Vollmitglied', 'Fördermitglied', 'Jugendmitglied', 'Ehrenmitglied')),
              `status` VARCHAR(20) DEFAULT 'Aktiv' CHECK (`status` IS NULL OR `status` IN ('Aktiv', 'Inaktiv', 'Gesperrt')),
              `invoice_marker` INTEGER DEFAULT 0,
              `street` VARCHAR(255) DEFAULT NULL,
              `zip` VARCHAR(255) DEFAULT NULL,
              `city` VARCHAR(255) DEFAULT NULL,
              `country` VARCHAR(255) DEFAULT NULL,
              `join_date` DATE NOT NULL,
              `leave_date` DATE DEFAULT NULL,
              `birth_date` DATE NOT NULL,
              `bank_name` VARCHAR(255) DEFAULT NULL,
              `bank_bic` VARCHAR(255) DEFAULT NULL,
              `bank_iban` VARCHAR(255) DEFAULT NULL,
              `bank_holder` VARCHAR(255) DEFAULT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_by` VARCHAR(80) DEFAULT NULL,
              `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (`club_id`) REFERENCES `clubs` (`Id`) ON DELETE RESTRICT
            );

            CREATE TABLE IF NOT EXISTS `departments` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `name` VARCHAR(100) NOT NULL
            );

            CREATE TABLE IF NOT EXISTS `member_departments` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `member_id` INTEGER NOT NULL,
              `department_id` INTEGER NOT NULL,
              `assigned_date` DATE DEFAULT NULL,
              `description` TEXT DEFAULT NULL,
              UNIQUE (`member_id`, `department_id`),
              FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
              FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS `club_functions` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `name` VARCHAR(100) NOT NULL
            );

            CREATE TABLE IF NOT EXISTS `member_functions` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `member_id` INTEGER NOT NULL,
              `function_id` INTEGER NOT NULL,
              `start_date` DATE NOT NULL,
              `end_date` DATE DEFAULT NULL,
              `description` TEXT DEFAULT NULL,
              FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
              FOREIGN KEY (`function_id`) REFERENCES `club_functions` (`id`) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS `events` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `name` VARCHAR(100) NOT NULL
            );

            CREATE TABLE IF NOT EXISTS `member_events` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `member_id` INTEGER NOT NULL,
              `event_id` INTEGER NOT NULL,
              `event_date` DATE NOT NULL,
              `description` TEXT DEFAULT NULL,
              FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
              FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS `sepa_runs` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `due_date` DATE NOT NULL
            );

            CREATE TABLE IF NOT EXISTS `membership_fees` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `member_id` INTEGER NOT NULL,
              `fee_class_id` INTEGER NOT NULL,
              `sepa_run_id` INTEGER DEFAULT NULL REFERENCES `sepa_runs` (`id`) ON DELETE RESTRICT,
              `amount` DECIMAL(10,2) NOT NULL,
              `due_date` DATE NOT NULL,
              `paid_date` DATE DEFAULT NULL,
              `status` VARCHAR(20) DEFAULT 'Offen' CHECK (`status` IS NULL OR `status` IN ('Offen', 'Bezahlt', 'Überfällig')),
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE RESTRICT,
              FOREIGN KEY (`fee_class_id`) REFERENCES `fee_classes` (`id`) ON DELETE RESTRICT
            );

            CREATE TABLE IF NOT EXISTS `member_fee_classes` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `member_id` INTEGER NOT NULL,
              `fee_class_id` INTEGER NOT NULL,
              `custom_amount` DECIMAL(10,2) DEFAULT NULL,
              `start_date` DATE NOT NULL,
              `end_date` DATE DEFAULT NULL,
              `is_active` INTEGER DEFAULT 1,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              UNIQUE (`member_id`, `fee_class_id`, `start_date`),
              FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE RESTRICT,
              FOREIGN KEY (`fee_class_id`) REFERENCES `fee_classes` (`id`) ON DELETE RESTRICT
            );

            CREATE TABLE IF NOT EXISTS `users` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `name` VARCHAR(255) NOT NULL,
              `email` VARCHAR(255) NOT NULL UNIQUE,
              `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
              `password` VARCHAR(255) NOT NULL,
              `phone` VARCHAR(255) DEFAULT NULL,
              `admin` INTEGER NOT NULL DEFAULT 0,
              `role` VARCHAR(20) NOT NULL DEFAULT 'user',
              `profile_image` VARCHAR(255) DEFAULT NULL,
              `locale` VARCHAR(5) NOT NULL DEFAULT 'de',
              `club_id` INTEGER DEFAULT NULL,
              `created_by` INTEGER DEFAULT NULL,
              `remember_token` VARCHAR(100) DEFAULT NULL,
              `created_at` TIMESTAMP NULL DEFAULT NULL,
              `updated_at` TIMESTAMP NULL DEFAULT NULL,
              FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
            );

            CREATE TABLE IF NOT EXISTS `audit_logs` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `user_id` INTEGER DEFAULT NULL,
              `action` VARCHAR(50) NOT NULL,
              `entity_type` VARCHAR(50) NOT NULL,
              `entity_id` INTEGER DEFAULT NULL,
              `old_values` TEXT DEFAULT NULL,
              `new_values` TEXT DEFAULT NULL,
              `ip_address` VARCHAR(45) DEFAULT NULL,
              FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
            );

            CREATE INDEX IF NOT EXISTS `idx_members_club_id` ON `members` (`club_id`);
            CREATE INDEX IF NOT EXISTS `idx_member_departments_department_id` ON `member_departments` (`department_id`);
            CREATE INDEX IF NOT EXISTS `idx_member_functions_member_id` ON `member_functions` (`member_id`);
            CREATE INDEX IF NOT EXISTS `idx_member_functions_function_id` ON `member_functions` (`function_id`);
            CREATE INDEX IF NOT EXISTS `idx_member_events_member_id` ON `member_events` (`member_id`);
            CREATE INDEX IF NOT EXISTS `idx_member_events_event_id` ON `member_events` (`event_id`);
            CREATE INDEX IF NOT EXISTS `idx_membership_fees_member_id` ON `membership_fees` (`member_id`);
            CREATE INDEX IF NOT EXISTS `idx_membership_fees_fee_class_id` ON `membership_fees` (`fee_class_id`);
            CREATE INDEX IF NOT EXISTS `idx_membership_fees_sepa_run_id` ON `membership_fees` (`sepa_run_id`);
            CREATE INDEX IF NOT EXISTS `idx_member_fee_classes_fee_class_id` ON `member_fee_classes` (`fee_class_id`);
            CREATE INDEX IF NOT EXISTS `idx_audit_entity` ON `audit_logs` (`entity_type`, `entity_id`);
            CREATE INDEX IF NOT EXISTS `idx_audit_user` ON `audit_logs` (`user_id`);
            CREATE INDEX IF NOT EXISTS `idx_audit_timestamp` ON `audit_logs` (`timestamp`);
        ");
    }

    /**
     * Create MySQL schema from SQL script.
     *
     * @param PDO $pdo
     * @return void
     */
    protected static function createMysqlSchema(PDO $pdo): void
    {
        $sqlPath = __DIR__ . '/schema.sql';
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            $pdo->exec($sql);
        }
    }

    /**
     * Seed initial club data, user credentials, and sample members.
     *
     * @param PDO $pdo
     * @return void
     */
    public static function seedData(PDO $pdo): void
    {
        // Check if users already seeded
        $stmt = $pdo->query("SELECT COUNT(*) FROM `users`");
        if ($stmt && (int)$stmt->fetchColumn() > 0) {
            return; // Already seeded
        }

        // 1. Seed Club Master Data
        $clubStmt = $pdo->prepare("
            INSERT INTO `clubs` (`Id`, `name`, `name2`, `boss`, `street`, `zip`, `city`, `country`, `email`, `phone`, `bank_name`, `bank_creditor_id`, `bank_bic`, `bank_iban`)
            VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $clubStmt->execute([
            'Musikverein Denkendorf e.V.',
            'Tradition & Moderne Blasmusik',
            'Reinhard Schiessl',
            'Wassertal 3b',
            '85095',
            'Denkendorf',
            'Deutschland',
            'info@musikverein-denkendorf.de',
            '+49 8466 ',
            'Raiba Ingolstadt',
            'DE98ZZZ09999999999',
            'BYLADEMxxx',
            'DE02611500200000123456',
        ]);

        // 2. Seed Users (admin: admin / treasurer: kasse)
        $adminPasswordHash = password_hash('admin', PASSWORD_DEFAULT);
        $treasurerPasswordHash = password_hash('kasse', PASSWORD_DEFAULT);
        $now = date('Y-m-d H:i:s');

        $userStmt = $pdo->prepare("
            INSERT INTO `users` (`name`, `email`, `password`, `phone`, `admin`, `role`, `locale`, `club_id`, `created_at`, `updated_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?)
        ");
        $userStmt->execute(['Reinhard Schiessl (Admin)', 'admin@musikverein-denkendorf.de', $adminPasswordHash, '+49 711 3456781', 1, 'admin', 'de', $now, $now]);
        $userStmt->execute(['Kassenwart Denkendorf', 'kasse@musikverein-denkendorf.de', $treasurerPasswordHash, '+49 711 3456782', 0, 'treasurer', 'de', $now, $now]);

        // 3. Seed Fee Classes
        $feeClassStmt = $pdo->prepare("
            INSERT INTO `fee_classes` (`name`, `description`, `base_amount`, `currency`, `billing_cycle`, `is_active`, `min_age`, `max_age`)
            VALUES (?, ?, ?, 'EUR', 'Jährlich', 1, ?, ?)
        ");
        $feeClasses = [
            ['Jugend / Schüler', 'Kinder & Jugendliche in musikalischer Ausbildung', 36.00, 0, 17],
            ['Erwachsene (Aktiv)', 'Aktive Musikerinnen und Musiker ab 18 Jahren', 84.00, 18, 64],
            ['Senioren (Aktiv)', 'Aktive Musikerinnen und Musiker ab 65 Jahren', 60.00, 65, 999],
            ['Fördermitglied', 'Passive Förderer und Freunde der Blasmusik', 40.00, 0, 999],
            ['Ehrenmitglied', 'Beitragsfreie Ehrenmitglieder für besondere Verdienste', 0.00, 0, 999],
        ];

        foreach ($feeClasses as $fc) {
            $feeClassStmt->execute($fc);
        }

        // 4. Seed Realistic Sample Members
        $memberStmt = $pdo->prepare("
            INSERT INTO `members` (
                `club_id`, `salutation`, `sex`, `first_name`, `last_name`, `email`, `phone`, 
                `membership_type`, `status`, `invoice_marker`, `street`, `zip`, `city`, `country`, 
                `join_date`, `leave_date`, `birth_date`, `bank_name`, `bank_bic`, `bank_iban`, `bank_holder`
            ) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Deutschland', ?, NULL, ?, ?, ?, ?, ?)
        ");

        $members = [
            // 1. Young adult who just turned 18 (eligible for age recategorization from Jugend to Erwachsene!)
            [
                'Herr', 'Männlich', 'Lukas', 'Bauer', 'lukas.bauer@example.de', '+49 171 1122334',
                'Vollmitglied', 'Aktiv', 0, 'Albstraße 5', '73770', 'Denkendorf',
                '2018-04-01', date('Y-m-d', strtotime('-18 years -1 month')),
                'Raiba Bayern MItte', 'ESSLDE66XXX', 'DE21611500200000000101', 'Lukas Bauer',
            ],
            // 2. Child member (Jugend)
            [
                'Herr', 'Männlich', 'Felix', 'Müller', 'eltern.mueller@example.de', '+49 711 987654',
                'Jugendmitglied', 'Aktiv', 0, 'Brunnenweg 3', '73770', 'Denkendorf',
                '2021-09-01', date('Y-m-d', strtotime('-12 years -2 months')),
                'Sparkasse Eichstätt', 'GENODE61VBM', 'DE44611600760000000202', 'Martin Müller',
            ],
            // 3. Active Adult Musician (turning 50 soon - milestone birthday!)
            [
                'Frau', 'Weiblich', 'Sabine', 'Wagner', 'sabine.wagner@example.de', '+49 172 3344556',
                'Vollmitglied', 'Aktiv', 0, 'Kirchstraße 14', '73770', 'Denkendorf',
                '2000-01-15', date('Y', strtotime('-50 years')) . '-' . date('m', strtotime('+20 days')) . '-' . date('d', strtotime('+20 days')),
                'Raiba Bayern MItte', 'ESSLDE66XXX', 'DE68611500200000000303', 'Sabine Wagner',
            ],
            // 4. Senior Musician (25-year club anniversary milestone!)
            [
                'Herr', 'Männlich', 'Hans', 'Schneider', 'hans.schneider@example.de', '+49 711 341234',
                'Vollmitglied', 'Aktiv', 0, 'Kelterweg 8', '73770', 'Denkendorf',
                date('Y', strtotime('-25 years')) . '-05-10', '1955-08-14',
                'Audi-Bank Ingolstadt', 'SOLADEST600', 'DE89600501010000000404', 'Hans Schneider',
            ],
            // 5. Supporting Member (Fördermitglied, turning 70 milestone birthday!)
            [
                'Herr', 'Männlich', 'Wilhelm', 'Fischer', 'wilhelm.fischer@example.de', '+49 711 345099',
                'Fördermitglied', 'Aktiv', 0, 'Rosenweg 2', '73770', 'Denkendorf',
                '1985-03-20', date('Y', strtotime('-70 years')) . '-' . date('m', strtotime('+35 days')) . '-' . date('d', strtotime('+35 days')),
                'Raiba Bayern MItte', 'ESSLDE66XXX', 'DE12611500200000000505', 'Wilhelm Fischer',
            ],
            // 6. Honorary Member (Ehrenmitglied, 50 years membership milestone!)
            [
                'Herr', 'Männlich', 'Ernst', 'Zimmermann', 'ernst.zimmermann@example.de', '+49 711 342211',
                'Ehrenmitglied', 'Aktiv', 0, 'Schlossplatz 4', '73770', 'Denkendorf',
                date('Y', strtotime('-50 years')) . '-06-01', '1942-03-12',
                'Raiba Bayern MItte', 'ESSLDE66XXX', 'DE35611500200000000606', 'Ernst Zimmermann',
            ],
            // 7. Member with Invoice Marker (no direct debit)
            [
                'Frau', 'Weiblich', 'Petra', 'Koch', 'petra.koch@example.de', '+49 173 5566778',
                'Fördermitglied', 'Aktiv', 1, 'Gartenstraße 19', '73770', 'Denkendorf',
                '2015-11-01', '1982-10-05',
                'Commerzbank', 'COBADEFFXXX', 'DE57370800400000000707', 'Petra Koch',
            ],
            // 8. Inactive / Archived member
            [
                'Herr', 'Männlich', 'Thomas', 'Braun', 'thomas.braun@example.de', '+49 176 8899001',
                'Vollmitglied', 'Inaktiv', 0, 'Goethestraße 21', '73770', 'Denkendorf',
                '2010-01-01', '1990-12-15',
                'Raiba Bayern MItte', 'ESSLDE66XXX', 'DE79611500200000000808', 'Thomas Braun',
            ],
        ];

        foreach ($members as $m) {
            $memberStmt->execute($m);
        }

        // 5. Seed Member Fee Class Assignments
        // Member 1 (Lukas Bauer - is 18, but currently assigned to Jugend fee_class 1! Perfect test for automated recategorization)
        $mfcStmt = $pdo->prepare("
            INSERT INTO `member_fee_classes` (`member_id`, `fee_class_id`, `custom_amount`, `start_date`, `end_date`, `is_active`)
            VALUES (?, ?, ?, ?, NULL, 1)
        ");

        $mfcStmt->execute([1, 1, null, '2018-04-01']); // Lukas: assigned to Jugend (1)
        $mfcStmt->execute([2, 1, null, '2021-09-01']); // Felix: Jugend (1)
        $mfcStmt->execute([3, 2, null, '2000-01-15']); // Sabine: Erwachsene (2)
        $mfcStmt->execute([4, 3, null, '2020-01-01']); // Hans: Senioren (3)
        $mfcStmt->execute([5, 4, null, '1985-03-20']); // Wilhelm: Fördermitglied (4)
        $mfcStmt->execute([6, 5, null, '2015-01-01']); // Ernst: Ehrenmitglied (5)
        $mfcStmt->execute([7, 4, 50.00, '2015-11-01']); // Petra: Fördermitglied with custom €50 amount
    }
}
