-- ===================== MASTER =====================
CREATE TABLE roles (
    id_role INT AUTO_INCREMENT PRIMARY KEY,
    nama_role VARCHAR(50) UNIQUE NOT NULL,
    deskripsi VARCHAR(255)
);

CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    id_role INT NOT NULL,
    nidn_nip VARCHAR(30) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    login_gagal INT DEFAULT 0,
    blokir_sampai DATETIME DEFAULT NULL,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_role) REFERENCES roles(id_role)
);

CREATE TABLE fakultas (
    id_fakultas INT AUTO_INCREMENT PRIMARY KEY,
    nama_fakultas VARCHAR(150) NOT NULL
);

CREATE TABLE prodi (
    id_prodi INT AUTO_INCREMENT PRIMARY KEY,
    id_fakultas INT,
    kode_prodi VARCHAR(20) UNIQUE,
    nama_prodi VARCHAR(150) NOT NULL,
    FOREIGN KEY (id_fakultas) REFERENCES fakultas(id_fakultas)
);

-- ===================== PUBLIK =====================
CREATE TABLE berita (
    id_berita INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT,
    judul VARCHAR(200) NOT NULL,
    slug VARCHAR(220) UNIQUE NOT NULL,
    kategori ENUM('Berita','Agenda','Pengumuman') DEFAULT 'Berita',
    konten LONGTEXT NOT NULL,
    cover VARCHAR(255),
    is_published TINYINT(1) DEFAULT 0,
    published_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user)
);

CREATE TABLE pengaduan (
    id_pengaduan INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    instansi VARCHAR(150),
    subjek VARCHAR(200) NOT NULL,
    isi_pesan TEXT NOT NULL,
    status ENUM('Baru','Diproses','Selesai') DEFAULT 'Baru',
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE akreditasi (
    id_akreditasi INT AUTO_INCREMENT PRIMARY KEY,
    id_prodi INT,
    tingkat ENUM('Prodi','Institusi') DEFAULT 'Prodi',
    peringkat VARCHAR(30),          -- Unggul / A / Baik Sekali / B
    lembaga ENUM('BAN-PT','LAMDIK','LAMSAMA','Internasional'),
    no_sk VARCHAR(100),
    tanggal_sk DATE,
    masa_berlaku DATE,
    file_sertifikat VARCHAR(255),
    FOREIGN KEY (id_prodi) REFERENCES prodi(id_prodi)
);

-- ===================== DOKUMEN MUTU (PPEPP) =====================
CREATE TABLE kategori_dokumen (
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100)    -- Kebijakan, Manual Mutu, Standar, Formulir
);

CREATE TABLE dokumen_mutu (
    id_dokumen INT AUTO_INCREMENT PRIMARY KEY,
    id_kategori INT,
    id_user INT,
    kode_dokumen VARCHAR(50) UNIQUE,
    judul_dokumen VARCHAR(200) NOT NULL,
    deskripsi TEXT,
    file_path VARCHAR(255) NOT NULL,
    versi INT DEFAULT 1,
    tipe_akses ENUM('publik','internal') DEFAULT 'internal',
    status ENUM('Aktif','Revisi','Nonaktif') DEFAULT 'Aktif',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES kategori_dokumen(id_kategori),
    FOREIGN KEY (id_user) REFERENCES users(id_user)
);

-- ===================== AMI (AUDIT MUTU INTERNAL) =====================
CREATE TABLE jadwal_ami (
    id_jadwal INT AUTO_INCREMENT PRIMARY KEY,
    tahun_ami YEAR NOT NULL,
    tanggal_mulai DATE,
    tanggal_selesai DATE,
    status ENUM('Draf','Berjalan','Selesai') DEFAULT 'Draf'
);

