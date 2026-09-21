DROP DATABASE IF EXISTS seblak_db;
CREATE DATABASE seblak_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE seblak_db;

CREATE TABLE kategori (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(50) NOT NULL,
    urutan INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE menu_item (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_kategori INT UNSIGNED NOT NULL,
    nama VARCHAR(100) NOT NULL,
    harga_satuan DECIMAL(10,2) NOT NULL DEFAULT 0,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_menu_kategori (id_kategori),
    CONSTRAINT fk_menu_kategori FOREIGN KEY (id_kategori)
        REFERENCES kategori(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE kuah (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(50) NOT NULL,
    harga DECIMAL(10,2) NOT NULL DEFAULT 0,
    aktif TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE level_pedas (
    level TINYINT UNSIGNED PRIMARY KEY CHECK (level BETWEEN 0 AND 5),
    surcharge DECIMAL(10,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE meja (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nomor_meja INT UNSIGNED NOT NULL,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_meja_nomor (nomor_meja)
) ENGINE=InnoDB;

CREATE TABLE pesanan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(30) NOT NULL,
    nama_pelanggan VARCHAR(80) NOT NULL,
    no_meja INT UNSIGNED NULL,
    tipe ENUM('dine-in','takeaway') NOT NULL DEFAULT 'dine-in',
    id_kuah INT UNSIGNED NULL,
    level TINYINT UNSIGNED NOT NULL DEFAULT 0,
    harga_subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    harga_kuah DECIMAL(10,2) NOT NULL DEFAULT 0,
    surcharge_level DECIMAL(10,2) NOT NULL DEFAULT 0,
    tot_harga DECIMAL(10,2) NOT NULL DEFAULT 0,
    status_pembayaran ENUM('pending','lunas') NOT NULL DEFAULT 'pending',
    status_pesanan ENUM('baru','dimasak','siap','selesai') NOT NULL DEFAULT 'baru',
    catatan VARCHAR(255) NULL,
    dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    diperbarui_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pesanan_kode (kode),
    INDEX idx_pesanan_waktu (dibuat_pada),
    INDEX idx_pesanan_status (status_pesanan),
    INDEX idx_pesanan_idkuah (id_kuah),
    CONSTRAINT fk_pesanan_kuah FOREIGN KEY (id_kuah)
        REFERENCES kuah(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pesanan_detail (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pesanan INT UNSIGNED NOT NULL,
    id_menu_item INT UNSIGNED NULL,
    nama_barang VARCHAR(100) NOT NULL,
    harga_satuan DECIMAL(10,2) NOT NULL DEFAULT 0,
    qty INT UNSIGNED NOT NULL DEFAULT 1,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    INDEX idx_detail_pesanan (id_pesanan),
    CONSTRAINT fk_detail_pesanan FOREIGN KEY (id_pesanan)
        REFERENCES pesanan(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;