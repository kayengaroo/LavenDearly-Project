<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentUser = check_session_timeout() ? require_login() : null;

$pageTitle = 'About & Data Privacy';
$activePage = 'about.php';
include __DIR__ . '/includes/header.php';
?>
<div class="page-header">
  <div>
    <h1>About LavenDearly &amp; Ethical Considerations</h1>
    <p>Purpose, role access, clinical boundaries, and Data Privacy Act of 2012 (RA 10173) safeguards.</p>
  </div>
</div>

<div class="card">
  <h2>Purpose &amp; Coordination Scope</h2>
  <p>LavenDearly gives families one shared web record for an elderly loved one's daily care—replacing scattered chat messages, unrecorded refill dates, lost receipts, and unclear expense splits.</p>
  <p><strong>Medical Disclaimer:</strong> LavenDearly supports record-keeping and family coordination. It does not replace licensed medical advice, diagnosis, or treatment; medicine schedules must strictly follow the prescribing physician's instructions.</p>
</div>

<div class="card">
  <h2>Data Privacy Act of 2012 (RA 10173) &amp; Access Control</h2>
  <ul>
    <li><strong>Role-Based Access:</strong> <em>Administrators</em> manage family access, roles, share settings, and audit logs; <em>Caregivers</em> log doses, appointments, expenses, and daily care notes; <em>Contributors</em> view shared records, comment on care notes, and log their own contributions.</li>
    <li><strong>Revoking Access:</strong> When a family member no longer needs access, the Administrator can deactivate their account immediately in User Management.</li>
    <li><strong>Offline Backup Reminder:</strong> Because web apps depend on connectivity and device availability, families should keep a printed copy of active medicines, allergies, and emergency contacts at home.</li>
  </ul>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
