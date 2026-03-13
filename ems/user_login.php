<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (isUserLoggedIn()) { header('Location: ' . BASE_URL); exit; }

$error    = '';
$redirect = $_GET['redirect'] ?? (BASE_URL . '/user_dashboard.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!$email || !$pass) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            setFlash('success', 'Welcome back, ' . $user['name'] . '! 👋');
            header('Location: ' . (filter_var($redirect, FILTER_VALIDATE_URL) ? $redirect : BASE_URL));
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — <?= SITE_NAME ?></title>
  <script>(function(){var t=localStorage.getItem('ems_theme')||'dark';document.documentElement.setAttribute('data-theme',t);})();</script>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>

<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="brand-icon">🎪</div>
      <?= SITE_NAME ?>
    </div>

    <h2 class="auth-title">Welcome back</h2>
    <p class="auth-subtitle">Log in to manage your event bookings.</p>

    <?php if ($error): ?>
      <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="you@example.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Your password" required>
      </div>

      <button type="submit" class="btn btn-primary w-full btn-lg" style="margin-top:.5rem;">
        Login →
      </button>
    </form>

    <hr class="divider">

    <p style="text-align:center;font-size:.875rem;color:var(--txt2);">
      Don't have an account?
      <a href="<?= BASE_URL ?>/user_register.php" style="font-weight:600;">Register here</a>
    </p>

    <p style="text-align:center;margin-top:.5rem;">
      <a href="<?= BASE_URL ?>" style="font-size:.82rem;color:var(--txt3);">← Browse Events</a>
    </p>

    <div style="text-align:center;margin-top:1.5rem;">
      <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme"></button>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/theme.js"></script>
</body>
</html>
