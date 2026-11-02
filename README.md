# LPM SIM-Mutu - Sistem Informasi Manajemen Penjaminan Mutu Internal

[![PHP](https://img.shields.io/badge/PHP-8.0+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Production%20Ready-success.svg)]()

**Sistem Informasi Manajemen untuk Lembaga Penjaminan Mutu (LPM) Perguruan Tinggi** - Platform terintegrasi untuk mengelola akreditasi, evaluasi, tracer study, dan penjaminan mutu internal dengan antarmuka modern dan fitur keamanan tinggi.

---

## 📋 Daftar Isi

- [Fitur Utama](#-fitur-utama)
- [Teknologi](#-teknologi)
- [Persyaratan Sistem](#-persyaratan-sistem)
- [Instalasi](#-instalasi)
- [Struktur Database](#-struktur-database)
- [Konfigurasi](#-konfigurasi)
- [Struktur Folder](#-struktur-folder)
- [Fitur Keamanan](#-fitur-keamanan)
- [Panduan Penggunaan](#-panduan-penggunaan)
- [API Documentation](#-api-documentation)
- [Troubleshooting](#-troubleshooting)
- [Kontribusi](#-kontribusi)
- [Lisensi](#-lisensi)
- [Tim Pengembang](#-tim-pengembang)

---

## ✨ Fitur Utama

###  **Modul Akademik**
- **Akreditasi Program Studi & Institusi**
  - Monitoring status akreditasi (Unggul/Baik Sekali/Baik)
  - Tracking masa berlaku sertifikat
  - Distribusi peringkat visual
  - Akreditasi Internasional (IABEE, ASIIN, AUN-QA, dll)
  - Export data CSV/PDF

- **EDOM (Evaluasi Dosen oleh Mahasiswa)**
  - Evaluasi 10 aspek kompetensi dosen
  - Sistem anonim dengan pencegahan double-fill
  - Real-time progress tracking
  - Auto-save draft
  - Statistik respons per dosen/mata kuliah

- **Tracer Study Alumni**
  - Tracking karier lulusan
  - Masa tunggu kerja
  - Kesesuaian bidang studi
  - Kisaran gaji
  - Testimoni alumni
  - Integrasi dengan responden akreditasi

- **Survei Pengguna Lulusan**
  - Penilaian oleh instansi/perusahaan
  - 7 aspek kompetensi (Etika, Keahlian, Bahasa, Teknologi, Komunikasi, Kerjasama, Pengembangan)
  - Rating bintang 1-5
  - Feedback untuk kurikulum

### 📊 **Modul Penjaminan Mutu**

- **AMI (Audit Mutu Internal)**
  - Jadwal audit tahunan
  - Temuan dan rekomendasi
  - Tindak lanjut (follow-up)
  - Dashboard progress

- **PPEPP (Penetapan, Pelaksanaan, Evaluasi, Pengendalian, Peningkatan)**
  - Siklus penjaminan mutu berkelanjutan
  - Dokumentasi standar
  - Monitoring implementasi

- **Dokumen Mutu**
  - Manajemen dokumen SPMI
  - Version control
  - Akses berbasis role
  - Download publik untuk dokumen tertentu

### 📢 **Modul Komunikasi**

- **Berita & Agenda**
  - Publikasi berita LPM
  - Jadwal kegiatan (AMI, RTM, Workshop)
  - Kategori dan tagging
  - SEO-friendly URLs

- **Pengaduan Masyarakat**
  - Form pengaduan online
  - Tracking status pengaduan
  - Notifikasi email
  - Kategori pengaduan

### 👤 **Modul Pengguna**

- **Profil Pengguna**
  - Edit profil
  - Ganti password
  - Upload foto profil
  - **Two-Factor Authentication (2FA)**
    - Google Authenticator/Authy
    - Recovery codes
    - QR code setup

- **Manajemen Hak Akses**
  - Role-based access control (Superadmin, LPM, Auditor, Prodi, Dosen, Mahasiswa, Alumni)
  - Permission granular
  - Audit trail

### 🎨 **Fitur UI/UX**

- **Dark Mode** - Toggle tema gelap/terang dengan sinkronisasi cross-tab
- **Responsive Design** - Mobile-first, works on all devices
- **Multi-language** - Indonesian & English (i18n ready)
- **Accessibility** - WCAG 2.1 compliant
- **Progressive Web App** - Installable, offline-capable

---

## 🛠 Teknologi

### **Backend**
- **PHP 8.0+** - Server-side scripting
- **PDO** - Database abstraction layer
- **Native PHP** - No framework dependency (lightweight)

### **Frontend**
- **HTML5** - Semantic markup
- **CSS3** - Modern styling with CSS Grid, Flexbox, Custom Properties
- **Vanilla JavaScript (ES6+)** - No framework bloat
- **Chart.js** - Data visualization
- **QRCode.js** - QR code generation

### **Database**
- **MySQL 5.7+ / MariaDB 10.3+**

### **Security**
- **Password Hashing** - bcrypt/Argon2
- **CSRF Protection** - Token-based
- **XSS Prevention** - Output escaping
- **SQL Injection Prevention** - Prepared statements
- **2FA/TOTP** - Time-based One-Time Password
- **Session Security** - HTTPOnly, Secure flags

### **Tools & Libraries**
- **PHPMailer** - Email functionality
- **PHPWord/PHPSpreadsheet** - Document generation
- **TCPDF/mPDF** - PDF generation
- **Google Charts/Chart.js** - Analytics visualization

---

## 📋 Persyaratan Sistem

### **Minimum Requirements**
- **Web Server**: Apache 2.4+ / Nginx 1.18+
- **PHP Version**: 8.0 or higher
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Extensions**: 
  - `pdo_mysql`
  - `mbstring`
  - `gd` or `imagick`
  - `openssl`
  - `json`
  - `session`
  - `filter`

### **Recommended**
- **PHP Version**: 8.1+
- **Web Server**: Nginx 1.20+ with PHP-FPM
- **Database**: MySQL 8.0+ / MariaDB 10.6+
- **SSL Certificate**: Let's Encrypt
- **RAM**: 2GB+
- **Storage**: 5GB+

---

## 📦 Instalasi

### **1. Clone Repository**

```bash
git clone https://github.com/yolislibmanmof/lpm-kampus.git
cd lpm-kampus

-- Buat database
CREATE DATABASE lpm_sim_mutu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import struktur dan data
mysql -u root -p lpm_sim_mutu < database/lpm_sim_mutu.sql

cp config/config.example.php config/config.php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'lpm_sim_mutu');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('BASE_URL', 'http://localhost/lpm-kampus');
define('SITE_NAME', 'LPM SIM-Mutu');
define('TIMEZONE', 'Asia/Jakarta');

// Security
define('ENCRYPTION_KEY', 'your-32-character-random-key-here-change-this!');
define('SESSION_LIFETIME', 3600); // 1 hour

# Linux/Mac
chmod 755 -R .
chmod 777 uploads/
chmod 777 cache/
chmod 600 config/config.php

# Windows (via icacls)
icacls uploads /grant Everyone:F /T
icacls cache /grant Everyone:F /T

# Jika menggunakan Composer
composer install

http://localhost/lpm-kampus

// SMTP Configuration
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'your-email@gmail.com');
define('MAIL_PASS', 'your-app-password');
define('MAIL_FROM', 'no-reply@kampus.ac.id');
define('MAIL_FROM_NAME', 'LPM SIM-Mutu');
define('MAIL_ENCRYPTION', 'tls');

// Upload Settings
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// Session Security
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set 1 jika HTTPS
ini_set('session.use_strict_mode', 1);

// Password Policy
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_NUMBER', true);
define('PASSWORD_REQUIRE_SPECIAL', false);

lpm-kampus/
├── config/
│   ├── config.php              # Konfigurasi utama
│   ├── database.php            # Koneksi database
│   └── routes.php              # Routing (jika ada)
│
├── core/
│   ├── Auth.php                # Authentication class
│   ├── Database.php            # Database wrapper
│   ├── Security.php            # Security utilities
│   ├── Validator.php           # Form validation
│   ├── TotpAuth.php            # 2FA/TOTP handler
│   ├── Logger.php              # Logging system
│   └── Notifier.php            # Notification system
│
├── includes/
│   ├── header.php              # Header template
│   ├── footer.php              # Footer template
│   ├── sidebar.php             # Sidebar navigation
│   └── functions.php           # Helper functions
│
├── sim/                        # Sistem Informasi Manajemen
│   ├── admin/                  # Modul admin
│   │   ├── dashboard.php
│   │   ├── users.php
│   │   ├── roles.php
│   │   └── ...
│   │
│   ├── profil/                 # Profil pengguna
│   │   ├── index.php
│   │   ├── 2fa-setup.php       # Setup 2FA
│   │   └── change-password.php
│   │
│   ├── akreditasi/             # Modul akreditasi
│   │   ├── index.php
│   │   ├── prodi.php
│   │   ├── internasional.php
│   │   └── legalitas.php
│   │
│   ├── edom/                   # Modul EDOM
│   │   ├── index.php
│   │   ├── periode.php
│   │   ├── kelas.php
│   │   └── hasil.php
│   │
│   ├── tracer/                 # Tracer study
│   │   ├── index.php
│   │   ├── alumni.php
│   │   └── survei-pengguna.php
│   │
│   ├── ami/                    # Audit Mutu Internal
│   │   ├── index.php
│   │   ├── jadwal.php
│   │   └── temuan.php
│   │
│   ├── dokumen/                # Dokumen mutu
│   │   ├── index.php
│   │   ── upload.php
│   │
│   ├── berita/                 # Berita & agenda
│   │   ├── index.php
│   │   ── create.php
│   │
│   └── pengaduan/              # Pengaduan
│       ├── index.php
│       └── detail.php
│
── publik/                     # Halaman publik
│   ├── index.php               # Homepage
│   ├── akreditasi.php          # Status akreditasi publik
│   ├── edom.php                # Form EDOM (token-based)
│   ├── tracer.php              # Tracer study alumni
│   ├── survei-pengguna.php     # Survei pengguna
│   ├── profil.php              # Profil LPM
│   ├── berita.php              # Berita publik
│   └── pengaduan.php           # Form pengaduan
│
── assets/
│   ├── css/
│   │   ├── style.css           # Main stylesheet
│   │   ├── dark-mode.css       # Dark mode styles
│   │   └── responsive.css      # Mobile styles
│   │
│   ├── js/
│   │   ├── app.js              # Main JavaScript
│   │   ├── charts.js           # Chart configurations
│   │   └── validation.js       # Form validation
│   │
│   ├── images/
│   │   ├── logo.png
│   │   └── ...
│   │
│   └── uploads/                # User uploads
│       ├── profiles/
│       ├── documents/
│       └── certificates/
│
├── database/
│   ├── lpm_sim_mutu.sql        # Full database dump
│   ├── seeders/                # Sample data
│   └── migrations/             # Database migrations
│
├── vendor/                     # Composer dependencies
│
├── .htaccess                   # Apache rewrite rules
├── index.php                   # Main entry point
├── README.md                   # This file
└── LICENSE                     # MIT License