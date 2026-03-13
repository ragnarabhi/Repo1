<?php
$pageTitle = ($pageTitle ?? 'Admin') . ' — ' . SITE_NAME . ' Admin';
$flash     = getFlash();
$admin     = currentAdmin();
$current   = basename($_SERVER['PHP_SELF']);

$navItems = [
  ['file' => 'index.php',      'icon' => '📊', 'label' => 'Dashboard'],
  ['file' => 'events.php',     'icon' => '🗓', 'label' => 'Events'],
  ['file' => 'categories.php', 'icon' => '🏷', 'label' => 'Categories'],
  ['file' => 'attendees.php',  'icon' => '👥', 'label' => 'Attendees'],
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <script>
    (function(){
      var t = localStorage.getItem('ems_theme') || 'dark';
      document.documentElement.setAttribute('data-theme', t);
    })();
  </script>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>

<div class="admin-wrap">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon">🎪</div>
      <?= SITE_NAME ?>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-label">Main</div>
      <ul class="sidebar-nav">
        <?php foreach ($navItems as $item): ?>
          <li>
            <a href="<?= ADMIN_PATH . '/' . $item['file'] ?>"
               class="<?= $current === $item['file'] ? 'active' : '' ?>">
              <span class="nav-icon"><?= $item['icon'] ?></span>
              <?= $item['label'] ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-label">Quick Links</div>
      <ul class="sidebar-nav">
        <li><a href="<?= BASE_URL ?>" target="_blank"><span class="nav-icon">🌐</span> View Site</a></li>
        <li><a href="<?= ADMIN_PATH ?>/event_form.php"><span class="nav-icon">➕</span> New Event</a></li>
      </ul>
    </div>

    <div class="sidebar-bottom">
      <div class="sidebar-user">
        <div class="avatar" style="width:34px;height:34px;"><?= strtoupper(substr($admin['name'],0,1)) ?></div>
        <div>
          <div class="name"><?= htmlspecialchars($admin['name']) ?></div>
          <div class="role">Administrator</div>
        </div>
      </div>
      <a href="<?= ADMIN_PATH ?>/logout.php" style="display:flex;align-items:center;gap:.5rem;padding:.5rem .75rem;border-radius:8px;font-size:.82rem;color:var(--red);margin-top:.25rem;transition:background var(--transition);" onmouseover="this.style.background='rgba(239,68,68,.1)'" onmouseout="this.style.background='transparent'">
        ↩ Logout
      </a>
    </div>
  </aside>

  <!-- Main content -->
  <div class="admin-content">
    <header class="admin-topbar">
      <h1><?= $pageHeading ?? ($pageTitle ?? 'Admin') ?></h1>
      <div class="admin-topbar-right">
        <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme"></button>
        <span class="theme-icon theme-sun" style="display:none">☀️</span>
        <span class="theme-icon theme-moon" style="display:none">🌙</span>
        <div class="avatar" style="width:32px;height:32px;font-size:.75rem;"><?= strtoupper(substr($admin['name'],0,1)) ?></div>
      </div>
    </header>

    <main class="admin-main">
      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom:1.5rem;">
          <?= $flash['type'] === 'success' ? '✅' : ($flash['type'] === 'error' ? '❌' : 'ℹ️') ?>
          <?= htmlspecialchars($flash['msg']) ?>
        </div>
      <?php endif; ?>
