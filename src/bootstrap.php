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
 $module=['peserta'=>'peserta','guru'=>'guru','pengurus'=>'pengurus','aset'=>'aset','keuangan'=>'bendahara','laporan'=>'bendahara'][$page]??'';
 return $module!=='' && in_array($role,['admin','pimpinan',$module],true);
}
function master_config(string $module): array {
 return ['guru'=>['table'=>'teachers','title'=>'Guru','fields'=>['kode'=>['Kode Guru',50],'nama'=>['Nama',180],'bidang_ajar'=>['Bidang Ajar',120],'no_hp'=>['No. HP',30]],'statuses'=>['aktif','nonaktif','cuti']],
  'pengurus'=>['table'=>'staff_members','title'=>'Pengurus','fields'=>['kode'=>['Kode Pengurus',50],'nama'=>['Nama',180],'jabatan'=>['Jabatan',120],'no_hp'=>['No. HP',30]],'statuses'=>['aktif','nonaktif']]][$module];
}
function require_role(string $module): void { if (!can_write($module)) { http_response_code(403); exit('Akses ditolak.'); } }
function redirect(string $page): never { header('Location: ?page='.rawurlencode($page)); exit; }
function rupiah_bulat($v): string { return 'Rp '.number_format((float)$v,0,',','.'); }
function rupiah_ringkas($v,bool $pendek=false): string {
 $n=abs((float)$v);$tanda=(float)$v<0?'-':'';
 $angka=function(float $x): string { return rtrim(rtrim(number_format($x,2,',','.'),'0'),','); };
 if ($n>=1000000000) return $tanda.'Rp '.$angka($n/1000000000).($pendek?' M':' miliar');
 if ($n>=1000000) return $tanda.'Rp '.$angka($n/1000000).($pendek?' jt':' juta');
 return $tanda.rupiah_bulat($n);
}
function icon(string $name): string {
 static $paths=[
  'home'=>'<path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v10h14V10"/><path d="M10 20v-5h4v5"/>',
  'peserta'=>'<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="9" r="2.4"/><path d="M16.5 14.2c2.6.2 4.5 2.4 4.5 5.3"/>',
  'guru'=>'<path d="M3 5.5 12 3l9 2.5-9 2.5z"/><path d="M7 9.2v4.3c0 1.5 2.2 2.7 5 2.7s5-1.2 5-2.7V9.2"/><path d="M21 6v6"/>',
  'pengurus'=>'<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a1.5 1.5 0 0 1 1.5-1.5h3A1.5 1.5 0 0 1 15 5v2"/><path d="M3 13h18"/>',
  'aset'=>'<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/>',
  'keuangan'=>'<rect x="3" y="6" width="18" height="13" rx="2.5"/><path d="M3 10h18"/><circle cx="16.5" cy="14.5" r="1.2"/>',
  'laporan'=>'<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 13h7M9 17h7"/>',
  'pengguna'=>'<circle cx="12" cy="8" r="3.4"/><path d="M5 20c.6-3.6 3.4-5.5 7-5.5s6.4 1.9 7 5.5"/>',
  'akun'=>'<circle cx="12" cy="12" r="3"/><path d="M12 3v2.5M12 18.5V21M3 12h2.5M18.5 12H21M5.6 5.6l1.8 1.8M16.6 16.6l1.8 1.8M18.4 5.6l-1.8 1.8M7.4 16.6l-1.8 1.8"/>',
  'plus'=>'<path d="M12 5v14M5 12h14"/>',
  'minus'=>'<path d="M5 12h14"/>',
  'menu'=>'<path d="M4 7h16M4 12h16M4 17h16"/>',
 ];
 return '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??'').'</svg>';
}
function kategori_lain_ada(): bool {
 static $ada;
 if ($ada===null) $ada=(bool)sql("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='transactions' AND COLUMN_NAME='kategori_lain' LIMIT 1")->fetchColumn();
 return $ada;
}
function kolom_kategori_lain(string $alias=''): string { return kategori_lain_ada()?($alias!==''?$alias.'.':'').'kategori_lain':'NULL AS kategori_lain'; }
function label_kategori(array $r): string {
 $lain=trim((string)($r['kategori_lain']??''));
 return ($r['kategori']??'')==='lainnya'&&$lain!==''?'lainnya: '.$lain:(string)($r['kategori']??'');
}
function insert_transaksi(array $v,string $lain=''): void {
 // $v = [tanggal,jenis,kategori,uraian,nominal,created_by]; $lain = kategori ketikan sendiri (hanya untuk "lainnya").
 if ($lain!==''&&$v[2]==='lainnya') {
  if (!kategori_lain_ada()) throw new RuntimeException('Kolom kategori_lain belum ada di database. Jalankan perintah ALTER TABLE dari README terlebih dahulu.');
  $v[]=$lain;sql('INSERT INTO transactions (tanggal,jenis,kategori,uraian,nominal,created_by,kategori_lain) VALUES (?,?,?,?,?,?,?)',$v);
 } else sql('INSERT INTO transactions (tanggal,jenis,kategori,uraian,nominal,created_by) VALUES (?,?,?,?,?,?)',$v);
}
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
 $rows=sql('SELECT tanggal,jenis,kategori,'.kolom_kategori_lain().',uraian,nominal FROM transactions WHERE tanggal BETWEEN ? AND ? ORDER BY tanggal,id',[$from,$to]);
 header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="laporan-keuangan.csv"');
 $f=fopen('php://output','w');fwrite($f,"\xEF\xBB\xBF");fputcsv($f,['Tanggal','Jenis','Kategori','Uraian','Nominal'],',','"','');
 while ($r=$rows->fetch()) fputcsv($f,array_map('csv_cell',[$r['tanggal'],$r['jenis'],label_kategori($r),$r['uraian'],$r['nominal']]),',','"','');fclose($f);
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
  } elseif (in_array($action,['save_guru','save_pengurus'],true)) {
   $module=substr($action,5);require_role($module);$target=$module;$cfg=master_config($module);
   $status=input('status');if (!in_array($status,$cfg['statuses'],true)) throw new RuntimeException('Status tidak valid.');
   $values=[];foreach ($cfg['fields'] as $col=>[$label,$max]) $values[]=field($col,$max,in_array($col,['kode','nama'],true));
   $values[]=$status;$cols=array_keys($cfg['fields']);$cols[]='status';
   if (input('id')!=='') { $id=filter_var(input('id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if (!$id || !sql('SELECT id FROM '.$cfg['table'].' WHERE id=?',[$id])->fetch()) throw new RuntimeException('Data tidak ditemukan.');$values[]=$id;sql('UPDATE '.$cfg['table'].' SET '.implode('=?,',$cols).'=? WHERE id=?',$values); }
   else sql('INSERT INTO '.$cfg['table'].' ('.implode(',',$cols).') VALUES ('.implode(',',array_fill(0,count($cols),'?')).')',$values);
  } elseif ($action==='save_aset') {
   require_role('aset');$target='aset';$condition=input('kondisi');
   if (!in_array($condition,['baik','rusak_ringan','rusak_berat'],true)) throw new RuntimeException('Kondisi tidak valid.');
   $values=[field('kode',60),field('nama',180),field('kategori',100,false),field('lokasi',180,false),$condition,money(input('nilai'))];
   if (input('id')!=='') { $id=filter_var(input('id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if (!$id || !sql('SELECT id FROM assets WHERE id=?',[$id])->fetch()) throw new RuntimeException('Aset tidak ditemukan.');$values[]=$id;sql('UPDATE assets SET kode=?,nama=?,kategori=?,lokasi=?,kondisi=?,nilai_perolehan=? WHERE id=?',$values); }
   else sql('INSERT INTO assets (kode,nama,kategori,lokasi,kondisi,nilai_perolehan) VALUES (?,?,?,?,?,?)',$values);
  } elseif ($action==='add_keuangan') {
   require_role('bendahara');$target='keuangan';$jenis=input('jenis');$kategori=input('kategori');$date=input('tanggal');
   if (!in_array($jenis,['masuk','keluar'],true)||!in_array($kategori,categories($jenis),true)||!valid_date($date)) throw new RuntimeException('Tanggal, jenis, atau kategori tidak valid.');
   insert_transaksi([$date,$jenis,$kategori,field('uraian',255),money(input('nominal'),true),$_SESSION['user_id']],$kategori==='lainnya'?field('kategori_lain',100,false):'');
  } elseif ($action==='koreksi_keuangan') {
   require_role('bendahara');$target='keuangan';
   $id=filter_var(input('id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$reason=field('alasan',255);$cdate=input('tanggal_koreksi');
   if (!$id||!valid_date($cdate)) throw new RuntimeException('Transaksi atau tanggal koreksi tidak valid.');
   $replace=input('ganti')==='1';$new=[];$newLain='';
   if ($replace) {
    $kind=input('jenis');$cat=input('kategori');$ndate=input('tanggal');
    if (!in_array($kind,['masuk','keluar'],true)||!in_array($cat,categories($kind),true)||!valid_date($ndate)) throw new RuntimeException('Data transaksi pengganti tidak valid (tanggal, jenis, atau kategori).');
    $new=[$ndate,$kind,$cat,field('uraian',255),money(input('nominal'),true),$_SESSION['user_id']];$newLain=$cat==='lainnya'?field('kategori_lain',100,false):'';
   }
   $pdo=db();$pdo->beginTransaction();
   try {
    $orig=sql('SELECT id,jenis,kategori,'.kolom_kategori_lain().',uraian,nominal FROM transactions WHERE id=? FOR UPDATE',[$id])->fetch();
    if (!$orig) throw new RuntimeException('Transaksi tidak ditemukan.');
    if (sql('SELECT id FROM transaction_corrections WHERE transaction_id=? OR reversal_id=?',[$id,$id])->fetch()) throw new RuntimeException('Transaksi ini sudah dikoreksi atau merupakan transaksi pembalik.');
    $note=preg_replace('/^(.{255}).*$/us','$1','KOREKSI #'.$id.': '.$orig['uraian']);
    insert_transaksi([$cdate,$orig['jenis']==='masuk'?'keluar':'masuk',$orig['kategori'],$note,$orig['nominal'],$_SESSION['user_id']],(string)($orig['kategori_lain']??''));
    $reversalId=(int)$pdo->lastInsertId();$replacementId=null;
    if ($replace) {insert_transaksi($new,$newLain);$replacementId=(int)$pdo->lastInsertId();}
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
