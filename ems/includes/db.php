<?php
require_once __DIR__ . '/../config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('<div style="font-family:sans-serif;padding:2rem;color:#ef4444;">
         <strong>Database Error:</strong> ' . htmlspecialchars($e->getMessage()) . '<br><br>
         Make sure MySQL is running on <code>' . DB_HOST . '</code> and the database 
         <code>' . DB_NAME . '</code> exists. Run <a href="' . BASE_URL . '/setup.php">setup.php</a> first.
         </div>');
}
