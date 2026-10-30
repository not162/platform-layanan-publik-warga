/* Registrasi, lupa password, dan reset password — validasi real-time ke MySQL via API */
(function () {
  'use strict';
  const { $, $$ } = P;
  const digits = (el) => el && el.addEventListener('input', () => (el.value = el.value.replace(/\D/g, '').slice(0, 16)));

  const hint = (name, ok, msg) => {
    const h = $(`[data-hint="${name}"]`); const input = $(`[name="${name}"]`);
    if (h) { h.textContent = msg || ''; h.className = 'hint ' + (ok === true ? 'ok' : ok === false ? 'bad' : ''); }
    if (input) { input.classList.toggle('valid', ok === true); input.classList.toggle('invalid', ok === false); }
  };
  const showErrors = (form, err) => {
    Object.entries(err.errors || {}).forEach(([k, v]) => hint(k, false, v[0]));
    const first = form.querySelector('.invalid'); first && first.focus();
  };
  const busy = (btn, on, text) => { btn.disabled = on; btn.dataset.t = btn.dataset.t || btn.textContent; btn.textContent = on ? 'Memproses…' : (text || btn.dataset.t); };

  /* ---------- Registrasi ---------- */
  const reg = $('#register-form');
  if (reg) {
    ['nik', 'no_kk'].forEach((n) => digits($(`[name="${n}"]`, reg)));
    $('[name="username"]', reg).addEventListener('input', (e) => (e.target.value = e.target.value.toLowerCase().replace(/[^a-z0-9_.]/g, '')));

    let timer; let seq = 0; let last = '';
    const check = () => {
      clearTimeout(timer);
      timer = setTimeout(async () => {
        const v = (n) => reg.elements[n].value.trim();
        const nik = v('nik'); const kk = v('no_kk'); const name = v('name'); const username = v('username');
        // Isian yang belum lengkap dicek di browser saja; server hanya dipanggil untuk nilai lengkap (kuota pemeriksaan dibatasi).
        if (nik && nik.length < 16) hint('nik', null, `${nik.length}/16 digit`);
        if (kk && kk.length < 16) hint('no_kk', null, `${kk.length}/16 digit`);
        if (username && username.length < 4) hint('username', null, 'Minimal 4 karakter.');
        const body = { nik: nik.length === 16 ? nik : '', no_kk: kk.length === 16 ? kk : '', name: name.length >= 3 ? name : '', username: username.length >= 4 ? username : '' };
        const key = JSON.stringify(body);
        if (key === last || !Object.values(body).some(Boolean)) return;
        last = key;
        const mine = ++seq;
        try {
          const r = await P.post('/api/v1/auth/check-citizen', body);
          if (mine !== seq) return;
          if (body.nik) hint('nik', r.checks.nik.ok, r.checks.nik.message);
          if (body.no_kk) hint('no_kk', r.checks.no_kk.ok, r.checks.no_kk.message);
          if (body.name && r.checks.name.ok !== null) hint('name', r.checks.name.ok, r.checks.name.message);
          if (body.username) hint('username', r.checks.username.ok, r.checks.username.message);
          const al = $('#reg-alert');
          if (['ALREADY_REGISTERED', 'PENDING_VERIFICATION'].includes(r.status)) {
            al.className = 'alert warning'; al.innerHTML = '<div>' + P.esc(r.message) + (r.status === 'ALREADY_REGISTERED' ? ' <a class="link" href="/login">Masuk</a> · <a class="link" href="/forgot-password">Lupa password</a>' : '') + '</div>'; al.hidden = false;
          } else al.hidden = true;
        } catch (e) { last = ''; /* abaikan galat jaringan / batas kuota sementara; pendaftaran tetap divalidasi saat dikirim */ }
      }, 450);
    };
    ['nik', 'no_kk', 'name', 'username'].forEach((n) => $(`[name="${n}"]`, reg).addEventListener('input', check));

    reg.password_confirmation.addEventListener('input', () => {
      const same = reg.password_confirmation.value === reg.password.value;
      hint('password_confirmation', reg.password_confirmation.value ? same : null, reg.password_confirmation.value ? (same ? 'Password cocok.' : 'Konfirmasi tidak cocok.') : '');
    });

    reg.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = $('#reg-submit'); busy(btn, true);
      const fd = new FormData(reg);
      if (!reg.ktp_file.files.length) fd.delete('ktp_file');
      try {
        const r = await P.api('POST', '/api/v1/register', fd);
        P.ok('Pendaftaran berhasil', r.message);
        setTimeout(() => (location.href = r.redirect_url || '/dashboard'), 900);
      } catch (err) {
        busy(btn, false); showErrors(reg, err); P.fail(err);
      }
    });
  }

  /* ---------- Lupa password ---------- */
  const forgot = $('#forgot-form');
  if (forgot) {
    ['nik', 'no_kk'].forEach((n) => digits($(`[name="${n}"]`, forgot)));
    forgot.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = $('button[type=submit]', forgot); busy(btn, true);
      const al = $('#forgot-alert'); al.hidden = true;
      try {
        const r = await P.post('/api/v1/auth/forgot-password', { username: forgot.username.value, nik: forgot.nik.value, no_kk: forgot.no_kk.value });
        // Tautan reset tidak pernah dikirim ke browser: lewat email, atau diserahkan pengurus RT.
        P.ok('Permintaan diterima');
        forgot.innerHTML = `<div class="alert success">${P.icon('check-circle')}<div>${P.esc(r.message)}<br><small>Periksa kotak masuk dan folder spam (tautan email berlaku 20 menit). Tidak punya email? Temui Ketua atau Sekretaris RT dengan membawa KTP untuk menerima tautan.</small></div></div><a class="btn btn-block" href="/login">Kembali ke halaman masuk</a>`;
      } catch (err) {
        busy(btn, false); al.textContent = err.message; al.hidden = false; showErrors(forgot, err);
      }
    });
  }

  /* ---------- Reset password ---------- */
  const reset = $('#reset-form');
  if (reset) {
    reset.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = $('button[type=submit]', reset); busy(btn, true);
      const al = $('#reset-alert'); al.hidden = true;
      try {
        const r = await P.post('/api/v1/auth/reset-password', { token: reset.dataset.token, password: reset.password.value, password_confirmation: reset.password_confirmation.value });
        P.ok('Password diubah', r.message);
        setTimeout(() => (location.href = r.redirect_url || '/login'), 900);
      } catch (err) {
        busy(btn, false); al.textContent = err.message; al.hidden = false; showErrors(reset, err);
      }
    });
  }
})();
