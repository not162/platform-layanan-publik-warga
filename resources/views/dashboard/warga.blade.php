<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Warga - Layanan Publik RT</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="/js/citizen-document-exporter.js"></script>
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
        .btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; text-decoration: none; border: none; transition: all 0.15s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-teal { background: var(--teal); color: white; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-dark); }
        .btn-outline:hover { background: #F1F5F9; }
        .section-title { font-size: 1.15rem; font-weight: 700; color: var(--text-dark); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        th, td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #F8FAFC; color: var(--text-muted); font-weight: 600; }
    </style>
</head>
<body>
    <header class="topbar">
        <a href="/">
            <x-brand-logo variant="symbol" class="h-7 w-7" />
            <span>Portal Warga RT 01</span>
        </a>
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <a href="{{ route('profile') }}" class="btn btn-outline" style="background: rgba(255,255,255,0.15); color: white; border-color: rgba(255,255,255,0.25); font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                <i data-lucide="user" style="width: 14px; height: 14px;"></i> Profil Saya
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
        <!-- Stat Warga -->
        <div class="grid-stats">
            <div class="card card-stat">
                <h3>Permohonan Surat Saya</h3>
                <div class="value" style="color: var(--primary);">{{ $myLettersCount }}</div>
                <small style="color: var(--text-muted);">Total diajukan</small>
            </div>
            <div class="card card-stat">
                <h3>Surat Sah Siap Unduh</h3>
                <div class="value" style="color: var(--emerald);">{{ $myCompletedLettersCount }}</div>
                <small style="color: var(--text-muted);">Format Word (.docx) & PDF</small>
            </div>
            <div class="card card-stat">
                <h3>Pengaduan Saya</h3>
                <div class="value" style="color: var(--amber);">{{ $myComplaintsCount }}</div>
                <small style="color: var(--text-muted);">Aspirasi / keluhan</small>
            </div>
            <div class="card card-stat">
                <h3>Laporan Keamanan</h3>
                <div class="value" style="color: #E11D48;">{{ $mySecurityReportsCount }}</div>
                <small style="color: var(--text-muted);">Insiden lingkungan</small>
            </div>
        </div>

        <!-- Tabel Surat Saya -->
        <div class="card" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <div class="section-title" style="margin-bottom: 0;">
                    <i data-lucide="file-text" style="width: 20px; height: 20px; color: var(--primary);"></i>
                    Daftar Permohonan Surat Saya
                </div>
                <a href="/#services" class="btn btn-primary" style="font-size: 0.825rem;">
                    <i data-lucide="plus" style="width: 14px; height: 14px;"></i> Ajukan Surat Baru
                </a>
            </div>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Nomor Tiket</th>
                            <th>Jenis Surat</th>
                            <th>Keperluan</th>
                            <th>Status</th>
                            <th>Tanggal Pengajuan</th>
                            <th>Unduh Dokumen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($myLetters as $letter)
                        <tr>
                            <td><strong>{{ $letter->ticket_number }}</strong></td>
                            <td>{{ $letter->letterType?->nama_surat ?? $letter->type }}</td>
                            <td>{{ $letter->keperluan }}</td>
                            <td>
                                <span class="badge" style="
                                    background: {{ in_array($letter->status, ['approved', 'completed']) ? '#ECFDF5' : '#EFF6FF' }};
                                    color: {{ in_array($letter->status, ['approved', 'completed']) ? '#065F46' : '#1E40AF' }};
                                ">
                                    {{ strtoupper($letter->status) }}
                                </span>
                            </td>
                            <td>{{ $letter->created_at->translatedFormat('d M Y') }}</td>
                            <td>
                                @if(in_array($letter->status, ['approved', 'completed']))
                                <div style="display: flex; gap: 0.4rem;">
                                    <button type="button" onclick="exportWord({{ $letter->id }})" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; border-color: #2563EB; color: #2563EB;">
                                        <i data-lucide="file-edit" style="width: 12px; height: 12px;"></i> Word (.docx)
                                    </button>
                                    <button type="button" onclick="exportPdf({{ $letter->id }})" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; border-color: #DC2626; color: #DC2626;">
                                        <i data-lucide="printer" style="width: 12px; height: 12px;"></i> PDF
                                    </button>
                                </div>
                                @else
                                <span style="font-size: 0.8rem; color: var(--text-muted);">Menunggu Approval</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                Anda belum memiliki permohonan surat. Klik tombol "Ajukan Surat Baru" untuk memulai.
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
        const exporter = new CitizenDocumentExporter();

        async function exportWord(letterId) {
            try {
                const doc = await exporter.fetchDocumentPayload(letterId);
                exporter.downloadAsWord(doc);
            } catch (e) {
                alert('Gagal mengunduh berkas Word: ' + e.message);
            }
        }

        async function exportPdf(letterId) {
            try {
                const doc = await exporter.fetchDocumentPayload(letterId);
                exporter.downloadAsPdf(doc);
            } catch (e) {
                alert('Gagal menyiapkan cetak PDF: ' + e.message);
            }
        }
    </script>
</body>
</html>
