<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (isAdminLoggedIn()) { header('Location: ' . ADMIN_PATH . '/index.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($email && $pass) {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($pass, $admin['password'])) {
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            header('Location: ' . ADMIN_PATH . '/index.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — <?= SITE_NAME ?></title>
  <script>(function(){var t=localStorage.getItem('ems_theme')||'dark';document.documentElement.setAttribute('data-theme',t);})();</script>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>

<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="brand-icon">🛡</div>
      Admin Panel
    </div>

    <h2 class="auth-title">Admin Login</h2>
    <p class="auth-subtitle"><?= SITE_NAME ?> Administrator Access</p>

    <?php if ($error): ?>
      <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="admin@example.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Admin password" required>
      </div>

      <button type="submit" class="btn btn-primary w-full btn-lg" style="margin-top:.5rem;">
        Enter Admin Panel →
      </button>
    </form>

    <p style="text-align:center;margin-top:1.5rem;">
      <a href="<?= BASE_URL ?>" style="font-size:.82rem;color:var(--txt3);">← Back to Site</a>
    </p>

    <div style="text-align:center;margin-top:1rem;">
      <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme"></button>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/theme.js"></script>
</body>
</html>
