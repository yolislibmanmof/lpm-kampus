<?php
require_once __DIR__ . '/../config/config.php';
Auth::requireRole([1, 2, 3, 4]);
$simTitle = 'Buku Panduan Pengguna';
$activeMenu = 'panduan';
require_once __DIR__ . '/../includes/header-sim.php';
$brand = Site::setting('brand_utama', 'LPM') . ' ' . Site::setting('brand_aksen', 'Kampus');
?>

<style>
    .pd-hero { background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: #fff; border-radius: var(--radius-lg); padding: 40px; margin-bottom: 28px; position: relative; overflow: hidden; }
    .pd-hero::before { content: ''; position: absolute; width: 300px; height: 300px; border-radius: 50%; background: radial-gradient(circle, rgba(201,162,39,.25), transparent 70%); right: -80px; top: -100px; }
    .pd-sec { margin-bottom: 28px; }
    .pd-sec h3 { border-left: 4px solid var(--accent); padding-left: 14px; margin-bottom: 16px; }
    .pd-step { display: flex; gap: 14px; margin-bottom: 12px; }
    .pd-step .n { min-width: 30px; height: 30px; border-radius: 9px; background: rgba(15,61,92,.08); color: var(--primary); font-weight: 800; display: grid; place-items: center; font-size: 13px; flex-shrink: 0; }
    body.dark .pd-step .n { background: rgba(201,162,39,.15); color: var(--accent-light); }
    .pd-step p { font-size: 14px; color: var(--text-muted); }
    .pd-step b { color: var(--text-dark); }
    @media print {
        .no-print { display: none !important; }
        .card { box-shadow: none !important; border: 1px solid #ccc !important; break-inside: avoid; }
        .pd-sec { break-inside: avoid; }
    }
</style>

<div class="pd-hero no-print">
    <h2 style="font-size:26px;position:relative;">📘 Buku Panduan Pengguna SIM-Mutu</h2>
    <p style="opacity:.85;position:relative;max-width:640px;">Manual resmi penggunaan sistem per peran. Klik "Cetak / Simpan PDF" lalu pilih <i>Save as PDF</i> untuk membagikan dokumen ini.</p>
</div>

<div class="no-print" style="margin-bottom:24px;">
    <button class="btn btn-gold" onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
</div>

<div class="card" style="margin-bottom:28px;">
    <h3 style="margin-bottom:6px;">Buku Panduan Pengguna</h3>
    <p class="text-muted" style="font-size:13px;">Sistem Informasi Penjaminan Mutu (SIM-Mutu) • <?= Security::e($brand) ?> • v4.0 • <?= date('Y') ?></p>
</div>

<!-- ============ 1. UMUM ============ -->
<div class="card pd-sec">
    <h3>1. Memulai & Login</h3>
    <div class="pd-step"><span class="n">1</span><p>Buka alamat sistem (mis. <b>lpm.kampus.ac.id</b>) melalui browser Chrome/Edge terbaru.</p></div>
    <div class="pd-step"><span class="n">2</span><p>Klik tombol <b>🔐 SIM-Mutu</b>, masukkan <b>email institusi</b> dan <b>password</b> yang diberikan Admin.</p></div>
    <div class="pd-step"><span class="n">3</span><p>Lupa password? Hubungi Admin LPM — tombol <b>🔑 Reset</b> akan mengembalikan password ke <b>Mutu2026!</b> (segera ganti setelah masuk).</p></div>
    <div class="pd-step"><span class="n">4</span><p>Gunakan <b>🌙</b> untuk mode gelap dan <b>Ctrl+K</b> untuk melompat ke menu apa pun secara instan.</p></div>
    <div class="pd-step"><span class="n">5</span><p>🔔 Lonceng di kanan atas menampilkan notifikasi real-time; angka merah = belum dibaca.</p></div>
</div>

<!-- ============ 2. ADMIN ============ -->
<div class="card pd-sec">
    <h3>2. Panduan Admin LPM</h3>
    <div class="pd-step"><span class="n">1</span><p><b>Manajemen Pengguna:</b> menu 👥 → isi NIDN/NIP, nama, email, role, prodi (untuk Kaprodi), password min. 8 karakter → 💾 Simpan.</p></div>
    <div class="pd-step"><span class="n">2</span><p><b>Prodi & Fakultas:</b> menu 🏫 → tambah fakultas dahulu, lalu prodi (kode + nama). Data ini mengisi semua dropdown otomatis.</p></div>
    <div class="pd-step"><span class="n">3</span><p><b>Dokumen PPEPP:</b> menu 📁 → unggah file (PDF/Office maks. 10MB), atur kode, versi, dan tipe akses (publik/internal).</p></div>
    <div class="pd-step"><span class="n">4</span><p><b>Penjadwalan AMI:</b> menu 📅 → buat jadwal per tahun → plot auditor ke prodi. Auditor langsung menerima notifikasi.</p></div>
    <div class="pd-step"><span class="n">5</span><p><b>Instrumen AMI:</b> menu 🧾 → buat tahun → <b>⚡ Generate Template</b> (3 butir/standar) atau tulis butir sendiri → <b>📑 Salin Instrumen</b> untuk tahun berikutnya.</p></div>
    <div class="pd-step"><span class="n">6</span><p><b>Kuesioner Monev:</b> menu 📱 → buat periode → bagikan tautan/QR → pantau grafik hasil live.</p></div>
    <div class="pd-step"><span class="n">7</span><p><b>Konten Publik:</b> menu 🏛️ → ganti logo, nama brand, visi-misi, foto struktur, dan SK legalitas — langsung tampil di website.</p></div>
    <div class="pd-step"><span class="n">8</span><p><b>Laporan:</b> menu 🖨️ Pusat Laporan → Export PDF laporan AMI per penugasan & RTM per tahun (ber-kop & blok tanda tangan).</p></div>
    <div class="pd-step"><span class="n">9</span><p><b>Audit Trail:</b> menu 📜 → pantau seluruh aktivitas pengguna (login, unggah, hapus, verifikasi).</p></div>
</div>

<!-- ============ 3. PIMPINAN ============ -->
<div class="card pd-sec">
    <h3>3. Panduan Pimpinan</h3>
    <div class="pd-step"><span class="n">1</span><p><b>Dashboard:</b> ringkasan total prodi, akreditasi Unggul, rata-rata capaian, dan temuan mayor terbuka.</p></div>
    <div class="pd-step"><span class="n">2</span><p><b>📈 Monitoring Capaian:</b> pilih tahun & prodi → <b>Radar Chart</b> menunjukkan area capaian vs target; grafik kuesioner menampilkan suara mahasiswa/dosen.</p></div>
    <div class="pd-step"><span class="n">3</span><p><b>🗺️ Heatmap Temuan:</b> menu Temuan Audit → sel merah = prodi prioritas pembinaan.</p></div>
    <div class="pd-step"><span class="n">4</span><p><b> Status Akreditasi:</b> perhatikan penanda <b>SEGERA BERAKHIR</b> — jadwalkan reakreditasi lebih awal.</p></div>
    <div class="pd-step"><span class="n">5</span><p><b>📑 Laporan RTM:</b> cetak PDF bahan RTM (standar belum tercapai + temuan mayor + kolom keputusan rapat).</p></div>
</div>

<!-- ============ 4. KAPRODI ============ -->
<div class="card pd-sec">
    <h3>4. Panduan Kaprodi</h3>
    <div class="pd-step"><span class="n">1</span><p><b>EDP:</b> menu 📝 → isi capaian, target, bukti, dan catatan per standar → <b>💾 Simpan Draft</b> → <b>🔒 Kunci</b> bila final.</p></div>
    <div class="pd-step"><span class="n">2</span><p><b>Cloud Borang:</b> menu ☁️ → unggah LED/LKPS per kriteria → atur status Draf → Final → Terkirim.</p></div>
    <div class="pd-step"><span class="n">3</span><p><b>Tindakan Koreksi:</b> menu 📜 → baca temuan auditor → isi kolom koreksi → 📤 Kirim → pantau status verifikasi.</p></div>
    <div class="pd-step"><span class="n">4</span><p><b>Instrumen:</b> menu 🧾 → pelajari butir penilaian tahun berjalan sebagai ceklis kesiapan borang.</p></div>
</div>

<!-- ============ 5. AUDITOR ============ -->
<div class="card pd-sec">
    <h3>5. Panduan Auditor</h3>
    <div class="pd-step"><span class="n">1</span><p><b>E-Audit:</b> menu 🔍 → pilih penugasan → isi kategori (Mayor/Minor/Observasi/Kondisi Baik) + catatan per standar → 💾 Simpan.</p></div>
    <div class="pd-step"><span class="n">2</span><p>Bila audit rampung: <b>✅ Tandai Selesai</b> — lembar terkunci & Kaprodi ternotifikasi otomatis.</p></div>
    <div class="pd-step"><span class="n">3</span><p><b>Verifikasi:</b> menu ✅ → baca koreksi prodi → <b>Terima</b> atau <b>Tolak</b> dengan catatan.</p></div>
    <div class="pd-step"><span class="n">4</span><p><b>Laporan:</b> cetak PDF laporan audit resmi via 🖨️ Pusat Laporan (Admin) atau tombol cetak di riwayat.</p></div>
</div>

<!-- ============ 6. TIPS ============ -->
<div class="card pd-sec">
    <h3>6. Tips & Etika Penggunaan</h3>
    <div class="pd-step"><span class="n">•</span><p>Jangan bagikan password; sistem merekam semua aktivitas (audit trail).</p></div>
    <div class="pd-step"><span class="n">•</span><p>Unggah dokumen dengan nama file yang jelas (mis. <b>LKD-IF-2026.pdf</b>).</p></div>
    <div class="pd-step"><span class="n">•</span><p>Gunakan tombol 🖨️ pada laporan untuk arsip fisik rapat.</p></div>
    <div class="pd-step"><span class="n">•</span><p>Data pengaduan publik bersifat rahasia — hanya petugas berwenang yang mengakses.</p></div>
</div>

<p class="text-muted" style="font-size:12px;text-align:center;">© <?= date('Y') ?> <?= Security::e($brand) ?> — Dokumen ini dibuat otomatis oleh SIM-Mutu v3.0</p>

<?php require_once __DIR__ . '/../includes/footer-sim.php'; ?>