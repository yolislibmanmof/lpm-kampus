<style>
/* ============================================================
   FOOTER v8.2 — RAPIH & PRESISI (berlaku di semua halaman publik)
   Grid sejajar container • spacing konsisten • tanpa clipping
============================================================ */
.pf-wave { line-height: 0; background: transparent; }
.pf-wave svg { display: block; width: 100%; height: 70px; }

.pf-footer { background: #061D2E; color: #fff; position: relative; overflow: hidden; }
.pf-footer::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; z-index: 3;
    background: linear-gradient(90deg, #C9A227, #1A5A82, #E8C55A, #C9A227);
    background-size: 300% 100%; animation: pfLine 6s linear infinite;
}
@keyframes pfLine { to { background-position: 300% 0; } }

.pf-orb {
    position: absolute; width: 500px; height: 500px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,162,39,.1), transparent 70%);
    top: -150px; right: -150px; animation: pfFloat 10s ease-in-out infinite;
    pointer-events: none;
}
@keyframes pfFloat { 0%,100% { transform: scale(1); } 50% { transform: scale(1.15); } }
.pf-watermark {
    position: absolute; bottom: -30px; left: 50%; transform: translateX(-50%);
    font-size: clamp(120px, 20vw, 280px); font-weight: 900; letter-spacing: -.04em;
    color: rgba(255,255,255,.025); line-height: 1; pointer-events: none;
    white-space: nowrap; user-select: none;
}

.pf-body { position: relative; z-index: 2; }

