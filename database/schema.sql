CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(80) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','peserta','aset','bendahara','pimpinan') NOT NULL DEFAULT 'admin',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS participants (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nomor_induk VARCHAR(50) NOT NULL UNIQUE,
 nama VARCHAR(180) NOT NULL,
 angkatan VARCHAR(30),
 nama_wali VARCHAR(180),
 status ENUM('aktif','nonaktif','lulus') NOT NULL DEFAULT 'aktif',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS assets (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 kode VARCHAR(60) NOT NULL UNIQUE,
 nama VARCHAR(180) NOT NULL,
 kategori VARCHAR(100),
 lokasi VARCHAR(180),
 kondisi ENUM('baik','rusak_ringan','rusak_berat') NOT NULL DEFAULT 'baik',
 nilai_perolehan DECIMAL(18,2) NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS transactions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tanggal DATE NOT NULL,
 jenis ENUM('masuk','keluar') NOT NULL,
 kategori ENUM('infaq','usaha','iuran','donasi','lainnya','operasional','listrik_air','konsumsi','pendidikan','pemeliharaan') NOT NULL,
 uraian VARCHAR(255) NOT NULL,
 nominal DECIMAL(18,2) NOT NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_transactions_user FOREIGN KEY (created_by) REFERENCES users(id),
 INDEX idx_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS transaction_corrections (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 transaction_id BIGINT UNSIGNED NOT NULL UNIQUE,
 reversal_id BIGINT UNSIGNED NOT NULL UNIQUE,
 replacement_id BIGINT UNSIGNED NULL UNIQUE,
 alasan VARCHAR(255) NOT NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_corr_tx FOREIGN KEY (transaction_id) REFERENCES transactions(id),
 CONSTRAINT fk_corr_rev FOREIGN KEY (reversal_id) REFERENCES transactions(id),
 CONSTRAINT fk_corr_rep FOREIGN KEY (replacement_id) REFERENCES transactions(id),
 CONSTRAINT fk_corr_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
