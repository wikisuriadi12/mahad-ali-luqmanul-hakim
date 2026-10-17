<?php
// CLI only. Upload manually outside the public document root when possible.
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/src/bootstrap.php';
function prompt(string $label): string { fwrite(STDOUT,$label); $line=fgets(STDIN);if($line===false)throw new RuntimeException('Input terhenti.');return trim($line); }
try {
 echo "Pemasangan tabel Ma'had (tanpa menghapus atau mengosongkan data).\n";
 $cfg=require dirname(__DIR__).'/config.php';
 echo 'Database tujuan: '.$cfg['db_name']."\n";
 if(prompt('Ketik PASANG untuk melanjutkan: ')!=='PASANG') exit("Dibatalkan.\n");
 $schema=file_get_contents(dirname(__DIR__).'/database/schema.sql');
 foreach(explode(';',$schema) as $statement) if(trim($statement)!=='') db()->exec($statement);
 echo "Empat tabel sistem siap.\n";
 if((int)sql('SELECT COUNT(*) FROM users')->fetchColumn()>0) exit("Pengguna sudah ada. Tidak membuat atau mengganti akun.\n");
 $username=prompt('Username admin pertama: ');
 if(!preg_match('/^[A-Za-z0-9_.-]{3,80}$/D',$username)) throw new RuntimeException('Username tidak valid.');
 if(!function_exists('shell_exec') || !function_exists('stream_isatty') || !stream_isatty(STDIN)) throw new RuntimeException('Gunakan terminal interaktif dengan stty untuk memasukkan password secara tersembunyi.');
 $mode=shell_exec('stty -g');if(!$mode)throw new RuntimeException('Terminal tidak mendukung input password tersembunyi.');
 shell_exec('stty -echo');
 try {$password=prompt('Password admin (12–72 karakter; tidak ditampilkan): ');echo "\n";$confirm=prompt('Ulangi password: ');echo "\n";}
 finally {shell_exec('stty '.escapeshellarg(trim($mode)));}
 if(strlen($password)<12||strlen($password)>72||$password!==$confirm)throw new RuntimeException('Password tidak memenuhi syarat atau konfirmasi berbeda.');
 sql('INSERT INTO users (username,password_hash,role) VALUES (?,?,?)',[$username,password_hash($password,PASSWORD_DEFAULT),'admin']);
 echo "Admin dibuat. Silakan login ke website.\n";
} catch(Throwable $e) {fwrite(STDERR,"Pemasangan belum selesai. Periksa konfigurasi, tabel, dan input; akun lama tidak diubah.\n");exit(1);}
