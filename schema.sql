-- ITstok v2 - Skema Database MySQL/MariaDB

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(191) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS barang (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(50) NOT NULL UNIQUE,
  nama VARCHAR(255) NOT NULL,
  model VARCHAR(255) NOT NULL DEFAULT '',
  kategori VARCHAR(100) NOT NULL DEFAULT '',
  satuan VARCHAR(50) NOT NULL DEFAULT 'Unit',
  stok INT NOT NULL DEFAULT 0,
  stok_min INT NOT NULL DEFAULT 0,
  kondisi ENUM('baik', 'rusak', 'servis') NOT NULL DEFAULT 'baik',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_barang_stok CHECK (stok >= 0),
  KEY idx_barang_kategori (kategori)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS barang_masuk (
  id INT AUTO_INCREMENT PRIMARY KEY,
  barang_id INT NOT NULL,
  tanggal DATE NOT NULL,
  jumlah INT NOT NULL,
  supplier VARCHAR(255) NOT NULL DEFAULT '',
  keterangan VARCHAR(255) NOT NULL DEFAULT '',
  petugas_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_masuk_jumlah CHECK (jumlah > 0),
  CONSTRAINT fk_masuk_barang FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE CASCADE,
  CONSTRAINT fk_masuk_petugas FOREIGN KEY (petugas_id) REFERENCES users(id) ON DELETE SET NULL,
  KEY idx_masuk_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS barang_keluar (
  id INT AUTO_INCREMENT PRIMARY KEY,
  barang_id INT NOT NULL,
  tanggal DATE NOT NULL,
  jumlah INT NOT NULL,
  penerima VARCHAR(255) NOT NULL DEFAULT '',
  departemen VARCHAR(100) NOT NULL DEFAULT '',
  keperluan VARCHAR(255) NOT NULL DEFAULT '',
  petugas_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_keluar_jumlah CHECK (jumlah > 0),
  CONSTRAINT fk_keluar_barang FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE CASCADE,
  CONSTRAINT fk_keluar_petugas FOREIGN KEY (petugas_id) REFERENCES users(id) ON DELETE SET NULL,
  KEY idx_keluar_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
