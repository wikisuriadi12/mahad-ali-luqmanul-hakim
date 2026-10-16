# SIM MA'HAD ALI LUQMANUL HAKIM

Aplikasi administrasi internal berbasis PHP 8.2+ dan MySQL/MariaDB untuk hosting IDwebhost/cPanel.

## Modul tahap awal
- Login administrator dan sesi aman
- Dashboard ringkasan
- Data Peserta (tambah dan daftar)
- Inventaris Aset (tambah dan daftar)
- Keuangan: pemasukan (infaq, usaha, iuran, donasi, lainnya) dan pengeluaran operasional (tambah dan daftar)
- Saldo kas sederhana dari transaksi tercatat

## Instalasi di IDwebhost
1. Buat database dan user MySQL melalui cPanel; berikan hak akses hanya ke database aplikasi.
2. Impor `database/schema.sql` lewat phpMyAdmin.
3. Unggah isi repository ke document root subdomain.
4. Salin `config.example.php` menjadi `config.php`, lalu isi kredensial database. Jangan commit `config.php`.
5. Buat akun admin pertama lewat SQL dengan hash dari `password_hash('password-kuat', PASSWORD_DEFAULT)` yang dibuat secara lokal. Contoh: `INSERT INTO users (username,password_hash,role) VALUES ('admin','HASH_HASIL_PASSWORD_HASH','admin');`
6. Akses HTTPS subdomain; login dan segera gunakan password yang kuat.

**Penting:** ini versi awal/MVP untuk pengujian, bukan sistem produksi final. Sebelum menyimpan data peserta sungguhan perlu pengujian keamanan, backup, kebijakan privasi, dan penambahan edit/hapus, approval, serta laporan. Jangan unggah dokumen sensitif atau kredensial ke GitHub.
