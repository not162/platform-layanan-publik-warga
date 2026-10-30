<!DOCTYPE html>
<html lang="id">
<head>
    <title>{{ $pageDef['label'] }} — PRISMA RT 04</title>
    @include('partials.head')
</head>
<body>
@include('partials.splash')
@include('partials.icons')
@php
    $groups = collect($nav)->groupBy('group');
    $initial = mb_strtoupper(mb_substr($user->name, 0, 1));
    $avatarUrl = $user->avatar_url ? url('/api/v1/files/avatar/'.$user->id).'?v='.$user->updated_at->timestamp : null;
    $tierLabel = $user->role->tierLabel();
@endphp

{{-- Konten menu dipakai ulang oleh sidebar (desktop) dan drawer (mobile) --}}
@php ob_start(); @endphp
@foreach ($groups as $group => $items)
    <div class="nav-group">{{ $group }}</div>
    @foreach ($items as $item)
        <a class="nav-link {{ $item['key'] === $page ? 'active' : '' }}" href="{{ $item['href'] }}">@include('partials.i', ['n' => $item['icon']]) {{ $item['label'] }}</a>
    @endforeach
@endforeach
@php $menuHtml = ob_get_clean(); @endphp

<div class="shell">
    <aside class="sidebar" aria-label="Menu utama">
        <a href="{{ route('home') }}" class="brand"><span class="brand-mark"><img src="{{ asset('images/prisma-mark.svg') }}" alt="" width="34" height="34"></span><span>PRISMA RT 04</span></a>
        {!! $menuHtml !!}
        <div class="side-user">
            <div class="avatar">@if ($avatarUrl)<img src="{{ $avatarUrl }}" alt="" width="38" height="38">@else{{ $initial }}@endif</div>
            <div class="who"><b>{{ $user->name }}</b><span>{{ $user->role->label() }}</span></div>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="btn btn-icon btn-sm btn-ghost" title="Keluar" aria-label="Keluar">@include('partials.i', ['n' => 'logout'])</button>
            </form>
        </div>
    </aside>

    <div class="main">
        <header class="appbar">
            <button class="btn btn-icon btn-ghost only-mobile" data-open-drawer aria-label="Menu">@include('partials.i', ['n' => 'menu'])</button>
            <div class="grow">
                <h1>{{ $pageDef['label'] }}</h1>
            </div>
            <span class="badge blue only-desktop">{{ $tierLabel }} · {{ $user->role->label() }}</span>
            <button class="btn btn-sm only-desktop" data-install hidden>@include('partials.i', ['n' => 'download']) Pasang</button>
            @include('partials.notif')
            <button class="btn btn-icon" data-theme-toggle aria-label="Ganti tema">@include('partials.i', ['n' => 'moon'])</button>
        </header>

        <div class="install-bar alert info" style="margin:12px 14px 0;align-items:center">
            @include('partials.i', ['n' => 'smartphone'])<span style="flex:1">Pasang PRISMA RT 04 di layar utama untuk akses cepat.</span>
            <button class="btn btn-sm btn-primary" data-install>Pasang</button>
        </div>

        <main class="content" id="content">
            @yield('content')
        </main>
    </div>
</div>

<template id="drawer-tpl">
    <div class="brand" style="padding:.4rem .5rem .6rem"><span class="brand-mark"><img src="{{ asset('images/prisma-mark.svg') }}" alt="" width="34" height="34"></span><span>PRISMA RT 04</span>
        <button class="btn btn-ghost btn-icon btn-sm" data-close-drawer style="margin-left:auto">@include('partials.i', ['n' => 'x'])</button></div>
    <div class="side-user" style="margin:.2rem 0 .4rem">
        <div class="avatar">@if ($avatarUrl)<img src="{{ $avatarUrl }}" alt="" width="38" height="38">@else{{ $initial }}@endif</div>
        <div class="who"><b>{{ $user->name }}</b><span>{{ $tierLabel }} · {{ $user->role->label() }}</span></div>
    </div>
    {!! $menuHtml !!}
    <div class="nav-group">Lainnya</div>
    <a class="nav-link" href="{{ route('home') }}">@include('partials.i', ['n' => 'building']) Beranda publik</a>
    <button class="nav-link" data-theme-toggle style="border:0;background:none;cursor:pointer;text-align:left">@include('partials.i', ['n' => 'moon']) Ganti tema</button>
    <form method="POST" action="{{ route('logout') }}">@csrf
        <button class="nav-link" style="width:100%;border:0;background:none;cursor:pointer;text-align:left;color:#f87171">@include('partials.i', ['n' => 'logout']) Keluar</button>
    </form>
</template>

<nav class="bottom-nav" aria-label="Navigasi bawah">
    @foreach ($bottomNav as $item)
        <a href="{{ $item['href'] }}" class="{{ $item['key'] === $page ? 'active' : '' }}">@include('partials.i', ['n' => $item['icon']])<span>{{ $item['short'] }}</span></a>
    @endforeach
    <button data-open-drawer aria-label="Menu lainnya">@include('partials.i', ['n' => 'menu'])<span>Menu</span></button>
</nav>

<script src="{{ asset('js/prisma.js') }}?v={{ filemtime(public_path('js/prisma.js')) }}"></script>
@stack('scripts')
</body>
</html>
