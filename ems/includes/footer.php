<footer style="background:var(--bg2);border-top:1px solid var(--border);padding:2rem 0;margin-top:4rem;transition:background var(--transition);">
  <div class="container" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
    <div style="display:flex;align-items:center;gap:.5rem;font-weight:700;color:var(--txt);">
      <div class="brand-icon" style="width:28px;height:28px;background:var(--accent);border-radius:8px;display:flex;align-items:center;justify-content:center;">🎪</div>
      <?= SITE_NAME ?>
    </div>
    <p style="font-size:.82rem;">© <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
    <div style="display:flex;gap:1rem;font-size:.85rem;">
      <a href="<?= BASE_URL ?>">Browse Events</a>
      <a href="<?= BASE_URL ?>/user_register.php">Register</a>
      <a href="<?= ADMIN_PATH ?>/login.php" style="color:var(--txt3)">Admin</a>
    </div>
  </div>
</footer>

<script src="<?= BASE_URL ?>/assets/theme.js"></script>
</body>
</html>
