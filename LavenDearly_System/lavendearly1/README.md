# LavenDearly

A web-based family caregiving coordination system for an elderly person.
Built with HTML5, CSS, PHP (PDO) and MySQL. Web Management System Project, CC312-M · BSCS 3B.

## Run it locally (XAMPP)

1. Copy the `lavendearly` folder into `C:\xampp\htdocs\` (or `htdocs` on your system).
2. Start **Apache** and **MySQL** in the XAMPP control panel.
3. Open **phpMyAdmin → Import** and import, in this order:
   1. `database/lavendearly.sql` — creates the `lavendearly` database, tables and 4 starter accounts
   2. `database/demo_data.sql` — *(optional)* sample medicines, dose history, expenses and notes
4. If your MySQL has a password, edit `config/db.php`.
5. Visit **http://localhost/lavendearly/**

PHP 8.0+ with the `pdo_mysql` and `fileinfo` extensions is required (both are on by default in XAMPP).

## Starter accounts

Password for all four: `LavenDearly@2026` — **change them** under *User Management* before real use.

| Username | Role                 | What they can do |
|----------|----------------------|------------------|
| `maria`  | Family Administrator | Everything: members, roles, shares, reports, audit log |
| `rosa`   | Caregiver            | Log doses, care notes, purchases, receipts; manage medicines, appointments, profile |
| `paolo`  | Contributor          | View medicines, notes, appointments, expense totals; record own contributions; comment |
| `liza`   | Contributor          | Same as above |

## Folder map

```
config/db.php          database settings
includes/              functions (auth, roles, doses, balances), header, footer
assets/css/style.css   palette styling
database/              lavendearly.sql (schema) · demo_data.sql (sample data)
uploads/               receipts and documents (blocked from direct web access)
*.php                  one page per module (see About page inside the app)
```

## Notes on design decisions

- **Balances.** A member's balance is *(purchases they paid personally + contributions they sent) − (their agreed % of all expenses)*.
  Purchases marked "Shared care fund" are paid from contributions, so they count toward everyone's share but not as anyone's personal payment.
  Shares are set per member on the *Family Members* page and should add up to 100%.
- **Alerts.** A dose is "overdue" 60 minutes after its scheduled time; refills are flagged 7 days ahead. Both are constants at the top of `includes/functions.php`.
- **Privacy.** Uploaded files are served only through `file.php` to signed-in users; passwords are hashed with `password_hash`; forms use CSRF tokens; sessions expire after 20 minutes of inactivity; every change is written to the audit log.
- The system records care; it does not replace medical advice.

## Credits

- The wax-seal logo and the lavender sprig pattern are original vector artwork drawn for this project (`assets/img/logo.svg`). Headings use Playfair Display and body text uses Montserrat, loaded from Google Fonts.
- Colors: Soft Lavender `#C6B5D8`, Dusty Lavender `#A693C2`, Cream `#F5F0E6`, Warm Beige `#E7DCC6`, Soft Taupe `#CBBEB1`. A deeper plum (for text and buttons) and a berry (for alerts) are darker shades added so small text stays readable. The logo adds a mint green and gold of its own.
