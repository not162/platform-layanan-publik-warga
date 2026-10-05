<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal Ketua RT - Layanan Publik Warga</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    @vite(['resources/css/dashboard.css'])
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
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.03); margin-bottom: 1.5rem; }
        .card-stat h3 { font-size: 0.825rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600; margin-bottom: 0.5rem; }
        .card-stat .value { font-size: 1.75rem; font-weight: 700; color: var(--text-dark); }
        .badge { display: inline-flex; align-items: center; padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .badge-role { background: rgba(255,255,255,0.18); color: #FFF; border: 1px solid rgba(255,255,255,0.3); }
        .badge-verified { background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; }
        .badge-submitted { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
        .badge-published { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }
        .btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 0.85rem; border-radius: 0.5rem; font-size: 0.825rem; font-weight: 500; cursor: pointer; text-decoration: none; border: none; transition: all 0.15s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-light); }
        .btn-success { background: var(--emerald); color: white; }
        .btn-success:hover { opacity: 0.9; }
        .btn-danger { background: var(--rose); color: white; }
        .btn-danger:hover { opacity: 0.9; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-dark); }
        .btn-outline:hover { background: #F1F5F9; }
        .section-title { font-size: 1.15rem; font-weight: 700; color: var(--text-dark); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        th, td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #F8FAFC; color: var(--text-muted); font-weight: 600; }
        .profile-banner { display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #1B365D 0%, #2A4D80 100%); color: white; padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 2rem; }
        .alert-status { background: #ECFDF5; border-left: 4px solid var(--emerald); color: #065F46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem; }
    </style>
</head>
<body>
    <header class="topbar">
        <a href="/">
            <x-brand-logo variant="symbol" class="h-7 w-7" />
            <span>Portal Resmi Ketua RT 01 / RW 05</span>
            <span class="badge badge-role">Ketua RT</span>
        </a>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="{{ route('profile') }}" class="btn btn-outline" style="background: rgba(255,255,255,0.15); color: white; border-color: rgba(255,255,255,0.25);">
                <i data-lucide="user" style="width: 14px; height: 14px;"></i> Profil Saya
            </a>
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-outline" style="background: rgba(255,255,255,0.1); color: white; border-color: rgba(255,255,255,0.2);">
                    Keluar
                </button>
            </form>
        </div>
    </header>

    <main class="container">
        @if(session('status'))
            <div class="alert-status">
                {{ session('status') }}
            </div>
        @endif

        <!-- Banner Profil Ketua RT -->
        <div class="profile-banner">
            <div>
                <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.85;">Pimpinan Wilayah RT 01 / RW 05</div>
                <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0.25rem 0;">{{ $user->name }}</h1>
                <p style="font-size: 0.875rem; opacity: 0.9;">{{ $user->email }} | NIK: {{ $user->citizen?->nik ?? 'Terkoneksi Akun Pejabat RT' }}</p>
            </div>
            <div style="text-align: right;">
                <span class="badge" style="background: #10B981; color: white; font-size: 0.8rem; padding: 0.35rem 0.75rem;">Masa Bakti Aktif</span>
                <div style="font-size: 0.75rem; margin-top: 0.35rem; opacity: 0.8;">SK Kepengurusan Lingkungan RT</div>
            </div>
        </div>

        <!-- Ringkasan Statistik -->
        <div class="grid-stats">
            <div class="card card-stat">
                <h3>Surat Perlu Pengesahan</h3>
                <div class="value" style="color: var(--amber);">{{ $pendingApprovalCount }}</div>
                <small style="color: var(--text-muted);">Menunggu persetujuan Ketua RT</small>
            </div>
            <div class="card card-stat">
                <h3>Surat Telah Sah</h3>
                <div class="value" style="color: var(--emerald);">{{ $approvedLettersCount }}</div>
                <small style="color: var(--text-muted);">Telah diterbitkan resmi</small>
            </div>
            <div class="card card-stat">
                <h3>Saldo Kas RT</h3>
                <div class="value" style="font-size: 1.35rem; color: var(--primary);">
                    Rp {{ number_format($financeSummary['current_balance'] ?? 0, 0, ',', '.') }}
                </div>
                <small style="color: var(--text-muted);">Status kas aktif bulan ini</small>
            </div>
            <div class="card card-stat">
                <h3>Aduan & Keamanan</h3>
                <div class="value" style="color: var(--rose);">{{ $activeComplaintsCount + $securityReportsCount }}</div>
                <small style="color: var(--text-muted);">Tiket aktif lingkungan</small>
            </div>
        </div>

        <!-- Antrean Pengesahan Surat Pengantar (Approval Queue) -->
        <div class="card">
            <div class="section-title">
                <i data-lucide="stamp" style="width: 20px; height: 20px; color: var(--primary);"></i>
                Antrean Pengesahan Surat Pengantar Warga
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                Surat yang telah diverifikasi oleh Sekretaris RT siap Anda sahkan secara resmi atau ditinjau ulang.
            </p>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>No. Tiket</th>
                            <th>Pemohon</th>
                            <th>Jenis Surat</th>
                            <th>Tanggal Pengajuan</th>
                            <th>Status Verifikasi</th>
                            <th style="text-align: right;">Aksi Pengesahan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingApprovalLetters as $letter)
                        <tr>
                            <td style="font-weight: 600; font-family: monospace;">{{ $letter->ticket_number }}</td>
                            <td>
                                <div>{{ $letter->citizen?->full_name ?? 'Warga' }}</div>
                                <small style="color: var(--text-muted);">NIK: {{ $letter->citizen?->nik ?? '-' }}</small>
                            </td>
                            <td>{{ $letter->letterType?->name ?? $letter->type }}</td>
                            <td>{{ $letter->created_at ? $letter->created_at->translatedFormat('d M Y, H:i') : '-' }}</td>
                            <td>
                                @if($letter->status === 'verified')
                                    <span class="badge badge-verified">Terverifikasi Sekretaris</span>
                                @else
                                    <span class="badge badge-submitted">Diajukan Warga</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.4rem;">
                                    <a href="/letters/{{ $letter->id }}/preview" target="_blank" class="btn btn-outline" title="Pratinjau Berkas">
                                        <i data-lucide="eye" style="width: 14px; height: 14px;"></i> Pratinjau
                                    </a>
                                    <form method="POST" action="{{ route('dashboard.letters.approve', $letter->id) }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-success" title="Sahkan Surat Resmi">
                                            <i data-lucide="check" style="width: 14px; height: 14px;"></i> Sahkan Surat
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                Tidak ada permohonan surat yang sedang menunggu pengesahan saat ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pengesahan Laporan Keuangan Kuartal / Triwulan -->
        <div class="card">
            <div class="section-title">
                <i data-lucide="file-check" style="width: 20px; height: 20px; color: var(--emerald);"></i>
                Pengesahan Laporan Keuangan Kas Triwulan / Kuartal
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                Sebagai Ketua RT, Anda berwenang memeriksa dan menandatangani persetujuan laporan kas triwulan yang diajukan oleh Bendahara RT.
            </p>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Periode</th>
                            <th>Saldo Awal</th>
                            <th>Penerimaan</th>
                            <th>Pengeluaran</th>
                            <th>Saldo Akhir</th>
                            <th>Status Laporan</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($quarterlyReports as $report)
                        <tr>
                            <td style="font-weight: 600;">Kuartal {{ $report->quarter }} (Tahun {{ $report->year }})</td>
                            <td>Rp {{ number_format($report->opening_balance_idr, 0, ',', '.') }}</td>
                            <td style="color: var(--emerald); font-weight: 600;">+Rp {{ number_format($report->income_idr, 0, ',', '.') }}</td>
                            <td style="color: var(--rose); font-weight: 600;">-Rp {{ number_format($report->expense_idr, 0, ',', '.') }}</td>
                            <td style="font-weight: 700;">Rp {{ number_format($report->closing_balance_idr, 0, ',', '.') }}</td>
                            <td>
                                @if($report->status === 'published')
                                    <span class="badge badge-published">Telah Disahkan Ketua RT</span>
                                @else
                                    <span class="badge badge-submitted">Draft Siap Disahkan</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                @if($report->status !== 'published')
                                    <form method="POST" action="{{ route('dashboard.finance.reports.approve', $report->id) }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">
                                            <i data-lucide="check-circle" style="width: 14px; height: 14px;"></i> Sahkan Laporan
                                        </button>
                                    </form>
                                @else
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">Sah & Terkunci</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                Belum ada berkas laporan triwulan kas RT yang diajukan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rekapitulasi Kas & Dokumen Lingkungan -->
        <div class="card">
            <div class="section-title">
                <i data-lucide="printer" style="width: 20px; height: 20px; color: var(--teal);"></i>
                Unduh Rekapitulasi Buku Kas & Transparansi Iuran
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                Akses dokumen cetak transparansi kas RT untuk rapat warga dan papan pengumuman lingkungan.
            </p>
            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                <a href="{{ route('finance.report.monthly', ['year' => date('Y'), 'month' => date('m'), 'format' => 'html']) }}" target="_blank" class="btn btn-primary">
                    <i data-lucide="file-text" style="width: 16px; height: 16px;"></i> Rekap Kas Bulan Ini (.HTML / Cetak)
                </a>
                <a href="{{ route('finance.report.citizen-dues', ['year' => date('Y'), 'month' => date('m')]) }}" target="_blank" class="btn btn-outline">
                    <i data-lucide="users" style="width: 16px; height: 16px;"></i> Rekap Iuran Kas Pembayaran Warga
                </a>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
