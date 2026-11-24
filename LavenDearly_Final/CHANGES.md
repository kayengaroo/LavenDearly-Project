# Light Violet redesign – what changed

Changed (presentation only):
- assets/css/style.css  (rewritten: Light Violet theme)
- assets/img/logo.svg   (your uploaded LavenDearly logo)
- includes/header.php   (lavender sidebar, grouped navigation, mobile menu)
- includes/footer.php   (closes the new layout)
- login.php             (only the Google Fonts <link> tags were removed)

Untouched: includes/functions.php (identical copy), every other page, auth, sessions, CSRF, permissions, uploads logic.
NOT included (keep yours): config/, database/, uploads/.

To install: copy these files over your lavendearly1 folder, keeping config/, database/ and uploads/.
