/* PRISMA RT 04 — core: API client, toast, modal, notifikasi real-time, tema, PWA */
(function () {
  'use strict';
  const P = (window.P = {});
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  P.$ = $; P.$$ = $$;

  P.esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  P.icon = (n, cls = '') => `<svg class="ic ${cls}" aria-hidden="true"><use href="#i-${n}"/></svg>`;
  P.rp = (n) => 'Rp ' + Number(n || 0).toLocaleString('id-ID');
  P.date = (v, withTime) => {
    if (!v) return '—';
    const d = new Date(v.length === 10 ? v + 'T00:00:00' : v);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}) });
  };
  P.ago = (v) => {
    const s = Math.max(0, (Date.now() - new Date(v).getTime()) / 1000);
    if (s < 60) return 'baru saja';
    if (s < 3600) return Math.floor(s / 60) + ' mnt lalu';
    if (s < 86400) return Math.floor(s / 3600) + ' jam lalu';
    return Math.floor(s / 86400) + ' hari lalu';
  };

  const STATUS = {
    draft: ['Draft', 'gray'], submitted: ['Diajukan', 'amber'], verified: ['Terverifikasi', 'blue'], approved: ['Disahkan', 'green'],
    completed: ['Selesai', 'green'], rejected: ['Ditolak', 'rose'], reviewed: ['Ditinjau', 'blue'], processing: ['Diproses', 'blue'],
    assigned: ['Ditugaskan', 'violet'], resolved: ['Terselesaikan', 'green'], closed: ['Ditutup', 'gray'], published: ['Terbit', 'green'],
    reversed: ['Dikoreksi', 'rose'], tetap: ['Warga tetap', 'green'], pending_verification: ['Menunggu verifikasi', 'amber'], pindah: ['Nonaktif', 'gray'],
    rendah: ['Rendah', 'gray'], sedang: ['Sedang', 'blue'], tinggi: ['Tinggi', 'amber'], darurat: ['Darurat', 'rose'],
    low: ['Rendah', 'gray'], medium: ['Sedang', 'blue'], high: ['Tinggi', 'amber'], emergency: ['Darurat', 'rose'],
    income: ['Pemasukan', 'green'], expense: ['Pengeluaran', 'rose'],
  };
  P.badge = (v, tone) => {
    const s = STATUS[v] || [String(v ?? '—'), 'gray'];
    return `<span class="badge ${tone || s[1]}">${P.esc(s[0])}</span>`;
  };
  P.statusLabel = (v) => (STATUS[v] || [v])[0];

  /* ---------- API ---------- */
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
  P.api = async (method, url, body) => {
    const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() };
    const init = { method, headers, credentials: 'same-origin' };
    if (body instanceof FormData) init.body = body;
    else if (body !== undefined) { headers['Content-Type'] = 'application/json'; init.body = JSON.stringify(body); }
    let res;
    try { res = await fetch(url, init); } catch (e) {
      const err = new Error('Tidak dapat terhubung ke server. Periksa koneksi internet Anda.'); err.offline = true; throw err;
    }
    if (res.status === 304) return { notModified: true };
    let json = null;
    try { json = await res.json(); } catch (e) { /* kosong */ }
    if (!res.ok) {
      if (res.status === 401 && !url.includes('/auth/') && !url.includes('/login')) { location.href = '/login'; }
      const first = json?.errors ? Object.values(json.errors).flat()[0] : null;
      const err = new Error(first || json?.message || `Terjadi kesalahan (${res.status}).`);
      err.status = res.status; err.errors = json?.errors || {}; throw err;
    }
    return json;
  };
  P.get = (u) => P.api('GET', u);
  P.post = (u, b) => P.api('POST', u, b ?? {});
  P.patch = (u, b) => P.api('PATCH', u, b);
  P.del = (u) => P.api('DELETE', u);

  /* ---------- Toast ---------- */
  P.toast = (title, text = '', type = 'info', ms = 5200) => {
    let box = $('.toasts');
    if (!box) { box = document.createElement('div'); box.className = 'toasts'; box.setAttribute('role', 'status'); box.setAttribute('aria-live', 'polite'); document.body.appendChild(box); }
    const icon = { success: 'check-circle', danger: 'alert', warning: 'alert', info: 'info' }[type] || 'info';
    const el = document.createElement('div');
    el.className = 'toast ' + type;
    el.innerHTML = `${P.icon(icon)}<div><b>${P.esc(title)}</b>${text ? `<span>${P.esc(text)}</span>` : ''}</div>`;
    box.appendChild(el);
    const kill = () => el.remove();
    el.addEventListener('click', kill);
    if (ms) setTimeout(kill, ms);
    return el;
  };
  P.ok = (t, x) => P.toast(t, x, 'success');
  P.fail = (e) => P.toast('Gagal', e?.message || String(e), 'danger', 7000);

  /* ---------- Modal ---------- */
  P.modal = ({ title, body = '', foot = '', onClose } = {}) => {
    const back = document.createElement('div');
    back.className = 'modal-back';
    back.innerHTML = `<div class="modal" role="dialog" aria-modal="true" aria-label="${P.esc(title)}">
      <div class="modal-head"><h3>${P.esc(title)}</h3><button class="btn btn-ghost btn-icon btn-sm" data-close aria-label="Tutup">${P.icon('x')}</button></div>
      <div class="modal-body"></div>${foot ? '<div class="modal-foot"></div>' : ''}</div>`;
    const bodyEl = $('.modal-body', back);
    typeof body === 'string' ? (bodyEl.innerHTML = body) : bodyEl.appendChild(body);
    if (foot) { const f = $('.modal-foot', back); typeof foot === 'string' ? (f.innerHTML = foot) : f.appendChild(foot); }
    const close = () => { back.remove(); document.removeEventListener('keydown', esc); onClose && onClose(); };
    const esc = (e) => e.key === 'Escape' && close();
    back.addEventListener('mousedown', (e) => e.target === back && close());
    $$('[data-close]', back).forEach((b) => b.addEventListener('click', close));
    document.addEventListener('keydown', esc);
    document.body.appendChild(back);
    setTimeout(() => $('input:not([type=hidden]),select,textarea', back)?.focus(), 30);
    return { el: back, body: bodyEl, close };
  };
  P.confirm = (message, { confirmText = 'Ya, lanjutkan', tone = 'danger', title = 'Konfirmasi' } = {}) => new Promise((resolve) => {
    const m = P.modal({ title, body: `<p>${P.esc(message)}</p>`, foot: `<button class="btn" data-no>Batal</button><button class="btn btn-${tone}" data-yes>${P.esc(confirmText)}</button>`, onClose: () => resolve(false) });
    $('[data-no]', m.el).onclick = () => { resolve(false); m.close(); };
    $('[data-yes]', m.el).onclick = () => { resolve(true); m.close(); };
  });
  P.ask = (label, { title = 'Isi keterangan', required = true } = {}) => new Promise((resolve) => {
    const m = P.modal({ title, body: `<div class="field"><label>${P.esc(label)}</label><textarea class="input" id="ask-val" rows="3"></textarea></div>`, foot: `<button class="btn" data-no>Batal</button><button class="btn btn-primary" data-yes>Kirim</button>`, onClose: () => resolve(null) });
    $('[data-no]', m.el).onclick = () => { resolve(null); m.close(); };
    $('[data-yes]', m.el).onclick = () => { const v = $('#ask-val', m.el).value.trim(); if (required && !v) { $('#ask-val', m.el).classList.add('invalid'); return; } resolve(v); m.close(); };
  });

  /* ---------- Polling (real-time) ---------- */
  P.poll = (fn, ms) => {
    let timer = null; let stopped = false;
    const tick = async () => { if (stopped || document.hidden) return; try { await fn(); } catch (e) { /* abaikan */ } };
    const start = () => { clearInterval(timer); timer = setInterval(tick, ms); };
    document.addEventListener('visibilitychange', () => { if (!document.hidden) { tick(); start(); } });
    tick(); start();
    return () => { stopped = true; clearInterval(timer); };
  };

  /* ---------- Theme ---------- */
  const applyTheme = (t) => { document.documentElement.dataset.theme = t; $$('[data-theme-toggle] use').forEach((u) => u.setAttribute('href', t === 'dark' ? '#i-sun' : '#i-moon')); };
  P.theme = () => {
    let t = 'dark'; try { t = localStorage.getItem('prisma-theme') || 'dark'; } catch (e) { /* private mode */ }
    applyTheme(t);
    $$('[data-theme-toggle]').forEach((b) => b.addEventListener('click', () => {
      t = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
      try { localStorage.setItem('prisma-theme', t); } catch (e) { /* abaikan */ }
      applyTheme(t);
      const meta = $('meta[name=theme-color]'); if (meta) meta.content = t === 'dark' ? '#070b16' : '#f3f6fc';
    }));
  };

  /* ---------- Drawer (mobile) ---------- */
  P.drawer = () => {
    $$('[data-open-drawer]').forEach((b) => b.addEventListener('click', () => {
      const tpl = $('#drawer-tpl'); if (!tpl) return;
      const wrap = document.createElement('div');
      wrap.innerHTML = `<div class="drawer-back"></div><aside class="drawer">${tpl.innerHTML}</aside>`;
      const close = () => wrap.remove();
      $('.drawer-back', wrap).onclick = close;
      $$('[data-close-drawer], .drawer a', wrap).forEach((a) => a.addEventListener('click', close));
      $$('[data-theme-toggle]', wrap).forEach((t) => t.addEventListener('click', () => $('[data-theme-toggle]').click()));
      document.body.appendChild(wrap);
    }));
  };

  /* ---------- Notifikasi real-time ---------- */
  P.notif = (() => {
    let seen = null; const panelId = '#notif-panel';
    const levelOf = (l) => (['success', 'danger', 'warning'].includes(l) ? l : '');
    const render = (items) => {
      const list = $('#notif-list'); if (!list) return;
      list.innerHTML = items.length ? items.map((n) => `
        <div class="n-item ${n.read ? 'read' : 'unread'}" data-id="${n.id}" data-url="${P.esc(n.url || '')}">
          <i class="n-dot ${levelOf(n.level)}"></i>
          <div style="flex:1;min-width:0"><b>${P.esc(n.title)}</b><span>${P.esc(n.body)}</span><time>${P.ago(n.created_at)}</time></div>
        </div>`).join('') : `<div class="empty">${P.icon('bell')}<div>Belum ada notifikasi</div></div>`;
      $$('.n-item', list).forEach((el) => el.addEventListener('click', async () => {
        const id = el.dataset.id; const url = el.dataset.url;
        el.classList.replace('unread', 'read');
        try { await P.post(`/api/v1/notifications/${id}/read`); } catch (e) { /* abaikan */ }
        if (url) location.href = url; else refresh();
      }));
    };
    const setBadge = (n) => $$('[data-notif-count]').forEach((b) => { b.textContent = n > 99 ? '99+' : n; b.hidden = n < 1; });
    const refresh = async () => {
      const res = await P.get('/api/v1/notifications?limit=30');
      const items = res.data;
      setBadge(res.meta.unread_count);
      render(items);
      const fresh = items.filter((n) => !n.read && seen && !seen.has(n.id));
      seen = new Set(items.map((n) => n.id));
      fresh.slice(0, 3).forEach((n) => {
        P.toast(n.title, n.body, levelOf(n.level) || 'info');
        try {
          if ('Notification' in window && Notification.permission === 'granted' && document.hidden) new Notification(n.title, { body: n.body, icon: '/icons/icon-192.png' });
          if (navigator.vibrate) navigator.vibrate(120);
        } catch (e) { /* abaikan */ }
      });
      document.dispatchEvent(new CustomEvent('prisma:notifications', { detail: { fresh, unread: res.meta.unread_count } }));
    };
    const init = () => {
      const btn = $('#notif-btn'); if (!btn) return;
      const panel = $(panelId);
      btn.addEventListener('click', (e) => { e.stopPropagation(); panel.hidden = !panel.hidden; });
      document.addEventListener('click', (e) => { if (!panel.hidden && !panel.contains(e.target)) panel.hidden = true; });
      $('#notif-readall')?.addEventListener('click', async () => { await P.post('/api/v1/notifications/read-all'); refresh(); });
      $('#notif-clear')?.addEventListener('click', async () => { if (await P.confirm('Hapus semua notifikasi?')) { await P.del('/api/v1/notifications'); refresh(); } });
      $('#notif-perm')?.addEventListener('click', async () => {
        if (!('Notification' in window)) return P.toast('Tidak didukung', 'Browser ini tidak mendukung notifikasi sistem.', 'warning');
        const r = await Notification.requestPermission();
        P.toast(r === 'granted' ? 'Notifikasi aktif' : 'Notifikasi diblokir', r === 'granted' ? 'Anda akan menerima pemberitahuan baru.' : 'Izinkan lewat pengaturan browser.', r === 'granted' ? 'success' : 'warning');
      });
      P.poll(refresh, 10000);
    };
    return { init, refresh };
  })();

  /* ---------- PWA ---------- */
  P.pwa = () => {
    if ('serviceWorker' in navigator) window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
    let deferred = null;
    window.addEventListener('beforeinstallprompt', (e) => { e.preventDefault(); deferred = e; $$('[data-install]').forEach((b) => (b.hidden = false)); $('.install-bar')?.classList.add('show'); });
    window.addEventListener('appinstalled', () => { $$('[data-install]').forEach((b) => (b.hidden = true)); $('.install-bar')?.classList.remove('show'); P.ok('Aplikasi terpasang', 'PRISMA RT 04 kini ada di layar utama Anda.'); });
    $$('[data-install]').forEach((b) => b.addEventListener('click', async () => { if (!deferred) return; deferred.prompt(); await deferred.userChoice; deferred = null; }));
    const offline = () => P.toast('Anda sedang offline', 'Data terbaru akan dimuat saat koneksi kembali.', 'warning', 4000);
    window.addEventListener('offline', offline);
    window.addEventListener('online', () => P.toast('Kembali online', 'Menyegarkan data…', 'success', 2500));
  };

  /* ---------- Logout (form POST bawaan) ---------- */
  document.addEventListener('DOMContentLoaded', () => {
    P.theme(); P.drawer(); P.pwa(); P.notif.init();
    $$('[data-pw-toggle]').forEach((b) => b.addEventListener('click', () => {
      const i = b.parentElement.querySelector('input'); const show = i.type === 'password'; i.type = show ? 'text' : 'password';
      b.innerHTML = P.icon(show ? 'eye-off' : 'eye');
    }));
  });
})();
