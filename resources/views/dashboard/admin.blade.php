<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal Pengurus RT - Layanan Publik Warga</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --primary: #1B365D;
            --primary-light: #2A4D80;
            --teal: #3A9696;
            --bg: #F8FAFC;
            --card: #FFFFFF;
            --border: #E2E8F0;
            --text-dark: #0F172A;
            --text-muted: #64748B;
            --emerald: #059669;
            --amber: #D97706;
            --rose: #E11D48;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg); color: var(--text-dark); min-height: 100vh; }
        .topbar { background: var(--primary); color: white; padding: 0.85rem 1.5rem; display: flex; justify-content: space-between; align-items: center; }
        .topbar a { color: white; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1.5rem; }
        .grid-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
        .card-stat h3 { font-size: 0.825rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600; margin-bottom: 0.5rem; }
        .card-stat .value { font-size: 1.75rem; font-weight: 700; color: var(--text-dark); }
        .badge { display: inline-flex; align-items: center; padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .badge-role { background: rgba(255,255,255,0.15); color: #FFF; border: 1px solid rgba(255,255,255,0.3); }
        .btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; text-decoration: none; border: none; transition: all 0.15s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-light); }
        .btn-teal { background: var(--teal); color: white; }
        .btn-teal:hover { opacity: 0.9; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-dark); }
        .btn-outline:hover { background: #F1F5F9; }
        .section-title { font-size: 1.15rem; font-weight: 700; color: var(--text-dark); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        th, td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #F8FAFC; color: var(--text-muted); font-weight: 600; }
        .doc-card { display: flex; align-items: flex-start; gap: 1rem; padding: 1rem; border: 1px solid var(--border); border-radius: 0.5rem; background: #FFFFFF; text-decoration: none; color: inherit; transition: border-color 0.2s; }
        .doc-card:hover { border-color: var(--teal); }
        .icon-box { width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; shrink-0; }
    </style>
</head>
<body>
    <header class="topbar">
        <a href="/">
            <x-brand-logo variant="symbol" class="h-7 w-7" />
            <span>Portal Pengurus RT 01</span>
            <span class="badge badge-role">{{ $user->role }}</span>
        </a>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <span style="font-size: 0.85rem; color: #CBD5E1;">{{ $user->name }} ({{ $user->email }})</span>
            <a href="/docs/api" class="btn btn-teal" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;" target="_blank">
                <i data-lucide="book-open" style="width: 14px; height: 14px;"></i> Swagger API Docs
            </a>
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-outline" style="background: rgba(255,255,255,0.1); color: white; border-color: rgba(255,255,255,0.2); font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                    Keluar
                </button>
            </form>
        </div>
    </header>

    <main class="container">
        <!-- Statistik Cepat -->
        <div class="grid-stats">
            <div class="card card-stat">
                <h3>Surat Menunggu Tindakan</h3>
                <div class="value" style="color: var(--amber);">{{ $pendingLettersCount }}</div>
                <small style="color: var(--text-muted);">Submitted & Verified</small>
            </div>
            <div class="card card-stat">
                <h3>Surat Telah Disahkan</h3>
                <div class="value" style="color: var(--emerald);">{{ $approvedLettersCount }}</div>
                <small style="color: var(--text-muted);">Approved & Selesai</small>
            </div>
            <div class="card card-stat">
                <h3>Aduan Lingkungan Aktif</h3>
                <div class="value" style="color: var(--primary);">{{ $activeComplaintsCount }}</div>
                <small style="color: var(--text-muted);">Perlu ditindaklanjuti</small>
            </div>
            <div class="card card-stat">
                <h3>Laporan Keamanan</h3>
                <div class="value" style="color: var(--rose);">{{ $securityReportsCount }}</div>
                <small style="color: var(--text-muted);">Tiket insiden lingkungan</small>
            </div>
            <div class="card card-stat">
                <h3>Saldo Kas Warga</h3>
                <div class="value" style="font-size: 1.35rem; color: var(--primary);">
                    Rp {{ number_format($financeSummary['current_balance'] ?? 0, 0, ',', '.') }}
                </div>
                <small style="color: var(--text-muted);">Bulan {{ $financeSummary['period'] ?? date('F Y') }}</small>
            </div>
        </div>

        <!-- Panel Khusus Bendahara & Sekretaris: Laporan Keuangan & Backup Store -->
        @if($user->isBendahara() || $user->isSekretaris() || $user->isSuperadmin())
        <div class="card" style="margin-bottom: 2rem; border-left: 4px solid var(--emerald);">
            <div class="section-title" style="color: var(--emerald);">
                <i data-lucide="wallet" style="width: 20px; height: 20px;"></i>
                Tata Kelola Laporan Keuangan RT & Backup Store Data (Subfolder Khusus)
            </div>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                Akses eksklusif untuk <strong>{{ $user->role }}</strong>: Mengunduh rekapitulasi buku kas RT, memantau pembayaran iuran kas bulanan warga dari subfolder <code>storage/app/private/financial-reports/</code>, dan membuat cadangan data aman (<em>Immutable Ledger Backup Store</em>).
            </p>
            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                <a href="{{ route('finance.report.monthly', ['year' => date('Y'), 'month' => date('m'), 'format' => 'html']) }}" target="_blank" class="btn btn-primary">
                    <i data-lucide="file-text" style="width: 16px; height: 16px;"></i> Unduh Laporan Kas RT Bulan Ini (.HTML / Cetak)
                </a>
                <a href="{{ route('finance.report.monthly', ['year' => date('Y'), 'month' => date('m'), 'format' => 'csv']) }}" class="btn btn-outline">
                    <i data-lucide="download" style="width: 16px; height: 16px;"></i> Ekspor Rekap Kas RT (.CSV)
                </a>
                <a href="{{ route('finance.report.citizen-dues', ['year' => date('Y'), 'month' => date('m')]) }}" target="_blank" class="btn btn-teal">
                    <i data-lucide="users" style="width: 16px; height: 16px;"></i> Unduh Kas Pembayaran Warga Per Bulan
                </a>
                <button type="button" onclick="triggerBackupStore()" class="btn btn-outline" style="border-color: var(--emerald); color: var(--emerald);">
                    <i data-lucide="shield-check" style="width: 16px; height: 16px;"></i> Jalankan Backup Store Data Kas (SHA-256)
                </button>
            </div>
            <div id="backupAlert" style="display: none; margin-top: 1rem; padding: 0.75rem; border-radius: 0.375rem; font-size: 0.85rem;"></div>
        </div>
        @endif

        <!-- Daftar Dokumen Resmi & Master Template -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="section-title">
                <i data-lucide="folder-check" style="width: 20px; height: 20px; color: var(--primary);"></i>
                Katalog Master Dokumen & Spesifikasi Template Surat
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
                <div class="doc-card">
                    <div class="icon-box" style="background: #EBF7F7; color: var(--teal);">
                        <i data-lucide="file-badge"></i>
                    </div>
                    <div>
                        <h4 style="font-size: 0.95rem; font-weight: 600;">Surat Keterangan (SK-UMUM)</h4>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.25rem 0 0.5rem 0;">Surat pengantar umum untuk urusan administrasi warga.</p>
                        <span class="badge" style="background: #E2E8F0; color: #334155;">Template Key: surat-keterangan</span>
                    </div>
                </div>

                <div class="doc-card">
                    <div class="icon-box" style="background: #FEE2E2; color: #DC2626;">
                        <i data-lucide="file-heart"></i>
                    </div>
                    <div>
                        <h4 style="font-size: 0.95rem; font-weight: 600;">Surat Kematian (SK-KEMATIAN)</h4>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.25rem 0 0.5rem 0;">Pencatatan data almarhum/ah warga RT secara resmi.</p>
                        <span class="badge" style="background: #E2E8F0; color: #334155;">Template Key: surat-kematian</span>
                    </div>
                </div>

                <div class="doc-card">
                    <div class="icon-box" style="background: #FEF3C7; color: #D97706;">
                        <i data-lucide="truck"></i>
                    </div>
                    <div>
                        <h4 style="font-size: 0.95rem; font-weight: 600;">Surat Pindah (SK-PINDAH)</h4>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.25rem 0 0.5rem 0;">Keterangan perpindahan domisili keluarga warga.</p>
                        <span class="badge" style="background: #E2E8F0; color: #334155;">Template Key: surat-pindah</span>
                    </div>
                </div>

                <div class="doc-card">
                    <div class="icon-box" style="background: #ECFDF5; color: #059669;">
                        <i data-lucide="file-check"></i>
                    </div>
                    <div>
                        <h4 style="font-size: 0.95rem; font-weight: 600;">Surat Keterangan Tidak Mampu (SKTM)</h4>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.25rem 0 0.5rem 0;">Keterangan ekonomi untuk beasiswa dan bantuan sosial.</p>
                        <span class="badge" style="background: #E2E8F0; color: #334155;">Template Key: surat-keterangan-tidak-mampu</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Surat Terbaru -->
        <div class="card">
            <div class="section-title">
                <i data-lucide="clock" style="width: 20px; height: 20px; color: var(--primary);"></i>
                Daftar Permohonan Surat Terkini
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Nomor Tiket</th>
                            <th>Pemohon</th>
                            <th>Jenis Surat</th>
                            <th>Status</th>
                            <th>Tanggal Masuk</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentLetters as $letter)
                        <tr>
                            <td><strong>{{ $letter->ticket_number }}</strong></td>
                            <td>{{ $letter->citizen?->full_name ?? '—' }}</td>
                            <td>{{ $letter->letterType?->nama_surat ?? $letter->type }}</td>
                            <td>
                                <span class="badge" style="
                                    background: {{ $letter->status === 'approved' ? '#ECFDF5' : ($letter->status === 'submitted' ? '#FEF3C7' : '#EFF6FF') }};
                                    color: {{ $letter->status === 'approved' ? '#065F46' : ($letter->status === 'submitted' ? '#92400E' : '#1E40AF') }};
                                ">
                                    {{ strtoupper($letter->status) }}
                                </span>
                            </td>
                            <td>{{ $letter->created_at->translatedFormat('d M Y H:i') }}</td>
                            <td>
                                <a href="/api/v1/letters/{{ $letter->id }}/preview" target="_blank" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                    Tinjau Dokumen
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                Belum ada permohonan surat masuk saat ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();

        async function triggerBackupStore() {
            const alertBox = document.getElementById('backupAlert');
            alertBox.style.display = 'block';
            alertBox.style.background = '#EFF6FF';
            alertBox.style.color = '#1E40AF';
            alertBox.textContent = 'Menjalankan snapshot backup store data kas...';

            try {
                const token = localStorage.getItem('auth_token');
                const headers = { 
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                };
                if (token) headers['Authorization'] = `Bearer ${token}`;

                const res = await fetch('{{ route('finance.backup.create') }}', {
                    method: 'POST',
                    headers: headers
                });
                const json = await res.json();

                if (res.ok) {
                    alertBox.style.background = '#ECFDF5';
                    alertBox.style.color = '#065F46';
                    alertBox.textContent = `✓ Sukses: Backup store tersimpan di ${json.data?.backup_file} (Checksum: ${json.data?.checksum_sha256?.substring(0, 16)}...)`;
                } else {
                    alertBox.style.background = '#FEF2F2';
                    alertBox.style.color = '#991B1B';
                    alertBox.textContent = json.message || 'Gagal membuat backup store.';
                }
            } catch (err) {
                alertBox.style.background = '#FEF2F2';
                alertBox.style.color = '#991B1B';
                alertBox.textContent = 'Gagal menghubungi server backup.';
            }
        }
    </script>
</body>
</html>
