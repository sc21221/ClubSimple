# Clubmanager - Vereinsverwaltung Musikverein Denkendorf

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B%20%7C%208.4%20%7C%208.5-blue.svg)](https://php.net)
[![Version](https://img.shields.io/badge/Version-1.7-green.svg)]()
[![Standards](https://img.shields.io/badge/Standards-PSR--4%20%7C%20PSR--12-orange.svg)]()
[![SEPA ISO 20022](https://img.shields.io/badge/SEPA-pain.008.001.02-purple.svg)]()

Webbasierte Vereinsverwaltungssoftware **"Clubmanager"** für den **Musikverein Denkendorf e.V.**, entwickelt als performante, schlanke **Pure-PHP**-Anwendung ohne ressourcenintensive Full-Stack-Frameworks.

---

## 1. Projektdaten & Metadaten

- **Name der Anwendung:** Clubmanager
- **Autor:** Reinhard Schiessl
- **Version:** 1.7
- **Datum:** 16. Oktober 2025
- **PHP-Kompatibilität:** Pure PHP 8.2+ (getestet und optimiert auf PHP 8.4 und kompatibel mit PHP 8.5)
- **Architektur:** Custom MVC (Model-View-Controller) mit minimalen externen Abhängigkeiten
- **Datenbankzugriff:** Data Mapper & Active Record Pattern mit Standalone PDO-Wrapper (Unterstützt MariaDB / MySQL sowie SQLite mit Zero-Config-Fallback)
- **Frontend / UI:** HTML5, native PHP-Templates, Bootstrap 5 & Bootstrap Icons
- **Internationalisierung (i18n):** Vollständige Zweisprachigkeit für Deutsch (`de`) und Englisch (`en`), auswählbar beim Login und dynamisch umschaltbar
- **Technisches Logging:** Strukturierter Serilog-kompatibler JSON-Logger (CLEF-Format: Compact Log Event Format)

---

## 2. Funktionsübersicht & Spezifikationsabdeckung

### 2.1 Authentifizierung & Login (`users` Tabelle)
- Authentifizierung über E-Mail und Passwort mit `password_hash()` (`BCRYPT`/`DEFAULT`).
- Sprachauswahl auf der Login-Maske (Deutsch `de` oder Englisch `en`), gespeichert in `users.locale` und Sitzungskontext.
- Dynamisches rollenbasiertes Menü nach Login entsprechend der Rolle (`users.admin` Flag und Zugriffsrechte).
- "Angemeldet bleiben"-Funktion mit sicherem Token-Cookie (`remember_token`).

### 2.2 Dashboard
- **Vier Pflicht-Kennzahlenkarten:**
  1. **Verein & Vertretung:** Offizieller Vereinsname (`clubs.name`) und 1. Vorsitzender (`clubs.boss`).
  2. **Aktive Mitglieder:** Gesamtzahl aller Mitglieder mit Status `Aktiv`.
  3. **Beitragsklassen:** Gesamtzahl der hinterlegten Beitragsklassen (`fee_classes`).
  4. **Letzter SEPA-Lauf:** Datum und Gesamtbetrag des zuletzt generierten Lastschriftlaufs (`membership_fees`).
- Ergänzende Widgets:
  - Mitgliederstatus-Verteilung (Aktiv, Inaktiv, Gesperrt).
  - Schnellzugriff-Schaltflächen (Mitglied anlegen, SEPA vorbereiten, Berichte).
  - Vorschau der nächsten runden Geburtstage und Vereinsjubiläen.

### 2.3 Mitgliederverwaltung (`members` Tabelle)
- **Vollständiges CRUD-Formular:**
  - Pflicht- und Stammdaten: `salutation`, `sex`, `first_name`, `last_name`, `birth_date`, `join_date`, `leave_date`.
  - Anschrift & Kontakt: `email`, `phone`, `street`, `zip`, `city`, `country`.
  - Vereinsstatus: `membership_type` (`Vollmitglied`, `Fördermitglied`, `Jugendmitglied`, `Ehrenmitglied`), `status` (`Aktiv`, `Inaktiv`, `Gesperrt`), `invoice_marker` (Rechnungsempfänger).
  - Bankverbindung: `bank_name`, `bank_bic`, `bank_iban`, `bank_holder`.
- **Such- & Filtergitter (DataGrid):**
  - Volltextsuche nach Name, E-Mail, Wohnort und Straße.
  - Filterung nach Status und Beitragsklassen.
  - Sortierung und Paginierung.
- **DSGVO-Compliance (Datenschutz):**
  - Recht auf Auskunft (Art. 15 DSGVO): Export aller gespeicherten Mitgliedsdaten als **PDF-Auskunftsbericht** und strukturierte **JSON-Datei**.
  - Recht auf Vergessenwerden (Art. 17 DSGVO): Unwiderrufliche Anonymisierung personenbezogener Daten unter Beibehaltung der Buchungshistorie für steuerliche Aufbewahrungsfristen.
  - DSGVO-konforme Archivierung (Soft-Delete auf Status `Inaktiv`).

### 2.4 Beitragsklassen & Beitragszuweisung (`fee_classes` & `member_fee_classes`)
- CRUD-Verwaltung für Beitragsklassen:
  - `name`, `description`, `base_amount`, `currency`, `billing_cycle` (`Jährlich`, `Halbjährlich`, `Vierteljährlich`, `Monatlich`), `min_age`, `max_age`, `is_active`.
- Flexible Mehrfach-Zuweisung pro Mitglied:
  - Unterstützung für abweichende Sonderbeiträge (`custom_amount`).
  - Gültigkeitszeiträume (`start_date`, `end_date`, `is_active`).

### 2.5 SEPA-Lastschriftverfahren & Generator (`membership_fees`)
- **Bankdatenprüfung:** Strikte Validierung von IBAN (ISO 7064 Modulo 97-10 Checksumme) und BIC (SWIFT-Format).
- **Automatisierte altersbasierte Rekategorisierung:**
  - Vor der Generierung des SEPA-Laufs wird das genaue Alter jedes Mitglieds am Fälligkeitstag berechnet.
  - Abgleich gegen die Altersgrenzen (`min_age`, `max_age` in `fee_classes`).
  - Volljährige Mitglieder (z.B. Wechsel von Jugend zu Erwachsen) werden automatisch erkannt und die Beitragszuweisung wird aktualisiert.
  - Interaktive Vorschau erkannter Altersübergänge vor Ausführung.
- **ISO 20022 SEPA XML-Export:**
  - Generiert standardkonforme `pain.008.001.02` XML-Dateien.
  - Aggregation aller aktiven Beitragsklassen pro Mitglied in eine einzelne Lastschriftbuchung.
  - Enthält `GrpHdr`, `PmtInf` (CORE/RCUR) und individuelle `DrctDbtTxInf` mit Gläubiger-ID und Mandatsreferenz.
- **Ausführungsprotokollierung:**
  - Verbuchung jeder Transaktion in `membership_fees` mit Status `Offen`.
  - Protokollierung im Audit-Trail und im Serilog-Event-Log.
  - Direkter Download der generierten `.xml`-Datei.

### 2.6 Berichte & Jubiläumslisten
- **Geburtstagslisten:**
  - Filterung nach Zeiträumen (nächste 30, 60, 90, 180, 365 Tage) oder Kalendermonat.
  - Spezialfilter für **runde Geburtstage** (50, 60, 70, 75, 80, 85, 90, 95, 100 Jahre).
  - Berechnung des Zielalters und der verbleibenden Tage.
- **Jubiläumslisten:**
  - Auswertung von Vereinsjubiläen (10, 20, 25, 30, 40, 50, 60, 70 Jahre Mitgliedschaft) anhand `join_date`.
  - Filterung nach Zieljahr.
- **Export-Formate:**
  - **CSV-Export:** Mit UTF-8 BOM für fehlerfreie Umlautdarstellung in Microsoft Excel.
  - **PDF-Export:** Professionelle Vektor-PDF-Generierung mit Vereins-Briefkopf, formatierter Tabelle und Seitennummerierung.

### 2.7 Benutzer- & Rollenverwaltung (`users` Tabelle)
- CRUD für Benutzerzugänge (`name`, `email`, `phone`, `password`, `admin` Flag, `locale`).
- Rollenbasierte Zugriffskontrolle (RBAC): Trennung zwischen Administrator (`admin = 1`) und Kassenwart / Mitarbeiter (`admin = 0`).

### 2.8 Audit-Trail / Journal-Protokoll (`audit_logs` Tabelle)
- Unveränderliche Protokollierung aller Änderungen an Mitgliedern, Vereinstammdaten und Beitragsklassen.
- Speichert: `timestamp`, `user_id`, `action`, `entity_type`, `entity_id`, `old_values` (JSON), `new_values` (JSON), `ip_address`.
- Administrativer Log-Viewer mit Filter nach Datum, Benutzer, Bereich und Vorher/Nachher-Diff-Ansicht.

### 2.9 Vereinsstammdaten (`clubs` Tabelle)
- Verwaltung der Vereinsdaten: Name, Untertitel, 1. Vorsitzender (`boss`), Anschrift, Kontaktdaten.
- SEPA-Gläubigerdaten: Gläubiger-Identifikationsnummer (`bank_creditor_id`), Vereins-IBAN (`bank_iban`), BIC (`bank_bic`) und Kreditinstitut.
- Das Kreditinstitut des Vereins darf bis zu 100 Zeichen lang sein. Bei bestehenden MySQL-/MariaDB-Datenbanken nach einer Sicherung `database/migrate_club_bank_name.sql` einmal auf der richtigen Datenbank ausführen; `schema.sql` ändert bestehende Tabellen nicht. SQLite erzwingt die deklarierte `VARCHAR`-Länge nicht und benötigt hierfür keine Migration.

### 2.10 Abteilungen (Datenbankschema)
- Unter „Abteilungen“ können Abteilungen angelegt, umbenannt und gelöscht sowie ihre Mitglieder angezeigt werden. Zugeordnete Abteilungen können erst nach dem Entfernen ihrer Zuordnungen gelöscht werden.
- Im Tab „Abteilungen“ beim Bearbeiten eines Mitglieds lassen sich Abteilungen mit optionalem Datum und Beschreibung zuordnen und wieder entfernen. Jede Abteilung kann einem Mitglied einmal zugeordnet werden.
- `departments`: `id` (automatisch erzeugter Primärschlüssel), `name` (`VARCHAR(100)`, Pflichtfeld).
- `member_departments`: `id` (automatisch erzeugter Primärschlüssel), `member_id` und `department_id` (Pflichtfelder und Fremdschlüssel), `assigned_date` (`DATE`, optional), `description` (`TEXT`, optional). Das Paar `member_id`/`department_id` ist eindeutig; beim Löschen eines Mitglieds oder einer Abteilung werden dessen Zuordnungen gelöscht.
- Für bestehende Datenbanken nach einer Sicherung `database/migrate_departments.sql` für MariaDB/MySQL bzw. `database/migrate_departments_sqlite.sql` für SQLite auf der richtigen Datenbank ausführen. Bei SQLite müssen Fremdschlüssel pro Verbindung mit `PRAGMA foreign_keys = ON` aktiviert sein; die Anwendung aktiviert sie beim Verbindungsaufbau. Die Migrationen erstellen fehlende Tabellen, ändern aber keine bereits vorhandenen Tabellen gleichen Namens.

### 2.11 Vereinsfunktionen (Datenbankschema)
- `club_functions`: `id` (automatisch erzeugter Primärschlüssel), `name` (`VARCHAR(100)`, Pflichtfeld).
- `member_functions`: `id`, `member_id` und `function_id` (Pflichtfelder und indizierte Fremdschlüssel), `start_date` (`DATE`, Pflichtfeld), `end_date` (`DATE`, optional) und `description` (`TEXT`, optional). Für dasselbe Mitglied und dieselbe Funktion sind mehrere Zeiträume möglich; Überschneidungen werden nicht verhindert. Beim Löschen eines Mitglieds oder einer Funktion werden die Zuordnungen gelöscht.
- Für bestehende Datenbanken nach einer Sicherung `database/migrate_functions.sql` für MariaDB/MySQL bzw. `database/migrate_functions_sqlite.sql` für SQLite auf der richtigen Datenbank ausführen. Die Migrationen erstellen fehlende Tabellen, ändern aber keine bereits vorhandenen Tabellen gleichen Namens. SQLite-Fremdschlüssel müssen pro Verbindung mit `PRAGMA foreign_keys = ON` aktiviert sein.
- Über **Funktionen** lassen sich Funktionen anlegen und bearbeiten; zugeordnete Funktionen können erst nach Entfernen ihrer Zuordnungen gelöscht werden. Im Tab **Funktionen** eines Mitglieds können Zuordnungen mit Startdatum, optionalem Enddatum und Beschreibung hinzugefügt oder entfernt werden.
- Die Liste aktueller Mitglieder umfasst Zuordnungen ab einschließlich Startdatum bis einschließlich Enddatum; ohne Enddatum sind sie unbefristet. Die Historie enthält alle bereits begonnenen Zuordnungen, auch beendete, aber keine zukünftigen. Entfernte Zuordnungen erscheinen nicht mehr in der Historie.

### 2.12 Ereignisse (Datenbankschema)
- `events`: `id` (automatisch erzeugter Primärschlüssel), `name` (`VARCHAR(100)`, Pflichtfeld).
- `member_events`: `id`, `member_id` und `event_id` (Pflichtfelder und indizierte Fremdschlüssel), `event_date` (`DATE`, Pflichtfeld) und `description` (`TEXT`, optional). Wiederholte Zuordnungen desselben Ereignisses zum selben Mitglied sind auch am selben Datum erlaubt. Beim Löschen eines Mitglieds oder Ereignisses werden die Zuordnungen gelöscht.
- Für bestehende Datenbanken nach einer Sicherung `database/migrate_events.sql` für MariaDB/MySQL bzw. `database/migrate_events_sqlite.sql` für SQLite auf der richtigen Datenbank ausführen. Die Migrationen erstellen fehlende Tabellen, ändern aber keine bereits vorhandenen Tabellen gleichen Namens. SQLite-Fremdschlüssel müssen pro Verbindung mit `PRAGMA foreign_keys = ON` aktiviert sein; SQLite erzwingt weder die Länge von `VARCHAR(100)` noch ein gültiges Datumsformat.

---

## 3. Verzeichnisstruktur

```text
ClubSimple/
├── config/
│   ├── app.php                # Anwendungsmetadaten & i18n
│   ├── database.php           # DB-Verbindungen (MySQL / SQLite)
│   └── logging.php            # Serilog/CLEF Konfiguration
├── database/
│   ├── schema.sql             # MariaDB / MySQL DDL-Schema
│   └── DatabaseInitializer.php# Auto-Migration & Denkendorf-Seeder
├── lang/
│   ├── de.php                 # Deutsche Übersetzungen
│   └── en.php                 # Englische Übersetzungen
├── public/
│   ├── index.php              # Front-Controller & Router-Bootstrap
│   ├── .htaccess              # Apache URL-Rewriting
│   └── assets/
│       └── css/custom.css     # UI-Styling
├── src/
│   ├── Core/
│   │   ├── Auth.php           # Session- & Cookie-Authentifizierung
│   │   ├── Controller.php     # Basis-Controller mit View-Renderer
│   │   ├── Csrf.php           # Anti-CSRF-Schutz
│   │   ├── Database.php       # PDO-Wrapper & Connection-Factory
│   │   ├── Helpers.php        # Globale Hilfsfunktionen (__, e, format)
│   │   ├── I18n.php           # Übersetzungs-Engine
│   │   ├── Logger.php         # Serilog CLEF JSON-Logger
│   │   ├── Model.php          # Basis-Modell / Active Record
│   │   ├── Request.php        # HTTP-Request Kapselung
│   │   ├── Response.php       # HTTP-Response, JSON & Downloads
│   │   ├── Router.php         # RESTful URL-Router mit RBAC-Middleware
│   │   ├── Session.php        # Sitzungs- & Flash-Message-Handler
│   │   └── Validator.php      # Validierungs-Engine inkl. IBAN/BIC Mod-97
│   ├── Libraries/
│   │   └── PdfBuilder.php     # Pure-PHP Vektor-PDF-Generator
│   ├── Models/
│   │   ├── Club.php
│   │   ├── Member.php
│   │   ├── FeeClass.php
│   │   ├── MemberFeeClass.php
│   │   ├── MembershipFee.php
│   │   ├── User.php
│   │   └── AuditLog.php
│   ├── Repositories/
│   │   ├── ClubRepository.php
│   │   ├── MemberRepository.php
│   │   ├── FeeClassRepository.php
│   │   ├── MembershipFeeRepository.php
│   │   ├── UserRepository.php
│   │   └── AuditLogRepository.php
│   ├── Services/
│   │   ├── SepaService.php    # Altersrekategorisierung & ISO 20022 XML
│   │   ├── ReportService.php  # Geburtstage, Jubiläen, CSV/PDF-Export
│   │   ├── AuditService.php   # Audit-Trail Aufzeichnung
│   │   └── GdprService.php    # DSGVO-Auskunft & Anonymisierung
│   └── Controllers/
│       ├── AuthController.php
│       ├── DashboardController.php
│       ├── MemberController.php
│       ├── FeeClassController.php
│       ├── SepaController.php
│       ├── ReportController.php
│       ├── UserController.php
│       ├── ClubController.php
│       └── AuditLogController.php
├── storage/
│   ├── logs/                  # Serilog JSON CLEF Logs
│   ├── sepa/                  # Generierte pain.008.001.02 XML-Dateien
│   └── database.sqlite        # SQLite-Datenbank (automatisch erzeugt)
├── tests/
│   ├── test_clubmanager.php                 # Unit- & Service-Verifikationstest
│   ├── test_http_routes.php                # Front-Controller & Route-Test
│   └── test_model_schema_consistency.php   # Modelle, Constraints & Schema-Defaults
├── views/                     # Native PHP-Views
├── .env.example
├── .env
├── composer.json
└── README.md
```

---

## 4. Installation & Start

### Schnellstart mit PHP Built-In Server (Zero-Config):
Das System enthält eine integrierte automatische Schema-Initialisierung und Seeder-Logik. Es sind keine externen Datenbankserver zwingend erforderlich, um die Anwendung sofort auszuführen:

```bash
# 1. Autoloader generieren
composer dump-autoload -o

# 2. PHP Development-Server starten
php -S localhost:8000 -t public/
```
Öffnen Sie anschließend `http://localhost:8000` im Browser.

### Verwendung mit MariaDB / MySQL:
1. Erstellen Sie eine Datenbank, z.B. `clubmanager`.
2. Führen Sie `database/schema.sql` aus.
3. Passen Sie die `.env`-Datei an:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=clubmanager
   DB_USERNAME=ihr_benutzer
   DB_PASSWORD=ihr_passwort
   ```

### Schema-Integrität und Bestandsdaten

Neu angelegte Datenbanken erhalten Fremdschlüssel für Mitglieder, Beitragszuordnungen und Buchungen, eine eindeutige Beitragszuordnung pro Mitglied/Klasse/Startdatum sowie zusätzliche Suchindizes. SQLite prüft außerdem die in MySQL als `ENUM` definierten Werte. Referenzierte Mitglieder und Beitragsklassen können nicht gelöscht werden; Beitragszuordnungen und Buchungshistorien bleiben erhalten. Mitglieder mit Historie können stattdessen archiviert oder anonymisiert werden.

Die Initialisierung verwendet `CREATE TABLE IF NOT EXISTS` und ist keine Migration: Bereits vorhandene SQLite- und MySQL-Datenbanken erhalten diese Änderungen nicht automatisch. Vor einer manuellen Migration sind vorhandene doppelte Zuordnungen und verwaiste Datensätze zu prüfen und die Datenbank zu sichern. SQLite und MySQL unterscheiden sich weiterhin bei Unicode-Collation, erzwungenen `VARCHAR`-Längen, Dezimalpräzision sowie Datums-/Zeittypen.

Für Benutzerrollen enthält `users` zusätzlich `role` mit Standardwert `user`; die bisherige Zugriffskontrolle verwendet weiterhin `admin`. Für bestehende MariaDB-/MySQL-Datenbanken nach einer Sicherung `database/migrate_user_roles.sql` einmal auf der richtigen Datenbank ausführen. Dabei wird `admin = 1` zu `admin` und `admin = 0` zu `treasurer`; das bisherige Flag bleibt erhalten. Die Migration nur ausführen, wenn `role` noch nicht existiert.

Für bestehende SQLite-Datenbanken nach einer Sicherung und bei gestoppter Anwendung die folgenden Anweisungen einmal auf der konfigurierten SQLite-Datei ausführen, sofern `role` noch nicht existiert:

```sql
ALTER TABLE `users` ADD COLUMN `role` VARCHAR(20) NOT NULL DEFAULT 'user';
UPDATE `users` SET `role` = CASE WHEN `admin` = 1 THEN 'admin' WHEN `admin` = 0 THEN 'treasurer' ELSE 'user' END;
```

---

## 5. Standard-Zugangsdaten (Demo-Seed)

| Rolle | E-Mail | Passwort | Beschreibung |
|---|---|---|---|
| **Administrator** | `admin@musikverein-denkendorf.de` | `admin` | Voller Zugriff auf alle Bereiche inkl. Benutzer, Vereinsdaten und Audit-Trail |
| **Kassenwart** | `kasse@musikverein-denkendorf.de` | `kasse` | Zugriff auf Dashboard, Mitglieder, Beitragsklassen, SEPA und Berichte |

---

## 6. Automatisierte Tests ausführen

Das System verfügt über eigenständige Testsuiten:

```bash
# 1. Kernfunktionen, SEPA-XML, Altersrekategorisierung und Audit-Trail testen:
php tests/test_clubmanager.php

# 2. Front-Controller, View-Rendering und Routing testen:
php tests/test_http_routes.php

# 3. Modellverhalten, Datenbank-Constraints und Löschschutz isoliert testen:
php tests/test_model_schema_consistency.php
```
