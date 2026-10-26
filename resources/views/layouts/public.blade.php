<!DOCTYPE html>
<html lang="id">
<head>
    <title>@yield('title', 'PRISMA RT 04') — PRISMA RT 04 Kemayoran</title>
    @include('partials.head')
</head>
<body>
@include('partials.icons')
@php
    $links = [
        ['Beranda', '/', 'home'],
        ['Layanan', '/#layanan', 'file'],
        ['Keuangan', '/#keuangan', 'wallet'],
        ['Kegiatan', '/#kegiatan', 'calendar'],
        ['Bantuan', '/#bantuan', 'chat'],
    ];
@endphp
<header class="topnav">
    <div class="container">
        <button class="btn btn-icon btn-ghost only-mobile" data-open-drawer aria-label="Menu">@include('partials.i', ['n' => 'menu'])</button>
        <a href="{{ route('home') }}" class="brand" aria-label="PRISMA RT 04">
            <span class="brand-mark">P</span><span>PRISMA RT 04</span>
        </a>
        <nav class="navlinks" aria-label="Navigasi utama">
            @foreach ($links as $l)
                <a href="{{ url($l[1]) }}" @class(['active' => $loop->first && request()->is('/')])>{{ $l[0] }}</a>
            @endforeach
        </nav>
        <div class="nav-actions">
            @auth @include('partials.notif') @endauth
            <button class="btn btn-icon" data-theme-toggle aria-label="Ganti tema">@include('partials.i', ['n' => 'moon'])</button>
            <button class="btn btn-sm only-desktop" data-install hidden>@include('partials.i', ['n' => 'download']) Pasang</button>
            @auth
                <a class="btn btn-primary btn-sm" href="{{ route('dashboard') }}">@include('partials.i', ['n' => 'user']) Dashboard</a>
            @else
                <a class="btn btn-primary btn-sm" href="{{ route('login') }}">@include('partials.i', ['n' => 'user']) Masuk</a>
            @endauth
        </div>
    </div>
</header>

<template id="drawer-tpl">
    <div class="brand" style="padding:.4rem .5rem 1rem"><span class="brand-mark">P</span><span>PRISMA RT 04</span>
        <button class="btn btn-ghost btn-icon btn-sm" data-close-drawer style="margin-left:auto">@include('partials.i', ['n' => 'x'])</button></div>
    @foreach ($links as $l)
        <a class="nav-link" href="{{ url($l[1]) }}">@include('partials.i', ['n' => $l[2]]) {{ $l[0] }}</a>
    @endforeach
    <div class="nav-group">Akun</div>
    @auth
        <a class="nav-link" href="{{ route('dashboard') }}">@include('partials.i', ['n' => 'user']) Dashboard</a>
    @else
        <a class="nav-link" href="{{ route('login') }}">@include('partials.i', ['n' => 'user']) Masuk</a>
        <a class="nav-link" href="{{ route('register') }}">@include('partials.i', ['n' => 'user-plus']) Daftar Warga</a>
    @endauth
    <button class="nav-link" data-theme-toggle style="border:0;background:none;cursor:pointer;text-align:left">@include('partials.i', ['n' => 'moon']) Ganti tema</button>
</template>

<main>@yield('content')</main>

<nav class="bottom-nav" aria-label="Navigasi bawah">
    @foreach ($links as $l)
        <a href="{{ url($l[1]) }}" @class(['active' => $loop->first && request()->is('/')])>@include('partials.i', ['n' => $l[2]])<span>{{ $l[0] }}</span></a>
    @endforeach
</nav>

@stack('scripts')
<script src="{{ asset('js/prisma.js') }}?v={{ filemtime(public_path('js/prisma.js')) }}"></script>
@stack('after')
</body>
</html>
