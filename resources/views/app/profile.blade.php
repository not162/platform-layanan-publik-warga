@extends('layouts.app')

@section('content')
<div class="page-head"><div><h2>Profil & Keamanan</h2><p>Kelola data akun dan password Anda.</p></div></div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">@include('partials.i', ['n' => 'user']) Data akun</div>
        <div style="display:flex;gap:1rem;align-items:center;margin-bottom:1rem">
            <div class="avatar" id="avatar-box" style="width:72px;height:72px;font-size:1.6rem;border-radius:20px">@if ($user->avatar_url)<img src="{{ url('/api/v1/files/avatar/'.$user->id).'?v='.$user->updated_at->timestamp }}" alt="Foto profil" width="72" height="72">@else{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}@endif</div>
            <div><b>{{ $user->role->label() }}</b><br>
                <span class="badge blue">Akses {{ $user->role->tierLabel() }}</span>
                @if ($user->citizen?->status_warga === 'pending_verification') <span class="badge amber">Menunggu verifikasi</span> @endif
            </div>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin:-.4rem 0 1rem">
            <label class="btn btn-sm" style="cursor:pointer">@include('partials.i', ['n' => 'edit']) Ganti foto<input type="file" id="photo-input" accept="image/*" hidden></label>
            @if ($user->avatar_url)<button type="button" class="btn btn-sm btn-danger" id="photo-remove">@include('partials.i', ['n' => 'trash']) Hapus foto</button>@endif
        </div>
        <form id="account-form" novalidate>
            <div class="field"><label for="name">Nama lengkap</label><input class="input" id="name" name="name" value="{{ $user->name }}" required><div class="hint" data-hint="name"></div></div>
            <div class="field"><label for="username">Username</label><input class="input" id="username" name="username" value="{{ $user->username }}" required><div class="hint" data-hint="username"></div></div>
            <div class="field"><label for="email">Email</label><input class="input" id="email" name="email" type="email" value="{{ $user->email }}"><div class="hint" data-hint="email"></div></div>
            @if ($user->citizen)
                <div class="field"><label for="phone">No. HP</label><input class="input" id="phone" name="phone" value="{{ $user->citizen->phone }}"><div class="hint" data-hint="phone"></div></div>
            @endif
            <button class="btn btn-primary" type="submit">@include('partials.i', ['n' => 'check']) Simpan perubahan</button>
        </form>
    </div>

    <div class="card">
        <div class="card-title">@include('partials.i', ['n' => 'lock']) Ganti password</div>
        <form id="password-form" novalidate>
            <div class="field"><label for="current_password">Password saat ini</label><div class="pw"><input class="input" id="current_password" name="current_password" type="password" autocomplete="current-password" required><button type="button" data-pw-toggle aria-label="Tampilkan">@include('partials.i', ['n' => 'eye'])</button></div><div class="hint" data-hint="current_password"></div></div>
            <div class="field"><label for="password">Password baru</label><div class="pw"><input class="input" id="password" name="password" type="password" autocomplete="new-password" required minlength="8"><button type="button" data-pw-toggle aria-label="Tampilkan">@include('partials.i', ['n' => 'eye'])</button></div><div class="hint" data-hint="password">Minimal 8 karakter.</div></div>
            <div class="field"><label for="password_confirmation">Ulangi password baru</label><div class="pw"><input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required><button type="button" data-pw-toggle aria-label="Tampilkan">@include('partials.i', ['n' => 'eye'])</button></div><div class="hint" data-hint="password_confirmation"></div></div>
            <button class="btn btn-primary" type="submit">@include('partials.i', ['n' => 'lock']) Ganti password</button>
        </form>
        <hr style="border:0;border-top:1px solid var(--border);margin:1.4rem 0">
        <div class="card-title">@include('partials.i', ['n' => 'smartphone']) Aplikasi (PWA)</div>
        <p class="muted" style="margin-bottom:.8rem">Pasang PRISMA RT 04 di perangkat Anda agar terbuka seperti aplikasi dan tetap bisa dibuka saat koneksi lemah.</p>
        <button class="btn" data-install hidden>@include('partials.i', ['n' => 'download']) Pasang aplikasi</button>
        <p class="muted" style="font-size:.82rem">Tidak muncul tombol? Di iPhone: Share → “Add to Home Screen”. Di Android/Chrome: menu ⋮ → “Install app”.</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const hint = (f, name, msg) => { const h = f.querySelector(`[data-hint="${name}"]`); const i = f.elements[name]; i && i.classList.toggle('invalid', !!msg); if (h) { h.textContent = msg || ''; h.className = 'hint' + (msg ? ' bad' : ''); } };
    const wire = (id, method, url, done) => {
        const f = document.getElementById(id);
        f.addEventListener('submit', async (e) => {
            e.preventDefault();
            Array.from(f.elements).forEach((el) => el.name && hint(f, el.name, ''));
            const body = Object.fromEntries(new FormData(f).entries());
            try { const r = await P.api(method, url, body); P.ok('Berhasil', r.message); done && done(f); }
            catch (err) { Object.entries(err.errors || {}).forEach(([k, v]) => hint(f, k, v[0])); P.fail(err); }
        });
    };
    document.getElementById('photo-input').addEventListener('change', async (e) => {
        const file = e.target.files[0]; if (!file) return;
        const fd = new FormData(); fd.append('photo', file);
        try { await P.api('POST', '/api/v1/me/photo', fd); P.ok('Foto diperbarui'); setTimeout(() => location.reload(), 600); } catch (err) { P.fail(err); }
    });
    document.getElementById('photo-remove')?.addEventListener('click', async () => {
        if (await P.confirm('Hapus foto profil?')) { await P.del('/api/v1/me/photo'); location.reload(); }
    });
    wire('account-form', 'PATCH', '/api/v1/me/account');
    wire('password-form', 'POST', '/api/v1/me/password', (f) => f.reset());
})();
</script>
@endpush
