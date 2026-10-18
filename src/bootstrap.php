<?php
declare(strict_types=1);
function esc($v): string { return htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function query(string $key, string $default=''): string { return isset($_GET[$key]) && is_string($_GET[$key]) ? $_GET[$key] : $default; }
function input(string $key): string { return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : ''; }
function db(): PDO {
 static $pdo;
 if (!$pdo) {
  $file=dirname(__DIR__).'/config.php';
  if (!is_file($file)) throw new RuntimeException('Database belum dikonfigurasi. Hubungi administrator.');
  $cfg=require $file;
  foreach (['db_host','db_name','db_user','db_pass'] as $key) if (!isset($cfg[$key]) || !is_string($cfg[$key])) throw new RuntimeException('Konfigurasi database belum lengkap.');
  $pdo=new PDO('mysql:host='.$cfg['db_host'].';dbname='.$cfg['db_name'].';charset=utf8mb4',$cfg['db_user'],$cfg['db_pass'],[
   PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
 }
 return $pdo;
}
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function token(): void { echo '<input type="hidden" name="csrf" value="'.esc(csrf()).'">'; }
function verify_csrf(): void { if (!hash_equals(csrf(), input('csrf'))) { http_response_code(419); exit('Form kedaluwarsa. Muat ulang halaman.'); } }
function can_write(string $module): bool { return in_array($_SESSION['role']??'', ['admin',$module],true); }
function can_view(string $page): bool {
 $role=$_SESSION['role']??'';
 if (in_array($page,['dashboard','akun'],true)) return $role!=='';
 if ($page==='pengguna') return $role==='admin';
 $module=['peserta'=>'peserta','aset'=>'aset','keuangan'=>'bendahara','laporan'=>'bendahara'][$page]??'';
 return $module!=='' && in_array($role,['admin','pimpinan',$module],true);
}
function require_role(string $module): void { if (!can_write($module)) { http_response_code(403); exit('Akses ditolak.'); } }
function redirect(string $page): never { header('Location: ?page='.rawurlencode($page)); exit; }
function rupiah($v): string { return 'Rp '.number_format((float)$v,2,',','.'); }
function field(string $key,int $max,bool $required=true): string {
 $value=input($key);
 if (($required && $value==='') || preg_match_all('/./us',$value)>$max) throw new RuntimeException('Isian '.$key.' kosong atau terlalu panjang.');
 return $value;
}
function money(string $value,bool $positive=false): string {
 if (!preg_match('/^(?:0|[1-9][0-9]{0,15})(?:\.[0-9]{1,2})?$/D',$value) || ($positive && (float)$value<=0)) throw new RuntimeException('Nominal harus berupa angka rupiah yang valid, maksimal dua desimal.');
 return $value;
}
function valid_date(string $v): bool { $d=DateTimeImmutable::createFromFormat('!Y-m-d',$v); return $d && $d->format('Y-m-d')===$v; }
function categories(string $kind): array { return $kind==='masuk' ? ['infaq','usaha','iuran','donasi','lainnya'] : ['operasional','listrik_air','konsumsi','pendidikan','pemeliharaan','lainnya']; }
function sql(string $statement,array $params=[]): PDOStatement { $q=db()->prepare($statement);$q->execute($params);return $q; }
function report_filter(): array {
 $from=query('dari',date('Y-m-01'));$to=query('sampai',date('Y-m-d'));
 if (!valid_date($from)||!valid_date($to)||$from>$to) throw new RuntimeException('Rentang tanggal tidak valid.');
 return [$from,$to];
}
function csv_cell($v): string { $s=(string)$v;return preg_match('/^[\s]*[=+@-]/u',$s) ? "'".$s : $s; }
function export_report(): void {
 [$from,$to]=report_filter();
 $rows=sql('SELECT tanggal,jenis,kategori,uraian,nominal FROM transactions WHERE tanggal BETWEEN ? AND ? ORDER BY tanggal,id',[$from,$to]);
 header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="laporan-keuangan.csv"');
 $f=fopen('php://output','w');fwrite($f,"\xEF\xBB\xBF");fputcsv($f,['Tanggal','Jenis','Kategori','Uraian','Nominal'],',','"','');
 while ($r=$rows->fetch()) fputcsv($f,array_map('csv_cell',array_values($r)),',','"','');fclose($f);
}
if (isset($_SESSION['user_id'])) {
 try {
  $current=sql('SELECT id,username,role,password_hash FROM users WHERE id=?',[$_SESSION['user_id']])->fetch();
  if (!$current || (isset($_SESSION['auth_version']) && !hash_equals($_SESSION['auth_version'],hash('sha256',$current['password_hash'])))) { $_SESSION=[];redirect('login'); }
  $_SESSION['role']=$current['role'];$_SESSION['username']=$current['username'];$_SESSION['auth_version']=hash('sha256',$current['password_hash']);
 } catch (Throwable $e) { error_log($e->getMessage());http_response_code(503);exit('Database belum siap. Administrator perlu memeriksa konfigurasi dan tabel sistem.'); }
}
if (($_SERVER['REQUEST_METHOD']??'')==='POST') {
 verify_csrf();$action=input('action');$target=query('page','dashboard');
 try {
  if ($action==='login') {
   $target='login';
   if (($_SESSION['login_wait']??0)>time()) throw new RuntimeException('Terlalu banyak percobaan. Tunggu satu menit.');
   $u=sql('SELECT id,username,password_hash,role FROM users WHERE username=?',[input('username')])->fetch();
   $password=is_string($_POST['password']??null)?$_POST['password']:'';
   if (!$u || !password_verify($password,$u['password_hash'])) {
    $_SESSION['login_fails']=($_SESSION['login_fails']??0)+1;
    if ($_SESSION['login_fails']>=5) {$_SESSION['login_wait']=time()+60;$_SESSION['login_fails']=0;}
    throw new RuntimeException('Username atau password tidak cocok.');
   }
   $secure=!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off';
   if (input('ingat')==='1') setcookie('ingat_username',$u['username'],['expires'=>time()+2592000,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
   else setcookie('ingat_username','',['expires'=>time()-3600,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
   session_regenerate_id(true);$_SESSION=[];
   $_SESSION['user_id']=$u['id'];$_SESSION['username']=$u['username'];$_SESSION['role']=$u['role'];$_SESSION['auth_version']=hash('sha256',$u['password_hash']);redirect('dashboard');
  }
  if (!isset($_SESSION['user_id'])) {http_response_code(401);exit('Login diperlukan.');}
  if ($action==='logout') {$_SESSION=[];session_destroy();redirect('login');}
  if ($action==='save_peserta') {
   require_role('peserta');$target='peserta';
   $status=input('status');if (!in_array($status,['aktif','nonaktif','lulus'],true)) throw new RuntimeException('Status tidak valid.');
   $values=[field('nomor_induk',50),field('nama',180),field('angkatan',30,false),field('nama_wali',180,false),$status];
   if (input('id')!=='') { $id=filter_var(input('id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if (!$id || !sql('SELECT id FROM participants WHERE id=?',[$id])->fetch()) throw new RuntimeException('Peserta tidak ditemukan.');$values[]=$id;sql('UPDATE participants SET nomor_induk=?,nama=?,angkatan=?,nama_wali=?,status=? WHERE id=?',$values); }
   else sql('INSERT INTO participants (nomor_induk,nama,angkatan,nama_wali,status) VALUES (?,?,?,?,?)',$values);
  } elseif ($action==='save_aset') {
   require_role('aset');$target='aset';$condition=input('kondisi');
   if (!in_array($condition,['baik','rusak_ringan','rusak_berat'],true)) throw new RuntimeException('Kondisi tidak valid.');
   $values=[field('kode',60),field('nama',180),field('kategori',100,false),field('lokasi',180,false),$condition,money(input('nilai'))];
   if (input('id')!=='') { $id=filter_var(input('id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if (!$id || !sql('SELECT id FROM assets WHERE id=?',[$id])->fetch()) throw new RuntimeException('Aset tidak ditemukan.');$values[]=$id;sql('UPDATE assets SET kode=?,nama=?,kategori=?,lokasi=?,kondisi=?,nilai_perolehan=? WHERE id=?',$values); }
   else sql('INSERT INTO assets (kode,nama,kategori,lokasi,kondisi,nilai_perolehan) VALUES (?,?,?,?,?,?)',$values);
  } elseif ($action==='add_keuangan') {
   require_role('bendahara');$target='keuangan';$jenis=input('jenis');$kategori=input('kategori');$date=input('tanggal');
   if (!in_array($jenis,['masuk','keluar'],true)||!in_array($kategori,categories($jenis),true)||!valid_date($date)) throw new RuntimeException('Tanggal, jenis, atau kategori tidak valid.');
   sql('INSERT INTO transactions (tanggal,jenis,kategori,uraian,nominal,created_by) VALUES (?,?,?,?,?,?)',[$date,$jenis,$kategori,field('uraian',255),money(input('nominal'),true),$_SESSION['user_id']]);
  } elseif ($action==='koreksi_keuangan') {
   require_role('bendahara');$target='keuangan';
   $id=filter_var(input('id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$reason=field('alasan',255);$cdate=input('tanggal_koreksi');
   if (!$id||!valid_date($cdate)) throw new RuntimeException('Transaksi atau tanggal koreksi tidak valid.');
   $replace=input('ganti')==='1';$new=[];
   if ($replace) {
    $kind=input('jenis');$cat=input('kategori');$ndate=input('tanggal');
    if (!in_array($kind,['masuk','keluar'],true)||!in_array($cat,categories($kind),true)||!valid_date($ndate)) throw new RuntimeException('Data transaksi pengganti tidak valid (tanggal, jenis, atau kategori).');
    $new=[$ndate,$kind,$cat,field('uraian',255),money(input('nominal'),true),$_SESSION['user_id']];
   }
   $pdo=db();$pdo->beginTransaction();
   try {
    $orig=sql('SELECT id,jenis,kategori,uraian,nominal FROM transactions WHERE id=? FOR UPDATE',[$id])->fetch();
    if (!$orig) throw new RuntimeException('Transaksi tidak ditemukan.');
    if (sql('SELECT id FROM transaction_corrections WHERE transaction_id=? OR reversal_id=?',[$id,$id])->fetch()) throw new RuntimeException('Transaksi ini sudah dikoreksi atau merupakan transaksi pembalik.');
    $note=preg_replace('/^(.{255}).*$/us','$1','KOREKSI #'.$id.': '.$orig['uraian']);
    sql('INSERT INTO transactions (tanggal,jenis,kategori,uraian,nominal,created_by) VALUES (?,?,?,?,?,?)',[$cdate,$orig['jenis']==='masuk'?'keluar':'masuk',$orig['kategori'],$note,$orig['nominal'],$_SESSION['user_id']]);
    $reversalId=(int)$pdo->lastInsertId();$replacementId=null;
    if ($replace) {sql('INSERT INTO transactions (tanggal,jenis,kategori,uraian,nominal,created_by) VALUES (?,?,?,?,?,?)',$new);$replacementId=(int)$pdo->lastInsertId();}
    sql('INSERT INTO transaction_corrections (transaction_id,reversal_id,replacement_id,alasan,created_by) VALUES (?,?,?,?,?)',[$id,$reversalId,$replacementId,$reason,$_SESSION['user_id']]);
    $pdo->commit();
   } catch (Throwable $e) {if ($pdo->inTransaction()) $pdo->rollBack();throw $e;}
  } elseif ($action==='change_password') {
   $target='akun';$old=$_POST['password_lama']??'';$new=$_POST['password_baru']??'';$confirm=$_POST['konfirmasi']??'';
   $u=sql('SELECT password_hash FROM users WHERE id=?',[$_SESSION['user_id']])->fetch();
   if (!is_string($old)||!password_verify($old,$u['password_hash'])) throw new RuntimeException('Password lama tidak cocok.');
   if (!is_string($new)||strlen($new)<12||strlen($new)>72||$new!==$confirm) throw new RuntimeException('Password baru harus 12–72 karakter dan konfirmasi harus sama.');
   $hash=password_hash($new,PASSWORD_DEFAULT);sql('UPDATE users SET password_hash=? WHERE id=?',[$hash,$_SESSION['user_id']]);
   session_regenerate_id(true);$_SESSION['auth_version']=hash('sha256',$hash);$_SESSION['csrf']=bin2hex(random_bytes(32));
  } elseif ($action==='add_user') {
   require_role('admin');$target='pengguna';$username=field('username',80);$role=input('role');$password=$_POST['password']??'';
   if (!preg_match('/^[A-Za-z0-9_.-]{3,80}$/D',$username)||!in_array($role,['admin','peserta','aset','bendahara','pimpinan'],true)||!is_string($password)||strlen($password)<12||strlen($password)>72) throw new RuntimeException('Username, peran, atau password tidak valid (password minimal 12 karakter).');
   sql('INSERT INTO users (username,password_hash,role) VALUES (?,?,?)',[$username,password_hash($password,PASSWORD_DEFAULT),$role]);
  } else throw new RuntimeException('Aksi tidak dikenal.');
  $_SESSION['flash']='Berhasil disimpan.';
 } catch (PDOException $e) {error_log($e->getMessage());$_SESSION['flash']=$e->getCode()==='23000'?'Nomor induk, kode, atau username sudah digunakan.':'Database belum siap atau penyimpanan gagal. Hubungi administrator.';}
 catch (RuntimeException $e) {$_SESSION['flash']=$e->getMessage();}
 redirect($target);
}
