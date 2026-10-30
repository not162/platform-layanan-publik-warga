@extends('layouts.public')
@section('title', 'Lupa Password')

@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <h1>Lupa password</h1>
        <p class="sub">Masukkan username, NIK, dan No. KK yang Anda daftarkan. Jika cocok, tautan atur ulang password dikirim ke email akun Anda. Akun tanpa email: tautan diserahkan langsung oleh Ketua/Sekretaris RT setelah mencocokkan KTP.</p>

        <div id="forgot-alert" class="alert danger" hidden></div>

        <form id="forgot-form" novalidate>
            <div class="field"><label for="username">Username</label><input class="input" id="username" name="username" autocomplete="username" autocapitalize="none" required><div class="hint" data-hint="username"></div></div>
            <div class="field"><label for="nik">NIK (16 digit)</label><input class="input" id="nik" name="nik" inputmode="numeric" maxlength="16" required><div class="hint" data-hint="nik"></div></div>
            <div class="field"><label for="no_kk">No. Kartu Keluarga (16 digit)</label><input class="input" id="no_kk" name="no_kk" inputmode="numeric" maxlength="16" required><div class="hint" data-hint="no_kk"></div></div>
            <button class="btn btn-primary btn-block" type="submit">Kirim permintaan reset</button>
        </form>

        <div class="auth-foot"><a href="{{ route('login') }}">← Kembali ke halaman masuk</a><br>
            <span style="font-size:.8rem">Akun pengurus (Ketua/Sekretaris/Bendahara/Admin): hubungi Superadmin untuk reset.</span></div>
    </div>
</div>
@endsection

@push('after')
<script src="{{ asset('js/auth.js') }}?v={{ filemtime(public_path('js/auth.js')) }}"></script>
@endpush
