<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal Keamanan RT - Layanan Publik Warga</title>
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
        .badge-urgent { background: #FEF2F2; color: #991B1B; border: 1px solid #FECDD3; }
        .badge-done { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }
        .btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 0.85rem; border-radius: 0.5rem; font-size: 0.825rem; font-weight: 500; cursor: pointer; text-decoration: none; border: none; transition: all 0.15s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-dark); }
        .btn-outline:hover { background: #F1F5F9; }
        .section-title { font-size: 1.15rem; font-weight: 700; color: var(--text-dark); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        th, td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #F8FAFC; color: var(--text-muted); font-weight: 600; }
        .profile-banner { display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #1B365D 0%, #E11D48 100%); color: white; padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 2rem; }
        .alert-status { background: #ECFDF5; border-left: 4px solid var(--emerald); color: #065F46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem; }
    </style>
</head>
<body>
    <header class="topbar">
        <a href="/">
            <x-brand-logo variant="symbol" class="h-7 w-7" />
            <span>Portal Resmi Petugas Keamanan RT 01</span>
            <span class="badge badge-role">Petugas Keamanan</span>
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

        <!-- Banner Profil Petugas Keamanan -->
        <div class="profile-banner">
            <div>
                <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.85;">Pos Keamanan & Ronda Kamling RT 01</div>
                <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0.25rem 0;">{{ $user->name }}</h1>
                <p style="font-size: 0.875rem; opacity: 0.9;">{{ $user->email }} | NIK: {{ $user->citizen?->nik ?? 'Terkoneksi Akun Petugas Keamanan' }}</p>
            </div>
            <div style="text-align: right;">
                <span class="badge" style="background: #10B981; color: white; font-size: 0.8rem; padding: 0.35rem 0.75rem;">Siaga 24 Jam</span>
                <div style="font-size: 0.75rem; margin-top: 0.35rem; opacity: 0.8;">Pos Kamling Terpadu</div>
            </div>
        </div>

        <!-- Ringkasan Statistik -->
        <div class="grid-stats">
            <div class="card card-stat">
                <h3>Laporan Perlu Tindakan</h3>
                <div class="value" style="color: var(--rose);">{{ $activeReports->count() }}</div>
                <small style="color: var(--text-muted);">Insiden sedang ditangani</small>
            </div>
            <div class="card card-stat">
                <h3>Insiden Diselesaikan</h3>
                <div class="value" style="color: var(--emerald);">{{ $resolvedReports->count() }}</div>
                <small style="color: var(--text-muted);">Selesai ditindaklanjuti</small>
            </div>
            <div class="card card-stat">
                <h3>Jadwal Ronda Aktif</h3>
                <div class="value" style="color: var(--primary);">{{ $roundSchedules->count() }}</div>
                <small style="color: var(--text-muted);">Shift ronda terjadwal</small>
            </div>
            <div class="card card-stat">
                <h3>Kontak Darurat Siaga</h3>
                <div class="value" style="color: var(--teal);">{{ $emergencyContacts->count() }}</div>
                <small style="color: var(--text-muted);">Instansi siaga tanggap darurat</small>
            </div>
        </div>

        <!-- Tiket Laporan Insiden Keamanan Lingkungan -->
        <div class="card">
            <div class="section-title">
                <i data-lucide="shield-alert" style="width: 20px; height: 20px; color: var(--rose);"></i>
                Tiket Laporan Insiden Keamanan & Ketertiban
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>No. Tiket</th>
                            <th>Judul Insiden</th>
                            <th>Lokasi</th>
                            <th>Tanggal & Waktu</th>
                            <th>Status Penanganan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activeReports as $report)
                        <tr>
                            <td style="font-weight: 600; font-family: monospace;">{{ $report->ticket_number ?? ('INC-' . $report->id) }}</td>
                            <td>
                                <div style="font-weight: 600;">{{ $report->title }}</div>
                                <small style="color: var(--text-muted);">{{ Str::limit($report->description, 60) }}</small>
                            </td>
                            <td>{{ $report->location ?? 'Lingkungan RT 01' }}</td>
                            <td>{{ $report->created_at ? $report->created_at->translatedFormat('d M Y, H:i') : '-' }}</td>
                            <td>
                                <span class="badge badge-urgent">{{ $report->status }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                Lingkungan aman dan tertib. Tidak ada insiden aktif saat ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Kontak Darurat Cepat -->
        <div class="card">
            <div class="section-title">
                <i data-lucide="phone-call" style="width: 20px; height: 20px; color: var(--teal);"></i>
                Kontak Cepat Tanggap Darurat
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                @foreach($emergencyContacts as $contact)
                <div style="padding: 1rem; border: 1px solid var(--border); border-radius: 0.5rem; background: #F8FAFC;">
                    <div style="font-weight: 600; font-size: 0.95rem;">{{ $contact->name }}</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">{{ $contact->institution ?? 'Instansi Resmi' }}</div>
                    <a href="tel:{{ $contact->phone }}" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.35rem 0.65rem;">
                        <i data-lucide="phone" style="width: 13px; height: 13px;"></i> {{ $contact->phone }}
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
