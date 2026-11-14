/* ============================================================
   LPM KAMPUS — ULTIMATE INTERACTION ENGINE v9.0 FINAL
   Sinkron dengan 11 halaman publik + SIM Portal
   Particles • Tilt 3D • Spotlight • Magnetic • Toast • Ring
============================================================ */
(function () {
    'use strict';

    var $ = function (s, ctx) { return (ctx || document).querySelector(s); };
    var $$ = function (s, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(s)); };
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var finePointer = window.matchMedia('(pointer: fine)').matches;

    /* ============ 1. TRANSISI HALUS ANTAR HALAMAN ============ */
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a');
        if (!a) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey) return;
        if (a.hasAttribute('target') || a.hasAttribute('download') || a.hasAttribute('data-no-transition')) return;
        var href = a.getAttribute('href') || '';
        if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return;
        if (href.indexOf('logout.php') > -1 || href.indexOf('download.php') > -1) return;
        if (/\.(pdf|png|jpg|jpeg|gif|svg|doc|docx|xls|xlsx|zip|rar)(\?|$)/i.test(href)) return;
        try {
            var url = new URL(a.href, location.href);
            if (url.origin !== location.origin) return;
        } catch (err) { return; }
        e.preventDefault();
        document.body.classList.add('page-leave');
        setTimeout(function () { window.location.href = a.href; }, 240);
    });

    /* ============ 2. SCROLL PROGRESS BAR ============ */
    var progressBar = document.createElement('div');
    progressBar.id = 'scrollProgress';
    document.body.appendChild(progressBar);

    /* ============ 3. BACK-TO-TOP DENGAN RING PROGRES ============ */
    var toTop = document.createElement('button');
    toTop.id = 'toTop';
    toTop.setAttribute('aria-label', 'Kembali ke atas');
    toTop.innerHTML =
        '<svg viewBox="0 0 50 50"><circle class="track" cx="25" cy="25" r="21"/>' +
        '<circle class="prog" cx="25" cy="25" r="21"/></svg><span>↑</span>';
    toTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
    document.body.appendChild(toTop);
    var ringProg = toTop.querySelector('.prog');

    /* ============ 4. SMART NAVBAR + SCROLL SYSTEM ============ */
    var navbar = $('.navbar');
    var lastY = 0;
    var ticking = false;

    function onScroll() {
        var y = window.scrollY || 0;
        var max = document.documentElement.scrollHeight - window.innerHeight;
        var p = max > 0 ? y / max : 0;

        progressBar.style.width = (p * 100) + '%';
        if (ringProg) ringProg.style.strokeDashoffset = 132 * (1 - p);
        toTop.classList.toggle('show', y > 400);

        if (navbar) {
            navbar.classList.toggle('scrolled', y > 40);
            if (y > 160 && y > lastY + 6) navbar.classList.add('hide');
            else if (y < lastY - 6 || y < 160) navbar.classList.remove('hide');
        }
        lastY = y;
    }

    window.addEventListener('scroll', function () {
        if (!ticking) {
            requestAnimationFrame(function () {
                onScroll();
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });

    /* ============ 5. CURSOR GLOW (desktop) ============ */
    if (finePointer && !reduced) {
        document.body.classList.add('glow-on');
        var glow = document.createElement('div');
        glow.id = 'cursorGlow';
        document.body.appendChild(glow);
        var gx = innerWidth / 2, gy = innerHeight / 2, tx = gx, ty = gy;
        document.addEventListener('mousemove', function (e) { tx = e.clientX; ty = e.clientY; });
        (function loop() {
            gx += (tx - gx) * .12; gy += (ty - gy) * .12;
            glow.style.transform = 'translate(' + (gx - 180) + 'px,' + (gy - 180) + 'px)';
            requestAnimationFrame(loop);
        })();
    }

    /* ============ 6. SCROLL REVEAL + STAGGER ============ */
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (en.isIntersecting) {
                en.target.classList.add('visible');
                io.unobserve(en.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -50px 0px' });

    $$('.card, .section-header, .stat-card, .table-wrapper, .hero-stats, .reveal').forEach(function (el, i) {
        if (!el.classList.contains('reveal')) el.classList.add('reveal');
        el.style.animationDelay = (i % 6) * 80 + 'ms';
        io.observe(el);
    });

    /* Baris tabel muncul berurutan */
    $$('tbody tr').forEach(function (tr, i) {
        tr.style.animationDelay = Math.min(i * 45, 420) + 'ms';
    });

    /* ============ 7. ANGKA STATISTIK BERJALAN ============ */
    var counterIO = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (!en.isIntersecting) return;
            var el = en.target;
            var text = el.textContent.trim();
            var m = text.match(/^([\d.,]+)(.*)$/);
            if (!m) return;
            var raw = m[1], norm;
            if (/^\d{1,3}(\.\d{3})+(,\d+)?$/.test(raw)) norm = raw.replace(/\./g, '').replace(',', '.');
            else if (/^\d+,\d+$/.test(raw)) norm = raw.replace(',', '.');
            else norm = raw.replace(/,/g, '');
            var target = parseFloat(norm);
            if (!isFinite(target)) return;
            var suffix = m[2] || '';
            var dec = (norm.split('.')[1] || '').length;
            var start = performance.now();
            (function tick(now) {
                var p = Math.min((now - start) / 1500, 1);
                var eased = 1 - Math.pow(1 - p, 3);
                var val = target * eased;
                el.textContent = val.toLocaleString('id-ID', { minimumFractionDigits: dec, maximumFractionDigits: dec }) + suffix;
                if (p < 1) requestAnimationFrame(tick);
            })(start);
            counterIO.unobserve(el);
        });
    }, { threshold: 0.4 });

    $$('.stat-card h3, .stat-item h3, [data-count]').forEach(function (el) {
        if (el.hasAttribute('data-count')) {
            el.textContent = '0';
        }
        counterIO.observe(el);
    });

    /* ============ 8. RIPPLE + MAGNETIC BUTTON ============ */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn, button[type="submit"]');
        if (!btn) return;
        var r = btn.getBoundingClientRect();
        var d = Math.max(r.width, r.height);
        var s = document.createElement('span');
        s.className = 'ripple';
        s.style.width = s.style.height = d + 'px';
        s.style.left = (e.clientX - r.left - d / 2) + 'px';
        s.style.top = (e.clientY - r.top - d / 2) + 'px';
        btn.appendChild(s);
        setTimeout(function () { s.remove(); }, 700);
    });

    if (finePointer && !reduced) {
        $$('.btn').forEach(function (b) {
            b.addEventListener('mousemove', function (e) {
                var r = b.getBoundingClientRect();
                b.style.translate = ((e.clientX - r.left - r.width / 2) * .1) + 'px ' +
                                    ((e.clientY - r.top - r.height / 2) * .18) + 'px';
            });
            b.addEventListener('mouseleave', function () { b.style.translate = '0px 0px'; });
        });
    }

    /* ============ 9. TILT 3D + SPOTLIGHT PADA KARTU ============ */
    if (finePointer && !reduced) {
        document.documentElement.classList.add('tilt-on');
        $$('.card, .stat-card').forEach(function (el) {
            if (el.querySelector('form, input, textarea, select, table')) return;
            el.addEventListener('mousemove', function (e) {
                var r = el.getBoundingClientRect();
                var px = (e.clientX - r.left) / r.width - .5;
                var py = (e.clientY - r.top) / r.height - .5;
                el.style.transform = 'perspective(900px) rotateY(' + (px * 7) + 'deg) rotateX(' + (-py * 7) + 'deg) translateY(-6px) scale(1.01)';
                el.style.setProperty('--mx', ((e.clientX - r.left)) + 'px');
                el.style.setProperty('--my', ((e.clientY - r.top)) + 'px');
            });
            el.addEventListener('mouseleave', function () { el.style.transform = ''; });
        });

        /* Spotlight untuk kartu ber-form juga */
        $$('.card').forEach(function (el) {
            el.addEventListener('mousemove', function (e) {
                var r = el.getBoundingClientRect();
                el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
                el.style.setProperty('--my', (e.clientY - r.top) + 'px');
            });
        });
    }

    /* ============ 10. PARTIKEL SINEMATIK DI HERO ============ */
    var hero = $('.hero');
    if (hero && !reduced && !$('#heroParticles')) {
        var canvas = document.createElement('canvas');
        canvas.id = 'heroParticles';
        hero.insertBefore(canvas, hero.firstChild);
        var ctx = canvas.getContext('2d');
        var W, H, pts = [], N = 55;
        function resize() {
            W = canvas.width = hero.offsetWidth;
            H = canvas.height = hero.offsetHeight;
        }
        resize();
        window.addEventListener('resize', resize);
        for (var i = 0; i < N; i++) {
            pts.push({
                x: Math.random() * 1200, y: Math.random() * 700,
                vx: (Math.random() - .5) * .45, vy: (Math.random() - .5) * .45,
                r: Math.random() * 1.8 + .6, o: Math.random() * .5 + .15
            });
        }
        (function step() {
            if (!document.hidden) {
                ctx.clearRect(0, 0, W, H);
                for (var a = 0; a < N; a++) {
                    var p = pts[a];
                    p.x += p.vx; p.y += p.vy;
                    if (p.x < 0 || p.x > W) p.vx *= -1;
                    if (p.y < 0 || p.y > H) p.vy *= -1;
                    ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, 6.283);
                    ctx.fillStyle = 'rgba(232,197,90,' + p.o + ')'; ctx.fill();
                }
                for (var x = 0; x < N; x++) {
                    for (var y2 = x + 1; y2 < N; y2++) {
                        var dx = pts[x].x - pts[y2].x, dy = pts[x].y - pts[y2].y;
                        var dist = Math.sqrt(dx * dx + dy * dy);
                        if (dist < 110) {
                            ctx.strokeStyle = 'rgba(255,255,255,' + (.14 * (1 - dist / 110)) + ')';
                            ctx.lineWidth = .6;
                            ctx.beginPath();
                            ctx.moveTo(pts[x].x, pts[x].y);
                            ctx.lineTo(pts[y2].x, pts[y2].y);
                            ctx.stroke();
                        }
                    }
                }
            }
            requestAnimationFrame(step);
        })();
    }

    /* ============ 11. ROTASI KATA HERO ============ */
    var hl = $('.hero h1 .highlight');
    if (hl && !reduced) {
        var words = ['Keunggulan Akademik', 'Budaya Mutu', 'Akreditasi Unggul', 'Daya Saing Global'];
        var wi = 0;
        setInterval(function () {
            hl.classList.add('word-swap-out');
            setTimeout(function () {
                wi = (wi + 1) % words.length;
                hl.textContent = words[wi];
                hl.classList.remove('word-swap-out');
                hl.classList.add('word-swap-in');
                setTimeout(function () { hl.classList.remove('word-swap-in'); }, 520);
            }, 380);
        }, 3800);
    }

    /* ============ 12. ALERT → TOAST NOTIFICATION ============ */
    (function () {
        var alerts = $$('.alert:not(.no-toast)');
        if (!alerts.length) return;
        var zone = $('#toastZone');
        if (!zone) {
            zone = document.createElement('div');
            zone.id = 'toastZone';
            document.body.appendChild(zone);
        }
        alerts.forEach(function (a, idx) {
            var danger = a.classList.contains('alert-danger');
            var t = document.createElement('div');
            t.className = 'toast' + (danger ? ' danger' : '');
            t.innerHTML = '<span style="font-size:18px">' + (danger ? '⚠️' : '✅') + '</span><div>' +
                          a.innerHTML + '</div><i class="bar"></i>';
            t.style.animationDelay = (idx * 130) + 'ms';
            t.style.animationFillMode = 'backwards';
            zone.appendChild(t);
            a.remove();
            setTimeout(function () {
                t.classList.add('hide');
                setTimeout(function () { t.remove(); }, 320);
            }, 5200);
        });
    })();

    /* ============ 13. SIDEBAR TOGGLE (MOBILE) ============ */
    var sidebarToggle = $('.sidebar-toggle');
    if (sidebarToggle) {
        sidebarToggle.style.display = 'inline-block';
        sidebarToggle.addEventListener('click', function () {
            $('.sim-sidebar').classList.toggle('open');
        });
    }

    /* ============ 14. FEEDBACK TOMBOL SUBMIT ============ */
    $$('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            var btn = form.querySelector('button[type="submit"], .btn[type="submit"]');
            if (!btn || btn.dataset.busy) return;
            btn.dataset.busy = '1';
            btn.dataset.original = btn.innerHTML;
            setTimeout(function () { btn.disabled = true; btn.innerHTML = '⏳ Memproses...'; }, 0);
            setTimeout(function () {
                if (btn.dataset.busy) { btn.disabled = false; btn.innerHTML = btn.dataset.original; delete btn.dataset.busy; }
            }, 8000);
        });
    });
    window.addEventListener('pageshow', function () {
        $$('button[data-busy]').forEach(function (b) {
            b.disabled = false;
            if (b.dataset.original) b.innerHTML = b.dataset.original;
            delete b.dataset.busy;
        });
    });

    /* ============ 15. SMOOTH ANCHOR DENGAN OFFSET NAVBAR ============ */
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a[href^="#"]');
        if (!a) return;
        var id = a.getAttribute('href');
        if (id.length > 1) {
            var t = $(id);
            if (t) {
                e.preventDefault();
                window.scrollTo({
                    top: t.getBoundingClientRect().top + window.scrollY - 92,
                    behavior: 'smooth'
                });
            }
        }
    });

    /* ============ 16. AUTO-FOCUS FIRST INPUT ============ */
    var firstInput = $('form input:not([type="hidden"]):not([type="submit"]), form textarea, form select');
    if (firstInput && window.scrollY < 100) {
        setTimeout(function () {
            try { firstInput.focus({ preventScroll: true }); } catch (e) {}
        }, 400);
    }

    /* ============ 17. FLOATING LABELS SUPPORT ============ */
    $$('select').forEach(function (sel) {
        var field = sel.closest('.sp-field, .tr-field, .rw-field, .pg-field, .ed-field');
        if (field) {
            function update() {
                field.classList.toggle('has-value', sel.value && sel.value !== '' && sel.value !== '0');
            }
            sel.addEventListener('change', update);
            update();
        }
    });

    /* ============ 18. COPY TO CLIPBOARD UTILITY ============ */
    window.lpmCopy = function (text) {
        text = String(text);
        function ok() { showToast('✅ Berhasil disalin: ' + text.substring(0, 50)); }
        function fail() { showToast('❌ Gagal menyalin. Silakan salin manual.', 'danger'); }
        function legacy() {
            try {
                var ta = document.createElement('textarea');
                ta.value = text; ta.style.cssText = 'position:fixed;top:0;left:0;opacity:0;';
                document.body.appendChild(ta); ta.focus(); ta.select();
                var s = document.execCommand('copy');
                document.body.removeChild(ta);
                s ? ok() : fail();
            } catch (e) { fail(); }
        }
        if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(text).then(ok).catch(legacy);
        else legacy();
    };

    /* ============ 19. TOAST UTILITY ============ */
    window.showToast = function (msg, type) {
        var zone = $('#toastZone');
        if (!zone) {
            zone = document.createElement('div');
            zone.id = 'toastZone';
            document.body.appendChild(zone);
        }
        var t = document.createElement('div');
        t.className = 'toast' + (type === 'danger' ? ' danger' : '');
        t.innerHTML = '<span style="font-size:18px">' + (type === 'danger' ? '⚠️' : '✅') + '</span><div>' + msg + '</div><i class="bar"></i>';
        zone.appendChild(t);
        setTimeout(function () {
            t.classList.add('hide');
            setTimeout(function () { t.remove(); }, 320);
        }, 5200);
    };

    /* ============ 20. CONFETTI UTILITY ============ */
    window.lpmConfetti = function (container, count) {
        count = count || 40;
        var colors = ['#C9A227', '#E8C55A', '#0F3D5C', '#10B981', '#F59E0B', '#3B82F6'];
        for (var i = 0; i < count; i++) {
            var conf = document.createElement('div');
            conf.style.cssText = 'position:absolute;width:10px;height:10px;opacity:0;pointer-events:none;' +
                'animation:confettiFall 3s ease-out forwards;';
            conf.style.left = Math.random() * 100 + '%';
            conf.style.top = '-20px';
            conf.style.background = colors[Math.floor(Math.random() * colors.length)];
            conf.style.borderRadius = Math.random() > .5 ? '50%' : '2px';
            conf.style.animationDelay = (Math.random() * 1.5) + 's';
            conf.style.animationDuration = (2 + Math.random() * 2) + 's';
            container.appendChild(conf);
            setTimeout(function (el) { return function () { el.remove(); }; }(conf), 5000);
        }
        /* Inject keyframe jika belum ada */
        if (!document.getElementById('lpm-confetti-style')) {
            var style = document.createElement('style');
            style.id = 'lpm-confetti-style';
            style.textContent = '@keyframes confettiFall{0%{transform:translateY(-100px) rotate(0);opacity:1;}100%{transform:translateY(400px) rotate(720deg);opacity:0;}}';
            document.head.appendChild(style);
        }
    };

    /* ============ 21. LAZY LOAD IMAGES ============ */
    if ('IntersectionObserver' in window) {
        var lazyIO = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) {
                    var img = en.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    lazyIO.unobserve(img);
                }
            });
        }, { rootMargin: '100px' });
        $$('img[data-src]').forEach(function (img) { lazyIO.observe(img); });
    }

    /* ============ 22. PRINT FRIENDLY ============ */
    window.addEventListener('beforeprint', function () {
        document.body.classList.add('printing');
    });
    window.addEventListener('afterprint', function () {
        document.body.classList.remove('printing');
    });

    /* ============ INITIAL CALL ============ */
    onScroll();

    /* ============ PERFORMANCE: Pause animations when tab hidden ============ */
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            document.body.classList.add('tab-hidden');
        } else {
            document.body.classList.remove('tab-hidden');
        }
    });

})();

