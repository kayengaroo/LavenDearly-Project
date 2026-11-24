<?php
declare(strict_types=1);
$isLoggedIn = !empty($currentUser);
?>
      </main>

      <footer class="site-footer">
        <div><strong>LavenDearly</strong> — Web-Based Family Caregiving Coordination System for an Elderly Person (RA 10173 Data Privacy Aware).</div>
        <div class="footer-note">Coordination &amp; record-keeping aid only; always follow licensed physician prescriptions and emergency care guidance.</div>
      </footer>
    </div>
  </div>

  <?php if ($isLoggedIn): ?>
  <script src="assets/js/app.js" data-timeout="<?= (int)SESSION_TIMEOUT_SECONDS ?>"></script>
  <?php endif; ?>
</body>
</html>
