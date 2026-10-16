<?php
declare(strict_types=1);
function esc($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function db(): PDO {
 static $pdo;
 if (!$pdo) {
  $file = dirname(__DIR__) . '/config.php';
  if (!is_file($file)) { http_response_code(503); exit('Database belum dikonfigurasi.'); }
  $cfg = require $file;
  $pdo = new PDO('mysql:host='.$cfg['db_host'].';dbname='.$cfg['db_name'].';charset=utf8mb4', $cfg['db_user'], $cfg['db_pass'], [
   PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
   PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
   PDO::ATTR_EMULATE_PREPARES => false
  ]);
 }
 return $pdo;
}
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function verify_csrf(): void {
 if (!hash_equals(csrf(), (string)($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Form kedaluwarsa.'); }
}
function require_role(string $module): void {
 $role = $_SESSION['role'] ?? '';
 if (!in_array($role, ['admin', $module], true)) { http_response_code(403); exit('Akses ditolak.'); }
}
function redirect(string $page): never { header('Location: ?page='.rawurlencode($page)); exit; }
function rupiah($value): string { return 'Rp '.number_format((float)$value, 0, ',', '.'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 verify_csrf();
 $action = $_POST['action'] ?? '';
 try {
  if ($action === 'login') {
   $q=db()->prepare('SELECT id,username,password_hash,role FROM users WHERE username=?');
   $q->execute([trim((string)($_POST['username'] ?? ''))]);
   $user=$q->fetch();
   if (!$user || !password_verify((string)($_POST['password'] ?? ''), $user['password_hash'])) throw new RuntimeException('Login tidak valid.');
   session_regenerate_id(true);
   $_SESSION['user_id']=$user['id']; $_SESSION['username']=$user['username']; $_SESSION['role']=$user['role'];
   redirect('dashboard');
  }
  if ($action === 'logout') { $_SESSION=[]; session_destroy(); redirect('login'); }
  if (!isset($_SESSION['user_id'])) { http_response_code(401); exit('Login diperlukan.'); }
  if ($action === 'add_peserta') {
   require_role('peserta');
   $id=trim((string)($_POST['nomor_induk'] ?? '')); $nama=trim((string)($_POST['nama'] ?? ''));
   if ($id==='' || $nama==='') throw new RuntimeException('Nomor induk dan nama wajib diisi.');
   $q=db()->prepare('INSERT INTO participants (nomor_induk,nama,angkatan,nama_wali) VALUES (?,?,?,?)');
   $q->execute([$id,$nama,trim((string)($_POST['angkatan'] ?? '')),trim((string)($_POST['nama_wali'] ?? ''))]);
   redirect('peserta');
  }
  if ($action === 'add_aset') {
   require_role('aset');
   $kode=trim((string)($_POST['kode'] ?? '')); $nama=trim((string)($_POST['nama'] ?? ''));
   $nilai=(string)($_POST['nilai'] ?? '');
   if ($kode==='' || $nama==='' || !is_numeric($nilai) || (float)$nilai<0) throw new RuntimeException('Data aset tidak valid.');
   $q=db()->prepare('INSERT INTO assets (kode,nama,kategori,lokasi,nilai_perolehan) VALUES (?,?,?,?,?)');
   $q->execute([$kode,$nama,trim((string)($_POST['kategori'] ?? '')),trim((string)($_POST['lokasi'] ?? '')),$nilai]);
   redirect('aset');
  }
  if ($action === 'add_keuangan') {
   require_role('bendahara');
   $jenis=(string)($_POST['jenis'] ?? '');
   $kategori=(string)($_POST['kategori'] ?? '');
   $masuk=['infaq','usaha','iuran','donasi','lainnya'];
   $keluar=['operasional','listrik_air','konsumsi','pendidikan','pemeliharaan','lainnya'];
   $nominal=(string)($_POST['nominal'] ?? '');
   $tanggal=(string)($_POST['tanggal'] ?? '');
   $uraian=trim((string)($_POST['uraian'] ?? ''));
   if (!in_array($jenis,['masuk','keluar'],true) || !in_array($kategori,$jenis==='masuk'?$masuk:$keluar,true) || !is_numeric($nominal) || (float)$nominal<=0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$tanggal) || $uraian==='') throw new RuntimeException('Data transaksi tidak valid.');
   $q=db()->prepare('INSERT INTO transactions (tanggal,jenis,kategori,uraian,nominal,created_by) VALUES (?,?,?,?,?,?)');
   $q->execute([$tanggal,$jenis,$kategori,$uraian,$nominal,$_SESSION['user_id']]);
   redirect('keuangan');
  }
  throw new RuntimeException('Aksi tidak dikenal.');
 } catch (PDOException $e) {
  error_log($e->getMessage()); $_SESSION['flash']='Gagal menyimpan data. Periksa kemungkinan data ganda.';
 } catch (RuntimeException $e) { $_SESSION['flash']=$e->getMessage(); }
 redirect((string)($_GET['page'] ?? 'dashboard'));
}
