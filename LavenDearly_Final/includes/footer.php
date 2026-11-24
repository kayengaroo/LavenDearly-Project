<?php
// Closes the layout opened by header.php.
$loggedIn = (function_exists('me') && me());
?>
  <footer class="site-footer no-print">
    <span>LavenDearly · Family caregiving coordination</span>
    <span>Supports record-keeping; it does not replace medical advice.</span>
  </footer>
</main>
<?php if ($loggedIn): ?></div><?php endif; ?>
<script>
(function () {
  var b = document.getElementById('menuBtn'), s = document.getElementById('sidebar');
  if (!b || !s) return;
  b.addEventListener('click', function () {
    var open = s.classList.toggle('open');
    b.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
})();
</script>
</body>
</html>
