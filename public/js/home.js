/* Beranda publik — data real-time dari GET /api/v1/portal */
(function () {
  'use strict';
  const { $, esc, icon, rp, date } = P;
  let last = '';

  const empty = (t) => `<div class="empty">${icon('inbox')}<div>${t}</div></div>`;
  const catTone = { penting: 'rose', kegiatan: 'violet', keuangan: 'green', info: 'blue' };

  function render(d) {
    $('#letter-types').innerHTML = d.letter_types.length
      ? d.letter_types.map((t) => `<a class="action-card" href="/app/my-letters"><span class="action-icon tone-blue">${icon('file')}</span><div><b>${esc(t.nama_surat)}</b><div class="muted" style="font-size:.78rem">${esc(t.kode_surat)}</div></div></a>`).join('')
      : empty('Belum ada jenis surat.');

    $('#fin-balance').textContent = rp(d.finance.balance);
    $('#fin-income').textContent = rp(d.finance.income);
    $('#fin-expense').textContent = rp(d.finance.expense);
    $('#fin-recent').innerHTML = d.finance.recent.length
      ? d.finance.recent.map((t) => `<div class="kv"><span><b>${esc(t.category)}</b><br><small class="muted">${esc(t.description || '')} · ${date(t.date)}</small></span><span class="money ${t.type === 'income' ? 'in' : 'out'}">${t.type === 'income' ? '+' : '−'} ${rp(t.amount)}</span></div>`).join('')
      : empty('Belum ada transaksi yang dipublikasikan.');

    $('#announcements').innerHTML = d.announcements.length
      ? d.announcements.map((a) => `<article class="news-item"><div class="meta">${a.is_pinned ? `<span class="badge amber">${icon('pin')} Dipin</span>` : ''}<span class="badge ${catTone[a.category] || 'blue'}">${esc(String(a.category).toUpperCase())}</span><span>${a.published_at ? date(a.published_at) : ''}</span></div><h3>${esc(a.title)}</h3><p>${esc(a.content)}</p></article>`).join('')
      : empty('Belum ada pengumuman.');

    $('#events').innerHTML = d.events.length
      ? d.events.map((e) => `<article class="news-item"><div class="meta"><span class="badge violet">${icon('calendar')} ${date(e.event_date)}${e.start_time ? ' · ' + esc(e.start_time) : ''}</span></div><h3>${esc(e.title)}</h3><p>${esc(e.description || '')}</p><div class="meta" style="margin:.5rem 0 0">${icon('pin')} ${esc(e.location)}</div></article>`).join('')
      : empty('Belum ada agenda mendatang.');

    $('#officers').innerHTML = d.officers.length
      ? d.officers.map((o) => `<div class="kv"><span><b>${esc(o.name)}</b><br><small class="muted">${esc(o.position)}</small></span>${o.phone ? `<a class="link" href="tel:${esc(o.phone)}">${esc(o.phone)}</a>` : ''}</div>`).join('')
      : empty('Data pengurus belum tersedia.');

    $('#schedules').innerHTML = d.schedules.length
      ? d.schedules.map((s) => `<div class="kv"><span><b>${esc(s.day)} · ${esc(s.shift)}</b><br><small class="muted">${esc((s.officers || []).join(', '))}</small></span><span class="muted">${esc(s.pos)}</span></div>`).join('')
      : empty('Jadwal ronda belum tersedia.');

    $('#contacts').innerHTML = d.contacts.map((c) => `<div class="kv"><span><b>${esc(c.name)}</b><br><small class="muted">${esc(c.category)}</small></span><a class="link" href="tel:${esc(c.phone_number)}">${esc(c.phone_number)}</a></div>`).join('');
  }

  async function load() {
    const res = await P.get('/api/v1/portal');
    const sig = JSON.stringify(res.data);
    $('#live-time').textContent = 'diperbarui ' + new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    if (sig !== last) { last = sig; render(res.data); }
  }

  P.poll(load, 20000);
})();
