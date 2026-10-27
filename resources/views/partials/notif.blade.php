<div class="dd">
    <button class="btn btn-icon" id="notif-btn" aria-label="Notifikasi" aria-haspopup="true">
        @include('partials.i', ['n' => 'bell'])
        <span class="count" data-notif-count hidden>0</span>
    </button>
    <div class="dd-panel" id="notif-panel" hidden>
        <div class="dd-head">
            <span>Notifikasi</span>
            <span style="display:flex;gap:.3rem">
                <button class="btn btn-sm btn-ghost" id="notif-readall" title="Tandai semua dibaca">@include('partials.i', ['n' => 'check']) Dibaca</button>
                <button class="btn btn-sm btn-ghost" id="notif-clear" title="Hapus semua">@include('partials.i', ['n' => 'trash'])</button>
            </span>
        </div>
        <div class="dd-list" id="notif-list"><div class="empty">Memuat…</div></div>
        <div style="padding:.6rem 1rem;border-top:1px solid var(--border)">
            <button class="btn btn-sm btn-block" id="notif-perm">@include('partials.i', ['n' => 'bell']) Aktifkan notifikasi browser</button>
        </div>
    </div>
</div>
