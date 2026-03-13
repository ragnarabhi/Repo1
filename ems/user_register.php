<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (isUserLoggedIn()) { header('Location: ' . BASE_URL); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password']   ?? '';
    $pass2 = $_POST['password2']  ?? '';

    if (!$name || !$email || !$pass) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $pass2) {
        $error = 'Passwords do not match.';
    } else {
        // Check duplicate
        $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $ins  = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?,?,?)");
            $ins->execute([$name, $email, $hash]);

            $uid = $pdo->lastInsertId();
            $_SESSION['user_id']    = $uid;
            $_SESSION['user_name']  = $name;
            $_SESSION['user_email'] = $email;
            setFlash('success', "Account created! Welcome to " . SITE_NAME . ", $name! 🎉");
            header('Location: ' . BASE_URL);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — <?= SITE_NAME ?></title>
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

    <h2 class="auth-title">Create your account</h2>
    <p class="auth-subtitle">Join <?= SITE_NAME ?> and start exploring events.</p>

    <?php if ($error): ?>
      <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" placeholder="John Doe"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="you@example.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Min 6 chars" required>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <input type="password" name="password2" class="form-control" placeholder="Repeat" required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-full btn-lg" style="margin-top:.25rem;">
        Create Account →
      </button>
    </form>

    <hr class="divider">

    <p style="text-align:center;font-size:.875rem;color:var(--txt2);">
      Already have an account?
      <a href="<?= BASE_URL ?>/user_login.php" style="font-weight:600;">Login here</a>
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
