<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Jakarta');
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly'=>true, 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite'=>'Lax']);
session_start();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');
require __DIR__.'/src/bootstrap.php';
$page = query('page', 'dashboard');
if (!isset($_SESSION['user_id'])) { require __DIR__.'/views/login.php'; exit; }
$allowed = ['dashboard','peserta','guru','pengurus','aset','keuangan','laporan','akun','pengguna'];
if (!in_array($page,$allowed,true)) $page='dashboard';
if (!can_view($page)) { http_response_code(403); $page='forbidden'; }
if (query('export') === 'csv' && $page === 'laporan') {
 try { export_report(); } catch (Throwable $e) { error_log($e->getMessage()); http_response_code(503); echo 'Laporan belum dapat diunduh. Periksa koneksi database.'; }
 exit;
}
require __DIR__.'/views/layout.php';
