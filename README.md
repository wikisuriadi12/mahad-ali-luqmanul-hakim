# SIM MA'HAD ALY LUQMANUL HAKIM

PHP 8.2+ / MySQL atau MariaDB, untuk IDwebhost/cPanel.
Domain: https://mahad.aly.yayasanluqmanulhakimmarelan.com/

## Fungsi struktur awal yang dilengkapi
- Login, logout, ganti password sendiri, dan tambah pengguna oleh admin.
- Dashboard sesuai hak akses: peserta, aset, pemasukan, pengeluaran, saldo.
- Peserta: tambah, edit, status aktif/nonaktif/lulus, cari, halaman daftar.
- Guru (tabel `teachers`): kode, nama, bidang ajar, no. HP, status aktif/nonaktif/cuti; tambah, edit, cari, daftar.
- Pengurus (tabel `staff_members`): kode, nama, jabatan, no. HP, status aktif/nonaktif; tambah, edit, cari, daftar.
- Dashboard modern (kartu ringkasan, grafik arus kas 6 bulan) dan tampilan responsif ponsel (menu geser, tabel menjadi kartu).
- Aset: tambah, edit, kondisi, lokasi, nilai perolehan, cari, halaman daftar.
- Keuangan: transaksi pemasukan/pengeluaran, validasi kategori dan tanggal.
- Koreksi transaksi berjejak: transaksi tidak diubah/dihapus; bendahara/admin membuat transaksi pembalik (jenis berlawanan, kategori dan nominal sama) beserta alasan, tanggal koreksi, dan pembuatnya. Dapat disertai transaksi pengganti yang benar. Satu transaksi hanya dapat dikoreksi sekali; transaksi pembalik tidak dapat dikoreksi.
- Laporan: rentang tanggal, saldo awal/akhir, CSV seluruh periode, cetak halaman.
- Tampilan responsif, CSRF, prepared statements, escaping HTML, cookie HttpOnly/SameSite,
  pembatasan percobaan login per sesi (bukan pengganti rate limiting server).
- Tidak ada fungsi hapus. Tidak ada akun atau password bawaan yang aktif.

## Hak akses
| Peran | Akses |
|---|---|
| admin | Semua modul, tambah pengguna |
| peserta | Petugas data peserta: tambah/edit/daftar |
| aset | Petugas inventaris: tambah/edit/daftar |
| bendahara | Input keuangan dan laporan |
| pimpinan | Baca peserta, guru, pengurus, aset, keuangan, laporan |

Data guru dan pengurus hanya dapat diubah oleh admin; peran lain tidak melihat menunya (pimpinan hanya membaca).
Semua peran dapat mengganti password sendiri. Perubahan password membatalkan sesi lain.

## Pemasangan database di cPanel
1. Pilih database aplikasi `yayas183_mahad_ali` di **Manage My Databases**. Buat hanya bila belum ada.
2. Hubungkan user MySQL aplikasi (sebelumnya `yayas183_mahaduser`) ke database itu saja.
   Untuk runtime aplikasi cukup SELECT, INSERT, UPDATE. Impor tabel dilakukan sebagai pemilik melalui phpMyAdmin.
3. Di phpMyAdmin pilih database aplikasi, lalu impor `database/schema.sql`.
   Berisi tujuh CREATE TABLE IF NOT EXISTS; tidak ada DROP, TRUNCATE, DELETE, atau data contoh.
4. Di document root `/home/yayas183/mahad.aly.yayasanluqmanulhakimmarelan.com`,
   salin `config.example.php` menjadi `config.php` dan isi host localhost, nama database,
   username MySQL lengkap, serta password MySQL yang dimasukkan sendiri. Jangan commit config.php.
5. Buat admin pertama lewat CLI interaktif `php tools/install.php` dari folder proyek lengkap.
   Script hanya berjalan di CLI, meminta konfirmasi database, membuat tabel yang belum ada,
   lalu meminta username/password tersembunyi. Bila sudah ada pengguna, tidak membuat akun tambahan.
   Bila menggunakan installer untuk membuat tabel, user MySQL memerlukan CREATE/REFERENCES saat pemasangan;
   setelahnya batasi kembali ke SELECT/INSERT/UPDATE.
   Folder tools dan database perlu diunggah manual; workflow tidak mengunggahnya.
6. Alternatif phpMyAdmin: buat hash dengan PHP `password_hash` secara lokal lalu INSERT akun
   ke users (username, password_hash, role). Jangan menyimpan password polos/MD5.
7. Login HTTPS dan periksa Dashboard, Peserta, Guru, Pengurus, Aset, Keuangan, Laporan, Akun.
   Akun sementara tidak kedaluwarsa otomatis; gunakan menu Akun untuk mengganti password.

## Pembaruan database yang sudah terpasang

Untuk fitur koreksi transaksi serta data guru dan pengurus, impor ulang `database/schema.sql` di phpMyAdmin pada database aplikasi.
Tabel lama tidak disentuh (CREATE TABLE IF NOT EXISTS); hanya tabel yang belum ada (`transaction_corrections`, `teachers`, `staff_members`) yang ditambahkan.
User MySQL aplikasi tetap cukup SELECT, INSERT, UPDATE. Unggah kode terbaru lewat deploy otomatis setelah tabel dibuat.

## Deployment
Workflow menguji PHP dan alur HTTP menggunakan database MariaDB sementara di GitHub Actions,
kemudian mengunggah index.php, src/*.php, views/*.php, dan assets/*.css melalui FTPS TLS terverifikasi.
FTP_SERVER_DIR: `mahad.aly.yayasanluqmanulhakimmarelan.com/` untuk akun FTP utama `yayas183`.
Server TLS: `sorosutan.idweb.host:21`. Tidak ada penghapusan remote atau migrasi otomatis.
config.php, SQL, tools, tests, dan .htaccess tidak diunggah otomatis. .htaccess dapat dipasang manual
setelah memeriksa aturan hosting yang sudah ada. Database hosting tidak dibuat oleh deployment.

## Pengujian
CI menggunakan database `mahad_test` terpisah. Fixture hanya boleh dijalankan dengan APP_TEST=1
pada lingkungan uji. Jangan menjalankannya di hosting produksi.
Cakupan: login, CSRF, escaping XSS, edit peserta, tambah aset, kategori/tanggal/nominal,
laporan saldo, CSV, batas hak akses, ganti password dan pembatalan sesi lama.

## Batas operasional
Laporan menghitung saldo transaksi tercatat, belum rekonsiliasi kas/bank atau akuntansi berpasangan.
Transaksi belum memiliki approval; tidak disediakan edit/hapus transaksi (kesalahan dikoreksi lewat transaksi pembalik).
CSV memuat seluruh periode; cetak memuat 50 transaksi per halaman yang dipilih.
Backup dan pembatasan login lintas sesi perlu diatur pada hosting sebelum pemakaian produksi.