/* ============================================================
   LPM "6D" HERO PARTICLE ENGINE — multi-layer depth parallax
   Aktif otomatis di .ix-hero (beranda). No-op di halaman lain.
============================================================ */
(function () {
    'use strict';
    var hero = document.querySelector('.ix-hero');
    if (!hero || hero.getAttribute('data-6d') === '1') return;
    hero.setAttribute('data-6d', '1');

    var CFG = {
        z: 4,
        stars: 90, orbs: 14, flies: 34,
        linkDist: 110,
        parallax: 26,
        hues: [45, 190, 280, 330, 210],
        dprCap: 2,
        mobileScale: 0.55,
        cometMin: 2600, cometMax: 6200
    };

    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var finePointer = window.matchMedia('(pointer: fine)').matches;
    var isMobile = /android|iphone|ipad|ipod|mobile/i.test(navigator.userAgent);
    var lowPower = (navigator.hardwareConcurrency || 4) <= 4;
    var scale = (isMobile ? CFG.mobileScale : 1) * (lowPower ? 0.7 : 1);

    var canvas = document.createElement('canvas');
    canvas.id = 'lpmHero6D';
    canvas.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:' + CFG.z + ';';
    hero.appendChild(canvas);
    var ctx = canvas.getContext('2d');

    var W = 0, H = 0, DPR = 1;
    var stars = [], orbs = [], flies = [], comets = [], bursts = [], rings = [];
    var mx = 0, my = 0, tmx = 0, tmy = 0;       // mouse normalized + smoothed
    var cursorX = -9999, cursorY = -9999, cursorIn = false;
    var scrollOff = 0;
    var running = false, raf = 0, heroVisible = true;
    var lastT = 0, nextComet = 0, auroraPhase = 0;

    /* ---- pre-rendered glow sprites (cepat) ---- */
    function glowSprite(hue, sat, light, size) {
        var c = document.createElement('canvas'); c.width = c.height = size;
        var g = c.getContext('2d');
        var grd = g.createRadialGradient(size/2, size/2, 0, size/2, size/2, size/2);
        grd.addColorStop(0, 'hsla(' + hue + ',' + sat + '%,' + Math.min(96, light+30) + '%,1)');
        grd.addColorStop(0.25, 'hsla(' + hue + ',' + sat + '%,' + light + '%,0.85)');
        grd.addColorStop(1, 'hsla(' + hue + ',' + sat + '%,' + light + '%,0)');
        g.fillStyle = grd; g.fillRect(0, 0, size, size);
        return c;
    }
    var sprStar = glowSprite(0, 0, 100, 16);
    var sprFly = CFG.hues.map(function (h) { return glowSprite(h, 90, 62, 48); });
    var sprOrb = CFG.hues.map(function (h) { return glowSprite(h, 80, 55, 128); });
    var sprHalo = glowSprite(45, 90, 60, 256);

    function rnd(a, b) { return a + Math.random() * (b - a); }
    function pick(arr) { return arr[(Math.random() * arr.length) | 0]; }

    function areaScale() {
        var a = (W * H) / (1400 * 800);
        return Math.max(0.5, Math.min(1.4, a)) * scale;
    }

    function build() {
        var s = areaScale();
        stars = []; orbs = []; flies = [];
        var i, n;
        n = Math.round(CFG.stars * s);
        for (i = 0; i < n; i++) stars.push({ x: Math.random()*W, y: Math.random()*H, z: rnd(0.15,0.35), r: rnd(0.5,1.6), tw: Math.random()*6.28, ts: rnd(0.6,2.0), vx: rnd(-0.05,0.05), vy: rnd(-0.03,0.03) });
        n = Math.round(CFG.orbs * s);
        for (i = 0; i < n; i++) orbs.push({ x: Math.random()*W, y: Math.random()*H, z: rnd(0.4,0.65), r: rnd(40,110), a: rnd(0.05,0.14), h: (Math.random()*sprOrb.length)|0, vx: rnd(-0.12,0.12), vy: rnd(-0.08,0.08), ph: Math.random()*6.28 });
        n = Math.round(CFG.flies * s);
        for (i = 0; i < n; i++) flies.push({ x: Math.random()*W, y: Math.random()*H, z: rnd(0.8,1.15), r: rnd(1.4,3.2), a: rnd(0.5,0.95), h: (Math.random()*sprFly.length)|0, vx: rnd(-0.35,0.35), vy: rnd(-0.3,0.3), ph: Math.random()*6.28 });
    }

    function resize() {
        DPR = Math.min(CFG.dprCap, window.devicePixelRatio || 1);
        W = hero.clientWidth; H = hero.clientHeight;
        canvas.width = Math.round(W * DPR); canvas.height = Math.round(H * DPR);
        ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
        build();
        if (reduced) drawStatic();
    }

    function wrap(p) {
        if (p.x < -60) p.x = W + 60; else if (p.x > W + 60) p.x = -60;
        if (p.y < -60) p.y = H + 60; else if (p.y > H + 60) p.y = -60;
    }
    function off(z) { return { x: mx * z * CFG.parallax, y: my * z * CFG.parallax + scrollOff * z * 0.06 }; }

    function spawnComet() {
        var fromLeft = Math.random() < 0.5;
        var sp = rnd(7, 12);
        comets.push({
            x: fromLeft ? -80 : W + 80, y: rnd(0, H * 0.45),
            vx: (fromLeft ? 1 : -1) * sp, vy: rnd(2.2, 4.2),
            life: 1, h: pick(CFG.hues), len: rnd(90, 170)
        });
    }

    function burst(x, y) {
        var n = isMobile ? 22 : 40, i;
        for (i = 0; i < n; i++) {
            var ang = Math.random() * 6.283, sp = rnd(1.5, 6.5);
            bursts.push({ x: x, y: y, vx: Math.cos(ang)*sp, vy: Math.sin(ang)*sp - 1, life: 1, h: (Math.random()*sprFly.length)|0, r: rnd(1.5,3.4) });
        }
        rings.push({ x: x, y: y, r: 6, life: 1 });
    }

    function drawStatic() {
        ctx.clearRect(0, 0, W, H);
        ctx.globalCompositeOperation = 'lighter';
        stars.forEach(function (p) { ctx.globalAlpha = 0.5; ctx.drawImage(sprStar, p.x - p.r*2, p.y - p.r*2, p.r*4, p.r*4); });
        ctx.globalAlpha = 1; ctx.globalCompositeOperation = 'source-over';
    }

    function frame(t) {
        if (!running) return;
        raf = requestAnimationFrame(frame);
        var dt = Math.min(50, t - lastT) || 16; lastT = t;
        var k = dt / 16.67;

        mx += (tmx - mx) * 0.06; my += (tmy - my) * 0.06;
        auroraPhase += dt * 0.00035;

        ctx.clearRect(0, 0, W, H);
        ctx.globalCompositeOperation = 'lighter';

        /* --- aurora ribbons --- */
        var r, x, y;
        for (r = 0; r < 3; r++) {
            var hue = CFG.hues[r % CFG.hues.length];
            var baseY = H * (0.28 + r * 0.2);
            var amp = H * 0.06;
            ctx.beginPath();
            for (x = -20; x <= W + 20; x += 24) {
                y = baseY + Math.sin(x * 0.004 + auroraPhase * (1 + r * 0.35) + r * 2) * amp
                          + Math.sin(x * 0.0011 - auroraPhase * 0.6) * amp * 0.6;
                if (x === -20) ctx.moveTo(x, y); else ctx.lineTo(x, y);
            }
            var lg = ctx.createLinearGradient(0, 0, W, 0);
            lg.addColorStop(0, 'hsla(' + hue + ',85%,60%,0)');
            lg.addColorStop(0.5, 'hsla(' + hue + ',85%,60%,0.06)');
            lg.addColorStop(1, 'hsla(' + hue + ',85%,60%,0)');
            ctx.strokeStyle = lg; ctx.lineWidth = 60 + r * 22; ctx.lineCap = 'round';
            ctx.stroke();
        }

        /* --- far star dust --- */
        var i, p, o;
        for (i = 0; i < stars.length; i++) {
            p = stars[i];
            p.x += p.vx * k; p.y += p.vy * k; wrap(p);
            p.tw += p.ts * dt * 0.001;
            o = off(p.z);
            var tw = 0.35 + 0.65 * (0.5 + 0.5 * Math.sin(p.tw));
            ctx.globalAlpha = tw * 0.8;
            var sz = p.r * 4;
            ctx.drawImage(sprStar, p.x + o.x - sz/2, p.y + o.y - sz/2, sz, sz);
        }

        /* --- mid bokeh orbs --- */
        for (i = 0; i < orbs.length; i++) {
            p = orbs[i];
            p.x += p.vx * k; p.y += p.vy * k; wrap(p);
            p.ph += dt * 0.0006;
            o = off(p.z);
            ctx.globalAlpha = p.a * (0.7 + 0.3 * Math.sin(p.ph));
            var osz = p.r * 2;
            ctx.drawImage(sprOrb[p.h], p.x + o.x - osz/2, p.y + o.y - osz/2, osz, osz);
        }

        /* --- near fireflies + constellation --- */
        var fo = [];
        for (i = 0; i < flies.length; i++) {
            p = flies[i];
            p.ph += dt * 0.001;
            p.vx += Math.sin(p.ph) * 0.012 * k; p.vy += Math.cos(p.ph * 0.8) * 0.012 * k;
            if (cursorIn && finePointer) {
                var dx = cursorX - p.x, dy = cursorY - p.y, d2 = dx*dx + dy*dy;
                if (d2 < 200*200 && d2 > 1) { var d = Math.sqrt(d2); p.vx += (dx/d) * 0.05 * k; p.vy += (dy/d) * 0.05 * k; }
            }
            p.vx *= 0.985; p.vy *= 0.985;
            p.x += p.vx * k; p.y += p.vy * k; wrap(p);
            o = off(p.z);
            fo.push({ x: p.x + o.x, y: p.y + o.y, p: p });
        }
        ctx.lineWidth = 0.7;
        for (i = 0; i < fo.length; i++) {
            for (var j = i + 1; j < fo.length; j++) {
                var ddx = fo[i].x - fo[j].x, ddy = fo[i].y - fo[j].y;
                var dist = Math.sqrt(ddx*ddx + ddy*ddy);
                if (dist < CFG.linkDist) {
                    ctx.globalAlpha = (1 - dist / CFG.linkDist) * 0.22;
                    ctx.strokeStyle = 'hsla(' + CFG.hues[fo[i].p.h] + ',90%,70%,1)';
                    ctx.beginPath(); ctx.moveTo(fo[i].x, fo[i].y); ctx.lineTo(fo[j].x, fo[j].y); ctx.stroke();
                }
            }
        }
        for (i = 0; i < fo.length; i++) {
            p = fo[i].p;
            ctx.globalAlpha = p.a * (0.7 + 0.3 * Math.sin(p.ph * 2));
            var fsz = p.r * 8;
            ctx.drawImage(sprFly[p.h], fo[i].x - fsz/2, fo[i].y - fsz/2, fsz, fsz);
        }

        /* --- comets --- */
        if (t > nextComet) { spawnComet(); nextComet = t + rnd(CFG.cometMin, CFG.cometMax); }
        for (i = comets.length - 1; i >= 0; i--) {
            var c = comets[i];
            c.x += c.vx * k; c.y += c.vy * k; c.life -= dt * 0.0006;
            if (c.life <= 0 || c.x < -200 || c.x > W + 200 || c.y > H + 200) { comets.splice(i, 1); continue; }
            var tx = c.x - c.vx * (c.len / 10), ty = c.y - c.vy * (c.len / 10);
            var cg = ctx.createLinearGradient(c.x, c.y, tx, ty);
            cg.addColorStop(0, 'hsla(' + c.h + ',95%,80%,' + (0.8 * c.life) + ')');
            cg.addColorStop(1, 'hsla(' + c.h + ',95%,60%,0)');
            ctx.globalAlpha = 1; ctx.strokeStyle = cg; ctx.lineWidth = 2.2; ctx.lineCap = 'round';
            ctx.beginPath(); ctx.moveTo(c.x, c.y); ctx.lineTo(tx, ty); ctx.stroke();
            ctx.globalAlpha = c.life; ctx.drawImage(sprFly[CFG.hues.indexOf(c.h) >= 0 ? CFG.hues.indexOf(c.h) : 0], c.x - 14, c.y - 14, 28, 28);
        }

        /* --- click bursts + rings --- */
        for (i = bursts.length - 1; i >= 0; i--) {
            var b = bursts[i];
            b.vy += 0.06 * k; b.x += b.vx * k; b.y += b.vy * k; b.life -= dt * 0.0012;
            if (b.life <= 0) { bursts.splice(i, 1); continue; }
            ctx.globalAlpha = b.life;
            var bsz = b.r * 8 * b.life;
            ctx.drawImage(sprFly[b.h], b.x - bsz/2, b.y - bsz/2, bsz, bsz);
        }
        for (i = rings.length - 1; i >= 0; i--) {
            var rg = rings[i];
            rg.r += 4.5 * k; rg.life -= dt * 0.0016;
            if (rg.life <= 0) { rings.splice(i, 1); continue; }
            ctx.globalAlpha = rg.life * 0.5; ctx.strokeStyle = 'hsla(45,95%,70%,1)'; ctx.lineWidth = 2;
            ctx.beginPath(); ctx.arc(rg.x, rg.y, rg.r, 0, 6.283); ctx.stroke();
        }

        /* --- cursor halo --- */
        if (cursorIn && finePointer) {
            ctx.globalAlpha = 0.35;
            ctx.drawImage(sprHalo, cursorX - 130, cursorY - 130, 260, 260);
        }

        /* --- cinematic vignette --- */
        ctx.globalCompositeOperation = 'source-over';
        ctx.globalAlpha = 1;
        var vg = ctx.createRadialGradient(W/2, H*0.42, Math.min(W,H)*0.25, W/2, H*0.5, Math.max(W,H)*0.75);
        vg.addColorStop(0, 'rgba(4,10,20,0)');
        vg.addColorStop(1, 'rgba(4,10,20,0.30)');
        ctx.fillStyle = vg; ctx.fillRect(0, 0, W, H);
    }

    function start() { if (running || reduced) return; running = true; lastT = performance.now(); nextComet = lastT + 1200; raf = requestAnimationFrame(frame); }
    function stop() { running = false; cancelAnimationFrame(raf); }

    /* ---- events ---- */
    if (finePointer) {
        hero.addEventListener('mousemove', function (e) {
            var r = hero.getBoundingClientRect();
            cursorX = e.clientX - r.left; cursorY = e.clientY - r.top; cursorIn = true;
            tmx = (cursorX / r.width - 0.5) * 2; tmy = (cursorY / r.height - 0.5) * 2;
        });
        hero.addEventListener('mouseleave', function () { cursorIn = false; tmx = 0; tmy = 0; });
    }
    hero.addEventListener('click', function (e) {
        if (reduced) return;
        var r = hero.getBoundingClientRect();
        burst(e.clientX - r.left, e.clientY - r.top);
    });
    window.addEventListener('scroll', function () { scrollOff = window.scrollY || 0; }, { passive: true });

    var rT = 0;
    window.addEventListener('resize', function () { clearTimeout(rT); rT = setTimeout(resize, 150); });

    if ('IntersectionObserver' in window) {
        new IntersectionObserver(function (en) {
            heroVisible = en[0].isIntersecting;
            if (heroVisible && !document.hidden) start(); else stop();
        }, { threshold: 0.05 }).observe(hero);
    }
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) stop(); else if (heroVisible) start();
    });

    resize();
    if (!reduced) start();
})();