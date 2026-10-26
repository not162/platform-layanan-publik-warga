@extends('layouts.public')
@section('title', 'Beranda')

@push('scripts')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        ['@type' => 'GovernmentOrganization', 'name' => 'PRISMA RT 04 / RW 09 Kemayoran', 'url' => url('/'), 'telephone' => '+62 878-7200-4448',
            'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'Gg. Bugis No. 95, RT 04 / RW 09, Kebon Kosong', 'addressLocality' => 'Kemayoran, Jakarta Pusat', 'addressRegion' => 'DKI Jakarta', 'postalCode' => '10620', 'addressCountry' => 'ID'],
            'geo' => ['@type' => 'GeoCoordinates', 'latitude' => -6.1618, 'longitude' => 106.8456]],
        ['@type' => 'WebSite', 'name' => 'PRISMA RT 04 Kemayoran', 'url' => url('/'), 'inLanguage' => 'id'],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
@php $wa = 'https://wa.me/6287872004448?text='.urlencode('Halo Pengurus RT 04, saya ingin bertanya.'); @endphp
<section class="hero">
    <div class="container">
        <div class="hero-icon">@include('partials.i', ['n' => 'building'])</div>
        <h1>Layanan Warga RT 04</h1>
        <p>Urus keperluan RT, lihat informasi warga, cek keuangan, dan laporkan masalah dengan mudah.</p>
        <p style="margin-top:.5rem;font-size:.9rem"><span class="live-dot"></span><span class="muted">Data tampil langsung dari database · <span id="live-time">memuat…</span></span></p>

        <h2 style="margin-top:2.2rem;font-size:1.15rem">Apa yang ingin Anda lakukan?</h2>
        <div class="actions-grid">
            <a class="action-card" href="{{ auth()->check() ? route('app.page', 'my-letters') : route('login') }}"><span class="action-icon tone-blue">@include('partials.i', ['n' => 'file'])</span>Saya mau urus surat</a>
            <a class="action-card" href="#keuangan"><span class="action-icon tone-green">@include('partials.i', ['n' => 'wallet'])</span>Saya mau lihat uang kas</a>
            <a class="action-card" href="{{ auth()->check() ? route('app.page', 'my-complaints') : route('login') }}"><span class="action-icon tone-rose">@include('partials.i', ['n' => 'alert'])</span>Saya mau lapor masalah</a>
            <a class="action-card" href="#kegiatan"><span class="action-icon tone-violet">@include('partials.i', ['n' => 'calendar'])</span>Saya mau lihat kegiatan</a>
            <a class="action-card" href="{{ $wa }}" target="_blank" rel="noopener"><span class="action-icon tone-cyan">@include('partials.i', ['n' => 'chat'])</span>Saya mau hubungi pengurus</a>
            @guest
                <a class="action-card" href="{{ route('register') }}"><span class="action-icon tone-amber">@include('partials.i', ['n' => 'user-plus'])</span>Saya warga baru, mau daftar</a>
            @endguest
        </div>
    </div>
</section>

<section class="section" id="layanan">
    <div class="container">
        <div class="section-head"><div class="eyebrow">Layanan</div><h2>Surat & Layanan Administrasi</h2><p>Ajukan surat pengantar secara daring. Alur: Warga → Sekretaris (verifikasi) → Ketua RT (pengesahan).</p></div>
        <div class="grid-3" id="letter-types"><div class="card skeleton" style="height:70px"></div><div class="card skeleton" style="height:70px"></div><div class="card skeleton" style="height:70px"></div></div>
    </div>
</section>

<section class="section" id="keuangan">
    <div class="container">
        <div class="section-head"><div class="eyebrow">Transparansi</div><h2>Keuangan Kas RT 04</h2><p>Hanya transaksi yang sudah dipublikasikan Bendahara.</p></div>
        <div class="grid-3" style="margin-bottom:1rem">
            <div class="card"><div class="muted">Saldo kas</div><div class="big-number" id="fin-balance">—</div></div>
            <div class="card"><div class="muted">Total pemasukan</div><div class="big-number money in" id="fin-income">—</div></div>
            <div class="card"><div class="muted">Total pengeluaran</div><div class="big-number money out" id="fin-expense">—</div></div>
        </div>
        <div class="card"><div class="card-title">@include('partials.i', ['n' => 'list']) Transaksi terbaru</div><div id="fin-recent"></div></div>
    </div>
</section>

<section class="section" id="kegiatan">
    <div class="container">
        <div class="section-head"><div class="eyebrow">Pengumuman & Agenda</div><h2>Kabar Terbaru RT 04</h2><p>Informasi terkini, jadwal kegiatan, dan pengumuman penting.</p></div>
        <div class="grid-2">
            <div><h3 style="margin-bottom:.8rem">Pengumuman</h3><div class="news" id="announcements"></div></div>
            <div><h3 style="margin-bottom:.8rem">Agenda kegiatan</h3><div class="news" id="events"></div></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container grid-2">
        <div class="card"><div class="card-title">@include('partials.i', ['n' => 'badge']) Struktur pengurus</div><div id="officers"></div></div>
        <div class="card"><div class="card-title">@include('partials.i', ['n' => 'moon']) Jadwal ronda</div><div id="schedules"></div></div>
    </div>
</section>

<section class="section" id="bantuan">
    <div class="container">
        <div class="section-head"><div class="eyebrow">Bantuan</div><h2>Hubungi Kami & Lokasi Sekretariat</h2><p>Respon cepat pada jam kerja 08.00 – 20.00 WIB.</p></div>
        <div class="grid-2">
            <div class="card">
                <div class="card-title">@include('partials.i', ['n' => 'phone']) Saluran bantuan</div>
                <div style="display:grid;gap:.6rem">
                    <a class="btn btn-success" href="{{ $wa }}" target="_blank" rel="noopener">@include('partials.i', ['n' => 'chat']) Chat WhatsApp Ketua RT / Admin</a>
                    <a class="btn" href="mailto:swandrax@gmail.com">@include('partials.i', ['n' => 'mail']) Kirim email</a>
                </div>
                <div id="contacts" style="margin-top:1rem"></div>
            </div>
            <div class="card">
                <div class="card-title">@include('partials.i', ['n' => 'pin']) Alamat Sekretariat PRISMA</div>
                <p class="muted">Rumah — Gg. Bugis No. 95, RT 04 / RW 09, Kebon Kosong, Kec. Kemayoran, Kota Jakarta Pusat, DKI Jakarta 10620</p>
                <a class="btn" style="margin-top:1rem" target="_blank" rel="noopener" href="https://www.google.com/maps?q=-6.1618,106.8456">@include('partials.i', ['n' => 'pin']) Buka di Google Maps</a>
            </div>
        </div>
    </div>
</section>

<footer class="footer">© {{ date('Y') }} PRISMA RT 04 / RW 09 Kemayoran · Platform Realisasi Informasi, Sistem Manajemen & Administrasi Warga</footer>
<div style="height:var(--bottom-h)" class="only-mobile"></div>
@endsection

@push('after')
<script src="{{ asset('js/home.js') }}?v={{ filemtime(public_path('js/home.js')) }}"></script>
@endpush
