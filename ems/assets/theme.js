// ── Theme Toggle ────────────────────────────────────
(function() {
  const saved = localStorage.getItem('ems_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', saved);
})();

function toggleTheme() {
  const html = document.documentElement;
  const current = html.getAttribute('data-theme');
  const next = current === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', next);
  localStorage.setItem('ems_theme', next);
  updateIcons(next);
}

function updateIcons(theme) {
  document.querySelectorAll('.theme-sun').forEach(el => {
    el.style.display = theme === 'dark' ? 'inline' : 'none';
  });
  document.querySelectorAll('.theme-moon').forEach(el => {
    el.style.display = theme === 'light' ? 'inline' : 'none';
  });
}

document.addEventListener('DOMContentLoaded', function() {
  const theme = document.documentElement.getAttribute('data-theme');
  updateIcons(theme);

  // Dropdown toggle
  document.querySelectorAll('.dropdown').forEach(function(dd) {
    const trigger = dd.querySelector('.user-btn, .dropdown-trigger');
    if (trigger) {
      trigger.addEventListener('click', function(e) {
        e.stopPropagation();
        dd.classList.toggle('open');
      });
    }
  });

  document.addEventListener('click', function() {
    document.querySelectorAll('.dropdown.open').forEach(dd => dd.classList.remove('open'));
  });
});
