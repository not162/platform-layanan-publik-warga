<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#070b16">
<meta name="description" content="@yield('description', 'PRISMA RT 04 Kemayoran — layanan surat, kas, pengaduan, dan informasi warga RT 04 / RW 09 dalam satu aplikasi.')">
<meta property="og:title" content="@yield('title', 'PRISMA RT 04') — Sistem Warga Digital">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="PRISMA RT 04">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="icon" type="image/svg+xml" href="{{ asset('images/prisma-icon.svg') }}">
<link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
<link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
<link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">
<script>try{document.documentElement.dataset.theme=localStorage.getItem('prisma-theme')||'dark'}catch(e){document.documentElement.dataset.theme='dark'}</script>
<link rel="stylesheet" href="{{ asset('css/prisma.css') }}?v={{ filemtime(public_path('css/prisma.css')) }}">
