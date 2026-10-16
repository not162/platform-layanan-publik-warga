<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Warga - Layanan Publik RT 01</title>
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
            --teal-light: #E6F4F4;
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
        
        /* Topbar Header */
        .topbar { background: var(--primary); color: white; padding: 0.85rem 1.5rem; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 4px rgba(0,0,0,0.08); }
        .topbar a.brand { color: white; text-decoration: none; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; font-size: 1.05rem; }
        .topbar-actions { display: flex; align-items: center; gap: 0.75rem; }
        
        /* Off-canvas Navigation Drawer */
        .nav-drawer-backdrop { display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); backdrop-filter: blur(2px); z-index: 200; transition: opacity 0.2s ease; }
        .nav-drawer-backdrop.active { display: block; }
        .nav-drawer { position: fixed; top: 0; right: -320px; width: 320px; height: 100%; background: #FFFFFF; z-index: 201; box-shadow: -4px 0 20px rgba(0,0,0,0.15); display: flex; flex-direction: column; transition: right 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        .nav-drawer.active { right: 0; }
        .drawer-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #F8FAFC; }
        .drawer-body { padding: 1rem 0; overflow-y: auto; flex: 1; }
        .drawer-heading { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94A3B8; padding: 0.75rem 1.5rem 0.35rem 1.5rem; }
        .drawer-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: #334155; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: all 0.15s ease; border-left: 3px solid transparent; width: 100%; border: none; background: transparent; text-align: left; cursor: pointer; }
        .drawer-item:hover, .drawer-item.active { background: #F1F5F9; color: var(--primary); border-left-color: var(--teal); font-weight: 600; }
        
        .container { max-width: 1200px; margin: 1.75rem auto; padding: 0 1.25rem; }
        
        /* Tab Navigation Bar */
        .tab-bar-container { background: white; border: 1px solid var(--border); border-radius: 0.75rem; padding: 0.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.03); overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .tab-bar { display: flex; gap: 0.4rem; min-width: max-content; }
        .tab-btn { display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.55rem 0.95rem; border-radius: 0.5rem; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); background: transparent; border: none; cursor: pointer; transition: all 0.15s; white-space: nowrap; }
        .tab-btn:hover { background: #F1F5F9; color: var(--text-dark); }
        .tab-btn.active { background: var(--primary); color: white; box-shadow: 0 2px 4px rgba(27,54,93,0.25); }
        
        /* Stats */
        .grid-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.03); margin-bottom: 1.5rem; }
        .card-stat h3 { font-size: 0.8rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600; margin-bottom: 0.4rem; }
        .card-stat .value { font-size: 1.65rem; font-weight: 700; color: var(--text-dark); }
        
        .badge { display: inline-flex; align-items: center; padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .badge-approved { background: #ECFDF5; color: #065F46; }
        .badge-pending { background: #EFF6FF; color: #1E40AF; }
        .badge-rejected { background: #FEF2F2; color: #991B1B; }
        .badge-info { background: #F0FDF4; color: #166534; }
        
        .btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; text-decoration: none; border: none; transition: all 0.15s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-light); }
        .btn-teal { background: var(--teal); color: white; }
        .btn-teal:hover { opacity: 0.92; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-dark); }
        .btn-outline:hover { background: #F1F5F9; }
        
        .section-title { font-size: 1.15rem; font-weight: 700; color: var(--text-dark); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        th, td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #F8FAFC; color: var(--text-muted); font-weight: 600; }
        
        /* Tab Sections */
        .tab-section { display: none; }
        .tab-section.active { display: block; animation: fadeIn 0.2s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Grid Cards */
        .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem; }
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; }
        
        /* Form inputs */
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-dark); margin-bottom: 0.35rem; }
        .form-control { width: 100%; padding: 0.6rem 0.85rem; border: 1px solid var(--border); border-radius: 0.5rem; font-size: 0.875rem; color: var(--text-dark); font-family: inherit; }
        .form-control:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(58,150,150,0.15); }
        textarea.form-control { min-height: 90px; resize: vertical; }
        
        @keyframes actionPulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.08); } }
        @keyframes actionShake { 0%, 100% { transform: translateX(0); } 20%, 60% { transform: translateX(-6px); } 40%, 80% { transform: translateX(6px); } }
        .animate-action-pulse { animation: actionPulse 1.8s infinite ease-in-out; }
        .animate-action-shake { animation: actionShake 0.6s ease-in-out; }
    </style>
