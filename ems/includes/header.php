<?php
// $pageTitle should be set before including this
$pageTitle = ($pageTitle ?? 'EventHub') . ' — ' . SITE_NAME;
$flash     = getFlash();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <script>
    // Apply theme before paint to avoid flash
    (function(){
      var t = localStorage.getItem('ems_theme') || 'dark';
      document.documentElement.setAttribute('data-theme', t);
    })();
  </script>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
</head>
<body>

<nav class="navbar">
  <div class="navbar-inner">
    <a href="<?= BASE_URL ?>" class="navbar-brand">
      <div class="brand-icon">🎪</div>
      <?= SITE_NAME ?>
    </a>

    <ul class="navbar-nav">
      <li><a href="<?= BASE_URL ?>" <?= basename($_SERVER['PHP_SELF'])==='index.php' ? 'class="active"' : '' ?>>Browse Events</a></li>
      <?php if (isUserLoggedIn()): ?>
        <li><a href="<?= BASE_URL ?>/user_dashboard.php" <?= basename($_SERVER['PHP_SELF'])==='user_dashboard.php' ? 'class="active"' : '' ?>>My Bookings</a></li>
      <?php endif; ?>
    </ul>

    <div class="navbar-right">
      <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle theme"></button>
      <span class="theme-icon theme-sun" style="display:none">☀️</span>
      <span class="theme-icon theme-moon" style="display:none">🌙</span>

      <?php if (isUserLoggedIn()):
        $u = currentUser();
        $initials = strtoupper(substr($u['name'], 0, 1));
      ?>
        <div class="dropdown">
          <button class="user-btn">
            <div class="avatar"><?= $initials ?></div>
            <?= htmlspecialchars(explode(' ', $u['name'])[0]) ?>
            <span style="color:var(--txt3);font-size:.8rem;">▾</span>
          </button>
          <div class="dropdown-menu">
            <a href="<?= BASE_URL ?>/user_dashboard.php">🎟 My Bookings</a>
            <div class="divider"></div>
            <a href="<?= BASE_URL ?>/logout.php" style="color:var(--red)">↩ Logout</a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/user_login.php"    class="btn btn-ghost btn-sm">Login</a>
        <a href="<?= BASE_URL ?>/user_register.php" class="btn btn-primary btn-sm">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<?php if ($flash): ?>
  <div class="container" style="padding-top:1.25rem;">
    <div class="alert alert-<?= $flash['type'] ?>">
      <?= $flash['type'] === 'success' ? '✅' : ($flash['type'] === 'error' ? '❌' : 'ℹ️') ?>
      <?= htmlspecialchars($flash['msg']) ?>
    </div>
  </div>
<?php endif; ?>
