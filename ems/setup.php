<?php
require_once __DIR__ . '/config.php';

$step  = 1;
$error = '';
$done  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminName  = trim($_POST['admin_name']  ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPass  = $_POST['admin_pass']       ?? '';
    $adminPass2 = $_POST['admin_pass2']      ?? '';

    if (!$adminName || !$adminEmail || !$adminPass) {
        $error = 'All fields are required.';
    } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($adminPass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($adminPass !== $adminPass2) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Connect without specifying DB first to create it
            $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `" . DB_NAME . "`");

            // Create tables
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS categories (
                    id         INT AUTO_INCREMENT PRIMARY KEY,
                    name       VARCHAR(100) NOT NULL,
                    color      VARCHAR(7)   DEFAULT '#6366f1',
                    icon       VARCHAR(10)  DEFAULT '📅',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS events (
                    id          INT AUTO_INCREMENT PRIMARY KEY,
                    title       VARCHAR(200) NOT NULL,
                    description TEXT,
                    category_id INT,
                    event_date  DATE    NOT NULL,
                    event_time  TIME    NOT NULL,
                    location    VARCHAR(200) NOT NULL,
                    capacity    INT          DEFAULT 100,
                    price       DECIMAL(10,2) DEFAULT 0.00,
                    status      ENUM('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
                    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
                );

                CREATE TABLE IF NOT EXISTS users (
                    id         INT AUTO_INCREMENT PRIMARY KEY,
                    name       VARCHAR(100)  NOT NULL,
                    email      VARCHAR(150)  UNIQUE NOT NULL,
                    password   VARCHAR(255)  NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS admins (
                    id         INT AUTO_INCREMENT PRIMARY KEY,
                    name       VARCHAR(100)  NOT NULL,
                    email      VARCHAR(150)  UNIQUE NOT NULL,
                    password   VARCHAR(255)  NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS bookings (
                    id        INT AUTO_INCREMENT PRIMARY KEY,
                    user_id   INT NOT NULL,
                    event_id  INT NOT NULL,
                    tickets   INT DEFAULT 1,
                    status    ENUM('confirmed','cancelled','pending') DEFAULT 'confirmed',
                    booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_booking (user_id, event_id),
                    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
                    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
                );
            ");

            // Insert default categories
            $pdo->exec("
                INSERT IGNORE INTO categories (name, color, icon) VALUES
                ('Music',       '#8b5cf6', '🎵'),
                ('Technology',  '#3b82f6', '💻'),
                ('Sports',      '#10b981', '⚽'),
                ('Arts',        '#f59e0b', '🎨'),
                ('Business',    '#6366f1', '💼'),
                ('Food & Drink','#ef4444', '🍕');
            ");

            // Create admin
            $hash = password_hash($adminPass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)
                                   ON DUPLICATE KEY UPDATE name=VALUES(name), password=VALUES(password)");
            $stmt->execute([$adminName, $adminEmail, $hash]);

            $done = true;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Setup — <?= SITE_NAME ?></title>
  <script>(function(){var t=localStorage.getItem('ems_theme')||'dark';document.documentElement.setAttribute('data-theme',t);})();</script>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>

<div class="auth-page">
  <div class="auth-card" style="max-width:500px;">
    <div class="auth-logo">
      <div class="brand-icon">🎪</div>
      <?= SITE_NAME ?> Setup
    </div>

    <?php if ($done): ?>
      <div class="alert alert-success" style="margin-bottom:1.5rem;">
        ✅ Setup complete! Database created and admin account ready.
      </div>
      <h2 class="auth-title">You're all set!</h2>
      <p style="margin-bottom:1.5rem;">Your admin credentials and database have been created.</p>
      <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
        <a href="<?= ADMIN_PATH ?>/login.php" class="btn btn-primary btn-lg" style="flex:1;">Go to Admin Panel</a>
        <a href="<?= BASE_URL ?>"             class="btn btn-ghost  btn-lg" style="flex:1;">View Site</a>
      </div>
    <?php else: ?>
      <h2 class="auth-title">First-time Setup</h2>
      <p class="auth-subtitle">Create the database and your admin account to get started.</p>

      <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label class="form-label">Admin Name</label>
          <input type="text" name="admin_name" class="form-control" placeholder="Your name"
                 value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Admin Email</label>
          <input type="email" name="admin_email" class="form-control" placeholder="admin@example.com"
                 value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="admin_pass" class="form-control" placeholder="Min 6 chars" required>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="admin_pass2" class="form-control" placeholder="Repeat password" required>
          </div>
        </div>

        <div class="alert alert-info" style="margin-bottom:1.25rem;font-size:.82rem;">
          ℹ️ This will create the <strong><?= DB_NAME ?></strong> database on <strong><?= DB_HOST ?></strong>
          and set up all required tables.
        </div>

        <button type="submit" class="btn btn-primary w-full btn-lg">🚀 Run Setup</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/theme.js"></script>
</body>
</html>
