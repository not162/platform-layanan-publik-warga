<!DOCTYPE html>
<html lang="id">
<head>
    <title>Sedang Offline — PRISMA RT 04</title>
    @include('partials.head')
</head>
<body>
@include('partials.icons')
<div class="auth-wrap" style="min-height:100vh">
    <div class="auth-card" style="text-align:center">
        <div class="hero-icon">@include('partials.i', ['n' => 'alert'])</div>
        <h1>Anda sedang offline</h1>
        <p class="sub">Koneksi internet terputus. Data layanan RT membutuhkan koneksi agar selalu terbaru. Periksa jaringan Anda lalu coba lagi.</p>
        <button class="btn btn-primary btn-block" onclick="location.reload()">@include('partials.i', ['n' => 'refresh']) Coba lagi</button>
    </div>
</div>
<script src="{{ asset('js/prisma.js') }}"></script>
<script>window.addEventListener('online', () => location.reload());</script>
</body>
</html>
