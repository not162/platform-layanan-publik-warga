@extends('layouts.public')
@section('title', 'Masuk')

@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <h1>Masuk ke PRISMA</h1>
        <p class="sub">Gunakan username dan password akun Anda.</p>

        @if (session('status'))<div class="alert success">@include('partials.i', ['n' => 'check-circle']) {{ session('status') }}</div>@endif
        @if ($errors->any())<div class="alert danger">@include('partials.i', ['n' => 'alert']) {{ $errors->first() }}</div>@endif

        <form method="POST" action="{{ route('login.post') }}" id="login-form">
            @csrf
            <div class="field">
                <label for="login">Username atau email</label>
                <input class="input @error('login') invalid @enderror" id="login" name="login" value="{{ old('login') }}" autocomplete="username" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <div class="pw">
                    <input class="input" id="password" name="password" type="password" autocomplete="current-password" required>
                    <button type="button" data-pw-toggle aria-label="Tampilkan password">@include('partials.i', ['n' => 'eye'])</button>
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.1rem">
                <label class="check"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
                <a class="link" href="{{ route('password.request') }}">Lupa password?</a>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Masuk</button>
        </form>

        <div class="auth-foot">Belum punya akun? <a href="{{ route('register') }}">Daftar sebagai warga</a></div>

        @if (app()->environment('local'))
        <div class="alert info" style="margin-top:1.2rem;margin-bottom:0;display:block">
            <b>Pintasan akun demo</b> (hanya lingkungan lokal, password: <code>password</code>)
            <div class="demo-chips">
                @foreach (['superadmin' => 'Superadmin', 'admin' => 'Admin', 'ketua_rt' => 'Ketua RT', 'sekretaris' => 'Sekretaris', 'bendahara' => 'Bendahara', 'keamanan' => 'Keamanan', 'warga' => 'Warga'] as $u => $l)
                    <button type="button" class="chip" data-demo="{{ $u }}">{{ $l }}</button>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('after')
<script>
document.querySelectorAll('[data-demo]').forEach(b => b.addEventListener('click', () => {
    document.getElementById('login').value = b.dataset.demo;
    document.getElementById('password').value = 'password';
    document.getElementById('login-form').requestSubmit();
}));
</script>
@endpush
