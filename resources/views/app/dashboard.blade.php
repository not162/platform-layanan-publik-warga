@extends('layouts.app')

@section('content')
@php
    $quick = collect($nav)->reject(fn ($i) => in_array($i['key'], ['dashboard', 'profile', 'system', 'audit']))->take(6);
    $greet = now()->hour < 11 ? 'Selamat pagi' : (now()->hour < 15 ? 'Selamat siang' : (now()->hour < 18 ? 'Selamat sore' : 'Selamat malam'));
@endphp
<div class="page-head">
    <div>
        <h2>{{ $greet }}, {{ \Illuminate\Support\Str::of($user->name)->explode(' ')->first() }} 👋</h2>
        <p>{{ $user->role->label() }} · akses <b>{{ $user->role->tierLabel() }}</b> · <span class="live-dot"></span>data real-time <span id="live-time" class="muted"></span></p>
    </div>
</div>

<div id="pending-banner" class="alert warning" hidden>@include('partials.i', ['n' => 'clock']) <div><b>Pendaftaran Anda sedang diverifikasi pengurus RT.</b><br>Anda tetap dapat menjelajahi layanan sementara pengurus memeriksa data kependudukan Anda.</div></div>

<div class="stats" id="stats"><div class="stat skeleton" style="height:84px"></div><div class="stat skeleton" style="height:84px"></div><div class="stat skeleton" style="height:84px"></div></div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">@include('partials.i', ['n' => 'arrow-right']) Akses cepat</div>
        <div style="display:grid;gap:.6rem">
            @foreach ($quick as $q)
                <a class="action-card" href="{{ $q['href'] }}"><span class="action-icon tone-blue">@include('partials.i', ['n' => $q['icon']])</span>{{ $q['label'] }}</a>
            @endforeach
        </div>
    </div>
    <div class="card">
        <div class="card-title">@include('partials.i', ['n' => 'clock']) {{ $user->isWarga() ? 'Aktivitas surat saya' : 'Aktivitas terbaru' }}</div>
        <div class="activity" id="recent"><div class="skeleton"></div></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
@endpush
