<?php
require __DIR__ . '/includes/functions.php';
if (!empty($_SESSION['uid'])) require_login();   // sidebar shows when signed in; page is public otherwise
$pageTitle = 'About the Project'; $active = 'about';
include __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <p class="small" style="margin:0 0 6px;opacity:.9">Web Management System Project · CC312-M · BSCS 3B</p>
  <h1>LavenDearly</h1>
  <p style="font-size:1.1rem;max-width:62ch;margin:0">A web-based family caregiving coordination system for an elderly person. One shared place for medicines, appointments, expenses and daily care.</p>
</section>

<section class="card">
  <h2>Project overview</h2>
  <p>This project identifies a real-world problem, analyzes its causes and effects, and proposes a solution through a Web Management System. The goal is not simply to create a website, but to think critically about a problem and design a functional web-based system that helps solve or manage it.</p>
  <p class="muted small" style="margin:0">Built with HTML5, CSS, PHP and MySQL.</p>
</section>

<section class="card">
  <h2>1 · The problem</h2>
  <p>Many Filipino families have an elderly member living at home while their children work in other cities or abroad. The elderly person may take several maintenance medicines, see specialists, and need regular refills. A live-in caregiver, often a relative or household helper, handles daily care. Everyone wants to help, but nobody has a shared view of the care.</p>
  <p>Doses are logged by text message, if at all. Doctor’s instructions sit on one sibling’s phone, prescriptions in a paper envelope, refill dates in memory, and receipts are photos lost in a group chat. When siblings send money, nobody keeps a clear record of who paid for what, which leads to arguments.</p>
  <h3>Who is affected</h3>
  <ul class="clean">
    <li><strong>The elderly person</strong> — can miss doses or receive duplicate doses.</li>
    <li><strong>The live-in caregiver</strong> — responsible for daily care but has no record of what was done.</li>
    <li><strong>Siblings and relatives away from home</strong> — want to help but cannot see what is happening.</li>
  </ul>
</section>

<section class="card">
  <h2>2 · Analysis of the problem</h2>
  <div class="grid g3">
    <div class="user-card" style="background:var(--beige)"><h3>Causes</h3><p style="margin:0">Care tasks are spread across group chats, phone notes and memory. Information is held by different people who are rarely available at the same time. There is no agreed place to record what was given, bought or due.</p></div>
    <div class="user-card" style="background:var(--plum);color:var(--cream)"><h3>Effects</h3><p style="margin:0">Missed or doubled doses can cause health problems. Emergency staff may lack the medicine list or allergies. Siblings feel left out or blame each other, and the caregiver carries an unfair, invisible share of the work.</p></div>
    <div class="user-card" style="background:var(--dusty);color:var(--ink)"><h3>Needs</h3><p style="margin:0">A single shared record, clear logging of doses and expenses, timely alerts, and a printable summary for medical visits.</p></div>
  </div>
  <h3 style="margin-top:18px">Current process and its limits</h3>
  <p style="margin:0">The caregiver texts the group when a dose is given or something is bought. When a doctor asks for the medicine list, the family writes it from memory or searches old messages. There is no single record, no reminder for overdue doses or refills, no structured expense split and no quick summary for a doctor — and information is lost when a phone is replaced or a chat is muted.</p>
</section>

<section class="card">
  <h2>3 · The proposed solution</h2>
  <p>LavenDearly gives the family one web-based place to manage the elderly person’s care. The caregiver logs each dose as given, missed or skipped, with the time and who logged it. The system shows the day’s schedule, flags overdue doses and warns when a medicine is close to its refill date.</p>
  <p>Doctor visits, prescriptions and lab results are stored with dates and files. Every family member records purchases and contributions, and the system compares what each person has paid with their agreed share. When a doctor or emergency room needs information, an authorized user prints a one-page summary.</p>
  <p style="margin:0"><strong>Goal:</strong> one shared, reliable record so medicines are taken on time, health information is available when needed, and money and responsibilities are shared fairly.</p>
</section>

