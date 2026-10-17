<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('APP_TEST')!=='1')exit(1);
$config=['db_host'=>'127.0.0.1','db_name'=>'mahad_test','db_user'=>'root','db_pass'=>'test-only-password'];
if(is_file(__DIR__.'/../config.php'))exit("Refusing to overwrite config.php\n");
file_put_contents(__DIR__.'/../config.php','<?php return '.var_export($config,true).';');
require __DIR__.'/../src/bootstrap.php';
foreach(explode(';',file_get_contents(__DIR__.'/../database/schema.sql')) as $q)if(trim($q)!=='')db()->exec($q);
foreach(['admin','peserta','aset','bendahara','pimpinan'] as $role)sql('INSERT INTO users (username,password_hash,role) VALUES (?,?,?)',[$role,password_hash('Testing-Password-123',PASSWORD_DEFAULT),$role]);
echo "Test fixtures ready\n";
