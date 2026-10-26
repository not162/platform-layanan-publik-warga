/* Dashboard — ringkasan real-time per peran */
(function () {
  'use strict';
  const { $, esc, icon, rp } = P;
  const tones = { blue: 'tone-blue', green: 'tone-green', amber: 'tone-amber', rose: 'tone-rose' };
  let last = '';
  const prev = {};

  function render(d) {
    $('#pending-banner').hidden = !d.pending_verification;
    $('#stats').innerHTML = d.cards.length ? d.cards.map((c) => `
      <a class="stat" href="${esc(c.href)}" data-key="${esc(c.label)}">
        <span class="action-icon ${tones[c.tone] || 'tone-blue'}">${icon(c.icon)}</span>
        <div><div class="num">${c.money ? rp(c.value) : Number(c.value).toLocaleString('id-ID')}</div><div class="lbl">${esc(c.label)}</div></div>
      </a>`).join('') : '<div class="card muted">Belum ada ringkasan untuk peran ini.</div>';

    d.cards.forEach((c) => {
      if (prev[c.label] !== undefined && prev[c.label] !== c.value) document.querySelector(`[data-key="${CSS.escape(c.label)}"]`)?.classList.add('flash');
      prev[c.label] = c.value;
    });

    $('#recent').innerHTML = d.recent.length ? d.recent.map((r) => `
      <div class="row"><div><b>${esc(r.title)}</b><br><small>${esc(r.meta)}</small></div>
      <div style="text-align:right">${r.status ? P.badge(r.status) : ''}<br><small>${P.ago(r.at)}</small></div></div>`).join('')
      : `<div class="empty">${icon('inbox')}<div>Belum ada aktivitas.</div></div>`;
  }

  async function load() {
    const res = await P.get('/api/v1/dashboard/summary');
    $('#live-time').textContent = '· ' + new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const sig = JSON.stringify(res.data.cards) + JSON.stringify(res.data.recent) + res.data.pending_verification;
    if (sig !== last) { last = sig; render(res.data); }
  }

  P.poll(load, 8000);
  document.addEventListener('prisma:notifications', (e) => e.detail.fresh.length && load());
})();
