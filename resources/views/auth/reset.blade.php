@extends('layouts.public')
@section('title', 'Reset Password')

@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <h1>Buat password baru</h1>
        <p class="sub">Tautan ini berlaku 20 menit dan hanya dapat dipakai sekali.</p>

        <div id="reset-alert" class="alert danger" hidden></div>

        <form id="reset-form" novalidate data-token="{{ $token }}">
            <div class="field"><label for="password">Password baru</label>
                <div class="pw"><input class="input" id="password" name="password" type="password" autocomplete="new-password" required minlength="8"><button type="button" data-pw-toggle aria-label="Tampilkan password">@include('partials.i', ['n' => 'eye'])</button></div>
                <div class="hint" data-hint="password">Minimal 8 karakter.</div></div>
            <div class="field"><label for="password_confirmation">Ulangi password baru</label>
                <div class="pw"><input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required><button type="button" data-pw-toggle aria-label="Tampilkan password">@include('partials.i', ['n' => 'eye'])</button></div>
                <div class="hint" data-hint="password_confirmation"></div></div>
            <button class="btn btn-primary btn-block" type="submit">Simpan password</button>
        </form>
    </div>
</div>
@endsection

@push('after')
<script src="{{ asset('js/auth.js') }}?v={{ filemtime(public_path('js/auth.js')) }}"></script>
@endpush
