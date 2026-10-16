<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/src/bootstrap.php';
$page = $_GET['page'] ?? 'dashboard';
if (!isset($_SESSION['user_id'])) {
    require __DIR__ . '/views/login.php';
    exit;
}
$allowed = ['dashboard','peserta','aset','keuangan'];
if (!in_array($page, $allowed, true)) $page = 'dashboard';
require __DIR__ . '/views/layout.php';
