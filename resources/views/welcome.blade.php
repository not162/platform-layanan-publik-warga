<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ config('app.name', 'Layanan Publik Warga') }} - Portal Layanan Publik RT</title>

    <!-- Favicon & PWA meta -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#1B365D">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Portal Warga">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

    <style>
        :root {
            color-scheme: light;
            --color-primary: #1B365D;
            --color-primary-dark: #12243F;
            --color-teal: #3A9696;
            --color-teal-light: #EBF7F7;
            --color-teal-dark: #276969;
            --color-slate: #475569;
            --color-slate-muted: #64748B;
            --color-slate-light: #F8FAFC;
            --color-border: #E2E8F0;
            --color-card: #FFFFFF;
            --color-emerald: #059669;
            --color-emerald-light: #ECFDF5;
            --color-amber: #D97706;
            --color-amber-light: #FFFBEB;
            --color-red: #DC2626;
            --color-red-light: #FEF2F2;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: var(--color-slate-light);
            color: #1E293B;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.6;
        }

        /* Container */
        .container {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* Header */
        header {
            background-color: #FFFFFF;
            border-bottom: 1px solid var(--color-border);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 4.5rem;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            list-style: none;
        }

        .nav-link {
            text-decoration: none;
            color: var(--color-slate);
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.15s ease;
        }

        .nav-link:hover {
            color: var(--color-primary);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .btn-primary {
            background-color: var(--color-primary);
            color: #FFFFFF;
        }

        .btn-primary:hover {
            background-color: var(--color-primary-dark);
            box-shadow: 0 4px 12px rgba(27, 54, 93, 0.2);
        }

        .btn-secondary {
            background-color: #FFFFFF;
            color: var(--color-primary);
            border-color: var(--color-border);
        }

        .btn-secondary:hover {
            border-color: var(--color-primary);
            background-color: var(--color-slate-light);
        }

        .btn-teal {
            background-color: var(--color-teal);
            color: #FFFFFF;
        }

        .btn-teal:hover {
            background-color: var(--color-teal-dark);
        }

        /* Hero */
        .hero {
            padding: 4.5rem 0 3.5rem;
            background: linear-gradient(180deg, #FFFFFF 0%, var(--color-slate-light) 100%);
            border-bottom: 1px solid var(--color-border);
            text-align: center;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: var(--color-teal-light);
            color: var(--color-teal);
            border: 1px solid rgba(58, 150, 150, 0.25);
            padding: 0.35rem 0.9rem;
            border-radius: 9999px;
            font-size: 0.825rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .hero h1 {
            font-size: 2.75rem;
            font-weight: 800;
            color: var(--color-primary);
            line-height: 1.2;
            letter-spacing: -0.025em;
            margin-bottom: 1.25rem;
        }

        .hero h1 span {
            color: var(--color-teal);
        }

        .hero p {
            font-size: 1.125rem;
            color: var(--color-slate);
            max-width: 720px;
            margin: 0 auto 2.25rem;
        }

        /* Ticket Tracker in Hero */
        .ticket-box {
            max-width: 580px;
            margin: 0 auto 2rem;
            background: #FFFFFF;
            padding: 0.5rem;
            border-radius: 0.75rem;
            border: 1px solid var(--color-border);
            box-shadow: 0 10px 25px -5px rgba(27, 54, 93, 0.08);
            display: flex;
            gap: 0.5rem;
        }

        .ticket-input {
            flex: 1;
            border: none;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            outline: none;
            color: #1E293B;
            background: transparent;
        }

        .ticket-result-box {
            max-width: 580px;
            margin: 0 auto 1.5rem;
            background: #FFFFFF;
            border: 1px solid var(--color-border);
            border-radius: 0.75rem;
            padding: 1.25rem;
            text-align: left;
            display: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-top: -2rem;
            margin-bottom: 3.5rem;
            position: relative;
            z-index: 10;
        }

        .stat-card {
            background-color: #FFFFFF;
            border: 1px solid var(--color-border);
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .stat-label {
            font-size: 0.825rem;
            font-weight: 600;
            color: var(--color-slate-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-value {
            font-size: 1.65rem;
            font-weight: 800;
            color: var(--color-primary);
        }

        .stat-note {
            font-size: 0.8rem;
            color: var(--color-slate);
        }

        /* Section Header */
        .section-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 2rem;
        }

        .section-header h2 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--color-primary);
        }

        .section-header p {
            color: var(--color-slate-muted);
            font-size: 0.95rem;
            margin-top: 0.25rem;
        }

        /* Grid layouts */
        .content-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 4rem;
        }

        .content-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 4rem;
        }

        /* Cards */
        .panel-card {
            background: #FFFFFF;
            border: 1px solid var(--color-border);
            border-radius: 0.75rem;
            padding: 1.75rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
        }

        .announcement-item {
            padding: 1.25rem 0;
            border-bottom: 1px solid var(--color-border);
        }

        .announcement-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .announcement-item:first-child {
            padding-top: 0;
        }

        .badge {
            display: inline-block;
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-kegiatan { background: var(--color-teal-light); color: var(--color-teal); }
        .badge-iuran { background: var(--color-emerald-light); color: var(--color-emerald); }
        .badge-keamanan { background: var(--color-amber-light); color: var(--color-amber); }
        .badge-darurat { background: var(--color-red-light); color: var(--color-red); }
        .badge-umum { background: #EEF2F6; color: var(--color-primary); }

        .pinned-badge {
            background: #FEF3C7;
            color: #92400E;
            font-size: 0.725rem;
            font-weight: 700;
            padding: 0.15rem 0.45rem;
            border-radius: 0.25rem;
            margin-left: 0.5rem;
        }

        /* Emergency quick call box */
        .emergency-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }

        .emergency-card {
            background: #FFFFFF;
            border: 1px solid var(--color-border);
            border-radius: 0.5rem;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s ease;
            text-decoration: none;
            color: inherit;
        }

        .emergency-card:hover {
            border-color: var(--color-red);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.1);
        }

        .emergency-num {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--color-red);
        }

        /* Table */
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .schedule-table th {
            text-align: left;
            padding: 0.75rem 1rem;
            background: var(--color-slate-light);
            border-bottom: 2px solid var(--color-border);
            color: var(--color-slate);
            font-weight: 600;
        }

        .schedule-table td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--color-border);
            color: #1E293B;
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.5);
            z-index: 100;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-box {
            background: #FFFFFF;
            border-radius: 0.75rem;
            max-width: 520px;
            width: 100%;
            padding: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--color-slate);
            margin-bottom: 0.35rem;
        }

        .form-input, .form-textarea, .form-select {
            width: 100%;
            border: 1px solid var(--color-border);
            border-radius: 0.5rem;
            padding: 0.65rem 0.85rem;
            font-size: 0.925rem;
            outline: none;
            color: #1E293B;
        }

        .form-input:focus, .form-textarea:focus, .form-select:focus {
            border-color: var(--color-teal);
            box-shadow: 0 0 0 3px rgba(58, 150, 150, 0.15);
        }

        /* Footer */
        footer {
            margin-top: auto;
            background-color: #FFFFFF;
            border-top: 1px solid var(--color-border);
            padding: 2.5rem 0;
        }

        .footer-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                height: auto;
                padding: 1rem 0;
                gap: 1rem;
            }
            .hero h1 {
                font-size: 2rem;
            }
            .stats-grid, .content-grid-2, .content-grid-3 {
                grid-template-columns: 1fr;
            }
            .ticket-box {
                flex-direction: column;
            }
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Top Header -->
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="{{ url('/') }}" aria-label="Layanan Publik Warga Home" style="display: flex; align-items: center; text-decoration: none;">
                    <x-brand-logo variant="horizontal" class="h-10" />
                </a>

                <ul class="nav-links">
                    <li><a href="#transparansi-kas" class="nav-link">Kas RT</a></li>
                    <li><a href="#pengumuman" class="nav-link">Pengumuman</a></li>
                    <li><a href="#agenda" class="nav-link">Agenda</a></li>
                    <li><a href="#keamanan" class="nav-link">Jadwal Ronda</a></li>
                    <li><a href="#darurat" class="nav-link">Kontak Darurat</a></li>
                    <li><button onclick="openComplaintModal()" class="btn btn-secondary" style="padding: 0.5rem 0.9rem; font-size: 0.85rem;">📝 Lapor Pengaduan</button></li>
                    @if (Route::has('login'))
                        @auth
                            <li><a href="{{ url('/dashboard') }}" class="btn btn-primary">Dashboard Warga</a></li>
                        @else
                            <li><a href="{{ Route::has('login') ? route('login') : url('/login') }}" class="btn btn-primary">Masuk Portal</a></li>
                        @endauth
                    @endif
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-badge">
                <span>🛡️</span> Portal Administrasi RT Terbuka, Transparan & Terverifikasi
            </div>
            <h1>Layanan Publik <span>Warga RT</span></h1>
            <p>
                Akses layanan mandiri warga: buat permohonan surat pengantar, pantau status permohonan secara real-time, transparansi kas lingkungan, dan laporkan keluhan lingkungan tanpa membuka data pribadi Anda.
            </p>

            <!-- Lacak Surat Quick Input -->
            <div class="ticket-box">
                <input type="text" id="trackInput" class="ticket-input" placeholder="Lacak surat? Masukkan nomor tiket (cth: SRT-202610-0001)...">
                <button type="button" onclick="trackTicket()" class="btn btn-teal">
                    <span>🔍</span> Lacak Tiket
                </button>
            </div>

            <!-- Ticket Track Result Container -->
            <div id="ticketResult" class="ticket-result-box">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                    <div>
                        <span id="ticketBadge" class="badge badge-umum">Status</span>
                        <h4 id="ticketNumberText" style="color: var(--color-primary); margin-top: 0.25rem;">-</h4>
                    </div>
                    <button onclick="document.getElementById('ticketResult').style.display='none'" style="border: none; background: transparent; cursor: pointer; color: var(--color-slate); font-size: 1.1rem;">✕</button>
                </div>
                <div style="font-size: 0.875rem; color: var(--color-slate); line-height: 1.5;">
                    <p><strong>Jenis Surat:</strong> <span id="ticketLetterType">-</span></p>
                    <p><strong>Tanggal Diajukan:</strong> <span id="ticketCreatedAt">-</span></p>
                    <p><strong>Catatan Petugas:</strong> <span id="ticketNotes">-</span></p>
                </div>
            </div>

            <div class="hero-cta" style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">Buka Dashboard Saya</a>
                @else
                    <a href="{{ Route::has('login') ? route('login') : url('/login') }}" class="btn btn-primary">Masuk ke Portal Warga</a>
                @endauth
                <button onclick="openComplaintModal()" class="btn btn-secondary">Pengaduan Lingkungan Cepat</button>
            </div>
        </div>
    </section>

    <!-- Transparansi Kas Section (Overlapping Stats Cards) -->
    <section id="transparansi-kas" class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-label">Total Pemasukan Kas RT</span>
                <span class="stat-value" style="color: var(--color-emerald);">Rp {{ number_format($financeSummary['total_income'] ?? 0, 0, ',', '.') }}</span>
                <span class="stat-note">Iuran warga & sumbangan resmi lingkungan</span>
            </div>

            <div class="stat-card">
                <span class="stat-label">Total Pengeluaran Kas RT</span>
                <span class="stat-value" style="color: var(--color-red);">Rp {{ number_format($financeSummary['total_expense'] ?? 0, 0, ',', '.') }}</span>
                <span class="stat-note">Kebersihan, keamanan, & perawatan fasilitas</span>
            </div>

            <div class="stat-card">
                <span class="stat-label">Saldo Bersih Kas Saat Ini</span>
                <span class="stat-value" style="color: var(--color-primary);">Rp {{ number_format($financeSummary['net_balance'] ?? 0, 0, ',', '.') }}</span>
                <span class="stat-note">Saldo kas terverifikasi & mutasi terbuka</span>
            </div>
        </div>
    </section>

    <!-- Public Community Content: Pengumuman & Agenda -->
    <section id="pengumuman" class="container" style="margin-bottom: 4rem;">
        <div class="content-grid-2">
            <!-- Papan Pengumuman Warga -->
            <div class="panel-card">
                <div class="section-header" style="margin-bottom: 1.25rem;">
                    <div>
                        <h2>📢 Papan Pengumuman</h2>
                        <p>Kabar penting dan warta lingkungan RT</p>
                    </div>
                </div>

                @forelse($announcements as $announcement)
                    <div class="announcement-item">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                            <span class="badge badge-{{ $announcement->category ?? 'umum' }}">{{ $announcement->category ?? 'umum' }}</span>
                            @if($announcement->is_pinned)
                                <span class="pinned-badge">📌 PENTING</span>
                            @endif
                            <span style="font-size: 0.775rem; color: var(--color-slate-muted); margin-left: auto;">
                                {{ $announcement->published_at ? $announcement->published_at->format('d M Y') : '' }}
                            </span>
                        </div>
                        <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--color-primary); margin-bottom: 0.35rem;">
                            {{ $announcement->title }}
                        </h3>
                        <p style="font-size: 0.875rem; color: var(--color-slate);">
                            {{ Str::limit($announcement->content, 120) }}
                        </p>
                    </div>
                @empty
                    <div style="padding: 2rem 0; text-align: center; color: var(--color-slate-muted);">
                        Belum ada pengumuman terbaru yang dipublikasikan.
                    </div>
                @endforelse
            </div>

            <!-- Agenda Kegiatan RT -->
            <div id="agenda" class="panel-card">
                <div class="section-header" style="margin-bottom: 1.25rem;">
                    <div>
                        <h2>🗓️ Agenda Kegiatan Warga</h2>
                        <p>Jadwal kegiatan sosial, rapat, & kerja bakti</p>
                    </div>
                </div>

                @forelse($upcomingEvents as $event)
                    <div class="announcement-item">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.35rem;">
                            <span style="font-size: 0.8rem; font-weight: 700; color: var(--color-teal);">
                                📍 {{ $event->location }}
                            </span>
                            <span style="font-size: 0.775rem; color: var(--color-slate-muted);">
                                {{ $event->event_date?->format('d M Y') }} @if($event->start_time)• {{ substr($event->start_time, 0, 5) }} WIB @endif
                            </span>
                        </div>
                        <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--color-primary); margin-bottom: 0.35rem;">
                            {{ $event->title }}
                        </h3>
                        <p style="font-size: 0.875rem; color: var(--color-slate);">
                            {{ Str::limit($event->description, 120) }}
                        </p>
                    </div>
                @empty
                    <div style="padding: 2rem 0; text-align: center; color: var(--color-slate-muted);">
                        Belum ada agenda kegiatan mendatang.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Keamanan & Kontak Darurat -->
    <section id="keamanan" class="container" style="margin-bottom: 4rem;">
        <div class="content-grid-2">
            <!-- Jadwal Ronda Malam -->
            <div class="panel-card">
                <div class="section-header" style="margin-bottom: 1.25rem;">
                    <div>
                        <h2>🛡️ Jadwal Ronda & Kamling</h2>
                        <p>Petugas jaga malam pos keamanan lingkungan</p>
                    </div>
                </div>

                @if($roundSchedules->isNotEmpty())
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Hari</th>
                                <th>Shift & Pos</th>
                                <th>Petugas Jaga</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roundSchedules as $sched)
                                <tr>
                                    <td style="font-weight: 700; text-transform: capitalize;">{{ $sched->day_of_week }}</td>
                                    <td>{{ $sched->shift_name }}<br><small style="color: var(--color-slate-muted);">{{ $sched->pos_location }}</small></td>
                                    <td>{{ is_array($sched->officer_names) ? implode(', ', $sched->officer_names) : $sched->officer_names }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="padding: 2rem 0; text-align: center; color: var(--color-slate-muted);">
                        Jadwal ronda minggu ini belum dirilis pengurus.
                    </div>
                @endif
            </div>

            <!-- Kontak Darurat & Pengurus -->
            <div id="darurat" class="panel-card">
                <div class="section-header" style="margin-bottom: 1.25rem;">
                    <div>
                        <h2>🚨 Kontak Darurat 24 Jam</h2>
                        <p>Akses cepat panggilan darurat & pengurus</p>
                    </div>
                </div>

                <div class="emergency-grid">
                    @forelse($emergencyContacts as $contact)
                        <a href="tel:{{ $contact->phone_number }}" class="emergency-card">
                            <div>
                                <div style="font-weight: 700; font-size: 0.9rem; color: var(--color-primary);">{{ $contact->name }}</div>
                                <div style="font-size: 0.775rem; color: var(--color-slate-muted);">{{ ucfirst($contact->category) }}</div>
                            </div>
                            <span class="emergency-num">{{ $contact->phone_number }}</span>
                        </a>
                    @empty
                        <a href="tel:110" class="emergency-card">
                            <div>
                                <div style="font-weight: 700; font-size: 0.9rem; color: var(--color-primary);">Polisi RI</div>
                                <div style="font-size: 0.775rem; color: var(--color-slate-muted);">Layanan Presisi 24 Jam</div>
                            </div>
                            <span class="emergency-num">110</span>
                        </a>
                        <a href="tel:119" class="emergency-card">
                            <div>
                                <div style="font-weight: 700; font-size: 0.9rem; color: var(--color-primary);">Ambulans Gawat Darurat</div>
                                <div style="font-size: 0.775rem; color: var(--color-slate-muted);">Kedaruratan Medis</div>
                            </div>
                            <span class="emergency-num">119</span>
                        </a>
                    @endforelse
                </div>

                <!-- Pengurus RT -->
                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--color-border);">
                    <h4 style="font-size: 0.925rem; font-weight: 700; color: var(--color-primary); margin-bottom: 0.75rem;">Struktur Pengurus RT</h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                        @forelse($officers as $officer)
                            <div style="background: var(--color-slate-light); border: 1px solid var(--color-border); border-radius: 0.5rem; padding: 0.4rem 0.75rem; font-size: 0.825rem;">
                                <strong style="color: var(--color-primary);">{{ $officer->position }}:</strong> {{ $officer->name }}
                            </div>
                        @empty
                            <div style="font-size: 0.825rem; color: var(--color-slate-muted);">Pengurus RT 01</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal Form Pengaduan Warga Cepat -->
    <div id="complaintModal" class="modal-overlay">
        <div class="modal-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <h3 style="color: var(--color-primary); font-size: 1.25rem; font-weight: 700;">Form Pengaduan Lingkungan</h3>
                <button type="button" onclick="closeComplaintModal()" style="background: transparent; border: none; font-size: 1.25rem; cursor: pointer; color: var(--color-slate);">✕</button>
            </div>

            <form id="complaintForm" onsubmit="submitComplaint(event)">
                <div class="form-group">
                    <label class="form-label" for="complaintTitle">Judul Laporan / Keluhan</label>
                    <input type="text" id="complaintTitle" class="form-input" required placeholder="Cth: Lampu jalan depan gang mati">
                </div>

                <div class="form-group">
                    <label class="form-label" for="complaintCategory">Kategori Masalah</label>
                    <select id="complaintCategory" class="form-select" required>
                        <option value="fasilitas">Fasilitas Umum & Jalan</option>
                        <option value="kebersihan">Kebersihan & Sampah</option>
                        <option value="keamanan">Keamanan & Ketertiban</option>
                        <option value="lainnya">Lain-lain</option>
                    </select>
                </div>

                <!-- Input Nama Jalan & Posisi Spesifik -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <div class="form-group">
                        <label class="form-label" for="complaintStreet">Nama Jalan / Gang</label>
                        <input type="text" id="complaintStreet" class="form-input" required placeholder="Cth: Jl. Mawar Gang 2">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="complaintLocationDetail">Bagian / Posisi Spesifik</label>
                        <input type="text" id="complaintLocationDetail" class="form-input" required placeholder="Cth: Depan No. 12 / Tiang listrik">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="complaintDescription">Rincian Deskripsi Kronologi</label>
                    <textarea id="complaintDescription" class="form-textarea" rows="2" required placeholder="Jelaskan detail kronologi kejadian secara jelas..."></textarea>
                </div>

                <!-- Permission Akses Kamera & Upload Foto (Khusus Akun Warga) -->
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                        <label class="form-label" style="margin-bottom: 0;">Bukti Foto Kejadian (Kamera)</label>
                        <span id="cameraPermissionBadge" style="font-size: 0.7rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 9999px; background: #FEF3C7; color: #92400E;">
                            🔒 Khusus Akun Warga
                        </span>
                    </div>

                    <!-- Kotak Kamera Aktif (Hanya untuk Warga Terverifikasi) -->
                    <div id="citizenCameraBox" style="display: none; border: 2px dashed #3A9696; border-radius: 0.5rem; padding: 1rem; text-align: center; background: #EBF7F7;">
                        <input type="file" id="complaintImage" accept="image/*" capture="environment" style="display: none;" onchange="handleImagePreview(event)">
                        <button type="button" onclick="document.getElementById('complaintImage').click()" class="btn btn-teal" style="font-size: 0.85rem; padding: 0.4rem 0.85rem;">
                            📷 Ambil Foto Kamera / Pilih Gambar
                        </button>
                        <p style="font-size: 0.75rem; color: #475569; margin-top: 0.4rem;">Format: JPG, PNG, WEBP (Maksimal 5MB)</p>
                        
                        <div id="imagePreviewContainer" style="display: none; margin-top: 0.75rem;">
                            <img id="imagePreview" src="" alt="Pratinjau Foto" style="max-height: 120px; border-radius: 0.375rem; border: 1px solid #CBD5E1; margin: 0 auto; display: block; object-fit: contain;">
                            <span id="imageFileName" style="font-size: 0.75rem; color: #0F172A; margin-top: 0.25rem; font-weight: 500; display: block;"></span>
                            <button type="button" onclick="clearImage()" style="margin-top: 0.25rem; background: none; border: none; color: #DC2626; font-size: 0.75rem; cursor: pointer; text-decoration: underline;">
                                Hapus Foto
                            </button>
                        </div>
                    </div>

                    <!-- Kotak Izin Terkunci (Tamu / Belum Login) -->
                    <div id="lockedCameraBox" style="border: 1px solid #FCD34D; border-radius: 0.5rem; padding: 0.75rem 0.85rem; background: #FFFBEB; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                        <div style="font-size: 0.8rem; color: #92400E;">
                            <strong>Izin Akses Kamera Terkunci:</strong> Upload bukti foto kamera hanya diizinkan untuk akun warga RT terdaftar.
                        </div>
                        <a href="/login" class="btn btn-primary" style="font-size: 0.75rem; padding: 0.3rem 0.6rem; shrink-0; white-space: nowrap;">Masuk Akun</a>
                    </div>
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" id="complaintAnonymous" value="1">
                    <label for="complaintAnonymous" style="font-size: 0.85rem; color: var(--color-slate); cursor: pointer;">
                        Kirim sebagai laporan anonim (nama Anda tidak ditampilkan ke publik)
                    </label>
                </div>

                <div id="complaintAlert" style="display: none; padding: 0.75rem; border-radius: 0.375rem; font-size: 0.85rem; margin-bottom: 1rem;"></div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button type="button" onclick="closeComplaintModal()" class="btn btn-secondary">Batal</button>
                    <button type="submit" id="btnSubmitComplaint" class="btn btn-primary">Kirim Laporan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <x-brand-logo variant="symbol" class="h-8" />
                    <div>
                        <strong style="color: var(--color-primary); font-size: 0.95rem;">Layanan Publik Warga</strong>
                        <div style="font-size: 0.75rem; color: var(--color-slate);">Portal Lingkungan Mandiri & Terbuka</div>
                    </div>
                </div>
                <div class="footer-copy" style="font-size: 0.85rem; color: var(--color-slate);">
                    &copy; {{ date('Y') }} Layanan Publik Warga. Pelayanan • Kebersamaan • Transparansi • Kepercayaan.
                </div>
            </div>
        </div>
    </footer>

    <!-- Client-side Tracker & Complaint handler (Pure Light Theme Vanilla JS) -->
    <script>
        async function trackTicket() {
            const ticketInput = document.getElementById('trackInput').value.trim();
            const resultBox = document.getElementById('ticketResult');

            if (!ticketInput) {
                alert('Silakan masukkan nomor tiket permohonan surat.');
                return;
            }

            try {
                const response = await fetch(`/api/v1/public/track/${encodeURIComponent(ticketInput)}`);
                const json = await response.json();

                if (!response.ok) {
                    alert(json.message || 'Nomor tiket permohonan tidak ditemukan.');
                    resultBox.style.display = 'none';
                    return;
                }

                const data = json.data;
                document.getElementById('ticketBadge').textContent = (data.status || 'PROSES').toUpperCase();
                document.getElementById('ticketNumberText').textContent = data.ticket_number || ticketInput;
                document.getElementById('ticketLetterType').textContent = data.letter_type?.name || data.purpose || 'Surat Pengantar RT';
                document.getElementById('ticketCreatedAt').textContent = data.created_at ? new Date(data.created_at).toLocaleDateString('id-ID', { dateStyle: 'long' }) : '-';
                document.getElementById('ticketNotes').textContent = data.rejection_reason || data.admin_notes || 'Sedang diproses oleh pengurus RT.';

                resultBox.style.display = 'block';
            } catch (err) {
                alert('Gagal menghubungi server layanan. Silakan coba kembali.');
            }
        }

        let selectedComplaintImage = null;

        async function openComplaintModal() {
            document.getElementById('complaintModal').style.display = 'flex';
            checkCameraPermission();
        }

        function closeComplaintModal() {
            document.getElementById('complaintModal').style.display = 'none';
            document.getElementById('complaintAlert').style.display = 'none';
            clearImage();
        }

        function checkCameraPermission() {
            const token = localStorage.getItem('auth_token');
            const profileStr = localStorage.getItem('user_profile');
            let isWarga = false;

            if (profileStr) {
                try {
                    const profile = JSON.parse(profileStr);
                    if (profile.role === 'WARGA' || profile.role === 'SUPERADMIN') {
                        isWarga = true;
                    }
                } catch(e) {}
            } else if (token) {
                isWarga = true; // Has authenticated token
            }

            const citizenBox = document.getElementById('citizenCameraBox');
            const lockedBox = document.getElementById('lockedCameraBox');
            const badge = document.getElementById('cameraPermissionBadge');

            if (isWarga) {
                citizenBox.style.display = 'block';
                lockedBox.style.display = 'none';
                badge.style.background = '#ECFDF5';
                badge.style.color = '#065F46';
                badge.textContent = '✓ Izin Kamera Aktif (Warga)';
            } else {
                citizenBox.style.display = 'none';
                lockedBox.style.display = 'flex';
                badge.style.background = '#FEF3C7';
                badge.style.color = '#92400E';
                badge.textContent = '🔒 Khusus Akun Warga';
            }
        }

        function handleImagePreview(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                alert('Ukuran foto terlalu besar. Maksimal 5 MB.');
                event.target.value = '';
                return;
            }

            selectedComplaintImage = file;
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').src = e.target.result;
                document.getElementById('imageFileName').textContent = `${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
                document.getElementById('imagePreviewContainer').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }

        function clearImage() {
            selectedComplaintImage = null;
            const fileInput = document.getElementById('complaintImage');
            if (fileInput) fileInput.value = '';
            const previewContainer = document.getElementById('imagePreviewContainer');
            if (previewContainer) previewContainer.style.display = 'none';
        }

        async function submitComplaint(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitComplaint');
            const alertBox = document.getElementById('complaintAlert');

            btn.disabled = true;
            btn.textContent = 'Mengirim...';

            const formData = new FormData();
            formData.append('title', document.getElementById('complaintTitle').value.trim());
            formData.append('category', document.getElementById('complaintCategory').value);
            formData.append('street_name', document.getElementById('complaintStreet').value.trim());
            formData.append('location_detail', document.getElementById('complaintLocationDetail').value.trim());
            formData.append('description', document.getElementById('complaintDescription').value.trim());
            formData.append('is_anonymous', document.getElementById('complaintAnonymous').checked ? '1' : '0');

            if (selectedComplaintImage) {
                formData.append('image', selectedComplaintImage);
            }

            const headers = {
                'Accept': 'application/json'
            };
            const token = localStorage.getItem('auth_token');
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            try {
                const response = await fetch('/api/v1/complaints', {
                    method: 'POST',
                    headers: headers,
                    body: formData
                });

                const json = await response.json();

                if (response.ok) {
                    alertBox.style.display = 'block';
                    alertBox.style.background = '#ECFDF5';
                    alertBox.style.color = '#065F46';
                    alertBox.textContent = '✓ Laporan pengaduan berhasil dikirim ke pengurus RT.';
                    document.getElementById('complaintForm').reset();
                    clearImage();
                    setTimeout(() => {
                        closeComplaintModal();
                    }, 2200);
                } else {
                    alertBox.style.display = 'block';
                    alertBox.style.background = '#FEF2F2';
                    alertBox.style.color = '#991B1B';
                    alertBox.textContent = json.message || 'Gagal mengirim laporan.';
                }
            } catch (err) {
                alertBox.style.display = 'block';
                alertBox.style.background = '#FEF2F2';
                alertBox.style.color = '#991B1B';
                alertBox.textContent = 'Terjadi kesalahan jaringan saat mengirim laporan.';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Kirim Laporan';
            }
        }

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch((err) => {
                    console.log('SW registration:', err);
                });
            });
        }
    </script>
</body>
</html>
