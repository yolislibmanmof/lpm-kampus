</main>
</div>

<style>
    /* ===== DARK MODE FIX v3.3 ===== */
    body.dark h1, body.dark h2, body.dark h3, body.dark h4 { color: #E6EDF7 !important; }
    body.dark .sim-topbar p { color: var(--text-muted) !important; }
    body.dark .card p, body.dark .card small { color: var(--text-muted); }
    body.dark [style*="color:var(--primary)"] { color: #7FB2E5 !important; }
    body.dark [style*="color:var(--success)"] { color: #6EE7B7 !important; }
    body.dark [style*="color:var(--danger)"] { color: #FCA5A5 !important; }
    body.dark [style*="color:var(--info)"] { color: #93C5FD !important; }
</style>

<script src="/assets/js/main.js"></script>
<script>
// ===== CLIPBOARD POLYFILL v4.0 =====
(function () {
    if (navigator.clipboard && window.isSecureContext) return;
    navigator.clipboard = navigator.clipboard || {};
    navigator.clipboard.writeText = function (t) {
        return new Promise(function (resolve, reject) {
            var ta = document.createElement('textarea');
            ta.value = t; ta.setAttribute('readonly', '');
            ta.style.cssText = 'position:fixed;top:0;left:0;opacity:0;pointer-events:none;';
            document.body.appendChild(ta);
            var isIOS = /ipad|iphone|ipod/i.test(navigator.userAgent);
            if (isIOS) { ta.contentEditable = true; ta.readOnly = false; var r = ta.createTextRange(); r.select(); }
            else { ta.select(); ta.setSelectionRange(0, 99999); }
            try { document.execCommand('copy'); resolve(); } catch (e) { reject(e); }
            document.body.removeChild(ta);
        });
    };
})();

// ===== RESTYLE CHART UNTUK DARK MODE =====
window.restyleCharts = function () {
    if (!window.Chart) return;
    var dark = document.body.classList.contains('dark');
    try {
        Chart.defaults.color = dark ? '#9FB3CC' : '#64748B';
        Chart.defaults.borderColor = dark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.08)';
    } catch (e) {}
    document.querySelectorAll('canvas').forEach(function (cv) {
        try {
            var ch = Chart.getChart(cv);
            if (!ch || !ch.data) return;
            function swap(c) { return (typeof c === 'string' && c.toUpperCase() === '#0F3D5C') ? (dark ? '#3E76A8' : '#0F3D5C') : c; }
            ch.data.datasets.forEach(function (ds) {
                if (typeof ds.backgroundColor === 'string') ds.backgroundColor = swap(ds.backgroundColor);
                if (typeof ds.borderColor === 'string') ds.borderColor = swap(ds.borderColor);
                if (Array.isArray(ds.backgroundColor)) ds.backgroundColor = ds.backgroundColor.map(function (c) { return typeof c === 'string' ? swap(c) : c; });
                if (ds.pointBackgroundColor) ds.pointBackgroundColor = swap(ds.pointBackgroundColor);
            });
            if (ch.options && ch.options.scales) {
                Object.keys(ch.options.scales).forEach(function (k) {
                    try {
                        var sc = ch.options.scales[k];
                        sc.grid = sc.grid || {};
                        sc.grid.color = dark ? 'rgba(255,255,255,.09)' : 'rgba(0,0,0,.08)';
                        if (sc.ticks) sc.ticks.color = dark ? '#9FB3CC' : '#64748B';
                        if (sc.pointLabels) sc.pointLabels.color = dark ? '#C9D6E8' : '#334155';
                        if (sc.angleLines) sc.angleLines.color = dark ? 'rgba(255,255,255,.12)' : 'rgba(0,0,0,.1)';
                    } catch (e) {}
                });
            }
            ch.update('none');
        } catch (e) {}
    });
};

// ===== Jam live (Versi Lengkap) =====
(function () {
    var el = document.getElementById('sbClock');
    if (!el) return;
    var hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    var bul = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    function tick() {
        var d = new Date();
        el.textContent = hari[d.getDay()] + ', ' + d.getDate() + ' ' + bul[d.getMonth()] + ' ' + d.getFullYear() +
            ' • ' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0') + ':' + String(d.getSeconds()).padStart(2,'0');
    }
    tick(); setInterval(tick, 1000);
})();

// ===== Collapse sidebar =====
(function () {
    var b = document.getElementById('sbCollapse');
    if (b) {
        b.addEventListener('click', function (e) {
            e.preventDefault();
            document.body.classList.toggle('sim-collapsed');
            localStorage.setItem('lpm-sidebar-collapsed', document.body.classList.contains('sim-collapsed') ? '1' : '0');
        });
    }
})();

// ===== DARK MODE + restyle chart =====
window.toggleDark = function () {
    document.body.classList.toggle('dark');
    localStorage.setItem('lpm-dark', document.body.classList.contains('dark') ? '1' : '0');
    var b = document.getElementById('dmToggle');
    if (b) b.textContent = document.body.classList.contains('dark') ? '☀️' : '🌙';
    window.restyleCharts();
};
(function () {
    var b = document.getElementById('dmToggle');
    if (b) {
        b.textContent = document.body.classList.contains('dark') ? '☀️' : '🌙';
        b.addEventListener('click', function(e) {
            e.preventDefault();
            window.toggleDark();
        });
    }
})();

// ===== COMMAND PALETTE =====
(function () {
    var overlay = document.getElementById('cmdk'), input = document.getElementById('cmdkInput'),
        list = document.getElementById('cmdkList'), btn = document.getElementById('cmdkBtn');
    if (!overlay) return;
    var items = window.CMD_ITEMS || [], sel = 0, filtered = items;

    function render() {
        list.innerHTML = '';
        if (!filtered.length) { list.innerHTML = '<div style="padding:20px 22px;color:var(--text-muted);font-size:13px;">Tidak ditemukan...</div>'; return; }
        filtered.forEach(function (it, i) {
            var a = document.createElement('a');
            a.className = 'cmdk-item' + (i === sel ? ' sel' : '');
            a.href = it.url || '#';
            a.innerHTML = '<span>' + it.icon + '</span> ' + it.label;
            a.addEventListener('click', function (e) { choose(it, e); });
            a.addEventListener('mouseenter', function () { sel = i; render(); });
            list.appendChild(a);
        });
    }
    function choose(it, e) {
        if (e) e.preventDefault();
        close();
        if (it.action === 'dark') window.toggleDark();
        else if (it.action === 'print') window.print();
        else if (it.action === 'logout') {
            if (confirm('Apakah Anda yakin ingin keluar dari SIM-Mutu?')) {
                document.getElementById('global-logout-form').submit();
            }
        }
        else if (it.url) { document.body.classList.add('page-leave'); setTimeout(function () { location.href = it.url; }, 220); }
    }
    function open() { overlay.classList.add('open'); input.value = ''; filtered = items; sel = 0; render(); setTimeout(function () { input.focus(); }, 60); }
    function close() { overlay.classList.remove('open'); }

    if (btn) btn.addEventListener('click', function(e) { e.preventDefault(); open(); });
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); overlay.classList.contains('open') ? close() : open(); return; }
        if (!overlay.classList.contains('open')) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); sel = Math.min(sel + 1, filtered.length - 1); render(); }
        if (e.key === 'ArrowUp') { e.preventDefault(); sel = Math.max(sel - 1, 0); render(); }
        if (e.key === 'Enter') { if (filtered[sel]) choose(filtered[sel]); }
        if (e.key === 'Escape') close();
    });
    input.addEventListener('input', function () {
        var q = input.value.toLowerCase();
        filtered = items.filter(function (it) { return it.label.toLowerCase().indexOf(q) > -1; });
        sel = 0; render();
    });
    overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
})();

