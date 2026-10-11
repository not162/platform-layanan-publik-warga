<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>@yield('title', 'Masuk') - PRISMA RT 04 Kemayoran</title>
    
    <!-- Meta & Open Graph -->
    <meta name="description" content="Portal autentikasi dan pendaftaran mandiri warga PRISMA RT 04 / RW 09 Kemayoran, Jakarta Pusat.">
    <meta property="og:title" content="@yield('title', 'Masuk') - PRISMA RT 04 Kemayoran" />
    <meta property="og:description" content="Sistem Informasi Manajemen Administrasi Warga Mandiri RT 04 Kemayoran." />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:type" content="website" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">

    <!-- Typography: Plus Jakarta Sans / Inter -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    
    <style>
        :root {
            color-scheme: light;
            --color-primary: #1B365D;
            --color-primary-light: #2A4D80;
            --color-teal: #3A9696;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #F8FAFC;
        }
        
        [x-cloak] { display: none !important; }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            15%, 45%, 75% { transform: translateX(-6px); }
            30%, 60%, 90% { transform: translateX(6px); }
        }
        
        .animate-shake {
            animation: shake 0.45s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
        }

        @keyframes slideDownFade {
            0% {
                opacity: 0;
                transform: translateY(-10px) scale(0.98);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .animate-slide-down {
            animation: slideDownFade 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes softPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
            50% { box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.18); }
        }

        .animate-pulse-error {
            animation: softPulse 1.2s ease-in-out infinite;
        }
    </style>

    <!-- Tailwind CSS (Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js & Lucide CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="min-h-screen bg-gradient-to-b from-slate-50 via-slate-100/60 to-slate-200/50 flex flex-col justify-between p-4 sm:p-6 text-slate-800 antialiased">
    
    <!-- Top Bar with Back to Home -->
    <header class="w-full max-w-lg mx-auto flex items-center justify-between py-2">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-blue-600 transition-colors py-1.5 px-3 rounded-lg hover:bg-white/80">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Kembali ke Beranda</span>
        </a>
        <a href="https://wa.me/6287872004448?text=Halo%20Pengurus%20PRISMA%20RT%2004,%20saya%20butuh%20bantuan%20terkait%20akun." target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200/80 px-2.5 py-1 rounded-full transition-colors">
            <i data-lucide="message-circle" class="w-3 h-3 text-emerald-600"></i>
            <span>Bantuan WA</span>
        </a>
    </header>

    <!-- Main Container -->
    <main class="w-full max-w-md mx-auto my-auto py-4">
        <!-- PRISMA RT 04 Brand Header -->
        <div class="text-center mb-6">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 mb-2.5 group">
                <div class="h-11 w-11 rounded-xl bg-gradient-to-br from-blue-600 via-indigo-600 to-cyan-500 flex items-center justify-center text-white font-extrabold text-xl shadow-md shadow-blue-500/20 group-hover:scale-105 transition-transform">
                    P
                </div>
                <span class="text-2xl font-extrabold bg-gradient-to-r from-blue-600 to-cyan-500 bg-clip-text text-transparent tracking-tight">
                    PRISMA RT 04
                </span>
            </a>
            <h1 class="text-base font-bold text-slate-900 tracking-tight">Platform Layanan Publik Warga</h1>
            <p class="text-xs text-slate-500 mt-0.5">Sekretariat RT 04 / RW 09 Kemayoran, Jakarta Pusat</p>
        </div>

        <!-- Auth Card -->
        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/60 p-6 sm:p-8 border border-slate-200/80">
            @yield('content')
        </div>
    </main>
        
    <!-- Footer -->
    <footer class="w-full max-w-md mx-auto text-center py-4 text-xs text-slate-400">
        <p>&copy; {{ date('Y') }} PRISMA RT 04 Kemayoran. Platform Realisasi Informasi, Sistem Manajemen & Administrasi.</p>
        <p class="mt-1 text-[11px] text-slate-400/80">Terproteksi enkripsi SSL & UU Perlindungan Data Pribadi (UU PDP).</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                lucide.createIcons();
            }
        });
        document.addEventListener('alpine:initialized', () => {
            if (window.lucide) {
                lucide.createIcons();
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
