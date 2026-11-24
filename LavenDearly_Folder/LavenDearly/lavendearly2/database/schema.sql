CREATE DATABASE IF NOT EXISTS carecircle_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE carecircle_db;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(140) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  relationship_label VARCHAR(80) NOT NULL,
  role ENUM('administrator', 'caregiver', 'contributor') NOT NULL DEFAULT 'contributor',
  agreed_share_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS patients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(140) NOT NULL,
  birthdate DATE NOT NULL,
  blood_type VARCHAR(10) DEFAULT 'O+',
  allergies TEXT NOT NULL,
  conditions TEXT NOT NULL,
  primary_doctor VARCHAR(160) NOT NULL,
  primary_clinic VARCHAR(180) DEFAULT '',
  address VARCHAR(200) NOT NULL,
  care_notes_summary TEXT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS family_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL DEFAULT 1,
  full_name VARCHAR(120) NOT NULL,
  relationship_label VARCHAR(80) NOT NULL,
  contact_number VARCHAR(40) NOT NULL,
  role ENUM('administrator', 'caregiver', 'contributor', 'emergency_only') NOT NULL DEFAULT 'contributor',
  is_emergency_contact TINYINT(1) NOT NULL DEFAULT 0,
  location_note VARCHAR(120) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS medicines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL DEFAULT 1,
  name VARCHAR(140) NOT NULL,
  dosage VARCHAR(80) NOT NULL,
  schedule_times VARCHAR(160) NOT NULL COMMENT 'Comma-separated 24h times, e.g. 08:00,20:00',
  instructions VARCHAR(255) DEFAULT '',
  prescribing_doctor VARCHAR(140) DEFAULT '',
  start_date DATE NOT NULL,
  refill_date DATE NOT NULL,
  status ENUM('active', 'discontinued') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS dose_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  medicine_id INT NOT NULL,
  logged_by INT NOT NULL,
  log_date DATE NOT NULL,
  scheduled_time VARCHAR(10) NOT NULL,
  actual_time VARCHAR(10) NOT NULL,
  status ENUM('given', 'missed', 'skipped') NOT NULL,
  note VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_med_date_slot (medicine_id, log_date, scheduled_time),
  FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
  FOREIGN KEY (logged_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL DEFAULT 1,
  appointment_date DATE NOT NULL,
  appointment_time VARCHAR(20) DEFAULT '09:00 AM',
  doctor_name VARCHAR(140) NOT NULL,
  clinic_or_hospital VARCHAR(160) NOT NULL,
  purpose_or_notes TEXT NOT NULL,
  attachment_path VARCHAR(255) NULL,
  attachment_label VARCHAR(160) NULL,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS expenses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL DEFAULT 1,
  expense_date DATE NOT NULL,
  item_name VARCHAR(160) NOT NULL,
  category ENUM('medicine', 'groceries', 'checkup', 'supplies', 'other') NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  paid_by INT NOT NULL,
  receipt_path VARCHAR(255) NULL,
  notes VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (paid_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS contributions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL DEFAULT 1,
  user_id INT NOT NULL,
  contribution_date DATE NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  channel VARCHAR(80) DEFAULT 'Bank Transfer / Remittance',
  notes VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS care_notes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL DEFAULT 1,
  author_id INT NOT NULL,
  note_date DATE NOT NULL,
  mood VARCHAR(60) NOT NULL,
  meals_summary VARCHAR(200) NOT NULL,
  sleep_summary VARCHAR(200) NOT NULL,
  concerns TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (author_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS care_note_comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  note_id INT NOT NULL,
  author_id INT NOT NULL,
  comment_text TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (note_id) REFERENCES care_notes(id) ON DELETE CASCADE,
  FOREIGN KEY (author_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action_type VARCHAR(60) NOT NULL,
  module_name VARCHAR(80) NOT NULL,
  record_ref VARCHAR(100) NOT NULL,
  details TEXT NOT NULL,
  ip_address VARCHAR(45) DEFAULT '127.0.0.1',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Seed Users (Password for all seeded accounts: CareCircle2026!)
-- Hash generated via password_hash('CareCircle2026!', PASSWORD_BCRYPT)
INSERT INTO users (full_name, email, password_hash, relationship_label, role, agreed_share_pct, is_active) VALUES
('Maria Santos', 'maria.santos@carecircle.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Eldest Daughter (Manila)', 'administrator', 40.00, 1),
('Rosa Reyes', 'rosa.reyes@carecircle.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Live-in Caregiver / Aunt', 'caregiver', 10.00, 1),
('Marco Santos', 'marco.santos@carecircle.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Son (Overseas Contributor)', 'contributor', 50.00, 1)
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);

INSERT INTO patients (id, full_name, birthdate, blood_type, allergies, conditions, primary_doctor, primary_clinic, address, care_notes_summary) VALUES
(1, 'Remedios L. Santos', '1945-04-12', 'O+', 'Penicillin, Sulfa drugs, Shellfish', 'Type 2 Diabetes Mellitus, Hypertension, Mild Osteoarthritis', 'Dr. Ramon Cruz, MD', 'St. Jude Family & Geriatric Clinic, Manila', 'Pandacan, Manila', 'Monitor blood pressure before morning dose; keep water bottle beside bed and log meal intake daily.')
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);

INSERT INTO family_members (patient_id, full_name, relationship_label, contact_number, role, is_emergency_contact, location_note) VALUES
(1, 'Maria Santos', 'Eldest Daughter', '+63 917 555 0101', 'administrator', 1, 'Manila — Primary Decision Maker'),
(1, 'Rosa Reyes', 'Live-in Caregiver', '+63 918 555 0102', 'caregiver', 1, 'Pandacan, Manila — Daily On-Site Care'),
(1, 'Marco Santos', 'Son', '+971 50 555 0103', 'contributor', 0, 'Dubai, UAE — Remittance & Check-ins'),
(1, 'Dr. Ramon Cruz, MD', 'Attending Physician', '+63 2 8555 0199', 'emergency_only', 1, 'Internal Medicine & Geriatrics');

INSERT INTO medicines (patient_id, name, dosage, schedule_times, instructions, prescribing_doctor, start_date, refill_date, status) VALUES
(1, 'Metformin HCl', '500 mg (1 tablet)', '08:00,20:00', 'Take after breakfast and after dinner with full glass of water.', 'Dr. Ramon Cruz, MD', '2026-01-10', DATE_ADD(CURDATE(), INTERVAL 4 DAY), 'active'),
(1, 'Losartan Potassium', '50 mg (1 tablet)', '08:00', 'Take every morning; check BP before dosing if dizzy.', 'Dr. Ramon Cruz, MD', '2026-02-01', DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'active'),
(1, 'Atorvastatin', '20 mg (1 tablet)', '21:00', 'Take once nightly after supper.', 'Dr. Ramon Cruz, MD', '2026-03-15', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'active');

INSERT INTO appointments (patient_id, appointment_date, appointment_time, doctor_name, clinic_or_hospital, purpose_or_notes, created_by) VALUES
(1, DATE_ADD(CURDATE(), INTERVAL 6 DAY), '10:00 AM', 'Dr. Ramon Cruz, MD', 'St. Jude Family & Geriatric Clinic, Manila', 'Quarterly HbA1c lab review, blood pressure check, and prescription renewal.', 1),
(1, DATE_SUB(CURDATE(), INTERVAL 20 DAY), '09:30 AM', 'Dr. Elena Navarro, MD', 'Manila Eye & Wellness Center', 'Routine geriatric vision check; artificial tears prescribed.', 1);

INSERT INTO expenses (patient_id, expense_date, item_name, category, amount, paid_by, notes) VALUES
(1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Metformin & Losartan 30-day supply', 'medicine', 2450.00, 2, 'Bought at neighborhood pharmacy in Pandacan'),
(1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Oatmeal, bananas, fish, and low-sodium groceries', 'groceries', 3180.00, 1, 'Weekly elderly meal groceries'),
(1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'BP monitor battery & adult care wipes', 'supplies', 640.00, 2, 'Home care supplies');

INSERT INTO contributions (patient_id, user_id, contribution_date, amount, channel, notes) VALUES
(1, 3, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 4000.00, 'International Remittance', 'Monthly support transfer from Marco'),
(1, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 2500.00, 'GCash / Bank Transfer', 'Initial share deposit');

INSERT INTO care_notes (patient_id, author_id, note_date, mood, meals_summary, sleep_summary, concerns) VALUES
(1, 2, CURDATE(), 'Calm & cheerful', 'Ate full oatmeal breakfast and steamed fish lunch; drank 6 glasses of water.', 'Slept 7 hours with one bathroom break at 2:00 AM.', 'Slight knee stiffness in the morning; eased after warm compress.'),
(1, 2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Content', 'Finished soft rice, vegetable soup, and papaya.', 'Slept well from 9:30 PM to 5:30 AM.', 'No dizziness or shortness of breath reported.');

INSERT INTO care_note_comments (note_id, author_id, comment_text) VALUES
(1, 3, 'Glad Lola slept well and ate her meals. Let me know if we need extra fruit this week.'),
(1, 1, 'Good catch on the knee warm compress, Tita Rosa. Let us mention it to Dr. Cruz on check-up day.');

INSERT INTO audit_logs (user_id, action_type, module_name, record_ref, details, ip_address) VALUES
(1, 'SEED_INIT', 'System', 'INIT-001', 'Initial CareCircle dataset seeded for Remedios L. Santos.', '127.0.0.1');
