<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal Bendahara RT - Layanan Publik Warga</title>
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
        .badge-in { background: #ECFDF5; color: #065F46; }
        .badge-out { background: #FEF2F2; color: #991B1B; }
        .amount-income { font-weight: 600; color: var(--emerald); }
        .amount-expense { font-weight: 600; color: var(--rose); }
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
        .profile-banner { display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #1B365D 0%, #059669 100%); color: white; padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 2rem; }
        .alert-status { background: #ECFDF5; border-left: 4px solid var(--emerald); color: #065F46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem; }
    </style>
</head>
<body>
    <header class="topbar">
        <a href="/">
            <x-brand-logo variant="symbol" class="h-7 w-7" />
            <span>Portal Resmi Bendahara RT 01 / RW 05</span>
            <span class="badge badge-role">Bendahara RT</span>
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

        <!-- Banner Profil Bendahara RT -->
        <div class="profile-banner">
            <div>
                <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.85;">Tata Kelola Keuangan & Kas Lingkungan RT</div>
                <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0.25rem 0;">{{ $user->name }}</h1>
                <p style="font-size: 0.875rem; opacity: 0.9;">{{ $user->email }} | NIK: {{ $user->citizen?->nik ?? 'Terkoneksi Akun Pejabat RT' }}</p>
            </div>
            <div style="text-align: right;">
                <span class="badge" style="background: #10B981; color: white; font-size: 0.8rem; padding: 0.35rem 0.75rem;">Masa Bakti Aktif</span>
                <div style="font-size: 0.75rem; margin-top: 0.35rem; opacity: 0.8;">Buku Kas Anti-Fraud</div>
            </div>
        </div>

        <!-- Ringkasan Finansial -->
        <div class="grid-stats">
            <div class="card card-stat">
                <h3>Saldo Kas RT Saat Ini</h3>
                <div class="value" style="font-size: 1.5rem; color: var(--primary);">
                    Rp {{ number_format($financeSummary['current_balance'] ?? 0, 0, ',', '.') }}
                </div>
                <small style="color: var(--text-muted);">Periode {{ $financeSummary['period'] ?? date('F Y') }}</small>
            </div>
            <div class="card card-stat">
                <h3>Total Pemasukan Bulan Ini</h3>
                <div class="value" style="color: var(--emerald);">
                    +Rp {{ number_format($financeSummary['total_income'] ?? 0, 0, ',', '.') }}
                </div>
                <small style="color: var(--text-muted);">Iuran warga & sumbangan</small>
            </div>
            <div class="card card-stat">
                <h3>Total Pengeluaran Bulan Ini</h3>
                <div class="value" style="color: var(--rose);">
                    -Rp {{ number_format($financeSummary['total_expense'] ?? 0, 0, ',', '.') }}
                </div>
                <small style="color: var(--text-muted);">Operasional & perawatan</small>
            </div>
            <div class="card card-stat">
                <h3>Transaksi Buku Kas</h3>
                <div class="value" style="color: var(--teal);">
                    {{ $recentTransactions->count() }}
                </div>
                <small style="color: var(--text-muted);">Mutasi mutakhir tercatat</small>
            </div>
        </div>

        <!-- Tata Kelola Dokumen & Cadangan Buku Kas RT -->
        <div class="card" style="border-left: 4px solid var(--emerald);">
            <div class="section-title" style="color: var(--emerald);">
                <i data-lucide="wallet" style="width: 20px; height: 20px;"></i>
                Layanan Unduh Rekapitulasi Kas & Pencadangan Data (Immutable Ledger Store)
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                Sebagai Bendahara RT, Anda dapat mengunduh rekapitulasi buku kas RT untuk pelaporan bulanan, memantau pembayaran iuran warga, dan membuat cadangan data kas resmi ber-checksum SHA-256.
            </p>
            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                <a href="{{ route('finance.report.monthly', ['year' => date('Y'), 'month' => date('m'), 'format' => 'html']) }}" target="_blank" class="btn btn-primary">
                    <i data-lucide="file-text" style="width: 16px; height: 16px;"></i> Unduh Rekap Kas Bulanan (.HTML / Cetak)
                </a>
                <a href="{{ route('finance.report.monthly', ['year' => date('Y'), 'month' => date('m'), 'format' => 'csv']) }}" class="btn btn-outline">
                    <i data-lucide="download" style="width: 16px; height: 16px;"></i> Ekspor Rekap Kas (.CSV)
                </a>
                <a href="{{ route('finance.report.citizen-dues', ['year' => date('Y'), 'month' => date('m')]) }}" target="_blank" class="btn btn-teal">
                    <i data-lucide="users" style="width: 16px; height: 16px;"></i> Rekap Iuran Kas Warga
                </a>
                <button type="button" onclick="triggerBackupStore()" class="btn btn-outline" style="border-color: var(--emerald); color: var(--emerald);">
                    <i data-lucide="shield-check" style="width: 16px; height: 16px;"></i> Buat Cadangan Buku Kas (SHA-256)
                </button>
            </div>
            <div id="backupAlert" data-url="{{ route('finance.backup.create') }}" data-csrf="{{ csrf_token() }}" style="display: none; margin-top: 1rem; padding: 0.75rem; border-radius: 0.375rem; font-size: 0.85rem;"></div>
        </div>

        <!-- Daftar Mutasi Buku Kas Terbaru -->
        <div class="card">
            <div class="section-title">
                <i data-lucide="list-ordered" style="width: 20px; height: 20px; color: var(--primary);"></i>
                Mutasi Buku Kas Terakhir
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Tanggal</th>
                            <th>Kategori / Sumber</th>
                            <th>Tipe Mutasi</th>
                            <th>Jumlah (Rupiah)</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $tx)
                        <tr>
                            <td style="font-weight: 600; font-family: monospace;">{{ $tx->transaction_number ?: ('TRX-' . $tx->id) }}</td>
                            <td>{{ $tx->transaction_date ? \Carbon\Carbon::parse($tx->transaction_date)->translatedFormat('d M Y') : '-' }}</td>
                            <td>{{ $tx->source ?? $tx->category }}</td>
                            <td>
                                @if($tx->type === 'income')
                                    <span class="badge badge-in">Pemasukan</span>
                                @else
                                    <span class="badge badge-out">Pengeluaran</span>
                                @endif
                            </td>
                            <td class="{{ $tx->type === 'income' ? 'amount-income' : 'amount-expense' }}">
                                {{ $tx->type === 'income' ? '+' : '-' }}Rp {{ number_format($tx->amount_idr ?? $tx->amount, 0, ',', '.') }}
                            </td>
                            <td>{{ $tx->description }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                Belum ada mutasi buku kas tercatat.
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
                    'X-CSRF-TOKEN': alertBox.dataset.csrf
                };
                if (token) headers['Authorization'] = `Bearer ${token}`;

                const res = await fetch(alertBox.dataset.url, {
                    method: 'POST',
                    headers: headers
                });
                const json = await res.json();

                if (res.ok) {
                    alertBox.style.background = '#ECFDF5';
                    alertBox.style.color = '#065F46';
                    alertBox.textContent = `Berhasil: Cadangan data tersimpan di ${json.data?.backup_file} (Kode verifikasi: ${json.data?.checksum_sha256?.substring(0, 16)}...)`;
                } else {
                    alertBox.style.background = '#FEF2F2';
                    alertBox.style.color = '#991B1B';
                    alertBox.textContent = json.message || 'Gagal membuat cadangan data.';
                }
            } catch (err) {
                alertBox.style.background = '#FEF2F2';
                alertBox.style.color = '#991B1B';
                alertBox.textContent = 'Gagal menghubungi server penyimpanan cadangan.';
            }
        }
    </script>
</body>
</html>