// ===== NOTIFICATION CENTER (AJAX & SOUND) =====
(function() {
    'use strict';
    const ntWrap = document.getElementById('ntWrap');
    const ntBell = document.getElementById('ntBell');
    const ntBadge = document.getElementById('ntBadge');
    const ntListContainer = document.getElementById('ntListContainer');
    const ntSoundToggle = document.getElementById('ntSoundToggle');
    const ntMarkAllRead = document.getElementById('ntMarkAllRead');

    const popSound = new Audio("data:audio/wav;base64,UklGRigAAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQQAAAAAAA==");
    let currentCount = parseInt(ntBadge ? ntBadge.textContent.replace('+', '') : 0);
    let isSoundEnabled = localStorage.getItem('lpm_notif_sound') === '1';

    if (ntSoundToggle) {
        ntSoundToggle.textContent = isSoundEnabled ? '🔊' : '🔇';
        ntSoundToggle.title = isSoundEnabled ? 'Matikan Suara Notifikasi' : 'Nyalakan Suara Notifikasi';
    }

    if (ntBell) {
        ntBell.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            ntWrap.classList.toggle('open');
        });
    }

    document.addEventListener('click', function(e) {
        if (ntWrap && !ntWrap.contains(e.target)) {
            ntWrap.classList.remove('open');
        }
    });

    if (ntSoundToggle) {
        ntSoundToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            isSoundEnabled = !isSoundEnabled;
            localStorage.setItem('lpm_notif_sound', isSoundEnabled ? '1' : '0');
            ntSoundToggle.textContent = isSoundEnabled ? '🔊' : '🔇';
            ntSoundToggle.title = isSoundEnabled ? 'Matikan Suara Notifikasi' : 'Nyalakan Suara Notifikasi';
        });
    }

    if (ntMarkAllRead) {
        ntMarkAllRead.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            try {
                await fetch('/sim/api/mark-all-read.php', { method: 'POST' });
                currentCount = 0;
                if (ntBadge) ntBadge.style.display = 'none';
                if (ntListContainer) ntListContainer.innerHTML = '<div class="nt-empty">🌙 Tidak ada notifikasi.<br>Semua tenang dan terkendali.</div>';
                if (ntMarkAllRead) ntMarkAllRead.remove();
            } catch (err) { console.error('Mark all read failed:', err); }
        });
    }

    async function fetchNotifications() {
        try {
            const response = await fetch('/sim/api/get-notifications.php');
            const data = await response.json();
            if (data.success) {
                if (data.count > currentCount && currentCount > 0 && isSoundEnabled) {
                    popSound.play().catch(function() {});
                }
                currentCount = data.count;
                if (ntBadge) {
                    if (currentCount > 0) {
                        ntBadge.textContent = currentCount > 9 ? '9+' : currentCount;
                        ntBadge.style.display = 'grid';
                    } else {
                        ntBadge.style.display = 'none';
                    }
                }
                if (ntListContainer) {
                    ntListContainer.innerHTML = data.html || '<div class="nt-empty">🌙 Tidak ada notifikasi.<br>Semua tenang dan terkendali.</div>';
                }
            }
        } catch (error) { console.error('Notif fetch failed:', error); }
    }

    document.addEventListener('click', function(e) {
        const item = e.target.closest('.nt-item[data-id]');
        if (item && item.classList.contains('unread')) {
            item.classList.remove('unread');
            item.style.borderLeft = 'none';
            try {
                const formData = new FormData();
                formData.append('id', item.dataset.id);
                fetch('/sim/api/mark-as-read.php', { method: 'POST', body: formData });
                if (currentCount > 0) {
                    currentCount--;
                    if (ntBadge) {
                        ntBadge.textContent = currentCount > 9 ? '9+' : currentCount;
                        if (currentCount === 0) ntBadge.style.display = 'none';
                    }
                }
            } catch (err) { console.error('Mark read failed:', err); }
        }
    });

    fetchNotifications();
    setInterval(fetchNotifications, 30000);
})();

// ===== Sidebar mobile =====
(function () {
    var t = document.querySelector('.sidebar-toggle');
    if (t) t.addEventListener('click', function () { document.querySelector('.sim-sidebar').classList.toggle('open'); });
})();

// ===== GLOBAL LOGOUT INTERCEPTOR =====
(function () {
    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[href*="logout.php"], [data-logout]');
        if (link) {
            e.preventDefault();
            e.stopImmediatePropagation();
            if (confirm('Apakah Anda yakin ingin keluar dari SIM-Mutu?')) {
                document.getElementById('global-logout-form').submit();
            }
        }
    }, true);
})();
</script>
</body>
</html>