# LavenDearly — Web-Based Family Caregiving Coordination System

A modular **HTML5, CSS3, JavaScript, PHP (PDO), and MySQL** web management system for coordinating the daily care of an elderly family member, styled with Soft Lavender (`#C6B5D8`), Dusty Lavender (`#A693C2`), Cream (`#F5F0E6`), Warm Beige (`#E7DCC6`), and Soft Taupe (`#CBBEB1`), featuring a cursive **D** and lavender sprig emblem and botanical background.

## Directory Structure (`CareCircle_System/carecircle`)
- `config/db.php` — PDO database connection, credentials, and session timeout constant.
- `database/schema.sql` — MySQL database schema and seeded sample data (`carecircle_db`).
- `includes/auth.php`, `includes/header.php`, `includes/footer.php` — Session & inactivity timeout handling, CSRF protection, RBAC helpers, audit logger, and shared layout.
- `assets/css/style.css`, `assets/js/app.js` — LavenDearly palette, botanical background, print stylesheet (`@media print`), and client-side idle countdown.
- `uploads/documents/` & `uploads/receipts/` — Storage folders for uploaded medical files and expense receipts (served safely via `file.php`).
- Core PHP pages: `index.php`, `login.php`, `logout.php`, `dashboard.php`, `patient.php`, `medicines.php`, `doselog.php`, `appointments.php`, `file.php`, `expenses.php`, `contributions.php`, `notes.php`, `search.php`, `reports.php`, `summary.php`, `members.php`, `users.php`, `audit.php`, `about.php`.