/* --- Newsletter: UTUH, tidak terpotong --- */
.pf-news {
    position: relative; margin-top: 56px; overflow: hidden;
    background: linear-gradient(135deg, #0F3D5C, #1A5A82);
    border-radius: 20px; padding: 32px 36px;
    display: flex; justify-content: space-between; align-items: center;
    gap: 28px; flex-wrap: wrap;
    box-shadow: 0 20px 50px rgba(0,0,0,.35), inset 0 0 0 1px rgba(255,255,255,.08);
}
.pf-news::before {
    content: ''; position: absolute; top: -80px; right: -80px; width: 260px; height: 260px;
    border-radius: 50%; background: radial-gradient(circle, rgba(201,162,39,.3), transparent 70%);
    pointer-events: none;
}
.pf-news-txt { position: relative; flex: 1; min-width: 260px; }
.pf-news-txt h4 { font-size: 19px; margin: 0 0 6px; color: #fff; letter-spacing: -.01em; }
.pf-news-txt p { color: rgba(255,255,255,.75); font-size: 13.5px; margin: 0; }
.pf-news-form { display: flex; gap: 10px; position: relative; flex: 1; min-width: 280px; max-width: 460px; }
.pf-news-form input {
    flex: 1; padding: 13px 20px; border-radius: 50px; border: 1.5px solid rgba(255,255,255,.25);
    background: rgba(255,255,255,.1); color: #fff; font-family: inherit; font-size: 14px;
    outline: none; transition: .3s; min-width: 0;
}
.pf-news-form input::placeholder { color: rgba(255,255,255,.5); }
.pf-news-form input:focus { border-color: #E8C55A; box-shadow: 0 0 0 4px rgba(201,162,39,.2); }
.pf-news-form button {
    padding: 13px 26px; border-radius: 50px; border: none; cursor: pointer;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    color: #092A40; font-weight: 800; font-size: 14px; font-family: inherit;
    transition: .3s; box-shadow: 0 8px 24px rgba(201,162,39,.4); white-space: nowrap;
}
.pf-news-form button:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(201,162,39,.6); }

/* --- Grid 4 kolom sejajar --- */
.pf-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1.3fr; gap: 48px; margin-top: 60px; }
.pf-col h4 {
    font-size: 13px; letter-spacing: 1.5px; text-transform: uppercase;
    color: #E8C55A; margin: 0 0 20px; padding-bottom: 10px; position: relative;
}
.pf-col h4::after {
    content: ''; position: absolute; left: 0; bottom: 0; width: 32px; height: 3px;
    border-radius: 3px; background: linear-gradient(90deg, #C9A227, #E8C55A);
    transition: width .4s cubic-bezier(.22, 1, .36, 1);
}
.pf-col:hover h4::after { width: 56px; }
.pf-col p, .pf-col a { color: rgba(255,255,255,.68); font-size: 14px; text-decoration: none; }
.pf-col ul { list-style: none; margin: 0; padding: 0; }
.pf-col ul li { margin-bottom: 10px; }
.pf-col ul a { transition: .25s cubic-bezier(.22, 1, .36, 1); display: inline-flex; align-items: center; gap: 8px; }
.pf-col ul a::before { content: '›'; color: #C9A227; opacity: 0; transform: translateX(-6px); transition: .25s; width: 0; }
.pf-col ul a:hover { color: #E8C55A; transform: translateX(6px); }
.pf-col ul a:hover::before { opacity: 1; width: 10px; transform: none; }
.pf-desc { margin: 16px 0 22px; line-height: 1.8; }

.pf-social { display: flex; gap: 10px; }
.pf-social a {
    width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center;
    background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12);
    font-size: 16px; transition: .3s cubic-bezier(.34, 1.56, .64, 1);
    position: relative; overflow: hidden;
}
.pf-social a::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(135deg, #C9A227, #E8C55A);
    transform: translateY(100%); transition: .3s cubic-bezier(.22, 1, .36, 1);
}
.pf-social a span { position: relative; z-index: 1; }
.pf-social a:hover::before { transform: none; }
.pf-social a:hover { color: #092A40; transform: translateY(-5px) rotate(-6deg); border-color: transparent; }

.pf-contact li { display: flex; gap: 12px; margin-bottom: 16px; align-items: flex-start; }
.pf-contact .ic {
    min-width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
    background: rgba(201,162,39,.12); border: 1px solid rgba(201,162,39,.25);
    display: grid; place-items: center; font-size: 14px; color: #E8C55A;
}

/* --- Bottom bar sejajar container --- */
.pf-bottom {
    margin-top: 60px; border-top: 1px solid rgba(255,255,255,.08);
    padding: 22px 0 26px; display: flex; justify-content: space-between;
    align-items: center; gap: 12px; flex-wrap: wrap;
    color: rgba(255,255,255,.45); font-size: 13px;
}
.pf-bottom b { color: #E8C55A; font-weight: 700; }
.pf-bottom-links { display: flex; gap: 20px; }
.pf-bottom-links a { color: rgba(255,255,255,.55); text-decoration: none; transition: .25s; }
.pf-bottom-links a:hover { color: #E8C55A; }

/* --- Responsive presisi --- */
@media (max-width: 992px) {
    .pf-grid { grid-template-columns: 1fr 1fr; gap: 36px; }
    .pf-news { padding: 28px; }
}
@media (max-width: 576px) {
    .pf-grid { grid-template-columns: 1fr; gap: 32px; }
    .pf-news-form { max-width: 100%; }
    .pf-bottom { justify-content: center; text-align: center; }
}
</style>

<div class="pf-wave">
    <svg viewBox="0 0 1440 70" preserveAspectRatio="none">
        <path fill="#061D2E" d="M0,40 C240,80 480,0 720,30 C960,60 1200,10 1440,40 L1440,70 L0,70 Z"></path>
    </svg>
</div>

<footer class="pf-footer">
    <div class="pf-orb"></div>
    <div class="pf-watermark">MUTU</div>

    <div class="container pf-body">

        <!-- Newsletter: utuh & sejajar container -->
        <div class="pf-news">
            <div class="pf-news-txt">
                <h4>📬 Tetap Terhubung dengan Budaya Mutu</h4>
                <p>Dapatkan info akreditasi, agenda AMI, dan berita mutu langsung ke email Anda.</p>
            </div>
            <form class="pf-news-form" id="pfNewsForm">
                <input type="email" placeholder="email@kampus.ac.id" required>
                <button type="submit">Berlangganan</button>
            </form>
        </div>

        <!-- Grid 4 kolom -->
        <div class="pf-grid">
            <div class="pf-col">
                <?= Site::brand('footer') ?>
                <p class="pf-desc">
                    Lembaga Penjaminan Mutu Internal sebagai garda terdepan budaya mutu kampus —
                    menetapkan standar, mengawasi pelaksanaan, mengaudit mutu, dan mengawal
                    akreditasi menuju keunggulan berkelanjutan.
                </p>
                <div class="pf-social">
                    <a href="#" aria-label="Instagram"><span>📷</span></a>
                    <a href="#" aria-label="YouTube"><span>▶️</span></a>
                    <a href="#" aria-label="Facebook"><span>📘</span></a>
                    <a href="mailto:lpm@kampus.ac.id" aria-label="Email"><span>✉️</span></a>
                </div>
            </div>

            <div class="pf-col">
                <h4>Navigasi</h4>
                <ul>
                    <li><a href="/index.php">Beranda</a></li>
                    <li><a href="/publik/profil.php">Profil Lembaga</a></li>
                    <li><a href="/publik/akreditasi.php">Status Akreditasi</a></li>
                    <li><a href="/publik/dokumen.php">Dokumen Publik</a></li>
                    <li><a href="/publik/berita.php">Berita & Agenda</a></li>
                </ul>
            </div>

            <div class="pf-col">
                <h4>Layanan</h4>
                <ul>
                    <li><a href="/login.php">Portal SIM-Mutu</a></li>
                    <li><a href="/publik/pengaduan.php">Pengaduan & Kritik</a></li>
                    <li><a href="/publik/dokumen.php">Unduh Formulir</a></li>
                    <li><a href="/publik/berita.php?kategori=Agenda">Jadwal AMI</a></li>
                    <li><a href="/publik/tracer.php">🎓 Tracer Study Alumni</a></li>
                    <li><a href="/publik/registrasi-wawancara.php">🎤 Responden Wawancara</a></li>
                    <li><a href="/publik/survei-pengguna.php">💼 Survei Pengguna Lulusan</a></li>
                </ul>
            </div>

            <div class="pf-col">
                <h4>Hubungi Kami</h4>
                <ul class="pf-contact">
                    <li><span class="ic">📍</span><span>Gedung Rektorat Lt. 3, Jl. Pendidikan No. 1, Maumere, NTT</span></li>
                    <li><span class="ic">📧</span><span>lpm@kampus.ac.id</span></li>
                    <li><span class="ic">📞</span><span>(0382) 123-4567</span></li>
                    <li><span class="ic">🕐</span><span>Senin – Jumat, 08.00 – 16.00 WITA</span></li>
                </ul>
            </div>
        </div>

        <!-- Bottom bar -->
        <div class="pf-bottom">
            <span>© <?= date('Y') ?> <b>LPM Kampus</b>. Seluruh hak cipta dilindungi.</span>
            <div class="pf-bottom-links">
                <a href="/publik/profil.php">Tentang</a>
                <a href="/publik/dokumen.php">Privasi</a>
                <a href="/sim/index.php">SIM-Mutu</a>
            </div>
            <span>Sistem Penjaminan Mutu Internal • <b>PPEPP</b></span>
        </div>
    </div>
</footer>

<script>
(function () {
    var f = document.getElementById('pfNewsForm');
    if (!f) return;
    f.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = f.querySelector('button');
        btn.textContent = '✅ Terdaftar!';
        btn.style.background = 'linear-gradient(135deg,#10B981,#34D399)';
        f.querySelector('input').value = '';
        setTimeout(function () {
            btn.textContent = 'Berlangganan';
            btn.style.background = '';
        }, 3500);
    });
})();
</script>

<script src="/assets/js/main.js"></script>
</body>
</html>