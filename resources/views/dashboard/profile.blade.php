<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil Akun & Jabatan - Layanan Publik Warga</title>
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
        .container { max-width: 900px; margin: 2rem auto; padding: 0 1.5rem; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.03); margin-bottom: 1.5rem; }
        .badge { display: inline-flex; align-items: center; padding: 0.25rem 0.65rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .badge-role { background: rgba(255,255,255,0.18); color: #FFF; border: 1px solid rgba(255,255,255,0.3); }
        .btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; text-decoration: none; border: none; transition: all 0.15s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-light); }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-dark); }
        .btn-outline:hover { background: #F1F5F9; }
        .profile-header { display: flex; align-items: center; gap: 1.25rem; margin-bottom: 1.5rem; }
        .avatar-circle { width: 4.5rem; height: 4.5rem; border-radius: 9999px; background: #E0E7FF; color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.75rem; font-weight: 700; border: 2px solid #CBD5E1; }
        .detail-row { display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border); font-size: 0.875rem; }
        .detail-label { color: var(--text-muted); font-weight: 500; }
        .detail-val { color: var(--text-dark); font-weight: 600; text-align: right; }
        .profile-action { transition: transform 140ms ease, background-color 140ms ease; }
        .profile-action:active { transform: translateY(1px); }
        .profile-field { display: grid; gap: 0.4rem; color: var(--text-dark); font-size: 0.85rem; font-weight: 600; }
        .profile-field input { width: 100%; min-height: 2.7rem; padding: 0.65rem 0.75rem; border: 1px solid var(--border); border-radius: 0.4rem; color: var(--text-dark); font: inherit; font-weight: 400; }
        .profile-field input:focus { border-color: var(--teal); outline: 3px solid rgba(58, 150, 150, 0.16); }
        .verification-status { color: var(--text-muted); font-size: 0.8rem; font-weight: 500; }
        @media (prefers-reduced-motion: reduce) { .profile-action { transition: none; } }
    </style>
</head>
<body>
    <header class="topbar">
        <a href="{{ route('dashboard') }}">
            <i data-lucide="arrow-left" style="width: 18px; height: 18px;"></i>
            <span>Kembali ke Dashboard</span>
        </a>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <span class="badge badge-role">{{ $user->role->value ?? $user->role }}</span>
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-outline" style="background: rgba(255,255,255,0.1); color: white; border-color: rgba(255,255,255,0.2); font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                    Keluar
                </button>
            </form>
        </div>
    </header>

    <main class="container">
        @if(session('status'))
            <div role="status" style="background: #ECFDF5; border-left: 4px solid var(--emerald); color: #065F46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem;">
                {{ session('status') }}
            </div>
        @endif

        <div class="card">
            <div class="profile-header">
                @if($user->avatar_url || $officer?->photo_url)
                    <img src="{{ asset($user->avatar_url ?? $officer?->photo_url) }}" alt="{{ $user->name }}" class="avatar-circle" style="object-fit: cover;">
                @else
                    <div class="avatar-circle">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                @endif
                <div>
                    <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--text-dark);">{{ $user->name }}</h2>
                    <p style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.15rem;">{{ $user->email }}</p>
                    <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem; align-items: center;">
                        <span class="badge" style="background: #E0F2FE; color: #0369A1;">
                            {{ $officer?->position ?? ($user->role->value ?? (string) $user->role) }}
                        </span>
                        @if($user->is_active)
                            <span class="badge" style="background: #ECFDF5; color: #047857;">Status: Aktif</span>
                        @else
                            <span class="badge" style="background: #FEF2F2; color: #B91C1C;">Status: Nonaktif</span>
                        @endif
                    </div>
                </div>
            </div>

            @if($user->isKetuaRt() || $user->isSekretaris() || $user->isBendahara() || $user->isPetugasKeamanan() || $user->isAdmin() || $user->isSuperadmin())
            <!-- Formulir Khusus Pengurus: Edit Foto Saja Dengan Format Gambar Apa Saja -->
            <div style="margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid var(--border);">
                <h4 style="font-size: 0.9rem; font-weight: 600; color: var(--primary); margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.4rem;">
                    <i data-lucide="camera" style="width: 16px; height: 16px;"></i> Perbarui Foto Resmi Pengurus (Edit Foto Saja)
                </h4>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.85rem;">
                    Sesuai ketentuan administrasi, anggota kepengurusan dapat mengubah foto profil resmi dengan format gambar apa saja (PNG, JPG, JPEG, WEBP, SVG, HEIC, GIF, BMP). Data nama dan jabatan dikunci oleh sistem.
                </p>
                <form method="POST" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
                    @csrf
                    <input type="file" name="photo" id="photo" accept="image/*,.jpg,.jpeg,.png,.webp,.svg,.bmp,.gif,.heic,.heif,.avif" required style="font-size: 0.825rem; padding: 0.45rem; border: 1px solid var(--border); border-radius: 0.375rem; background: #FFF;">
                    <button type="submit" class="btn btn-primary" style="font-size: 0.825rem; padding: 0.5rem 0.85rem;">
                        <i data-lucide="upload" style="width: 13px; height: 13px;"></i> Unggah Foto Profil
                    </button>
                </form>
                @error('photo')
                    <p style="color: var(--rose); font-size: 0.8rem; margin-top: 0.4rem;">{{ $message }}</p>
                @enderror
            </div>
            @endif

            @if($user->isWarga() && $user->citizen)
            <div style="margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid var(--border);">
                <h3 style="font-size: 1rem; font-weight: 600; color: var(--primary); margin-bottom: 0.35rem;">Kontak warga</h3>
                <p class="verification-status" style="margin-bottom: 1rem;">
                    Email:
                    {{ $user->email_verified_at ? 'terverifikasi' : 'belum terverifikasi' }}
                    <span aria-hidden="true">·</span>
                    Telepon:
                    {{ $user->citizen->phone_verified_at ? 'terverifikasi' : 'belum terverifikasi' }}
                    @if(!$user->citizen->phone_verified_at)
                        (verifikasi SMS belum tersedia)
                    @endif
                </p>
                <form method="POST" action="{{ route('profile.update') }}" style="display: grid; gap: 0.9rem; max-width: 34rem;">
                    @csrf
                    @method('PATCH')
                    <label class="profile-field" for="profile_email">
                        Email
                        <input id="profile_email" name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="email" required>
                    </label>
                    @error('email')<p role="alert" style="color: var(--rose); font-size: 0.8rem;">{{ $message }}</p>@enderror
                    <label class="profile-field" for="profile_phone">
                        Nomor telepon
                        <input id="profile_phone" name="phone" type="tel" value="{{ old('phone', $user->citizen->phone) }}" autocomplete="tel" inputmode="tel">
                    </label>
                    @error('phone')<p role="alert" style="color: var(--rose); font-size: 0.8rem;">{{ $message }}</p>@enderror
                    <label class="profile-field" for="profile_occupation">
                        Pekerjaan
                        <input id="profile_occupation" name="occupation" type="text" value="{{ old('occupation', $user->citizen->occupation) }}" autocomplete="organization-title">
                    </label>
                    @error('occupation')<p role="alert" style="color: var(--rose); font-size: 0.8rem;">{{ $message }}</p>@enderror
                    <div style="display: flex; flex-wrap: wrap; gap: 0.65rem;">
                        <button type="submit" class="btn btn-primary profile-action">
                            <i data-lucide="save" style="width: 15px; height: 15px;"></i> Simpan kontak
                        </button>
                        <a class="btn btn-outline profile-action" href="{{ url('/api/v1/me/export') }}">
                            <i data-lucide="download" style="width: 15px; height: 15px;"></i> Unduh data saya
                        </a>
                    </div>
                </form>
            </div>
            @elseif($user->isWarga())
            <div style="margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid var(--border);">
                <a class="btn btn-outline profile-action" href="{{ url('/api/v1/me/export') }}">
                    <i data-lucide="download" style="width: 15px; height: 15px;"></i> Unduh data akun saya
                </a>
            </div>
            @endif

            <div style="margin-top: 1rem;">
                <h3 style="font-size: 1rem; font-weight: 600; color: var(--primary); margin-bottom: 0.75rem; padding-bottom: 0.35rem; border-bottom: 2px solid #E2E8F0;">
                    Informasi Identitas & Jabatan
                </h3>
                <div class="detail-row">
                    <span class="detail-label">Nama Lengkap</span>
                    <span class="detail-val">{{ $user->name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Alamat Email Resmi</span>
                    <span class="detail-val">{{ $user->email }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Peran Sistem (RBAC)</span>
                    <span class="detail-val">{{ $user->role->value ?? (string) $user->role }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Jabatan di Lingkungan</span>
                    <span class="detail-val">{{ $officer?->position ?? 'Pengurus / Warga Lingkungan RT' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nomor Telepon Terdaftar</span>
                    <span class="detail-val">{{ $officer?->phone ?? ($user->citizen?->phone ?? 'Belum tercatat') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">NIK Terhubung</span>
                    <span class="detail-val">{{ $user->citizen?->nik ?? 'Terkoneksi Akun Pengurus' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Waktu Login Terakhir</span>
                    <span class="detail-val">{{ $user->last_login_at ? $user->last_login_at->translatedFormat('d F Y, H:i') . ' WIB' : 'Sesi saat ini' }}</span>
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="font-size: 1rem; font-weight: 600; color: var(--primary); margin-bottom: 0.75rem; padding-bottom: 0.35rem; border-bottom: 2px solid #E2E8F0;">
                Tugas Pokok & Wewenang Sesuai Peran
            </h3>
            <ul style="font-size: 0.875rem; color: #334155; line-height: 1.7; padding-left: 1.25rem;">
                @if($user->isKetuaRt())
                    <li>Mengesahkan permohonan surat pengantar resmi yang telah diverifikasi oleh Sekretaris RT.</li>
                    <li>Menyetujui dan mengesahkan laporan keuangan kas RT periode triwulan/kuartal.</li>
                    <li>Mengawasi ketertiban, keamanan wilayah pos kamling, serta penanganan aduan warga.</li>
                @elseif($user->isSekretaris())
                    <li>Memverifikasi kelengkapan identitas dan berkas permohonan surat pengantar warga.</li>
                    <li>Mengelola administrasi kependudukan warga tetap dan warga sementara.</li>
                    <li>Mempublikasikan warta dan pengumuman resmi kegiatan lingkungan RT.</li>
                @elseif($user->isBendahara())
                    <li>Mencatat mutasi buku kas (pemasukan iuran warga dan pengeluaran operasional).</li>
                    <li>Memverifikasi status pelunasan iuran bulanan kas warga.</li>
                    <li>Menyusun laporan keuangan triwulan dan menjalankan cadangan data kas (backup store).</li>
                @elseif($user->isPetugasKeamanan())
                    <li>Memantau tiket laporan gangguan ketertiban dan insiden keamanan warga.</li>
                    <li>Menjalankan jadwal ronda malam dan koordinasi pos kamling RT.</li>
                    <li>Menyiagakan kontak darurat kepolisian, babinsa, dan pemadam kebakaran.</li>
                @else
                    <li>Mengajukan permohonan surat pengantar secara mandiri.</li>
                    <li>Membayar dan memeriksa riwayat iuran kas bulanan warga.</li>
                    <li>Menyampaikan laporan pengaduan fasilitas lingkungan.</li>
                @endif
            </ul>
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
