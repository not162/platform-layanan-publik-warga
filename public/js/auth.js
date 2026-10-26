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

    let timer; let seq = 0;
    const check = () => {
      clearTimeout(timer);
      timer = setTimeout(async () => {
        const body = { nik: reg.nik.value, no_kk: reg.no_kk.value, name: reg.elements.name.value, username: reg.username.value };
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
            al.className = 'alert warning'; al.innerHTML = '<div>' + r.message + (r.status === 'ALREADY_REGISTERED' ? ' <a class="link" href="/login">Masuk</a> · <a class="link" href="/forgot-password">Lupa password</a>' : '') + '</div>'; al.hidden = false;
          } else al.hidden = true;
        } catch (e) { /* abaikan galat jaringan sementara */ }
      }, 350);
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
        P.ok('Identitas terverifikasi', r.message);
        setTimeout(() => (location.href = r.reset_url), 700);
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
