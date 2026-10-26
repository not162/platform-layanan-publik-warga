@extends('layouts.public')
@section('title', 'Daftar Warga')

@section('content')
<div class="auth-wrap">
    <div class="auth-card wide">
        <h1>Daftar Warga</h1>
        <p class="sub">Isi data sesuai KTP & Kartu Keluarga. NIK, nama + No. KK, dan username hanya dapat terdaftar satu kali.</p>

        <div id="reg-alert" class="alert info" hidden></div>

        <form id="register-form" novalidate enctype="multipart/form-data">
            <div class="form-row">
                <div class="field">
                    <label for="nik">NIK (16 digit)</label>
                    <input class="input" id="nik" name="nik" inputmode="numeric" maxlength="16" autocomplete="off" required>
                    <div class="hint" data-hint="nik"></div>
                </div>
                <div class="field">
                    <label for="no_kk">No. Kartu Keluarga (16 digit)</label>
                    <input class="input" id="no_kk" name="no_kk" inputmode="numeric" maxlength="16" autocomplete="off" required>
                    <div class="hint" data-hint="no_kk"></div>
                </div>
            </div>
            <div class="field">
                <label for="name">Nama lengkap (sesuai KTP)</label>
                <input class="input" id="name" name="name" autocomplete="name" required>
                <div class="hint" data-hint="name"></div>
            </div>
            <div class="form-row">
                <div class="field">
                    <label for="username">Username</label>
                    <input class="input" id="username" name="username" autocomplete="username" autocapitalize="none" required>
                    <div class="hint" data-hint="username">4–30 karakter: huruf kecil, angka, titik, garis bawah.</div>
                </div>
                <div class="field">
                    <label for="phone">No. HP / WhatsApp <span class="muted">(opsional)</span></label>
                    <input class="input" id="phone" name="phone" inputmode="tel" autocomplete="tel">
                    <div class="hint" data-hint="phone"></div>
                </div>
            </div>
            <div class="field">
                <label for="email">Email <span class="muted">(opsional)</span></label>
                <input class="input" id="email" name="email" type="email" autocomplete="email">
                <div class="hint" data-hint="email"></div>
            </div>
            <div class="form-row">
                <div class="field">
                    <label for="password">Password</label>
                    <div class="pw"><input class="input" id="password" name="password" type="password" autocomplete="new-password" required minlength="8"><button type="button" data-pw-toggle aria-label="Tampilkan password">@include('partials.i', ['n' => 'eye'])</button></div>
                    <div class="hint" data-hint="password">Minimal 8 karakter.</div>
                </div>
                <div class="field">
                    <label for="password_confirmation">Ulangi password</label>
                    <div class="pw"><input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required><button type="button" data-pw-toggle aria-label="Tampilkan password">@include('partials.i', ['n' => 'eye'])</button></div>
                    <div class="hint" data-hint="password_confirmation"></div>
                </div>
            </div>
            <div class="field">
                <label for="ktp_file">Foto/scan KTP <span class="muted">(opsional, JPG/PNG/PDF maks 5MB)</span></label>
                <input class="input" id="ktp_file" name="ktp_file" type="file" accept=".jpg,.jpeg,.png,.pdf">
                <div class="hint" data-hint="ktp_file"></div>
            </div>
            <button class="btn btn-primary btn-block" type="submit" id="reg-submit">Daftar sekarang</button>
        </form>

        <div class="auth-foot">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a> · <a href="{{ route('password.request') }}">Lupa password?</a></div>
    </div>
</div>
@endsection

@push('after')
<script src="{{ asset('js/auth.js') }}?v={{ filemtime(public_path('js/auth.js')) }}"></script>
@endpush