<section class="card">
  <h2>4 · Users and what each can do</h2>
  <div class="grid g3">
    <div class="user-card" style="background:var(--plum);color:var(--cream)"><h3>Family Administrator</h3><p style="margin:0">Usually the eldest or most involved sibling. Adds members, assigns roles, manages shares, edits all records, and views every report and the audit log.</p></div>
    <div class="user-card" style="background:var(--dusty);color:var(--ink)"><h3>Caregiver</h3><p style="margin:0">Live-in relative or helper. Logs doses, care notes and purchases, uploads receipts, and maintains medicines, appointments and the patient profile. Cannot change expense settings or delete records.</p></div>
    <div class="user-card" style="background:var(--beige)"><h3>Contributor</h3><p style="margin:0">Siblings and relatives, including those abroad. Views medicines, notes, appointments and expense totals; records their own contributions; comments on notes. Cannot log doses or change the schedule.</p></div>
  </div>
  <p class="muted" style="margin:14px 0 0">The patient does not log in; the printed summary is shared with the doctor.</p>
</section>

<section class="card">
  <h2>5 · Objectives</h2>
  <ol class="clean">
    <li>Record each medicine dose as given, missed or skipped, and alert the caregiver to overdue doses and upcoming refills.</li>
    <li>Store appointments, prescriptions and lab results, and generate a printable medical summary for doctors and emergency care.</li>
    <li>Track each family member’s contributions and expenses, showing totals and the balance each person owes or is owed.</li>
    <li>Control access by role, support search and filtering, and keep an audit trail of every change.</li>
  </ol>
</section>

<section class="card">
  <h2>6 · System modules</h2>
  <div class="table-wrap"><table>
    <thead><tr><th>#</th><th>Module</th><th>What it does</th></tr></thead><tbody>
    <?php
    $mods = [
      ['Login and Logout', 'Secure accounts by role; sessions time out after 20 minutes of inactivity.'],
      ['Dashboard', 'Today’s schedule, overdue doses, refills due within 7 days, upcoming appointments and balances.'],
      ['Patient Profile', 'Name, birthdate, allergies, conditions, primary doctor and emergency contacts.'],
      ['Family Member Management', 'Add, edit and view members with relationships, roles and expense shares.'],
      ['Medicine Schedule', 'Name, dose, times, start and refill dates, prescriber; active or discontinued.'],
      ['Dose Log', 'Given, missed or skipped with time, logger and note; blocks duplicate entries.'],
      ['Overdue and Refill Alerts', 'Flags unlogged doses and medicines nearing their refill date.'],
      ['Appointments and Documents', 'Visits, labs and prescriptions with files checked for type and size.'],
      ['Expense Recording', 'Purchases with date, item, category, amount, payer and receipt.'],
      ['Contribution Tracking and Balances', 'Paid versus agreed share for each member.'],
      ['Daily Care Notes', 'Mood, meals, sleep and concerns, with comments from contributors.'],
      ['Search and Filtering', 'By date, medicine, member, keyword, status or category.'],
      ['Reports', 'Monthly expenses, adherence rate and pending contributions, exportable to CSV.'],
      ['Doctor Summary', 'One-page printable profile, medicines, allergies, contacts and recent visits.'],
      ['User Management', 'Reset passwords, deactivate accounts and change roles.'],
      ['Audit Log and Data Validation', 'Records user, action, record and time of every change; requires valid dates and positive amounts.'],
    ];
    foreach ($mods as $i => [$n, $d]): ?>
      <tr><td><?= $i + 1 ?></td><td><strong><?= e($n) ?></strong></td><td><?= e($d) ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</section>

<section class="card">
  <h2>7 · Limitations and ethical considerations</h2>
  <ul class="clean">
    <li><strong>Sensitive data.</strong> The system stores health and financial information about a vulnerable person. It is handled under the Data Privacy Act of 2012 (RA 10173): only signed-in family members can see records, each role sees only what it needs, uploaded files are served only to signed-in users, and every change is logged.</li>
    <li><strong>Access removal.</strong> When a family member leaves, the administrator deactivates their account and they lose access immediately.</li>
    <li><strong>Not medical advice.</strong> LavenDearly supports record-keeping and coordination. The medicine schedule must follow the prescription and the doctor’s instructions.</li>
    <li><strong>Needs internet access.</strong> The caregiver should keep a printed copy of the medicine list (see Doctor Summary) in case the system is unavailable.</li>
  </ul>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
