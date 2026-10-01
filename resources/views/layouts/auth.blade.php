<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Login') - Portal Warga</title>
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="@yield('title', 'Layanan Publik RT')" />
    <meta property="og:description" content="Portal administrasi dan layanan untuk warga lingkungan RT." />
    <meta property="og:image" content="{{ asset('images/logo.jpg') }}" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:type" content="website" />

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc; /* Very light slate for non-AI feel */
        }
        
        [x-cloak] { display: none !important; }
    </style>

    <!-- Tailwind CSS (Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js & Pusher & Lucide (via CDN for quick setup, or bundle if needed) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-md">
        <!-- Logo and Header -->
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo.jpg') }}" alt="Portal Warga Logo" class="w-20 h-20 mx-auto rounded-full shadow-sm object-cover mb-4">
            <h1 class="text-2xl font-semibold text-slate-800">Layanan Publik Warga</h1>
            <p class="text-sm text-slate-500 mt-1">Sistem Administrasi Terpadu Lingkungan RT</p>
        </div>

        <!-- Main Card -->
        <div class="bg-white rounded-xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] p-8 border border-slate-100">
            @yield('content')
        </div>
        
        <!-- Footer -->
        <div class="mt-8 text-center text-xs text-slate-400">
            &copy; {{ date('Y') }} Pengurus Lingkungan RT. Semua Hak Dilindungi.
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });
        document.addEventListener('alpine:initialized', () => {
            lucide.createIcons(); // refresh icons on alpine load
        });
    </script>
    @stack('scripts')
</body>
</html>