</head>
<body>
    <!-- Topbar Header -->
    <header class="topbar">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <a href="{{ url('/') }}" class="brand">
                <x-brand-logo variant="symbol" class="h-7 w-7" />
                <span>Portal Warga RT 01</span>
            </a>
        </div>
        
        <div class="topbar-actions">
            <!-- Tombol Buka Menu Lengkap Layanan Warga -->
            <button type="button" onclick="toggleNavDrawer()" class="btn btn-outline" style="background: rgba(255,255,255,0.15); color: white; border-color: rgba(255,255,255,0.3); font-size: 0.8rem; padding: 0.35rem 0.75rem;" aria-label="Buka Menu Layanan Warga">
                <i data-lucide="menu" style="width: 15px; height: 15px;"></i> Menu Layanan
            </button>
            <a href="{{ route('profile') }}" class="btn btn-outline" style="background: rgba(255,255,255,0.15); color: white; border-color: rgba(255,255,255,0.3); font-size: 0.8rem; padding: 0.35rem 0.75rem;">
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

    <!-- Off-canvas Navigation Drawer (Pindahkan Semua Layanan Sesuai Gambar) -->
    <div id="navDrawerBackdrop" class="nav-drawer-backdrop" onclick="toggleNavDrawer()"></div>
    <aside id="navDrawer" class="nav-drawer" aria-label="Menu Lengkap Layanan Warga">
        <div class="drawer-header">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <x-brand-logo variant="symbol" class="h-7" />
                <span style="font-weight: 700; color: var(--primary); font-size: 0.95rem;">Layanan Mandiri Warga</span>
            </div>
            <button type="button" onclick="toggleNavDrawer()" style="background: none; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer;" aria-label="Tutup Menu">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>
        <div class="drawer-body">
            <div class="drawer-heading">INFORMASI UTAMA</div>
            <button type="button" onclick="switchTab('warta'); toggleNavDrawer();" class="drawer-item">
                <i data-lucide="newspaper" style="width: 18px; height: 18px; color: var(--teal);"></i>
                Warta & Pengumuman Baru
            </button>
            <button type="button" onclick="switchTab('kas'); toggleNavDrawer();" class="drawer-item">
                <i data-lucide="credit-card" style="width: 18px; height: 18px; color: var(--teal);"></i>
                Transparansi Kas Lingkungan
            </button>
            <button type="button" onclick="switchTab('pengaduan'); toggleNavDrawer();" class="drawer-item">
                <i data-lucide="file-text" style="width: 18px; height: 18px; color: var(--teal);"></i>
                Form Pengaduan Warga
            </button>

            <div class="drawer-heading">PELAYANAN & KEAMANAN</div>
            <button type="button" onclick="switchTab('agenda'); toggleNavDrawer();" class="drawer-item">
                <i data-lucide="calendar" style="width: 18px; height: 18px; color: var(--primary);"></i>
                Agenda Kegiatan Warga
            </button>
            <button type="button" onclick="switchTab('ronda'); toggleNavDrawer();" class="drawer-item">
                <i data-lucide="shield" style="width: 18px; height: 18px; color: var(--primary);"></i>
                Jadwal Ronda & Kamling
            </button>
            <button type="button" onclick="switchTab('darurat'); toggleNavDrawer();" class="drawer-item">
                <i data-lucide="phone-call" style="width: 18px; height: 18px; color: var(--primary);"></i>
                Kontak Darurat 24 Jam
            </button>
            <button type="button" onclick="switchTab('pengurus'); toggleNavDrawer();" class="drawer-item">
                <i data-lucide="users" style="width: 18px; height: 18px; color: var(--primary);"></i>
                Struktur Pengurus RT 01
            </button>

            <div class="drawer-heading">ADMINISTRASI SURAT</div>
            <button type="button" onclick="switchTab('surat'); toggleNavDrawer();" class="drawer-item">
                <i data-lucide="file-check-2" style="width: 18px; height: 18px; color: var(--emerald);"></i>
                Daftar Permohonan Surat Saya
            </button>
        </div>
    </aside>

    <main class="container">
        <!-- Status Flash Message -->
        @if(session('status'))
            <div style="background: #ECFDF5; border-left: 4px solid var(--emerald); color: #065F46; padding: 0.85rem 1.25rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if($user->citizen?->status_warga === 'pending_verification')
        <div style="background: #FEF3C7; border: 1px solid #FCD34D; color: #92400E; padding: 1rem 1.25rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; align-items: flex-start; gap: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="background: #FDE68A; padding: 0.35rem; border-radius: 0.5rem; margin-top: 2px;">
                <i data-lucide="clock" style="width: 20px; height: 20px; color: #B45309;"></i>
            </div>
            <div>
                <strong style="display: block; font-size: 0.95rem; margin-bottom: 0.25rem;">Pengajuan Akun Warga Baru Sedang Menunggu Verifikasi Pengurus RT</strong>
                <p style="font-size: 0.85rem; line-height: 1.5; opacity: 0.95;">
                    Data NIK <strong>{{ $user->citizen?->nik_masked ?? 'Tervalidasi' }}</strong> Anda telah diajukan dan sedang dalam antrean verifikasi oleh Sekretaris dan Ketua RT 01. Anda tetap dapat menjelajahi warta dan layanan RT.
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
                <div class="value" style="color: var(--rose);">{{ $mySecurityReportsCount }}</div>
                <small style="color: var(--text-muted);">Insiden lingkungan</small>
            </div>
        </div>

        <!-- Action Notification Banner (Berhasil / Tidak Berhasil Unduh Surat) -->
        <div id="actionNotificationCard" style="display: none; margin-bottom: 1.5rem; border-width: 1.5px;" class="card">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;">
                <div style="display: flex; align-items: flex-start; gap: 1rem; width: 100%;">
                    <div id="actionIconContainer" style="padding: 0.75rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; shrink: 0;">
                        <i id="actionIcon" data-lucide="check-circle-2" style="width: 26px; height: 26px;"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 id="actionTitle" style="font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem;"></h4>
                        <p id="actionMessage" style="font-size: 0.875rem; color: #475569; margin-bottom: 0.5rem;"></p>
                        <div id="actionReasonBox" style="display: none; background: #FFF1F2; border-left: 4px solid var(--rose); padding: 0.85rem 1rem; border-radius: 0.5rem; margin-top: 0.5rem;">
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

        <!-- Tab Bar Interaktif Warga (Memuat Semua Fitur Layanan) -->
        <div class="tab-bar-container">
            <div class="tab-bar" role="tablist">
                <button type="button" id="tab-btn-surat" class="tab-btn active" onclick="switchTab('surat')">
                    <i data-lucide="file-text" style="width: 16px; height: 16px;"></i> Surat Saya
                </button>
                <button type="button" id="tab-btn-warta" class="tab-btn" onclick="switchTab('warta')">
                    <i data-lucide="newspaper" style="width: 16px; height: 16px;"></i> Warta & Pengumuman
                </button>
                <button type="button" id="tab-btn-kas" class="tab-btn" onclick="switchTab('kas')">
                    <i data-lucide="credit-card" style="width: 16px; height: 16px;"></i> Transparansi Kas
                </button>
                <button type="button" id="tab-btn-pengaduan" class="tab-btn" onclick="switchTab('pengaduan')">
                    <i data-lucide="message-square" style="width: 16px; height: 16px;"></i> Form Pengaduan
                </button>
                <button type="button" id="tab-btn-agenda" class="tab-btn" onclick="switchTab('agenda')">
                    <i data-lucide="calendar" style="width: 16px; height: 16px;"></i> Agenda Warga
                </button>
                <button type="button" id="tab-btn-ronda" class="tab-btn" onclick="switchTab('ronda')">
                    <i data-lucide="shield" style="width: 16px; height: 16px;"></i> Jadwal Ronda
                </button>
                <button type="button" id="tab-btn-darurat" class="tab-btn" onclick="switchTab('darurat')">
                    <i data-lucide="phone-call" style="width: 16px; height: 16px;"></i> Kontak Darurat
                </button>
                <button type="button" id="tab-btn-pengurus" class="tab-btn" onclick="switchTab('pengurus')">
                    <i data-lucide="users" style="width: 16px; height: 16px;"></i> Pengurus RT
                </button>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: PERMOHONAN SURAT SAYA -->
        <!-- ========================================================================= -->
        <section id="section-surat" class="tab-section active">
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div class="section-title" style="margin-bottom: 0;">
                        <i data-lucide="file-check-2" style="width: 20px; height: 20px; color: var(--primary);"></i>
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
                                    @if(in_array($letter->status, ['approved', 'completed']))
                                        <span class="badge badge-approved">DISETUJUI</span>
                                    @elseif($letter->status === 'rejected')
                                        <span class="badge badge-rejected">DITOLAK</span>
                                    @else
                                        <span class="badge badge-pending">{{ strtoupper($letter->status) }}</span>
                                    @endif
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
        </section>

        <!-- ========================================================================= -->
        <!-- TAB 2: WARTA & PENGUMUMAN BARU -->
        <!-- ========================================================================= -->
        <section id="section-warta" class="tab-section">
            <div class="card">
                <div class="section-title">
                    <i data-lucide="newspaper" style="width: 20px; height: 20px; color: var(--teal);"></i>
                    Warta & Pengumuman Baru RT 01
                </div>
                <div class="grid-2">
                    @forelse($announcements as $ann)
                    <div style="border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem; background: #FAFBFD;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                            <span class="badge" style="background: #E0F2FE; color: #0369A1;">{{ $ann->category ?? 'Warta RT' }}</span>
                            <small style="color: var(--text-muted);">{{ $ann->created_at->translatedFormat('d F Y') }}</small>
                        </div>
                        <h4 style="font-size: 1rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.4rem;">{{ $ann->title }}</h4>
                        <p style="font-size: 0.85rem; color: #475569; line-height: 1.5;">{{ Str::limit($ann->content, 180) }}</p>
                    </div>
                    @empty
                    <div style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 2rem;">
                        Belum ada warta pengumuman baru yang diterbitkan oleh sekretaris RT.
                    </div>
                    @endforelse
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- TAB 3: TRANSPARANSI KAS LINGKUNGAN -->
        <!-- ========================================================================= -->
        <section id="section-kas" class="tab-section">
            <div class="card">
                <div class="section-title">
                    <i data-lucide="credit-card" style="width: 20px; height: 20px; color: var(--teal);"></i>
                    Transparansi Kas Lingkungan RT 01
                </div>

                <!-- Ringkasan Kas -->
                <div class="grid-stats" style="margin-bottom: 1.5rem;">
                    <div class="card card-stat" style="background: #F0FDF4; border-color: #BBF7D0;">
                        <h3>Total Pemasukan Kas</h3>
                        <div class="value" style="color: var(--emerald);">
                            Rp {{ number_format($financeSummary['total_income'] ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="card card-stat" style="background: #FFF1F2; border-color: #FECDD3;">
                        <h3>Total Pengeluaran Kas</h3>
                        <div class="value" style="color: var(--rose);">
                            Rp {{ number_format($financeSummary['total_expense'] ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="card card-stat" style="background: #EFF6FF; border-color: #BFDBFE;">
                        <h3>Saldo Kas Lingkungan</h3>
                        <div class="value" style="color: var(--primary);">
                            Rp {{ number_format($financeSummary['current_balance'] ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                <!-- Tabel Transaksi Terkini -->
                <h4 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 0.75rem;">Mutasi Pembukuan Kas Terkini</h4>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Deskripsi</th>
                                <th>Tipe</th>
                                <th>Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($financeTransactions as $txn)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($txn->transaction_date)->translatedFormat('d M Y') }}</td>
                                <td><span class="badge" style="background: #F1F5F9; color: var(--text-dark);">{{ $txn->category }}</span></td>
                                <td>{{ $txn->description }}</td>
                                <td>
                                    <span class="badge {{ $txn->type === 'income' ? 'badge-approved' : 'badge-rejected' }}">
                                        {{ $txn->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                                    </span>
                                </td>
                                <td>
                                    <strong>Rp {{ number_format($txn->amount_idr ?? (float)$txn->amount, 0, ',', '.') }}</strong>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                    Belum ada catatan mutasi kas yang dipublikasikan.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- TAB 4: FORM PENGADUAN WARGA -->
        <!-- ========================================================================= -->
        <section id="section-pengaduan" class="tab-section">
            <div class="card">
                <div class="section-title">
                    <i data-lucide="message-square" style="width: 20px; height: 20px; color: var(--teal);"></i>
                    Form Pelaporan Pengaduan & Aspirasi Warga
                </div>

                <div class="grid-2">
                    <!-- Formulir Pengaduan -->
                    <div>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                            Sampaikan keluhan fasilitas, ketertiban, sampah, atau keamanan lingkungan RT 01. Anda dapat memilih opsi anonim agar identitas tidak ditampilkan ke publik.
                        </p>
                        <form method="POST" action="{{ route('dashboard.complaints.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label class="form-label" for="title">Judul Laporan / Keluhan <span style="color: var(--rose);">*</span></label>
                                <input type="text" id="title" name="title" class="form-control" placeholder="Contoh: Lampu Penerangan Jalan Gang 2 Padam" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="category">Kategori Pengaduan</label>
                                <select id="category" name="category" class="form-control">
                                    <option value="Fasilitas Umum">Fasilitas Umum & Jalan</option>
                                    <option value="Kebersihan & Sampah">Kebersihan & Sampah</option>
                                    <option value="Ketertiban & Keamanan">Ketertiban & Keamanan</option>
                                    <option value="Administrasi Layanan">Administrasi & Layanan RT</option>
                                    <option value="Lain-lain">Lain-lain</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="street_name">Lokasi / Jalan / Gang</label>
                                <input type="text" id="street_name" name="street_name" class="form-control" placeholder="Contoh: Gang Cempaka RT 01 RW 05">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="description">Isi Rincian Pengaduan <span style="color: var(--rose);">*</span></label>
                                <textarea id="description" name="description" class="form-control" placeholder="Jelaskan kondisi permasalahan secara jelas..." required></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="image">Lampiran Foto Bukti (Opsional)</label>
                                <input type="file" id="image" name="image" accept="image/*" class="form-control" style="font-size: 0.8rem; padding: 0.4rem;">
                            </div>
                            <div class="form-group" style="margin-top: 0.5rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer; color: var(--text-dark);">
                                    <input type="checkbox" name="is_anonymous" value="1" style="width: 16px; height: 16px; accent-color: var(--teal);">
                                    <span><strong>Kirim Sebagai Anonim</strong> (Nama akun saya disembunyikan dari tampilan publik)</span>
                                </label>
                            </div>
                            <button type="submit" class="btn btn-teal" style="width: 100%; justify-content: center; margin-top: 0.5rem;">
                                <i data-lucide="send" style="width: 15px; height: 15px;"></i> Kirim Laporan Pengaduan
                            </button>
                        </form>
                    </div>

                    <!-- Riwayat Pengaduan Saya -->
                    <div>
                        <h4 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 0.75rem;">Riwayat Pengaduan Saya</h4>
                        @forelse($myComplaints as $cp)
                        <div style="border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.85rem; margin-bottom: 0.75rem; background: #FFF;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                <span style="font-size: 0.8rem; font-weight: 700; color: var(--primary);">{{ $cp->ticket_number }}</span>
                                <span class="badge" style="background: #E0E7FF; color: #3730A3;">{{ strtoupper($cp->status) }}</span>
                            </div>
                            <h5 style="font-size: 0.9rem; font-weight: 600; margin-bottom: 0.25rem;">{{ $cp->title }}</h5>
                            <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">{{ Str::limit($cp->description, 100) }}</p>
                            <div style="margin-top: 0.35rem; display: flex; justify-content: space-between; font-size: 0.75rem; color: #94A3B8;">
                                <span>{{ $cp->kategori ?? $cp->category }}</span>
                                <span>{{ $cp->created_at->translatedFormat('d M Y') }}</span>
                            </div>
                        </div>
                        @empty
                        <div style="text-align: center; color: var(--text-muted); padding: 2rem; border: 1px dashed var(--border); border-radius: 0.5rem;">
                            Anda belum pernah mengirim laporan pengaduan.
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- TAB 5: AGENDA KEGIATAN WARGA -->
        <!-- ========================================================================= -->
        <section id="section-agenda" class="tab-section">
            <div class="card">
                <div class="section-title">
                    <i data-lucide="calendar" style="width: 20px; height: 20px; color: var(--primary);"></i>
                    Agenda & Kegiatan Warga RT 01
                </div>
                <div class="grid-2">
                    @forelse($communityEvents as $event)
                    <div style="display: flex; gap: 1rem; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem; background: #FAFBFD;">
                        <div style="background: var(--teal-light); color: var(--teal); border-radius: 0.5rem; padding: 0.75rem; text-align: center; min-width: 65px; height: fit-content;">
                            <span style="font-size: 1.25rem; font-weight: 700; display: block; line-height: 1;">{{ \Carbon\Carbon::parse($event->event_date)->format('d') }}</span>
                            <small style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">{{ \Carbon\Carbon::parse($event->event_date)->translatedFormat('M') }}</small>
                        </div>
                        <div>
                            <h4 style="font-size: 1rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.25rem;">{{ $event->title }}</h4>
                            <div style="display: flex; gap: 0.75rem; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.4rem;">
                                <span><i data-lucide="map-pin" style="width: 12px; height: 12px; display: inline;"></i> {{ $event->location ?? 'Lingkungan RT 01' }}</span>
                                <span><i data-lucide="clock" style="width: 12px; height: 12px; display: inline;"></i> {{ \Carbon\Carbon::parse($event->event_date)->format('H:i') }} WIB</span>
                            </div>
                            <p style="font-size: 0.85rem; color: #475569; line-height: 1.4;">{{ $event->description }}</p>
                        </div>
                    </div>
                    @empty
                    <div style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 2rem;">
                        Belum ada agenda kegiatan warga yang dijadwalkan dalam waktu dekat.
                    </div>
                    @endforelse
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- TAB 6: JADWAL RONDA & KAMLING -->
        <!-- ========================================================================= -->
        <section id="section-ronda" class="tab-section">
            <div class="card">
                <div class="section-title">
                    <i data-lucide="shield" style="width: 20px; height: 20px; color: var(--primary);"></i>
                    Jadwal Ronda Malam & Siskamling RT 01
                </div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Hari</th>
                                <th>Petugas / Penanggung Jawab</th>
                                <th>Waktu Jaga</th>
                                <th>Pos Pantau</th>
                                <th>Catatan Operasional</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($roundSchedules as $ronda)
                            <tr>
                                <td><strong>{{ ucfirst($ronda->day_of_week ?? $ronda->hari ?? '') }}</strong></td>
                                <td>{{ is_array($ronda->officer_names) ? implode(', ', $ronda->officer_names) : ($ronda->officer_name ?? $ronda->petugas ?? 'Petugas Ronda') }}</td>
                                <td><span class="badge" style="background: #F1F5F9; color: var(--text-dark);">{{ $ronda->shift_name ?? '22:00 - 04:00 WIB' }}</span></td>
                                <td>{{ $ronda->pos_location ?? 'Pos Kamling RT 01' }}</td>
                                <td>{{ $ronda->notes ?? 'Patroli berkala setiap 2 jam.' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                    Jadwal ronda kamling belum diatur oleh petugas keamanan.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- TAB 7: KONTAK DARURAT 24 JAM -->
        <!-- ========================================================================= -->
        <section id="section-darurat" class="tab-section">
            <div class="card">
                <div class="section-title">
                    <i data-lucide="phone-call" style="width: 20px; height: 20px; color: var(--rose);"></i>
                    Kontak Darurat 24 Jam RT 01 & Sekitarnya
                </div>
                <div class="grid-3">
                    @forelse($emergencyContacts as $contact)
                    <div style="border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem; background: #FFF; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                                <div style="background: #FFF1F2; color: var(--rose); padding: 0.35rem; border-radius: 0.4rem;">
                                    <i data-lucide="phone" style="width: 16px; height: 16px;"></i>
                                </div>
                                <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-dark);">{{ $contact->name ?? $contact->title }}</h4>
                            </div>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.75rem;">{{ $contact->description ?? $contact->category ?? 'Layanan siaga cepat' }}</p>
                        </div>
                        <a href="tel:{{ $contact->phone_number ?? $contact->phone }}" class="btn btn-outline" style="border-color: var(--rose); color: var(--rose); justify-content: center; font-weight: 600;">
                            <i data-lucide="phone-outgoing" style="width: 14px; height: 14px;"></i> {{ $contact->phone_number ?? $contact->phone }}
                        </a>
                    </div>
                    @empty
                    <div style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 1.5rem;">
                        Belum ada kontak darurat yang didaftarkan.
                    </div>
                    @endforelse
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- TAB 8: STRUKTUR PENGURUS RT 01 -->
        <!-- ========================================================================= -->
        <section id="section-pengurus" class="tab-section">
            <div class="card">
                <div class="section-title">
                    <i data-lucide="users" style="width: 20px; height: 20px; color: var(--primary);"></i>
                    Struktur Kepengurusan RT 01 / RW 05 Kemayoran
                </div>
                <div class="grid-3">
                    @forelse($rtOfficers as $off)
                    <div style="border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem; text-align: center; background: #FAFBFD;">
                        @if($off->photo_url)
                            <img src="{{ asset($off->photo_url) }}" alt="{{ $off->name }}" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin: 0 auto 0.5rem auto; border: 2px solid var(--teal);">
                        @else
                            <div style="width: 60px; height: 60px; border-radius: 50%; background: #E0E7FF; color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; margin: 0 auto 0.5rem auto;">
                                {{ strtoupper(substr($off->name, 0, 2)) }}
                            </div>
                        @endif
                        <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-dark);">{{ $off->name }}</h4>
                        <span class="badge" style="background: #E0F2FE; color: #0369A1; margin: 0.35rem 0;">{{ $off->position }}</span>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                            <i data-lucide="phone" style="width: 12px; height: 12px; display: inline;"></i> {{ $off->phone ?? '—' }}
                        </div>
                    </div>
                    @empty
                    <div style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 1.5rem;">
                        Belum ada data pengurus yang diunggah.
                    </div>
                    @endforelse
                </div>
            </div>
        </section>
    </main>

    <script>
        lucide.createIcons();
        const exporter = new CitizenDocumentExporter();

        // Drawer toggle
        function toggleNavDrawer() {
            const drawer = document.getElementById('navDrawer');
            const backdrop = document.getElementById('navDrawerBackdrop');
            drawer.classList.toggle('active');
            backdrop.classList.toggle('active');
        }

        // Tab switcher
        function switchTab(tabId) {
            // Hide all sections
            document.querySelectorAll('.tab-section').forEach(sec => sec.classList.remove('active'));
            // Deactivate all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

            const targetSection = document.getElementById('section-' + tabId);
            const targetBtn = document.getElementById('tab-btn-' + tabId);

            if (targetSection) {
                targetSection.classList.add('active');
            }
            if (targetBtn) {
                targetBtn.classList.add('active');
                targetBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }

            window.location.hash = tabId;
            lucide.createIcons();
        }

        // Initialize active tab from URL hash if provided
        window.addEventListener('DOMContentLoaded', () => {
            const hash = (window.location.hash || '').replace('#', '');
            if (hash && document.getElementById('section-' + hash)) {
                switchTab(hash);
            }
        });

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
