@extends('layouts.app')

@section('content')
<div class="page-head"><div><h2>Pemodelan Sistem — 5 Unsur</h2><p>ERD, LRS, Use Case, Activity Diagram, dan UML — sesuai struktur database & kode yang berjalan.</p></div></div>

<div class="tabs" role="tablist" id="tabs">
    @foreach (['erd' => '1. ERD', 'lrs' => '2. LRS', 'usecase' => '3. Use Case', 'activity' => '4. Activity', 'uml' => '5. UML', 'rbac' => 'Matriks Hak Akses'] as $k => $l)
        <button class="tab {{ $loop->first ? 'active' : '' }}" role="tab" data-tab="{{ $k }}">{{ $l }}</button>
    @endforeach
</div>

<section data-panel="erd">
    <div class="card"><div class="card-title">Entity Relationship Diagram (ERD)</div>
    <div class="diagram"><pre class="mermaid">
erDiagram
    FAMILY_CARDS ||--o{ CITIZENS : "1 KK memuat N warga"
    CITIZENS ||--o| USERS : "1 warga punya 0..1 akun"
    USERS ||--o{ NOTIFICATIONS : "menerima"
    USERS ||--o{ PASSWORD_RESET_REQUESTS : "meminta reset"
    USERS ||--o{ ADMIN_PERMISSIONS : "izin tambahan"
    USERS ||--o{ AUDIT_LOGS : "memicu"
    CITIZENS ||--o{ LETTERS : "mengajukan"
    LETTER_TYPES ||--o{ LETTERS : "jenis"
    LETTERS ||--o{ LETTER_ATTACHMENTS : "lampiran"
    USERS ||--o{ COMPLAINTS : "melapor"
    USERS ||--o{ SECURITY_REPORTS : "melapor"
    USERS ||--o{ FINANCE_TRANSACTIONS : "mencatat"
    CITIZENS ||--o{ RESIDENT_DUES : "tagihan iuran"
    RESIDENT_DUES ||--o{ DUE_PAYMENTS : "pembayaran"
    FAMILY_CARDS {
        bigint id PK
        char no_kk_hash UK "SHA-256 No. KK"
        text no_kk "terenkripsi"
        string address
        string rt
        string rw
    }
    CITIZENS {
        bigint id PK
        bigint family_card_id FK
        bigint user_id FK
        char nik_hash UK "SHA-256 NIK"
        string full_name
        string full_name_key "UK bersama KK"
        string status_warga
        int version
    }
    USERS {
        bigint id PK
        bigint warga_id FK
        string name
        string username UK
        string email UK "opsional"
        string password
        string role "SUPERADMIN, ADMIN, KETUA_RT, SEKRETARIS, BENDAHARA, PETUGAS_KEAMANAN, WARGA"
        boolean is_active
    }
    NOTIFICATIONS {
        uuid id PK
        bigint notifiable_id FK
        text data "judul, isi, url, level"
        timestamp read_at
    }
    PASSWORD_RESET_REQUESTS {
        bigint id PK
        bigint user_id FK
        char token_hash UK
        timestamp expires_at
        timestamp used_at
    }
    LETTERS {
        bigint id PK
        bigint citizen_id FK
        bigint jenis_surat_id FK
        string ticket_number UK
        string status "draft, submitted, verified, approved, completed, rejected"
        string verification_token
        int version
    }
    FINANCE_TRANSACTIONS {
        bigint id PK
        string type "income, expense"
        bigint amount_idr
        string status "draft, published, reversed"
        bigint created_by FK
        int version
    }
    COMPLAINTS {
        bigint id PK
        bigint user_id FK
        string title
        string status
        string priority
    }
    SECURITY_REPORTS {
        bigint id PK
        bigint reporter_user_id FK
        string severity
        string status
    }
    AUDIT_LOGS {
        bigint id PK
        bigint user_id FK
        string action
        string entity_type
    }
    </pre></div></div>
</section>

<section data-panel="lrs" hidden>
    <div class="card"><div class="card-title">Logical Record Structure (LRS) — 3NF</div>
<div class="code">FAMILY_CARDS (id [PK], no_kk, no_kk_hash [UK], address, rt, rw, province, city, district, village, postal_code, version)
CITIZENS (id [PK], family_card_id [FK], user_id [FK, UK], nik, nik_hash [UK], full_name, full_name_key [UK bersama family_card_id],
          gender, place_of_birth, date_of_birth, occupation, phone, status_warga, is_active, version)
USERS (id [PK], warga_id, name, username [UK], email [UK, nullable], password, role, is_active, last_login_at, avatar_url)
PASSWORD_RESET_REQUESTS (id [PK], user_id [FK], token_hash [UK], ip_address, expires_at, used_at)
NOTIFICATIONS (id [PK uuid], type, notifiable_type, notifiable_id [FK], data, read_at, created_at)
ADMIN_PERMISSIONS (id [PK], user_id [FK], permission)
JENIS_SURAT (id [PK], kode_surat [UK], nama_surat, form_schema, approval_flow, is_active)
LETTERS (id [PK], citizen_id [FK], jenis_surat_id [FK], ticket_number [UK], keperluan, status, letter_number, verified_by [FK],
         approved_by [FK], verification_token, document_hash, version)
LETTER_ATTACHMENTS (id [PK], letter_id [FK], file_path, original_name, mime_type, file_size)
FINANCE_TRANSACTIONS (id [PK], transaction_number, type, category, amount_idr, transaction_date, status, created_by [FK],
                      original_transaction_id [FK], reversal_reason, version)
RESIDENT_DUES (id [PK], citizen_id [FK], due_month, due_year, amount, status)
DUE_PAYMENTS (id [PK], resident_due_id [FK], payment_reference [UK], amount, payment_method, payment_date)
COMPLAINTS (id [PK], user_id [FK], citizen_id [FK], ticket_number [UK], title, kategori, status, priority, admin_response, version)
SECURITY_REPORTS (id [PK], reporter_user_id [FK], ticket_number [UK], category, severity, title, location, status, assigned_to [FK], resolution)
ANNOUNCEMENTS (id [PK], title, category, content, is_pinned, is_published, published_at, version)
COMMUNITY_EVENTS (id [PK], title, location, event_date, start_time, end_time, is_published, version)
RT_OFFICERS (id [PK], name, position, phone, order_index, is_active)
ROUND_SCHEDULES (id [PK], day_of_week, shift_name, officer_names, pos_location, is_active)
EMERGENCY_CONTACTS (id [PK], name, category, phone_number, order_index, is_active)
AUDIT_LOGS (id [PK], user_id [FK], action, entity_type, entity_id, old_values, new_values, ip_address)</div></div>
</section>

<section data-panel="usecase" hidden>
    <div class="card"><div class="card-title">Use Case Diagram & Aktor</div>
    <div class="diagram"><pre class="mermaid">
flowchart LR
    W([Warga / User]):::a
    S([Sekretaris]):::a
    K([Ketua RT]):::a
    B([Bendahara]):::a
    G([Petugas Keamanan]):::a
    AD([Admin]):::a
    SA([Superadmin]):::a
    subgraph PRISMA["Sistem PRISMA RT 04"]
        UC1(Registrasi NIK, KK, Nama, Username)
        UC2(Lupa & reset password)
        UC3(Ajukan surat pengantar)
        UC4(Verifikasi berkas surat & warga baru)
        UC5(Sahkan surat + QR)
        UC6(Catat & terbitkan kas)
        UC7(Lihat transparansi kas)
        UC8(Pengaduan & laporan keamanan)
        UC9(Kelola konten: pengumuman, agenda, ronda)
        UC10(Kelola pengguna & peran)
        UC11(Audit log & pemodelan sistem)
        UC12(Terima notifikasi real-time)
    end
    W --> UC1 & UC2 & UC3 & UC7 & UC8 & UC12
    S --> UC4 & UC9 & UC12
    K --> UC5 & UC7 & UC8 & UC12
    B --> UC6 & UC7 & UC12
    G --> UC8 & UC9 & UC12
    AD --> UC9 & UC10 & UC12
    SA --> UC10 & UC11 & UC12
    classDef a fill:#dbeafe,stroke:#2563eb,color:#0f172a
    </pre></div></div>
</section>

<section data-panel="activity" hidden>
    <div class="card"><div class="card-title">Activity Diagram — Registrasi, Lupa Password & Surat</div>
    <div class="diagram"><pre class="mermaid">
flowchart TD
    A([Mulai]) --> B[Isi NIK, No. KK, Nama, Username, Password]
    B --> C{NIK sudah punya akun?}
    C -- Ya --> X1[Tolak: arahkan ke Masuk / Lupa Password]
    C -- Tidak --> D{Nama + No. KK sudah terdaftar?}
    D -- Ya --> X2[Tolak: tidak boleh daftar dua kali]
    D -- Tidak --> E{Username tersedia?}
    E -- Tidak --> X3[Tolak: pilih username lain]
    E -- Ya --> F{NIK ada di master RT?}
    F -- Ya --> G[Aktivasi akun - langsung aktif]
    F -- Tidak --> H[Warga baru - pending verification]
    H --> I[Sekretaris memverifikasi]
    G --> Z([Akun aktif + notifikasi])
    I --> Z
    </pre></div>
    <div class="diagram" style="margin-top:1rem"><pre class="mermaid">
stateDiagram-v2
    [*] --> Draft: Warga menyusun permohonan
    Draft --> Diajukan: Ajukan (notifikasi Sekretaris)
    Diajukan --> Terverifikasi: Sekretaris verifikasi (notifikasi Ketua RT)
    Diajukan --> Ditolak: Berkas tidak lengkap
    Terverifikasi --> Disahkan: Ketua RT mengesahkan, nomor + QR terbit
    Terverifikasi --> Ditolak: Ditolak Ketua RT
    Disahkan --> Selesai: Surat siap
    Selesai --> [*]
    Ditolak --> [*]
    </pre></div>
    <div class="diagram" style="margin-top:1rem"><pre class="mermaid">
flowchart LR
    L1([Lupa password]) --> L2[Isi username + NIK + No. KK]
    L2 --> L3{Cocok dengan database?}
    L3 -- Tidak --> L4[Tolak - data tidak cocok]
    L3 -- Ya --> L5[Terbitkan tautan sekali pakai 20 menit]
    L5 --> L6[Buat password baru]
    L6 --> L7([Semua sesi & token lama dicabut])
    </pre></div></div>
</section>

<section data-panel="uml" hidden>
    <div class="card"><div class="card-title">UML — Class Diagram</div>
    <div class="diagram"><pre class="mermaid">
classDiagram
    class User { +id +name +username +email +role +is_active +tier() +hasPermission(p) +citizen() }
    class Citizen { +id +nik_hash +full_name +full_name_key +status_warga +version }
    class FamilyCard { +id +no_kk_hash +address }
    class Letter { +ticket_number +status +letter_number +version }
    class FinanceTransaction { +type +amount_idr +status +version }
    class ManagedRegistry { +get(resource) +all() }
    class ManageController { +index() +store() +update() +destroy() +action() }
    class NotifyService { +to() +toRoles() +toStaff() +toEveryone() }
    class AuditService { +log() }
    class LetterService { +submit() +verify() +approve() +reject() }
    FamilyCard "1" --> "*" Citizen
    User "1" -- "0..1" Citizen
    Citizen "1" --> "*" Letter
    User "1" --> "*" FinanceTransaction : mencatat
    ManageController ..> ManagedRegistry : memakai definisi
    ManageController ..> AuditService : mencatat jejak
    ManagedRegistry ..> NotifyService : kirim notifikasi
    ManagedRegistry ..> LetterService : transisi status
    </pre></div></div>
    <div class="card" style="margin-top:1rem"><div class="card-title">UML — Sequence Diagram (CRUD real-time + notifikasi)</div>
    <div class="diagram"><pre class="mermaid">
sequenceDiagram
    actor Sek as Sekretaris
    participant UI as Browser (fetch)
    participant API as /api/v1/manage/letters
    participant DB as MySQL
    actor War as Warga
    Sek->>UI: klik Verifikasi
    UI->>API: POST /letters/12/verify {version}
    API->>DB: SELECT ... FOR UPDATE, cek version
    alt versi cocok
        API->>DB: UPDATE status = verified, version + 1
        API->>DB: INSERT audit_logs, notifications
        API-->>UI: 200 OK
    else versi basi
        API-->>UI: 409 Conflict
    end
    loop setiap 8-10 detik (polling + ETag)
        War->>API: GET /notifications
        API->>DB: SELECT notifications
        API-->>War: notifikasi baru, tampil toast + badge
    end
    </pre></div></div>
</section>

<section data-panel="rbac" hidden>
    <div class="card"><div class="card-title">Matriks hak akses (dihitung langsung dari kode)</div>
    <p class="muted" style="margin-bottom:1rem">Tiga tingkat akses: @foreach (collect($roles)->groupBy('tier_label', true) as $tierLabel => $tierRoles)<b>{{ $tierLabel }}</b> ({{ collect($tierRoles)->pluck('label')->implode(', ') }}){{ $loop->last ? '.' : ($loop->remaining === 1 ? ', dan ' : ', ') }}@endforeach Ketua RT berada di tingkat Superadmin namun tetap tidak mencatat kas (pemisahan tugas dengan Bendahara).</p>
    <div class="table-wrap" style="overflow:auto"><table class="tbl perm-table">
        <thead><tr><th>Fitur</th>@foreach ($roles as $r)<th>{{ $r['label'] }}<br><small>{{ $r['tier_label'] }}</small></th>@endforeach</tr></thead>
        <tbody>
        @foreach ($matrix as $feature => $cells)
            <tr><td data-label="Fitur">{{ $feature }}</td>@foreach ($cells as $roleKey => $ok)<td data-label="{{ $roles[$roleKey]['label'] }}">{!! $ok ? '<span class="badge green">✓</span>' : '<span class="muted">—</span>' !!}</td>@endforeach</tr>
        @endforeach
        </tbody></table></div></div>
</section>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/mermaid/10.9.1/mermaid.min.js"></script>
<script>
(function () {
    const natural = { useMaxWidth: false };
    mermaid.initialize({ startOnLoad: false, theme: 'default', securityLevel: 'strict', er: natural, flowchart: { ...natural, htmlLabels: true }, sequence: natural, class: natural, state: natural });
    const done = new Set();
    const show = async (k) => {
        document.querySelectorAll('[data-panel]').forEach((p) => (p.hidden = p.dataset.panel !== k));
        document.querySelectorAll('.tab').forEach((t) => t.classList.toggle('active', t.dataset.tab === k));
        if (!done.has(k)) { done.add(k); try { await mermaid.run({ nodes: document.querySelectorAll(`[data-panel="${k}"] .mermaid`) }); } catch (e) { P.toast('Diagram gagal dirender', String(e.message || e), 'danger'); } }
    };
    document.querySelectorAll('.tab').forEach((t) => t.addEventListener('click', () => show(t.dataset.tab)));
    show('erd');
})();
</script>
@endpush