CREATE TABLE penugasan_audit (
    id_tugas INT AUTO_INCREMENT PRIMARY KEY,
    id_jadwal INT,
    id_prodi INT,
    id_auditor INT,
    tanggal_audit DATE,
    status ENUM('Ditugaskan','Dikerjakan','Selesai') DEFAULT 'Ditugaskan',
    FOREIGN KEY (id_jadwal) REFERENCES jadwal_ami(id_jadwal),
    FOREIGN KEY (id_prodi) REFERENCES prodi(id_prodi),
    FOREIGN KEY (id_auditor) REFERENCES users(id_user)
);

CREATE TABLE standar_mutu (
    id_standar INT AUTO_INCREMENT PRIMARY KEY,
    kode_standar VARCHAR(30),
    nama_standar VARCHAR(255),
    kategori_standar VARCHAR(100)
);

CREATE TABLE evaluasi_diri (
    id_evaluasi INT AUTO_INCREMENT PRIMARY KEY,
    id_prodi INT,
    id_standar INT,
    tahun YEAR,
    capaian DECIMAL(5,2),
    target DECIMAL(5,2),
    bukti_fisik VARCHAR(255),
    catatan TEXT,
    status ENUM('Draf','Terkunci') DEFAULT 'Draf',
    FOREIGN KEY (id_prodi) REFERENCES prodi(id_prodi),
    FOREIGN KEY (id_standar) REFERENCES standar_mutu(id_standar)
);

CREATE TABLE temuan_audit (
    id_temuan INT AUTO_INCREMENT PRIMARY KEY,
    id_tugas INT,
    id_standar INT,
    kategori ENUM('Mayor','Minor','Observasi','Kondisi Baik'),
    deskripsi_temuan TEXT,
    tindakan_koreksi TEXT,
    tanggal_koreksi DATE,
    status_verifikasi ENUM('Menunggu','Diterima','Ditolak') DEFAULT 'Menunggu',
    catatan_verifikasi TEXT,
    FOREIGN KEY (id_tugas) REFERENCES penugasan_audit(id_tugas),
    FOREIGN KEY (id_standar) REFERENCES standar_mutu(id_standar)
);

-- ===================== AKREDITASI (CLOUD BORANG) =====================
CREATE TABLE borang_akreditasi (
    id_borang INT AUTO_INCREMENT PRIMARY KEY,
    id_prodi INT,
    id_user INT,
    kriteria VARCHAR(50),           -- Kriteria 1..9 / LED
    nama_file VARCHAR(200),
    file_path VARCHAR(255),
    catatan TEXT,
    status ENUM('Draf','Final','Terkirim') DEFAULT 'Draf',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_prodi) REFERENCES prodi(id_prodi),
    FOREIGN KEY (id_user) REFERENCES users(id_user)
);

-- ===================== SEED DATA =====================
INSERT INTO roles (nama_role, deskripsi) VALUES
('Admin LPM','Administrator sistem penjaminan mutu'),
('Pimpinan','Rektor / Dekan'),
('Kaprodi','Ketua Program Studi / GKM'),
('Auditor','Auditor Mutu Internal');

-- Password default: GantiSegera2026!
INSERT INTO users (id_role, nidn_nip, nama_lengkap, email, password_hash) VALUES
(1, 'ADMIN001', 'Administrator LPM', 'admin@kampus.ac.id',
 '$2y$12$LJ3X9u8Yq1ZQ0vXy5pZKf.7mHq3rJvN2cW1sAeG8tB5oYdU6iP4Sa');

INSERT INTO kategori_dokumen (nama_kategori) VALUES
('Kebijakan Mutu'),('Manual Mutu'),('Standar Mutu'),('Formulir'),('SOP');

INSERT INTO standar_mutu (kode_standar, nama_standar, kategori_standar) VALUES
('S1','Standar Kompetensi Lulusan','Pendidikan'),
('S2','Standar Isi Pembelajaran','Pendidikan'),
('S3','Standar Proses Pembelajaran','Pendidikan'),
('S4','Standar Penelitian','Penelitian'),
('S5','Standar Pengabdian Masyarakat','Pengabdian');