<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
session_destroy();
header('Location: ' . ADMIN_PATH . '/login.php');
exit;
