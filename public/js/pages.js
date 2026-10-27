/* Mesin CRUD real-time: tabel + modal form + aksi khusus, semuanya terhubung ke /api/v1/manage/{resource} (MySQL) */
(function () {
  'use strict';
  const { $, $$, esc, icon, rp, date, badge } = P;

  /* ---------- Opsi & helper ---------- */
  const rolesOpt = async () => (await P.get('/api/v1/roles')).data.map((r) => [r.value, r.label]);
  const letterTypesOpt = async () => (await P.get('/api/v1/portal')).data.letter_types.map((t) => [t.id, t.nama_surat]);
  const yn = (v, a = 'Aktif', b = 'Nonaktif') => `<span class="badge ${v ? 'green' : 'gray'}">${v ? a : b}</span>`;
  const sub = (a, b) => `<b>${esc(a)}</b><small class="muted">${esc(b || '')}</small>`;
  const ROLE_TONE = { SUPERADMIN: 'rose', ADMIN: 'violet', KETUA_RT: 'blue', SEKRETARIS: 'blue', BENDAHARA: 'green', PETUGAS_KEAMANAN: 'amber', WARGA: 'gray' };
  const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'].map((d) => [d, d]);
  const COMPLAINT_STATUS = ['submitted', 'reviewed', 'processing', 'resolved', 'closed', 'rejected'].map((s) => [s, P.statusLabel(s)]);
  const SECURITY_STATUS = ['submitted', 'reviewed', 'assigned', 'processing', 'resolved', 'closed'].map((s) => [s, P.statusLabel(s)]);
  const dueBadge = (r) => `<span class="badge ${({ paid: 'green', partial: 'blue', overdue: 'rose', unpaid: 'amber' }[r.status] || 'gray')}">${({ paid: 'Lunas', partial: 'Sebagian', overdue: 'Terlambat', unpaid: 'Belum bayar' }[r.status] || esc(r.status))}</span>`;
  const docLinks = (r) => {
    const out = (r.attachments || []).map((a) => `<a class="link" target="_blank" rel="noopener" href="${esc(a.url)}">${icon('file')} ${esc(a.name)}</a>`);
    if (r.can_download) out.push(`<a class="link" target="_blank" rel="noopener" href="${esc(r.download_url)}">${icon('download')} Surat PDF</a>`, `<a class="link" target="_blank" rel="noopener" href="${esc(r.preview_url)}">${icon('eye')} Cetak</a>`);
    return out.length ? `<span style="display:grid;gap:.2rem;text-align:left">${out.join('')}</span>` : '—';
  };
  const PRIORITY = [['rendah', 'Rendah'], ['sedang', 'Sedang'], ['tinggi', 'Tinggi'], ['darurat', 'Darurat']];

  /* ---------- Definisi halaman ---------- */
  const PAGES = {
    users: {
      resource: 'users', noun: 'pengguna',
      filters: [{ key: 'role', label: 'Semua peran', options: rolesOpt }, { key: 'is_active', label: 'Semua status', options: [['1', 'Aktif'], ['0', 'Nonaktif']] }],
      columns: [
        { label: 'Pengguna', primary: true, render: (r) => sub(r.name, '@' + r.username + (r.email ? ' · ' + r.email : '')) },
        { label: 'Peran', render: (r) => `<span class="badge ${ROLE_TONE[r.role] || 'gray'}">${esc(r.role_label)}</span>` },
        { label: 'Akses', render: (r) => `<span class="badge gray">${esc(r.tier)}</span>` },
        { label: 'Status', render: (r) => yn(r.is_active) },
        { label: 'Login terakhir', render: (r) => (r.last_login_at ? P.ago(r.last_login_at) : 'Belum pernah') },
      ],
      fields: (mode) => [
        { name: 'name', label: 'Nama lengkap', required: true },
        { name: 'username', label: 'Username', required: true, hint: 'Huruf kecil, angka, titik, garis bawah (4–30).' },
        { name: 'email', label: 'Email (opsional)', type: 'email' },
        { name: 'role', label: 'Peran', type: 'select', options: rolesOpt, required: true },
        { name: 'password', label: mode === 'create' ? 'Password' : 'Password baru (kosongkan jika tidak diubah)', type: 'password', required: mode === 'create' },
        { name: 'is_active', label: 'Akun aktif', type: 'checkbox', default: true },
      ],
    },

    citizens: {
      resource: 'citizens', exportable: true, noun: 'warga',
      filters: [{ key: 'status_warga', label: 'Semua status', options: [['tetap', 'Warga tetap'], ['pending_verification', 'Menunggu verifikasi'], ['pindah', 'Nonaktif']] }],
      columns: [
        { label: 'Warga', primary: true, render: (r) => sub(r.full_name, r.username ? '@' + r.username : 'Belum punya akun') },
        { label: 'NIK / KK', render: (r) => `<small>${esc(r.nik_masked || '—')}<br>${esc(r.no_kk_masked || '—')}</small>` },
        { label: 'Telepon', render: (r) => esc(r.phone || '—') },
        { label: 'Status', render: (r) => badge(r.status_warga) + (r.ktp_url ? `<br><a class="link" target="_blank" rel="noopener" href="${esc(r.ktp_url)}">${icon('file')} Lihat KTP</a>` : '') },
      ],
      fields: (mode) => [
        ...(mode === 'create' ? [{ name: 'nik', label: 'NIK (16 digit)', required: true, maxlength: 16, inputmode: 'numeric' }, { name: 'no_kk', label: 'No. KK (16 digit)', required: true, maxlength: 16, inputmode: 'numeric' }] : []),
        { name: 'full_name', label: 'Nama lengkap', required: true },
        { name: 'gender', label: 'Jenis kelamin', type: 'select', options: [['Laki-laki', 'Laki-laki'], ['Perempuan', 'Perempuan']] },
        { name: 'place_of_birth', label: 'Tempat lahir' },
        { name: 'date_of_birth', label: 'Tanggal lahir', type: 'date' },
        { name: 'occupation', label: 'Pekerjaan' },
        { name: 'phone', label: 'No. HP' },
        { name: 'status_warga', label: 'Status', type: 'select', options: [['tetap', 'Warga tetap'], ['pending_verification', 'Menunggu verifikasi'], ['pindah', 'Nonaktif']], default: 'tetap' },
      ],
      actions: [
        { name: 'verify', label: 'Verifikasi', icon: 'check', tone: 'success', when: (r) => r.can_verify, confirm: 'Verifikasi data warga ini sebagai warga tetap?' },
        { name: 'reject', label: 'Tolak', icon: 'x', tone: 'danger', when: (r) => r.can_verify, ask: 'Alasan penolakan', askKey: 'reason' },
      ],
    },

    announcements: {
      resource: 'announcements', noun: 'pengumuman',
      columns: [
        { label: 'Judul', primary: true, render: (r) => sub(r.title, r.content.slice(0, 70) + (r.content.length > 70 ? '…' : '')) },
        { label: 'Kategori', render: (r) => `<span class="badge blue">${esc(r.category)}</span>` },
        { label: 'Tayang', render: (r) => yn(r.is_published, 'Tayang', 'Draft') + (r.is_pinned ? ' <span class="badge amber">Dipin</span>' : '') },
        { label: 'Diperbarui', render: (r) => P.ago(r.updated_at) },
      ],
      fields: () => [
        { name: 'title', label: 'Judul', required: true },
        { name: 'category', label: 'Kategori', type: 'select', options: [['info', 'Info'], ['kegiatan', 'Kegiatan'], ['penting', 'Penting'], ['keuangan', 'Keuangan']], required: true, default: 'info' },
        { name: 'content', label: 'Isi pengumuman', type: 'textarea', required: true },
        { name: 'is_pinned', label: 'Pin di beranda', type: 'checkbox' },
        { name: 'is_published', label: 'Tayangkan (kirim notifikasi ke semua pengguna)', type: 'checkbox', default: true },
      ],
    },

    events: {
      resource: 'events', noun: 'agenda',
      columns: [
        { label: 'Kegiatan', primary: true, render: (r) => sub(r.title, r.location) },
        { label: 'Tanggal', render: (r) => date(r.event_date) + (r.start_time ? ' · ' + r.start_time : '') },
        { label: 'Status', render: (r) => yn(r.is_published, 'Tayang', 'Draft') },
      ],
      fields: () => [
        { name: 'title', label: 'Nama kegiatan', required: true },
        { name: 'location', label: 'Lokasi', required: true },
        { name: 'event_date', label: 'Tanggal', type: 'date', required: true },
        { name: 'start_time', label: 'Jam mulai', type: 'time' },
        { name: 'end_time', label: 'Jam selesai', type: 'time' },
        { name: 'description', label: 'Deskripsi', type: 'textarea' },
        { name: 'is_published', label: 'Tayangkan', type: 'checkbox', default: true },
      ],
    },

    officers: {
      resource: 'officers', noun: 'pengurus',
      columns: [
        { label: 'Pengurus', primary: true, render: (r) => sub(r.name, r.position) },
        { label: 'Telepon', render: (r) => esc(r.phone || '—') },
        { label: 'Urutan', render: (r) => r.order_index },
        { label: 'Status', render: (r) => yn(r.is_active) },
      ],
      fields: () => [
        { name: 'name', label: 'Nama', required: true }, { name: 'position', label: 'Jabatan', required: true },
        { name: 'phone', label: 'No. telepon' }, { name: 'order_index', label: 'Urutan tampil', type: 'number', default: 0 },
        { name: 'is_active', label: 'Aktif', type: 'checkbox', default: true },
      ],
    },

    schedules: {
      resource: 'schedules', noun: 'jadwal ronda',
      columns: [
        { label: 'Hari / Shift', primary: true, render: (r) => sub(r.day_of_week, r.shift_name) },
        { label: 'Petugas', render: (r) => esc(r.officer_names) },
        { label: 'Pos', render: (r) => esc(r.pos_location) },
        { label: 'Status', render: (r) => yn(r.is_active) },
      ],
      fields: () => [
        { name: 'day_of_week', label: 'Hari', type: 'select', options: DAYS, required: true },
        { name: 'shift_name', label: 'Shift (mis. Malam 22.00–04.00)', required: true },
        { name: 'officer_names', label: 'Nama petugas (pisahkan dengan koma)', required: true },
        { name: 'pos_location', label: 'Lokasi pos', required: true },
        { name: 'notes', label: 'Catatan', type: 'textarea' },
        { name: 'is_active', label: 'Aktif', type: 'checkbox', default: true },
      ],
    },

    contacts: {
      resource: 'contacts', noun: 'kontak darurat',
      columns: [
        { label: 'Kontak', primary: true, render: (r) => sub(r.name, r.category) },
        { label: 'Telepon', render: (r) => `<a class="link" href="tel:${esc(r.phone_number)}">${esc(r.phone_number)}</a>` },
        { label: 'Status', render: (r) => yn(r.is_active) },
      ],
      fields: () => [
        { name: 'name', label: 'Nama', required: true }, { name: 'category', label: 'Kategori (Polisi, Damkar, Ambulans…)', required: true },
        { name: 'phone_number', label: 'No. telepon', required: true }, { name: 'description', label: 'Keterangan' },
        { name: 'order_index', label: 'Urutan', type: 'number', default: 0 }, { name: 'is_active', label: 'Aktif', type: 'checkbox', default: true },
      ],
    },

    /* ----- Surat ----- */
    letters: {
      resource: 'letters', exportable: true, noun: 'surat', readonly: true,
      filters: [{ key: 'status', label: 'Semua status', options: ['submitted', 'verified', 'approved', 'completed', 'rejected', 'draft'].map((s) => [s, P.statusLabel(s)]) }],
      columns: [
        { label: 'Tiket', primary: true, render: (r) => sub(r.ticket_number, r.letter_number || 'Belum bernomor') },
        { label: 'Pemohon', render: (r) => esc(r.applicant || '—') },
        { label: 'Jenis & keperluan', render: (r) => `<b>${esc(r.type)}</b><small class="muted">${esc(r.keperluan || '')}</small>` },
        { label: 'Status', render: (r) => badge(r.status) + (r.rejection_reason ? `<br><small class="muted">${esc(r.rejection_reason)}</small>` : '') },
        { label: 'Dokumen', render: docLinks },
        { label: 'Update', render: (r) => P.ago(r.updated_at) },
      ],
      actions: [
        { name: 'verify', label: 'Verifikasi', icon: 'check', tone: 'success', when: (r) => r.can_verify, confirm: 'Berkas lengkap dan valid? Surat akan diteruskan ke Ketua RT.' },
        { name: 'approve', label: 'Sahkan', icon: 'check-circle', tone: 'success', when: (r) => r.can_approve, confirm: 'Sahkan surat ini? Nomor surat dan QR verifikasi akan diterbitkan.' },
        { name: 'complete', label: 'Selesaikan', icon: 'check', when: (r) => r.can_complete, confirm: 'Tandai surat selesai & siap diambil?' },
        { name: 'reject', label: 'Tolak', icon: 'x', tone: 'danger', when: (r) => r.can_reject, ask: 'Alasan penolakan', askKey: 'reason' },
      ],
    },
    'my-letters': {
      resource: 'letters', mine: true, noun: 'permohonan surat',
      columns: [
        { label: 'Tiket', primary: true, render: (r) => sub(r.ticket_number, r.letter_number || 'Belum bernomor') },
        { label: 'Jenis & keperluan', render: (r) => `<b>${esc(r.type)}</b><small class="muted">${esc(r.keperluan || '')}</small>` },
        { label: 'Status', render: (r) => badge(r.status) + (r.rejection_reason ? `<br><small class="muted">${esc(r.rejection_reason)}</small>` : '') },
        { label: 'Dokumen', render: docLinks },
        { label: 'Verifikasi', render: (r) => (r.verification_url ? `<a class="link" target="_blank" rel="noopener" href="${esc(r.verification_url)}">Cek keaslian</a>` : '—') },
        { label: 'Update', render: (r) => P.ago(r.updated_at) },
      ],
      fields: () => [
        { name: 'jenis_surat_id', label: 'Jenis surat', type: 'select', options: letterTypesOpt, required: true },
        { name: 'keperluan', label: 'Keperluan', type: 'textarea', required: true },
        { name: 'attachments', label: 'Lampiran (KTP/KK/dokumen pendukung)', type: 'files', hint: 'PDF/JPG/PNG, maks. 1 MB per berkas, hingga 5 berkas.' },
        { name: 'submit', label: 'Langsung ajukan ke Sekretaris', type: 'checkbox', default: true, createOnly: true },
      ],
      actions: [{ name: 'submit', label: 'Ajukan', icon: 'send', tone: 'success', when: (r) => r.can_submit, confirm: 'Ajukan permohonan ke Sekretaris RT?' }],
    },

    /* ----- Pengaduan ----- */
    complaints: {
      resource: 'complaints', exportable: true, noun: 'pengaduan',
      filters: [{ key: 'status', label: 'Semua status', options: COMPLAINT_STATUS }, { key: 'priority', label: 'Semua prioritas', options: PRIORITY }],
      columns: [
        { label: 'Pengaduan', primary: true, render: (r) => sub(r.title, r.ticket_number + ' · ' + r.kategori) },
        { label: 'Pelapor', render: (r) => esc(r.reporter || '—') },
        { label: 'Prioritas', render: (r) => badge(r.priority) },
        { label: 'Status', render: (r) => badge(r.status) },
        { label: 'Update', render: (r) => P.ago(r.updated_at) },
      ],
      editLabel: 'Tanggapi',
      fields: (mode) => [
        { name: 'status', label: 'Status', type: 'select', options: COMPLAINT_STATUS, required: true },
        { name: 'priority', label: 'Prioritas', type: 'select', options: PRIORITY },
        { name: 'admin_response', label: 'Tanggapan untuk warga', type: 'textarea' },
      ],
      detail: (r) => `<div class="alert info" style="display:block"><b>${esc(r.title)}</b><br>${esc(r.description)}${r.lokasi ? `<br><small>Lokasi: ${esc(r.lokasi)}</small>` : ''}${r.photo_url ? `<br><a class="link" target="_blank" rel="noopener" href="${esc(r.photo_url)}">${icon('eye')} Lihat foto bukti</a>` : ''}</div>`,
    },
    'my-complaints': {
      resource: 'complaints', mine: true, noun: 'pengaduan',
      columns: [
        { label: 'Pengaduan', primary: true, render: (r) => sub(r.title, r.ticket_number + ' · ' + r.kategori) },
        { label: 'Prioritas', render: (r) => badge(r.priority) },
        { label: 'Status', render: (r) => badge(r.status) },
        { label: 'Tanggapan', render: (r) => esc(r.admin_response || '—') + (r.photo_url ? `<br><a class="link" target="_blank" rel="noopener" href="${esc(r.photo_url)}">${icon('eye')} Foto</a>` : '') },
        { label: 'Update', render: (r) => P.ago(r.updated_at) },
      ],
      fields: () => [
        { name: 'title', label: 'Judul pengaduan', required: true },
        { name: 'kategori', label: 'Kategori', type: 'select', required: true, options: [['Kebersihan', 'Kebersihan'], ['Fasilitas Umum', 'Fasilitas Umum'], ['Keamanan', 'Keamanan'], ['Sosial', 'Sosial'], ['Lainnya', 'Lainnya']] },
        { name: 'lokasi', label: 'Lokasi kejadian' },
        { name: 'description', label: 'Uraian masalah', type: 'textarea', required: true },
        { name: 'priority', label: 'Prioritas', type: 'select', options: PRIORITY, default: 'sedang' },
        { name: 'attachment', label: 'Foto bukti (opsional)', type: 'file', createOnly: true, hint: 'JPG/PNG/WebP, maks. 2 MB.' },
        { name: 'is_anonymous', label: 'Kirim sebagai anonim', type: 'checkbox' },
      ],
    },

    /* ----- Keamanan ----- */
    security: {
      resource: 'security-reports', noun: 'laporan keamanan',
      filters: [{ key: 'status', label: 'Semua status', options: SECURITY_STATUS }],
      columns: [
        { label: 'Laporan', primary: true, render: (r) => sub(r.title, r.ticket_number + ' · ' + r.category) },
        { label: 'Lokasi', render: (r) => esc(r.location) },
        { label: 'Tingkat', render: (r) => badge(r.severity) },
        { label: 'Status', render: (r) => badge(r.status) },
        { label: 'Update', render: (r) => P.ago(r.updated_at) },
      ],
      editLabel: 'Tindak lanjut',
      fields: () => [
        { name: 'status', label: 'Status', type: 'select', options: SECURITY_STATUS, required: true },
        { name: 'severity', label: 'Tingkat', type: 'select', options: [['low', 'Rendah'], ['medium', 'Sedang'], ['high', 'Tinggi'], ['emergency', 'Darurat']] },
        { name: 'resolution', label: 'Tindakan / penyelesaian', type: 'textarea' },
      ],
      detail: (r) => `<div class="alert info" style="display:block"><b>${esc(r.title)}</b><br>${esc(r.description)}<br><small>${esc(r.location)} · ${date(r.incident_at, true)}</small></div>`,
    },
    'my-security': {
      resource: 'security-reports', mine: true, noun: 'laporan keamanan',
      columns: [
        { label: 'Laporan', primary: true, render: (r) => sub(r.title, r.ticket_number + ' · ' + r.category) },
        { label: 'Lokasi', render: (r) => esc(r.location) },
        { label: 'Tingkat', render: (r) => badge(r.severity) },
        { label: 'Status', render: (r) => badge(r.status) },
        { label: 'Tindak lanjut', render: (r) => esc(r.resolution || '—') },
      ],
      fields: () => [
        { name: 'title', label: 'Judul laporan', required: true },
        { name: 'category', label: 'Kategori', type: 'select', required: true, options: [['Pencurian', 'Pencurian'], ['Orang mencurigakan', 'Orang mencurigakan'], ['Kebakaran', 'Kebakaran'], ['Keributan', 'Keributan'], ['Lainnya', 'Lainnya']] },
        { name: 'severity', label: 'Tingkat kegawatan', type: 'select', options: [['low', 'Rendah'], ['medium', 'Sedang'], ['high', 'Tinggi'], ['emergency', 'Darurat']], default: 'medium' },
        { name: 'location', label: 'Lokasi', required: true },
        { name: 'incident_at', label: 'Waktu kejadian', type: 'datetime-local' },
        { name: 'description', label: 'Kronologi', type: 'textarea', required: true },
      ],
    },

    /* ----- Keuangan ----- */
    finance: {
      resource: 'finance', exportable: true, noun: 'transaksi kas',
      filters: [{ key: 'status', label: 'Semua status', options: [['draft', 'Draft'], ['published', 'Terbit'], ['reversed', 'Dikoreksi']] }, { key: 'type', label: 'Semua jenis', options: [['income', 'Pemasukan'], ['expense', 'Pengeluaran']] }],
      columns: [
        { label: 'Transaksi', primary: true, render: (r) => sub(r.category, r.transaction_number + (r.description ? ' · ' + r.description : '')) },
        { label: 'Tanggal', render: (r) => date(r.transaction_date) },
        { label: 'Nominal', render: (r) => `<span class="money ${r.type === 'income' ? 'in' : 'out'}">${r.type === 'income' ? '+' : '−'} ${rp(r.amount)}</span>` },
        { label: 'Status', render: (r) => badge(r.status) + (r.reversal_reason ? `<br><small class="muted">${esc(r.reversal_reason)}</small>` : '') },
      ],
      fields: () => [
        { name: 'type', label: 'Jenis', type: 'select', options: [['income', 'Pemasukan'], ['expense', 'Pengeluaran']], required: true },
        { name: 'category', label: 'Kategori (Iuran, Kebersihan, Keamanan…)', required: true },
        { name: 'amount', label: 'Nominal (Rp)', type: 'number', required: true, min: 1 },
        { name: 'transaction_date', label: 'Tanggal', type: 'date', required: true, default: new Date().toISOString().slice(0, 10) },
        { name: 'description', label: 'Keterangan', type: 'textarea' },
      ],
      actions: [
        { name: 'publish', label: 'Terbitkan', icon: 'check', tone: 'success', when: (r) => r.can_publish, confirm: 'Terbitkan transaksi? Setelah terbit data tidak bisa diubah, hanya dikoreksi.' },
        { name: 'reverse', label: 'Koreksi', icon: 'refresh', tone: 'danger', when: (r) => r.can_reverse, ask: 'Alasan koreksi', askKey: 'reason' },
      ],
    },


    /* ----- Iuran ----- */
    dues: {
      resource: 'dues', exportable: true, noun: 'tagihan iuran',
      createLabel: 'Buat tagihan bulanan',
      filters: [{ key: 'period_month', label: 'Semua bulan', options: Array.from({ length: 12 }, (_, i) => [String(i + 1), new Date(2000, i, 1).toLocaleString('id-ID', { month: 'long' })]) }],
      columns: [
        { label: 'Warga', primary: true, render: (r) => sub(r.citizen || '—', r.period) },
        { label: 'Tagihan', render: (r) => rp(r.amount_due) },
        { label: 'Terbayar', render: (r) => rp(r.amount_paid) + (r.remaining ? `<small class="muted">Sisa ${rp(r.remaining)}</small>` : '') },
        { label: 'Status', render: dueBadge },
        { label: 'Kuitansi', render: (r) => esc((r.receipts || []).join(', ') || '—') },
      ],
      fields: () => [
        { name: 'period_year', label: 'Tahun', type: 'number', required: true, default: new Date().getFullYear() },
        { name: 'period_month', label: 'Bulan (1-12)', type: 'number', required: true, min: 1, default: new Date().getMonth() + 1 },
        { name: 'amount_due_idr', label: 'Iuran per warga (Rp)', type: 'number', required: true, min: 1000, default: 50000 },
        { name: 'notes', label: 'Catatan' },
      ],
      actions: [{ name: 'pay', label: 'Catat bayar', icon: 'check', tone: 'success', when: (r) => r.can_pay,
        form: (r) => [
          { name: 'amount', label: 'Nominal dibayar (Rp)', type: 'number', required: true, default: r.remaining, min: 1 },
          { name: 'payment_method', label: 'Metode', type: 'select', options: [['cash', 'Tunai'], ['transfer', 'Transfer'], ['qris', 'QRIS']], default: 'cash' },
          { name: 'notes', label: 'Catatan' },
        ] }],
    },
    'my-dues': {
      resource: 'dues', mine: true, noun: 'iuran', readonly: true,
      columns: [
        { label: 'Periode', primary: true, render: (r) => sub(r.period, 'Jatuh tempo ' + date(r.due_date)) },
        { label: 'Tagihan', render: (r) => rp(r.amount_due) },
        { label: 'Terbayar', render: (r) => rp(r.amount_paid) + (r.remaining ? `<small class="muted">Sisa ${rp(r.remaining)}</small>` : '') },
        { label: 'Status', render: dueBadge },
        { label: 'Kuitansi', render: (r) => esc((r.receipts || []).join(', ') || '—') },
      ],
    },

    audit: {
      resource: 'audit-logs', exportable: true, noun: 'log', readonly: true,
      columns: [
        { label: 'Aksi', primary: true, render: (r) => sub(r.action, r.entity) },
        { label: 'Pengguna', render: (r) => esc(r.user) },
        { label: 'IP', render: (r) => esc(r.ip_address || '—') },
        { label: 'Waktu', render: (r) => date(r.created_at, true) },
      ],
    },
  };

  /* ---------- Engine ---------- */
  const root = $('#crud');
  if (!root) return;
  const cfg = PAGES[root.dataset.page];
  if (!cfg) { root.innerHTML = '<div class="empty">Halaman tidak dikenal.</div>'; return; }

  const canCreate = root.dataset.canCreate === '1' && !cfg.readonly;
  const base = `/api/v1/manage/${cfg.resource}`;
  const state = { page: 1, q: '', filters: {}, rows: [], sig: '', meta: null };
  const hasRowActions = (r) => r.can_edit || r.can_delete || (cfg.actions || []).some((a) => a.when(r));

  root.innerHTML = `
    <div class="page-head">
      <div><h2>${esc(root.dataset.title)}</h2><p><span class="live-dot"></span>Tersinkron langsung dengan database · <span id="sync-time" class="muted">memuat…</span></p></div>
      ${canCreate ? `<button class="btn btn-primary" id="btn-add">${icon('plus')} ${esc(cfg.createLabel || 'Tambah ' + cfg.noun)}</button>` : ''}
    </div>
    <div class="toolbar">
      <div class="search">${icon('search')}<input class="input" id="q" type="search" placeholder="Cari ${esc(cfg.noun)}…" aria-label="Cari"></div>
      ${(cfg.filters || []).map((f) => `<select class="input" data-filter="${f.key}" aria-label="${esc(f.label)}"><option value="">${esc(f.label)}</option></select>`).join('')}
      ${cfg.exportable ? `<a class="btn" id="btn-export" href="#" download>${icon('download')} Ekspor CSV</a>` : ''}
      <button class="btn btn-icon" id="btn-refresh" aria-label="Muat ulang">${icon('refresh')}</button>
    </div>
    <div class="table-wrap">
      <table class="tbl"><thead><tr>${cfg.columns.map((c) => `<th>${esc(c.label)}</th>`).join('')}<th></th></tr></thead><tbody id="tbody"></tbody></table>
      <div class="pager"><span id="pager-info"></span><span style="display:flex;gap:.4rem"><button class="btn btn-sm" id="prev">‹ Sebelumnya</button><button class="btn btn-sm" id="next">Berikutnya ›</button></span></div>
    </div>`;

  (cfg.filters || []).forEach(async (f) => {
    const sel = $(`[data-filter="${f.key}"]`, root);
    const opts = typeof f.options === 'function' ? await f.options() : f.options;
    opts.forEach(([v, l]) => sel.insertAdjacentHTML('beforeend', `<option value="${esc(v)}">${esc(l)}</option>`));
    sel.addEventListener('change', () => { state.filters[f.key] = sel.value; state.page = 1; load(true); });
  });

  let st; $('#q', root).addEventListener('input', (e) => { clearTimeout(st); st = setTimeout(() => { state.q = e.target.value.trim(); state.page = 1; load(true); }, 300); });
  $('#btn-refresh', root).onclick = () => load(true);
  $('#btn-export', root)?.addEventListener('click', (e) => { e.currentTarget.href = url().replace(base + '?', base + '/export?'); });
  $('#prev', root).onclick = () => { if (state.page > 1) { state.page--; load(true); } };
  $('#next', root).onclick = () => { if (state.meta && state.page < state.meta.last_page) { state.page++; load(true); } };
  $('#btn-add', root)?.addEventListener('click', () => openForm('create'));

  function url() {
    const p = new URLSearchParams({ page: state.page, per_page: 10 });
    if (cfg.mine) p.set('mine', '1');
    if (state.q) p.set('q', state.q);
    Object.entries(state.filters).forEach(([k, v]) => v !== '' && v != null && p.set(k, v));
    return `${base}?${p}`;
  }

  function rowHtml(r) {
    const acts = [];
    (cfg.actions || []).forEach((a) => a.when(r) && acts.push(`<button class="btn btn-sm ${a.tone ? 'btn-' + a.tone : ''}" data-act="${a.name}" data-id="${r.id}">${a.icon ? icon(a.icon) : ''} ${esc(a.label)}</button>`));
    if (r.can_edit) acts.push(`<button class="btn btn-sm" data-edit="${r.id}">${icon('edit')} ${esc(cfg.editLabel || 'Ubah')}</button>`);
    if (r.can_delete) acts.push(`<button class="btn btn-sm btn-danger" data-del="${r.id}" aria-label="Hapus">${icon('trash')}</button>`);
    return `<tr data-row="${r.id}">${cfg.columns.map((c) => `<td data-label="${esc(c.label)}" class="${c.primary ? 'primary-cell' : ''}">${c.render(r)}</td>`).join('')}<td class="actions-cell"><div class="row-actions">${acts.join('')}</div></td></tr>`;
  }

  function render(changedIds) {
    const tb = $('#tbody', root);
    tb.innerHTML = state.rows.length ? state.rows.map(rowHtml).join('')
      : `<tr><td colspan="${cfg.columns.length + 1}"><div class="empty">${icon('inbox')}<div>Belum ada data ${esc(cfg.noun)}.</div></div></td></tr>`;
    (changedIds || []).forEach((id) => $(`[data-row="${id}"]`, tb)?.classList.add('flash'));
    const m = state.meta;
    $('#pager-info', root).textContent = m ? `${m.total} data · halaman ${m.current_page}/${m.last_page}` : '';
    $('#prev', root).disabled = !m || m.current_page <= 1;
    $('#next', root).disabled = !m || m.current_page >= m.last_page;
  }

  async function load(force) {
    try {
      if (force) $('#tbody', root).style.opacity = '.55';
      const res = await P.get(url());
      $('#tbody', root).style.opacity = '';
      $('#sync-time', root).textContent = 'diperbarui ' + new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
      const sig = JSON.stringify(res.data);
      if (sig === state.sig && !force) return;
      const old = new Map(state.rows.map((r) => [r.id, JSON.stringify(r)]));
      const changed = res.data.filter((r) => old.size && old.get(r.id) !== JSON.stringify(r)).map((r) => r.id);
      state.rows = res.data; state.meta = res.meta; state.sig = sig;
      render(changed);
    } catch (e) { $('#tbody', root).style.opacity = ''; if (force) P.fail(e); }
  }

  /* ----- aksi baris ----- */
  root.addEventListener('click', async (ev) => {
    const edit = ev.target.closest('[data-edit]'); const del = ev.target.closest('[data-del]'); const act = ev.target.closest('[data-act]');
    if (edit) openForm('edit', state.rows.find((r) => r.id == edit.dataset.edit));
    if (del) {
      const row = state.rows.find((r) => r.id == del.dataset.del);
      if (await P.confirm(`Hapus ${cfg.noun} ini? Tindakan tidak dapat dibatalkan.`, { confirmText: 'Hapus' })) {
        try { await P.del(`${base}/${row.id}`); P.ok('Terhapus', `${cfg.noun} berhasil dihapus.`); load(true); } catch (e) { P.fail(e); }
      }
    }
    if (act) {
      const a = cfg.actions.find((x) => x.name === act.dataset.act); const row = state.rows.find((r) => r.id == act.dataset.id);
      const body = { version: row.version };
      if (a.confirm && !(await P.confirm(a.confirm, { confirmText: a.label, tone: a.tone === 'danger' ? 'danger' : 'success' }))) return;
      if (a.ask) { const v = await P.ask(a.ask, { title: a.label }); if (v === null) return; body[a.askKey] = v; }
      if (a.form) { const v = await actionForm(a, row); if (v === null) return; Object.assign(body, v); }
      act.disabled = true;
      try { await P.post(`${base}/${row.id}/${a.name}`, body); P.ok('Berhasil', `${a.label} berhasil diproses.`); load(true); } catch (e) { act.disabled = false; P.fail(e); load(true); }
    }
  });

  /* ----- form aksi (mis. catat pembayaran) ----- */
  async function actionForm(a, row) {
    const fields = a.form(row);
    const parts = await Promise.all(fields.map((f) => fieldHtml(f, f.default)));
    return new Promise((resolve) => {
      const m = P.modal({ title: a.label, body: `<form id="act-form" novalidate>${parts.join('')}</form>`,
        foot: `<button class="btn" data-no>Batal</button><button class="btn btn-primary" data-yes>${icon('check')} ${esc(a.label)}</button>`, onClose: () => resolve(null) });
      $('[data-no]', m.el).onclick = () => { resolve(null); m.close(); };
      $('[data-yes]', m.el).onclick = () => {
        const form = $('#act-form', m.el); const out = {};
        fields.forEach((f) => { const v = form.elements[f.name].value; if (v !== '') out[f.name] = f.type === 'number' ? Number(v) : v; });
        resolve(out); m.close();
      };
    });
  }

  /* ----- form ----- */
  async function fieldHtml(f, value) {
    const id = 'f-' + f.name; const req = f.required ? 'required' : '';
    const label = `<label for="${id}">${esc(f.label)}${f.required ? ' *' : ''}</label>`;
    const hint = `<div class="hint" data-hint="${f.name}">${esc(f.hint || '')}</div>`;
    if (f.type === 'checkbox') return `<div class="field"><label class="check"><input type="checkbox" id="${id}" name="${f.name}" ${value ? 'checked' : ''}> ${esc(f.label)}</label></div>`;
    if (f.type === 'file' || f.type === 'files') return `<div class="field">${label}<input class="input" id="${id}" name="${f.name}" type="file" ${f.type === 'files' ? 'multiple accept=".pdf,.jpg,.jpeg,.png"' : 'accept="image/*" capture="environment"'}>${hint}</div>`;
    if (f.type === 'textarea') return `<div class="field">${label}<textarea class="input" id="${id}" name="${f.name}" ${req}>${esc(value ?? '')}</textarea>${hint}</div>`;
    if (f.type === 'select') {
      const opts = typeof f.options === 'function' ? await f.options() : f.options;
      return `<div class="field">${label}<select class="input" id="${id}" name="${f.name}" ${req}><option value="">— pilih —</option>${opts.map(([v, l]) => `<option value="${esc(v)}" ${String(v) === String(value ?? '') ? 'selected' : ''}>${esc(l)}</option>`).join('')}</select>${hint}</div>`;
    }
    const extra = [f.maxlength ? `maxlength="${f.maxlength}"` : '', f.inputmode ? `inputmode="${f.inputmode}"` : '', f.min != null ? `min="${f.min}"` : ''].join(' ');
    const type = f.type || 'text';
    const v = type === 'password' ? '' : esc(value ?? '');
    return `<div class="field">${label}<input class="input" id="${id}" name="${f.name}" type="${type}" value="${v}" ${req} ${extra} ${type === 'password' ? 'autocomplete="new-password"' : ''}>${hint}</div>`;
  }

  async function openForm(mode, row) {
    const fields = cfg.fields(mode).filter((f) => !(mode === 'edit' && f.createOnly));
    const parts = await Promise.all(fields.map((f) => {
      let value = mode === 'edit' ? row[f.name] : f.default;
      if (f.type === 'datetime-local' && value) value = String(value).slice(0, 16);
      return fieldHtml(f, value);
    }));
    const m = P.modal({
      title: mode === 'create' ? `Tambah ${cfg.noun}` : `${cfg.editLabel || 'Ubah'} ${cfg.noun}`,
      body: `${mode === 'edit' && cfg.detail ? cfg.detail(row) : ''}<form id="crud-form" novalidate>${parts.join('')}</form>`,
      foot: `<button class="btn" data-close>Batal</button><button class="btn btn-primary" id="crud-save">${icon('check')} Simpan</button>`,
    });
    const form = $('#crud-form', m.el);
    $('#crud-save', m.el).onclick = async () => {
      const body = {}; const files = {};
      fields.forEach((f) => {
        const el = form.elements[f.name]; let v;
        if (f.type === 'file' || f.type === 'files') { if (el.files.length) files[f.name] = Array.from(el.files); return; }
        if (f.type === 'checkbox') v = el.checked;
        else { v = el.value; if (v === '') v = f.required ? '' : null; else if (f.type === 'number') v = Number(v); }
        if (!(f.type === 'password' && v == null)) body[f.name] = v;
      });
      if (mode === 'edit' && row.version !== undefined) body.version = row.version;
      $$('.invalid', form).forEach((x) => x.classList.remove('invalid'));
      const btn = $('#crud-save', m.el); btn.disabled = true;
      try {
        let payload = body;
        const wantSubmit = cfg.resource === 'letters' && body.submit === true && files.attachments;
        if (wantSubmit) body.submit = false; // lampiran hanya boleh diunggah saat draft
        if (files.attachment) { // multipart untuk foto pengaduan
          payload = new FormData();
          Object.entries(body).forEach(([k, v]) => v !== null && payload.append(k, v === true ? '1' : v === false ? '0' : v));
          payload.append('attachment', files.attachment[0]);
        }
        const res = await (mode === 'create' ? P.api('POST', base, payload) : P.patch(`${base}/${row.id}`, body));
        const id = res.data.id;
        if (files.attachments) {
          for (const file of files.attachments) { const fd = new FormData(); fd.append('attachment', file); await P.api('POST', `/api/v1/letters/${id}/attachments`, fd); }
          if (wantSubmit) { const fresh = await P.get(`${base}/${id}`); await P.post(`${base}/${id}/submit`, { version: fresh.data.version }); }
        }
        P.ok('Tersimpan', `Data ${cfg.noun} berhasil disimpan ke database.`);
        m.close(); load(true);
      } catch (e) {
        btn.disabled = false;
        Object.entries(e.errors || {}).forEach(([k, v]) => { const el = form.elements[k]; el?.classList.add('invalid'); const h = $(`[data-hint="${k}"]`, form); if (h) { h.textContent = v[0]; h.className = 'hint bad'; } });
        P.fail(e);
        if (e.status === 409) load(true);
      }
    };
  }

  P.poll(() => load(false), 8000);
  document.addEventListener('prisma:notifications', (e) => e.detail.fresh.length && load(false));
})();
