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
            var target = parseFloat(m[1].replace(/,/g, ''));
            var suffix = m[2] || '';
            var isInt = Number.isInteger(target);
            var start = performance.now();
            (function tick(now) {
                var p = Math.min((now - start) / 1500, 1);
                var eased = 1 - Math.pow(1 - p, 3);
                var val = isInt ? Math.round(target * eased) : (target * eased).toFixed(1);
                el.textContent = val + suffix;
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
            if (btn && !btn.dataset.busy) {
                btn.dataset.busy = '1';
                btn.dataset.original = btn.innerHTML;
                btn.innerHTML = '⏳ Memproses...';
                setTimeout(function () {
                    if (btn.dataset.busy) {
                        btn.innerHTML = btn.dataset.original;
                        delete btn.dataset.busy;
                    }
                }, 4000);
            }
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
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function () {
                showToast('✅ Berhasil disalin: ' + text.substring(0, 50));
            });
        }
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