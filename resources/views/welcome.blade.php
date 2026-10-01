<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Layanan Publik Warga') }} - Portal Warga Lingkungan</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

    <style>
        :root {
            --color-primary: #1B365D;
            --color-primary-dark: #12243F;
            --color-teal: #3A9696;
            --color-teal-light: #EBF7F7;
            --color-slate: #475569;
            --color-slate-light: #F8FAFC;
            --color-border: #E2E8F0;
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
            max-width: 1140px;
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
            gap: 1.75rem;
            list-style: none;
        }

        .nav-link {
            text-decoration: none;
            color: var(--color-slate);
            font-size: 0.925rem;
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
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            font-size: 0.925rem;
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

        /* Hero Section */
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
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.825rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .hero h1 {
            font-size: 2.75rem;
            font-weight: 700;
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
            max-width: 680px;
            margin: 0 auto 2.25rem;
        }

        .hero-cta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        /* Philosophy Grid */
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--color-primary);
            margin-bottom: 0.5rem;
        }

        .section-title p {
            color: var(--color-slate);
            font-size: 1rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            padding: 4rem 0;
        }

        .feature-card {
            background-color: #FFFFFF;
            border: 1px solid var(--color-border);
            border-radius: 0.75rem;
            padding: 2rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            border-color: var(--color-teal);
        }

        .feature-icon {
            width: 44px;
            height: 44px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
            font-size: 1.25rem;
        }

        .icon-blue { background: #EEF2F6; color: var(--color-primary); }
        .icon-teal { background: var(--color-teal-light); color: var(--color-teal); }
        .icon-slate { background: #F1F5F9; color: var(--color-slate); }
        .icon-emerald { background: #ECFDF5; color: #10B981; }

        .feature-card h3 {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--color-primary);
            margin-bottom: 0.5rem;
        }

        .feature-card p {
            font-size: 0.925rem;
            color: var(--color-slate);
            line-height: 1.5;
        }

        /* Vision & Mission Box */
        .vision-mission-section {
            background-color: #FFFFFF;
            border-top: 1px solid var(--color-border);
            border-bottom: 1px solid var(--color-border);
            padding: 4rem 0;
        }

        .vm-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: start;
        }

        .vm-card {
            background: var(--color-slate-light);
            border: 1px solid var(--color-border);
            border-radius: 0.75rem;
            padding: 2rem;
        }

        .vm-card h3 {
            color: var(--color-primary);
            font-size: 1.25rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .vm-card ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .vm-card li {
            position: relative;
            padding-left: 1.5rem;
            color: var(--color-slate);
            font-size: 0.925rem;
        }

        .vm-card li::before {
            content: "✓";
            position: absolute;
            left: 0;
            top: 0;
            color: var(--color-teal);
            font-weight: bold;
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

        .footer-copy {
            font-size: 0.85rem;
            color: var(--color-slate);
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
            .vm-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
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
                <a href="{{ url('/') }}" aria-label="Layanan Publik Warga Home">
                    <x-brand-logo variant="horizontal" class="h-10" />
                </a>

                <ul class="nav-links">
                    <li><a href="#layanan" class="nav-link">Layanan</a></li>
                    <li><a href="#filosofi" class="nav-link">Filosofi</a></li>
                    <li><a href="#visi-misi" class="nav-link">Visi & Misi</a></li>
                    @if (Route::has('login'))
                        @auth
                            <li><a href="{{ url('/dashboard') }}" class="btn btn-primary">Dashboard Warga</a></li>
                        @else
                            <li><a href="{{ Route::has('login') ? route('login') : url('/login') }}" class="btn btn-secondary">Masuk Portal</a></li>
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
                <span>🛡️</span> Portal Administrasi Lingkungan Transparan & Terverifikasi
            </div>
            <h1>Layanan Publik <span>Warga</span></h1>
            <p>
                Platform administrasi warga yang sederhana, dapat dilacak secara transparan, serta menjaga kerahasiaan data pribadi untuk mewujudkan lingkungan yang tertib dan berdaya.
            </p>
            <div class="hero-cta">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">Buka Dashboard Saya</a>
                @else
                    <a href="{{ Route::has('login') ? route('login') : url('/login') }}" class="btn btn-primary">Masuk ke Portal Warga</a>
                @endauth
                <a href="#layanan" class="btn btn-secondary">Pelajari Alur Layanan</a>
            </div>
        </div>
    </section>

    <!-- Philosophy Section -->
    <section id="filosofi" class="container">
        <div class="features-grid">
            <!-- 1. Pelayanan -->
            <div class="feature-card">
                <div class="feature-icon icon-blue">📋</div>
                <h3>1. Pelayanan</h3>
                <p>Warga mendapatkan kemudahan permohonan administrasi (surat pengantar, perizinan) dengan prosedur yang ringkas, jelas, dan tanpa hambatan birokrasi berbelit.</p>
            </div>

            <!-- 2. Kebersamaan -->
            <div class="feature-card">
                <div class="feature-icon icon-teal">🤝</div>
                <h3>2. Kebersamaan</h3>
                <p>Membangun sinergi harmonis antara warga dan pengurus RT/RW dalam satu ekosistem terbuka untuk musyawarah dan penanganan aduan bersama.</p>
            </div>

            <!-- 3. Transparansi Informasi -->
            <div class="feature-card">
                <div class="feature-icon icon-slate">🔍</div>
                <h3>3. Transparansi</h3>
                <p>Keterbukaan informasi publik, rekapitulasi kas lingkungan, dan pemantauan status pengajuan secara real-time sesuai hak akses.</p>
            </div>

            <!-- 4. Kepercayaan & Privasi -->
            <div class="feature-card">
                <div class="feature-icon icon-emerald">🛡️</div>
                <h3>4. Kepercayaan</h3>
                <p>Transparansi informasi tidak mengorbankan privasi. NIK dan data pribadi warga senantiasa dilindungi melalui enkripsi dan tata kelola keamanan ketat.</p>
            </div>
        </div>
    </section>

    <!-- Vision & Mission Section -->
    <section id="visi-misi" class="vision-mission-section">
        <div class="container">
            <div class="section-title">
                <h2>Visi & Misi Kami</h2>
                <p>Membangun tata kelola lingkungan modern yang berbasis kepercayaan warga.</p>
            </div>
            <div class="vm-grid">
                <div class="vm-card">
                    <h3>🎯 Visi</h3>
                    <p style="font-size: 1.05rem; font-weight: 500; color: var(--color-primary); line-height: 1.6;">
                        "Menjadi portal layanan warga yang sederhana, dapat dilacak, transparan, dan aman untuk mendukung administrasi lingkungan yang lebih tertib."
                    </p>
                </div>
                <div class="vm-card">
                    <h3>🚀 Misi</h3>
                    <ul>
                        <li>Mempermudah warga mengakses layanan administrasi dan informasi lingkungan.</li>
                        <li>Membuat status pengajuan surat dan pengaduan lebih mudah dipantau.</li>
                        <li>Menyediakan transparansi informasi publik tanpa membuka data pribadi.</li>
                        <li>Membantu pengurus bekerja dengan data terstruktur, jejak audit, dan pembagian hak akses yang jelas.</li>
                        <li>Menjaga layanan tetap mudah dipelihara dan dikembangkan secara bertahap.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

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
                <div class="footer-copy">
                    &copy; {{ date('Y') }} Layanan Publik Warga. Pelayanan • Kebersamaan • Transparansi • Kepercayaan.
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
