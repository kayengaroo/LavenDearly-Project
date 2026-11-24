document.addEventListener('DOMContentLoaded', () => {
  const scriptEl = document.querySelector('script[src*="assets/js/app.js"]');
  const timeoutSec = scriptEl ? parseInt(scriptEl.dataset.timeout || '900', 10) : 900;
  const timerEl = document.getElementById('sessionTimer');
  let remaining = timeoutSec;

  const resetTimer = () => {
    remaining = timeoutSec;
  };

  ['click', 'keydown', 'scroll', 'mousemove', 'touchstart'].forEach(evt => {
    window.addEventListener(evt, resetTimer, { passive: true });
  });

  if (timerEl) {
    setInterval(() => {
      remaining = Math.max(0, remaining - 1);
      const mins = Math.floor(remaining / 60);
      const secs = remaining % 60;
      timerEl.textContent = `Idle timeout in ${mins}:${String(secs).padStart(2, '0')}`;
      if (remaining === 0) {
        window.location.href = 'login.php?timeout=1';
      }
    }, 1000);
  }

  document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', (e) => {
      const msg = form.getAttribute('data-confirm') || 'Are you sure?';
      if (!window.confirm(msg)) {
        e.preventDefault();
      }
    });
  });
});
