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
        .badge-approved { background: #ECFDF5; color: #065F46; }
        .badge-pending { background: #EFF6FF; color: #1E40AF; }
        .btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; text-decoration: none; border: none; transition: all 0.15s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-teal { background: var(--teal); color: white; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-dark); }
        .btn-outline:hover { background: #F1F5F9; }
        .section-title { font-size: 1.15rem; font-weight: 700; color: var(--text-dark); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        th, td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #F8FAFC; color: var(--text-muted); font-weight: 600; }
        @keyframes actionPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }
        @keyframes actionShake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }
        .animate-action-pulse { animation: actionPulse 1.8s infinite ease-in-out; }
        .animate-action-shake { animation: actionShake 0.6s ease-in-out; }
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
            <a href="{{ url('/api/v1/me/export') }}" class="btn btn-outline" style="background: rgba(255,255,255,0.15); color: white; border-color: rgba(255,255,255,0.25); font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                <i data-lucide="download" style="width: 14px; height: 14px;"></i> Unduh data saya
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
        @if($user->citizen?->status_warga === 'pending_verification')
        <div style="background: #FEF3C7; border: 1px solid #FCD34D; color: #92400E; padding: 1rem 1.25rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; align-items: flex-start; gap: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="background: #FDE68A; padding: 0.35rem; border-radius: 0.5rem; margin-top: 2px;">
                <i data-lucide="clock" style="width: 20px; height: 20px; color: #B45309;"></i>
            </div>
            <div>
                <strong style="display: block; font-size: 0.95rem; margin-bottom: 0.25rem;">Pengajuan Akun Warga Baru Sedang Menunggu Verifikasi Pengurus RT</strong>
                <p style="font-size: 0.85rem; line-height: 1.5; opacity: 0.95;">
                    Data kependudukan NIK <strong>{{ $user->citizen?->nik }}</strong> Anda telah berhasil diajukan dan sedang dalam antrean verifikasi berkas oleh Sekretaris dan Ketua RT 01. Anda tetap dapat menjelajahi layanan portal sementara pengurus memverifikasi identitas Anda.
                </p>
            </div>
        </div>
        @endif

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
                <small style="color: var(--text-muted);">Format Word (.docx) & PDF (.pdf)</small>
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

        <!-- Action Notification Banner (Berhasil / Tidak Berhasil + Animasi & Alasan) -->
        <div id="actionNotificationCard" style="display: none; margin-bottom: 1.5rem; border-width: 1.5px;" class="card">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;">
                <div style="display: flex; align-items: flex-start; gap: 1rem; width: 100%;">
                    <!-- Animasi Action Icon -->
                    <div id="actionIconContainer" style="padding: 0.75rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; shrink: 0;">
                        <i id="actionIcon" data-lucide="check-circle-2" style="width: 26px; height: 26px;"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 id="actionTitle" style="font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem;"></h4>
                        <p id="actionMessage" style="font-size: 0.875rem; color: #475569; margin-bottom: 0.5rem;"></p>
                        <!-- Kalimat di bawah animasi action yang menampilkan alasan tidak bisa di-download / dibuka -->
                        <div id="actionReasonBox" style="display: none; background: #FFF1F2; border-left: 4px solid #E11D48; padding: 0.85rem 1rem; border-radius: 0.5rem; margin-top: 0.5rem;">
                            <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9F1239; margin-bottom: 0.25rem;">
                                ⚠️ Alasan Tidak Bisa Di-download atau Dibuka:
                            </div>
                            <p id="actionReasonText" style="font-size: 0.85rem; color: #881337; line-height: 1.5; font-weight: 500;"></p>
                        </div>
                    </div>
                </div>
                <button type="button" onclick="closeNotification()" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                    <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                </button>
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
                                <span class="badge {{ in_array($letter->status, ['approved', 'completed']) ? 'badge-approved' : 'badge-pending' }}">
                                    {{ strtoupper($letter->status) }}
                                </span>
                            </td>
                            <td>{{ $letter->created_at->translatedFormat('d M Y') }}</td>
                            <td>
                                @if(in_array($letter->status, ['approved', 'completed']))
                                <div style="display: flex; gap: 0.4rem;">
                                    <button type="button" onclick="downloadLetter('{{ $letter->id }}', 'docx')" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; border-color: #2563EB; color: #2563EB;" title="Unduh format Microsoft Word">
                                        <i data-lucide="file-text" style="width: 12px; height: 12px;"></i> Word (.docx)
                                    </button>
                                    <button type="button" onclick="downloadLetter('{{ $letter->id }}', 'pdf')" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; border-color: #DC2626; color: #DC2626;" title="Unduh format PDF resmi">
                                        <i data-lucide="file-check" style="width: 12px; height: 12px;"></i> PDF (.pdf)
                                    </button>
                                </div>
                                @else
                                <button type="button" onclick="showLockedReason('{{ $letter->status }}', '{{ addslashes($letter->rejection_reason ?? '') }}')" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; border-color: #CBD5E1; color: #64748B;">
                                    <i data-lucide="lock" style="width: 12px; height: 12px;"></i> Belum Siap Unduh
                                </button>
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

        function showNotification(isSuccess, title, message, reason = null) {
            const card = document.getElementById('actionNotificationCard');
            const iconContainer = document.getElementById('actionIconContainer');
            const icon = document.getElementById('actionIcon');
            const titleEl = document.getElementById('actionTitle');
            const messageEl = document.getElementById('actionMessage');
            const reasonBox = document.getElementById('actionReasonBox');
            const reasonText = document.getElementById('actionReasonText');

            card.style.display = 'block';
            titleEl.textContent = title;
            messageEl.textContent = message;

            if (isSuccess) {
                card.style.borderColor = '#10B981';
                iconContainer.style.background = '#ECFDF5';
                iconContainer.style.color = '#059669';
                iconContainer.className = '';
                icon.setAttribute('data-lucide', 'check-circle-2');
                reasonBox.style.display = 'none';
            } else {
                card.style.borderColor = '#F43F5E';
                iconContainer.style.background = '#FFF1F2';
                iconContainer.style.color = '#E11D48';
                iconContainer.className = 'animate-action-shake animate-action-pulse';
                icon.setAttribute('data-lucide', 'alert-triangle');

                if (reason) {
                    reasonBox.style.display = 'block';
                    reasonText.textContent = reason;
                } else {
                    reasonBox.style.display = 'none';
                }
            }

            lucide.createIcons();
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function closeNotification() {
            document.getElementById('actionNotificationCard').style.display = 'none';
        }

        async function downloadLetter(letterId, format) {
            try {
                const res = await exporter.downloadDirect(letterId, format, 'POST');
                showNotification(
                    true,
                    'Dokumen Berhasil Diunduh!',
                    `Berkas surat resmi berhasil diunduh dalam format ${res.format} (${res.filename}).`
                );
            } catch (err) {
                showNotification(
                    false,
                    'Pengunduhan Dokumen Tidak Berhasil',
                    err.message || 'Gagal memproses pengunduhan berkas surat.',
                    err.reason || 'Dokumen belum dapat diunduh atau dibuka karena masih menunggu persetujuan resmi dari Ketua RT.'
                );
            }
        }

        function showLockedReason(status, rejectionReason) {
            const statusUpper = (status || '').toUpperCase();
            let reasonText = '';

            switch (status) {
                case 'draft':
                    reasonText = 'Permohonan surat masih berstatus DRAF (belum diajukan). Harap periksa isian keperluan dan tekan tombol ajukan surat terlebih dahulu.';
                    break;
                case 'submitted':
                    reasonText = 'Permohonan surat sedang dalam antrean verifikasi berkas oleh Sekretaris RT sebelum diteruskan ke Ketua RT.';
                    break;
                case 'verified':
                    reasonText = 'Berkas surat telah diverifikasi oleh Sekretaris RT dan saat ini sedang menunggu pengesahan & tanda tangan digital Ketua RT.';
                    break;
                case 'rejected':
                    reasonText = 'Permohonan surat DITOLAK oleh pengurus RT. Alasan: ' + (rejectionReason || 'Syarat atau data belum memenuhi ketentuan.') + '. Dokumen resmi tidak dapat diterbitkan.';
                    break;
                default:
                    reasonText = 'Surat belum disahkan oleh Ketua RT (Status: ' + statusUpper + '). Berkas resmi hanya dapat diunduh setelah disetujui.';
            }

            showNotification(
                false,
                'Dokumen Belum Dapat Diunduh',
                `Surat ini belum siap untuk diunduh karena masih dalam status ${statusUpper}.`,
                reasonText
            );
        }
    </script>
</body>
</html>
