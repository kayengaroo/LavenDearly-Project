-- LavenDearly database schema (MySQL 5.7+ / MariaDB 10.3+)
-- Import with phpMyAdmin (Import tab) or:  mysql -u root < lavendearly.sql
CREATE DATABASE IF NOT EXISTS lavendearly CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lavendearly;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_log, note_comments, care_notes, contributions, expenses,
                     appointments, dose_log, medicines, patient, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(100) NOT NULL,
  username      VARCHAR(40)  NOT NULL UNIQUE,
  email         VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','caregiver','contributor') NOT NULL,
  relationship  VARCHAR(60)  NOT NULL DEFAULT '',
  share_percent DECIMAL(5,2) NOT NULL DEFAULT 0 CHECK (share_percent >= 0 AND share_percent <= 100),
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL
) ENGINE=InnoDB;

CREATE TABLE patient (
  id               INT UNSIGNED PRIMARY KEY,
  full_name        VARCHAR(100) NOT NULL,
  birthdate        DATE NULL,
  allergies        TEXT NULL,
  conditions       TEXT NULL,
  primary_doctor   VARCHAR(120) NULL,
  doctor_contact   VARCHAR(120) NULL,
  emergency_contacts TEXT NULL,
  updated_at       DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE medicines (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(100) NOT NULL,
  dose            VARCHAR(60)  NOT NULL,
  times_of_day    VARCHAR(100) NOT NULL,           -- e.g. "08:00,20:00"
  start_date      DATE NOT NULL,
  refill_date     DATE NULL,
  end_date        DATE NULL,
  prescribing_doctor VARCHAR(120) NULL,
  status          ENUM('active','discontinued') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB;

CREATE TABLE dose_log (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  medicine_id    INT UNSIGNED NOT NULL,
  scheduled_date DATE NOT NULL,
  scheduled_time TIME NOT NULL,
  status         ENUM('given','missed','skipped') NOT NULL,
  actual_time    TIME NULL,
  logged_by      INT UNSIGNED NOT NULL,
  note           VARCHAR(255) NULL,
  created_at     DATETIME NOT NULL,
  UNIQUE KEY uq_dose (medicine_id, scheduled_date, scheduled_time),   -- prevents duplicate entries
  FOREIGN KEY (medicine_id) REFERENCES medicines(id),
  FOREIGN KEY (logged_by)   REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE appointments (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type       ENUM('visit','lab','prescription') NOT NULL,
  title      VARCHAR(150) NOT NULL,
  doctor     VARCHAR(120) NULL,
  appt_date  DATE NOT NULL,
  notes      TEXT NULL,
  file_path  VARCHAR(255) NULL,
  created_by INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE expenses (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  expense_date DATE NOT NULL,
  item         VARCHAR(150) NOT NULL,
  category     ENUM('Medicines','Groceries','Check-up fees','Supplies','Other') NOT NULL,
  amount       DECIMAL(10,2) NOT NULL CHECK (amount > 0),
  paid_by      INT UNSIGNED NULL,                  -- NULL = paid from the shared care fund
  receipt_path VARCHAR(255) NULL,
  created_by   INT UNSIGNED NOT NULL,
  created_at   DATETIME NOT NULL,
  FOREIGN KEY (paid_by)    REFERENCES users(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE contributions (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL,
  amount       DECIMAL(10,2) NOT NULL CHECK (amount > 0),
  contrib_date DATE NOT NULL,
  note         VARCHAR(255) NULL,
  created_at   DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE care_notes (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  note_date  DATE NOT NULL,
  mood       VARCHAR(80)  NULL,
  meals      VARCHAR(255) NULL,
  sleep      VARCHAR(120) NULL,
  concerns   TEXT NULL,
  created_by INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE note_comments (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  note_id    INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  comment    VARCHAR(500) NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (note_id) REFERENCES care_notes(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE audit_log (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NULL,
  action     VARCHAR(40)  NOT NULL,
  record     VARCHAR(80)  NOT NULL DEFAULT '',
  details    VARCHAR(500) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  KEY idx_created (created_at)
) ENGINE=InnoDB;

-- ---------- starter accounts (password for all three: LavenDearly@2026 — change after first login) ----------
INSERT INTO users (full_name, username, email, password_hash, role, relationship, share_percent, is_active, created_at) VALUES
('Maria Santos',  'maria',  'maria@example.com',  '$2y$10$qkC8sIkl3VpKwCPXxroo0euKA5bV858NJSx7v6sbE1BwMi2D9QJW2', 'admin',       'Eldest daughter',  40, 1, NOW()),
('Rosa Dela Cruz','rosa',   'rosa@example.com',   '$2y$10$qkC8sIkl3VpKwCPXxroo0euKA5bV858NJSx7v6sbE1BwMi2D9QJW2', 'caregiver',   'Live-in caregiver', 0, 1, NOW()),
('Paolo Santos',  'paolo',  'paolo@example.com',  '$2y$10$qkC8sIkl3VpKwCPXxroo0euKA5bV858NJSx7v6sbE1BwMi2D9QJW2', 'contributor', 'Son (abroad)',     30, 1, NOW()),
('Liza Santos',   'liza',   'liza@example.com',   '$2y$10$qkC8sIkl3VpKwCPXxroo0euKA5bV858NJSx7v6sbE1BwMi2D9QJW2', 'contributor', 'Youngest daughter',30, 1, NOW());

INSERT INTO patient (id, full_name, birthdate, allergies, conditions, primary_doctor, doctor_contact, emergency_contacts, updated_at) VALUES
(1, 'Remedios Santos', '1945-03-12', 'Penicillin; sulfa drugs', 'Hypertension; Type 2 diabetes; mild arthritis',
 'Dr. Elena Ramos', '(02) 8123 4567',
 'Maria Santos (daughter) - 0917 000 0001\nPaolo Santos (son) - 0917 000 0002\nBarangay health center - (02) 8765 4321', NOW());
