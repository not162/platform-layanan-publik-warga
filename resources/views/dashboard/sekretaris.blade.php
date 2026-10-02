<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal Sekretaris RT - Layanan Publik Warga</title>
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
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.03); margin-bottom: 1.5rem; }
        .card-stat h3 { font-size: 0.825rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600; margin-bottom: 0.5rem; }
        .card-stat .value { font-size: 1.75rem; font-weight: 700; color: var(--text-dark); }
        .badge { display: inline-flex; align-items: center; padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .badge-role { background: rgba(255,255,255,0.18); color: #FFF; border: 1px solid rgba(255,255,255,0.3); }
        .badge-submitted { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
        .badge-verified { background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; }
        .badge-approved { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }
        .btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 0.85rem; border-radius: 0.5rem; font-size: 0.825rem; font-weight: 500; cursor: pointer; text-decoration: none; border: none; transition: all 0.15s; }
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
        .profile-banner { display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #1B365D 0%, #3A9696 100%); color: white; padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 2rem; }
        .alert-status { background: #ECFDF5; border-left: 4px solid var(--emerald); color: #065F46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem; }
    </style>
</head>
<body>
    <header class="topbar">
        <a href="/">
            <x-brand-logo variant="symbol" class="h-7 w-7" />
            <span>Portal Resmi Sekretaris RT 01 / RW 05</span>
            <span class="badge badge-role">Sekretaris RT</span>
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

        <!-- Banner Profil Sekretaris RT -->
        <div class="profile-banner">
            <div>
                <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.85;">Tata Usaha & Administrasi Persuratan RT</div>
                <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0.25rem 0;">{{ $user->name }}</h1>
                <p style="font-size: 0.875rem; opacity: 0.9;">{{ $user->email }} | NIK: {{ $user->citizen?->nik ?? 'Terkoneksi Akun Pejabat RT' }}</p>
            </div>
            <div style="text-align: right;">
                <span class="badge" style="background: #10B981; color: white; font-size: 0.8rem; padding: 0.35rem 0.75rem;">Masa Bakti Aktif</span>
                <div style="font-size: 0.75rem; margin-top: 0.35rem; opacity: 0.8;">Pelayanan Kependudukan Warga</div>
            </div>
        </div>

        <!-- Ringkasan Statistik -->
        <div class="grid-stats">
            <div class="card card-stat">
                <h3>Surat Perlu Verifikasi</h3>
                <div class="value" style="color: var(--amber);">{{ $submittedCount }}</div>
                <small style="color: var(--text-muted);">Menunggu verifikasi berkas</small>
            </div>
            <div class="card card-stat">
                <h3>Surat Terverifikasi</h3>
                <div class="value" style="color: var(--primary);">{{ $verifiedCount }}</div>
                <small style="color: var(--text-muted);">Diteruskan ke Ketua RT</small>
            </div>
            <div class="card card-stat">
                <h3>Total Warga</h3>
                <div class="value" style="color: var(--teal);">{{ $citizensCount }}</div>
                <small style="color: var(--text-muted);">Data kependudukan aktif</small>
            </div>
            <div class="card card-stat">
                <h3>Total Kepala Keluarga</h3>
                <div class="value" style="color: var(--emerald);">{{ $familyCardsCount }}</div>
                <small style="color: var(--text-muted);">Kartu Keluarga terdaftar</small>
            </div>
        </div>

        <!-- Meja Verifikasi Berkas Surat Warga -->
        <div class="card">
            <div class="section-title">
                <i data-lucide="file-check-2" style="width: 20px; height: 20px; color: var(--primary);"></i>
                Meja Verifikasi Berkas Surat Pengantar Warga
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                Periksa kelengkapan syarat administratif warga. Jika berkas lengkap, klik "Verifikasi Berkas" untuk meneruskan ke Ketua RT.
            </p>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>No. Tiket</th>
                            <th>Pemohon</th>
                            <th>Jenis Surat</th>
                            <th>Tanggal Pengajuan</th>
                            <th>Status Berkas</th>
                            <th style="text-align: right;">Aksi Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($verificationQueue as $letter)
                        <tr>
                            <td style="font-weight: 600; font-family: monospace;">{{ $letter->ticket_number }}</td>
                            <td>
                                <div>{{ $letter->citizen?->full_name ?? 'Warga' }}</div>
                                <small style="color: var(--text-muted);">NIK: {{ $letter->citizen?->nik ?? '-' }}</small>
                            </td>
                            <td>{{ $letter->letterType?->name ?? $letter->type }}</td>
                            <td>{{ $letter->created_at ? $letter->created_at->translatedFormat('d M Y, H:i') : '-' }}</td>
                            <td>
                                <span class="badge badge-submitted">Berkas Diajukan</span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.4rem;">
                                    <form method="POST" action="{{ route('dashboard.letters.verify', $letter->id) }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-teal" title="Verifikasi dan Teruskan ke Ketua RT">
                                            <i data-lucide="check" style="width: 14px; height: 14px;"></i> Verifikasi Berkas
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('dashboard.letters.reject', $letter->id) }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-outline" style="color: var(--rose); border-color: #FECDD3;" title="Minta Perbaikan / Tolak">
                                            <i data-lucide="x" style="width: 14px; height: 14px;"></i> Tolak
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                Tidak ada surat yang menunggu verifikasi saat ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Riwayat Surat Selesai Diterbitkan -->
        <div class="card">
            <div class="section-title">
                <i data-lucide="history" style="width: 20px; height: 20px; color: var(--teal);"></i>
                Riwayat Surat Terverifikasi & Diterbitkan Resmi
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>No. Surat / Tiket</th>
                            <th>Pemohon</th>
                            <th>Jenis Surat</th>
                            <th>Status Saat Ini</th>
                            <th style="text-align: right;">Unduh Dokumen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($verifiedLetters as $letter)
                        <tr>
                            <td style="font-weight: 600;">
                                {{ $letter->letter_number ?: $letter->ticket_number }}
                            </td>
                            <td>{{ $letter->citizen?->full_name ?? 'Warga' }}</td>
                            <td>{{ $letter->letterType?->name ?? $letter->type }}</td>
                            <td>
                                @if($letter->status === 'approved' || $letter->status === 'completed')
                                    <span class="badge badge-approved">Disahkan Ketua RT</span>
                                @else
                                    <span class="badge badge-verified">Terverifikasi</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <a href="/api/v1/letters/{{ $letter->id }}/download?format=pdf" target="_blank" class="btn btn-outline" style="font-size: 0.75rem;">
                                    <i data-lucide="printer" style="width: 13px; height: 13px;"></i> Cetak
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                Belum ada riwayat persuratan.
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
    </script>
</body>
</html>
