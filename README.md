# 🏛️ SIM-Mutu LPM Kampus

Sistem Informasi Penjaminan Mutu Internal untuk Lembaga Penjaminan Mutu perguruan tinggi. 
Dibangun dengan **PHP 7.4+**, **MySQL**, dan **vanilla JavaScript**.

## ✨ Fitur Utama

### 🌐 Website Publik (11 Halaman)
- **Beranda** — Hero slider foto, statistik, showcase akreditasi, berita
- **Profil** — Visi misi, struktur organisasi tree, tupoksi, legalitas
- **Akreditasi** — Statistik distribusi, kartu dengan ring masa berlaku
- **Dokumen** — 3D cards dengan preview tipe file
- **Berita** — Live ticker, hero news, pagination
- **Pengaduan** — Form glass dengan stepper
- **EDOM** — Evaluasi Dosen oleh Mahasiswa (star rating)
- **Kuesioner Monev** — Aspek badges dengan counter
- **Responden Wawancara** — Segmented control
- **Survei Pengguna** — Aspect cards dengan legend
- **Tracer Study** — Wizard multi-step

### 🔐 Portal Admin (SIM-Mutu)
- Dashboard per role (Admin, Pimpinan, Kaprodi, Auditor, GPM)
- Dark mode, command palette (Ctrl+K), notifikasi real-time
- Manajemen slider beranda, profil, berita, dokumen
- Audit trail, backup database

## 📁 Struktur Folder

```
lpm-kampus/
├── index.php                    # Beranda publik
├── login.php                    # Halaman login
├── logout.php                   # Handler logout
├── download.php                 # Handler download dokumen
├── config/
│   ├── config.example.php       # Template konfigurasi
│   └── config.php               # (tidak di-commit)
├── includes/
│   ├── header-publik.php        # Header website publik
│   ├── footer-publik.php        # Footer website publik
│   ├── header-sim.php           # Header portal admin
│   └── sidebar-sim.php          # Sidebar admin per role
├── publik/                      # 10 halaman publik tambahan
│   ├── profil.php
│   ├── akreditasi.php
│   ├── dokumen.php
│   ├── berita.php
│   ├── pengaduan.php
│   ├── edom.php
│   ├── kuesioner.php
│   ├── registrasi-wawancara.php
│   ├── survei-pengguna.php
│   └── tracer.php
├── sim/                         # Portal admin
│   ├── index.php               # Dashboard admin
│   ├── admin/                  # Modul admin
│   │   ├── users.php
│   │   ├── slider.php          # Manajemen slider beranda
│   │   ├── berita.php
│   │   ├── dokumen.php
│   │   └── ...
│   ├── pimpinan/
│   ├── prodi/
│   └── auditor/
├── assets/
│   ├── css/style.css           # CSS global v9.0 FINAL
│   └── js/main.js              # JS global v9.0 FINAL
└── uploads/                    # (tidak di-commit, kecuali .gitkeep)
    ├── slider/
    ├── logo/
    ├── foto/
    └── dokumen/
```

## 🚀 Instalasi Lokal (Laragon/XAMPP)

### 1. Clone & Setup
```bash
git clone https://github.com/username/lpm-kampus.git
cd lpm-kampus
cp config/config.example.php config/config.php
```

### 2. Buat Database
```sql
CREATE DATABASE sim_mutu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Import SQL
```bash
mysql -u root -p sim_mutu < database/sim_mutu_full.sql
```

### 4. Setup Admin Awal
Login default:
- **Email**: `admin@kampus.ac.id`
- **Password**: `Mutu2026!`

### 5. Konfigurasi Web Server
Arahkan domain `lpm-kampus` ke folder proyek.
- **Laragon**: auto-detect atau edit `etc/apache2/sites-enabled/lpm-kampus.conf`
- **XAMPP**: edit `httpd-vhosts.conf`
- Tambahkan ke `hosts`: `127.0.0.1 lpm-kampus`

## 🎨 Design System

### Palet Warna (CSS Variables)
```css
--primary: #0F3D5C;       /* Navy */
--primary-light: #1A5A82;
--primary-dark: #092A40;
--accent: #C9A227;        /* Gold */
--accent-light: #E8C55A;
```

### Role Pengguna
| ID | Role | Akses |
|----|------|-------|
| 1 | Admin LPM | Full akses |
| 2 | Pimpinan | Read + laporan |
| 3 | Kaprodi | Modul prodi |
| 4 | Auditor | Modul audit |
| 5 | GPM Fakultas | Monitoring |

## 🛠️ Teknologi
- **Backend**: PHP 7.4+ (PDO, prepared statements)
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Frontend**: Vanilla JS + CSS modern (no framework)
- **Font**: Plus Jakarta Sans (Google Fonts)
- **Icons**: Emoji native (no icon library)

## 📜 Lisensi
Proprietary — dikembangkan untuk LPM Kampus.
WEBSITE INI BELUM FINAL, JANGAN KEPO UNTUK MENGGUNAKANNYA
