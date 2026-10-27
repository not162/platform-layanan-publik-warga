# Diagram Sistem PRISMA RT 04

Kumpulan diagram Mermaid (ERD, LRS, use case, activity, UML) yang selaras dengan kode dan skema database pada rilis redesign PWA. Ringkasan alur aplikasi ada di [README](../../README.md#alur-aplikasi).

> ERD dan LRS dibangkitkan dari migrasi. Jika skema berubah, perbarui bagian ini bersama README.

### 1. Entity Relationship Diagram (ERD)

Dibangkitkan langsung dari skema hasil migrasi (`php artisan migrate`): 27 tabel domain. Tabel framework (`cache`, `jobs`, `sessions`) tidak ditampilkan.

```mermaid
erDiagram
    USERS {
        int id PK
        int warga_id UK
        string name
        string email UK
        string password
        string role
        bool is_active
        datetime email_verified_at
        datetime last_login_at
        string remember_token
        string avatar_url
        string username UK
    }
    ADMIN_PERMISSIONS {
        int id PK
        int user_id FK
        string permission
    }
    PERSONAL_ACCESS_TOKENS {
        int id PK
        string tokenable_type
        int tokenable_id
        text name
        string token UK
        text abilities
        datetime last_used_at
        datetime expires_at
    }
    PASSWORD_RESET_REQUESTS {
        int id PK
        int user_id FK
        string token_hash UK
        string ip_address
        datetime expires_at
        datetime used_at
    }
    NOTIFICATIONS {
        string id PK
        string type
        string notifiable_type
        int notifiable_id
        text data
        datetime read_at
    }
    PUSH_SUBSCRIPTIONS {
        int id PK
        int user_id FK
        text endpoint
        string endpoint_hash
        text public_key
        text auth_token
        string content_encoding
    }
    FAMILY_CARDS {
        int id PK
        text no_kk
        string no_kk_hash UK
        string address
        string rt
        string rw
        string province
        string city
        string district
        string village
        string postal_code
        int version
    }
    CITIZENS {
        int id PK
        int family_card_id FK
        int user_id FK, UK
        text nik
        string nik_hash UK
        string full_name
        string gender
        string place_of_birth
        date date_of_birth
        string religion
        string blood_type
        string occupation
        string phone
        string email
        string status_warga
        bool is_active
        int version
        datetime phone_verified_at
        string ktp_file_path
        text verification_notes
        string full_name_key
    }
    JENIS_SURAT {
        int id PK
        string kode_surat UK
        string nama_surat
        string format_penomoran
        text syarat_dokumen
        bool is_active
        string template_key
        int template_version
        text form_schema
        text approval_flow
        int estimated_process_hours
    }
    LETTERS {
        int id PK
        int citizen_id FK
        int jenis_surat_id FK
        string type
        string ticket_number UK
        string keperluan
        text data_tambahan
        string status
        string letter_number UK
        text catatan_admin
        text rejection_reason
        int verified_by FK
        datetime verified_at
        int approved_by FK
        datetime approved_at
        string verification_token UK
        string file_path
        string attachment_path
        int version
        string document_hash
        datetime generated_at
    }
    LETTER_ATTACHMENTS {
        int id PK
        int letter_id FK
        string judul_lampiran
        string file_path
        string mime_type
        int file_size
    }
    DOCUMENT_SEQUENCES {
        int id PK
        string document_type
        int year
        int month
        int last_number
    }
    COMPLAINTS {
        int id PK
        string ticket_number UK
        int citizen_id FK
        int user_id FK
        string kategori
        string title
        text description
        string lokasi
        bool is_anonymous
        string status
        string priority
        int assigned_admin_id FK
        text admin_response
        string attachment_path
        int version
    }
    SECURITY_REPORTS {
        int id PK
        string ticket_number UK
        int citizen_id FK
        int reporter_user_id FK
        string category
        string severity
        string title
        text description
        string location
        datetime incident_at
        bool is_anonymous
        string status
        int assigned_to FK
        text resolution
        datetime resolved_at
        int version
    }
    RESIDENT_DUES {
        int id PK
        int citizen_id FK
        int period_year
        int period_month
        int amount_due_idr
        date due_date
        string status
        text notes
    }
    DUE_PAYMENTS {
        int id PK
        int resident_due_id FK
        int amount_paid_idr
        datetime paid_at
        string payment_method
        string receipt_number UK
        string proof_path
        int received_by FK
        text notes
        string status
        int finance_transaction_id FK
    }
    FINANCE_TRANSACTIONS {
        int id PK
        string type
        string category
        decimal amount
        string description
        date transaction_date
        string receipt_path
        string status
        int created_by FK
        datetime published_at
        int original_transaction_id FK
        text reversal_reason
        int version
        string transaction_number UK
        string source
        int amount_idr
    }
    PURCHASES {
        int id PK
        int finance_transaction_id FK
        string vendor_name
        date purchase_date
        string invoice_number
        string purpose
        text notes
        string receipt_path
        int created_by FK
        int version
    }
    PURCHASE_ITEMS {
        int id PK
        int purchase_id FK
        string item_name
        string description
        int quantity
        string unit
        int unit_price_idr
        int subtotal_idr
    }
    FINANCIAL_REPORTS {
        int id PK
        int year
        int quarter
        int revision
        date period_start
        date period_end
        int opening_balance_idr
        int income_idr
        int expense_idr
        int closing_balance_idr
        int dues_assessed_idr
        int dues_collected_idr
        int dues_outstanding_idr
        string status
        datetime generated_at
        datetime published_at
        int published_by FK
        string checksum_sha256
        int version
    }
    DOWNLOAD_AUDITS {
        string id PK
        int actor_user_id FK
        string resource_type
        string resource_id
        string file_type
        int period_year
        int period_quarter
        string action
        string result
        string ip_hash
        string user_agent_hash
    }
    AUDIT_LOGS {
        int id PK
        int user_id FK
        string action
        string entity_type
        int entity_id
        text old_values
        text new_values
        string ip_address
    }
    ANNOUNCEMENTS {
        int id PK
        string title
        string category
        text content
        bool is_pinned
        bool is_published
        datetime published_at
        int version
    }
    COMMUNITY_EVENTS {
        int id PK
        string title
        text description
        string location
        date event_date
        time start_time
        time end_time
        bool is_published
        int version
    }
    RT_OFFICERS {
        int id PK
        string name
        string position
        string phone
        int order_index
        string photo_url
        bool is_active
    }
    EMERGENCY_CONTACTS {
        int id PK
        string name
        string category
        string phone_number
        string description
        int order_index
        bool is_active
    }
    ROUND_SCHEDULES {
        int id PK
        string day_of_week
        string shift_name
        text officer_names
        string pos_location
        text notes
        bool is_active
    }

    USERS ||--o{ ADMIN_PERMISSIONS : "user_id"
    USERS ||--o{ PASSWORD_RESET_REQUESTS : "user_id"
    USERS o|--o{ PUSH_SUBSCRIPTIONS : "user_id"
    USERS o|--o| CITIZENS : "user_id"
    FAMILY_CARDS o|--o{ CITIZENS : "family_card_id"
    USERS o|--o{ LETTERS : "approved_by"
    USERS o|--o{ LETTERS : "verified_by"
    JENIS_SURAT o|--o{ LETTERS : "jenis_surat_id"
    CITIZENS ||--o{ LETTERS : "citizen_id"
    LETTERS ||--o{ LETTER_ATTACHMENTS : "letter_id"
    USERS o|--o{ COMPLAINTS : "assigned_admin_id"
    USERS o|--o{ COMPLAINTS : "user_id"
    CITIZENS o|--o{ COMPLAINTS : "citizen_id"
    USERS o|--o{ SECURITY_REPORTS : "assigned_to"
    USERS o|--o{ SECURITY_REPORTS : "reporter_user_id"
    CITIZENS o|--o{ SECURITY_REPORTS : "citizen_id"
    CITIZENS ||--o{ RESIDENT_DUES : "citizen_id"
    FINANCE_TRANSACTIONS o|--o{ DUE_PAYMENTS : "finance_transaction_id"
    RESIDENT_DUES ||--o{ DUE_PAYMENTS : "resident_due_id"
    USERS o|--o{ DUE_PAYMENTS : "received_by"
    FINANCE_TRANSACTIONS o|--o{ FINANCE_TRANSACTIONS : "original_transaction_id"
    USERS o|--o{ FINANCE_TRANSACTIONS : "created_by"
    USERS o|--o{ PURCHASES : "created_by"
    FINANCE_TRANSACTIONS o|--o{ PURCHASES : "finance_transaction_id"
    PURCHASES ||--o{ PURCHASE_ITEMS : "purchase_id"
    USERS o|--o{ FINANCIAL_REPORTS : "published_by"
    USERS o|--o{ DOWNLOAD_AUDITS : "actor_user_id"
    USERS o|--o{ AUDIT_LOGS : "user_id"
    USERS ||--o{ NOTIFICATIONS : "notifiable (polimorfik)"
    USERS ||--o{ PERSONAL_ACCESS_TOKENS : "tokenable (polimorfik)"
```

---

### 2. Logical Record Structure (LRS)

Format: `TABEL (kolom [PK | FK→tabel_induk | UK])`. Kolom `created_at` dan `updated_at` ada di semua tabel dan tidak ditulis ulang.

```
USERS (id [PK], warga_id [UK], name, email [UK], password, role, is_active, email_verified_at, last_login_at, remember_token, avatar_url, username [UK])
ADMIN_PERMISSIONS (id [PK], user_id [FK→users], permission)
PERSONAL_ACCESS_TOKENS (id [PK], tokenable_type, tokenable_id, name, token [UK], abilities, last_used_at, expires_at)
PASSWORD_RESET_REQUESTS (id [PK], user_id [FK→users], token_hash [UK], ip_address, expires_at, used_at)
NOTIFICATIONS (id [PK], type, notifiable_type, notifiable_id, data, read_at)
PUSH_SUBSCRIPTIONS (id [PK], user_id [FK→users], endpoint, endpoint_hash, public_key, auth_token, content_encoding)
FAMILY_CARDS (id [PK], no_kk, no_kk_hash [UK], address, rt, rw, province, city, district, village, postal_code, version)
CITIZENS (id [PK], family_card_id [FK→family_cards], user_id [FK→users, UK], nik, nik_hash [UK], full_name, gender, place_of_birth, date_of_birth, religion, blood_type, occupation, phone, email, status_warga, is_active, version, phone_verified_at, ktp_file_path, verification_notes, full_name_key)
JENIS_SURAT (id [PK], kode_surat [UK], nama_surat, format_penomoran, syarat_dokumen, is_active, template_key, template_version, form_schema, approval_flow, estimated_process_hours)
LETTERS (id [PK], citizen_id [FK→citizens], jenis_surat_id [FK→jenis_surat], type, ticket_number [UK], keperluan, data_tambahan, status, letter_number [UK], catatan_admin, rejection_reason, verified_by [FK→users], verified_at, approved_by [FK→users], approved_at, verification_token [UK], file_path, attachment_path, version, document_hash, generated_at)
LETTER_ATTACHMENTS (id [PK], letter_id [FK→letters], judul_lampiran, file_path, mime_type, file_size)
DOCUMENT_SEQUENCES (id [PK], document_type, year, month, last_number)
COMPLAINTS (id [PK], ticket_number [UK], citizen_id [FK→citizens], user_id [FK→users], kategori, title, description, lokasi, is_anonymous, status, priority, assigned_admin_id [FK→users], admin_response, attachment_path, version)
SECURITY_REPORTS (id [PK], ticket_number [UK], citizen_id [FK→citizens], reporter_user_id [FK→users], category, severity, title, description, location, incident_at, is_anonymous, status, assigned_to [FK→users], resolution, resolved_at, version)
RESIDENT_DUES (id [PK], citizen_id [FK→citizens], period_year, period_month, amount_due_idr, due_date, status, notes)
DUE_PAYMENTS (id [PK], resident_due_id [FK→resident_dues], amount_paid_idr, paid_at, payment_method, receipt_number [UK], proof_path, received_by [FK→users], notes, status, finance_transaction_id [FK→finance_transactions])
FINANCE_TRANSACTIONS (id [PK], type, category, amount, description, transaction_date, receipt_path, status, created_by [FK→users], published_at, original_transaction_id [FK→finance_transactions], reversal_reason, version, transaction_number [UK], source, amount_idr)
PURCHASES (id [PK], finance_transaction_id [FK→finance_transactions], vendor_name, purchase_date, invoice_number, purpose, notes, receipt_path, created_by [FK→users], version)
PURCHASE_ITEMS (id [PK], purchase_id [FK→purchases], item_name, description, quantity, unit, unit_price_idr, subtotal_idr)
FINANCIAL_REPORTS (id [PK], year, quarter, revision, period_start, period_end, opening_balance_idr, income_idr, expense_idr, closing_balance_idr, dues_assessed_idr, dues_collected_idr, dues_outstanding_idr, status, generated_at, published_at, published_by [FK→users], checksum_sha256, version)
DOWNLOAD_AUDITS (id [PK], actor_user_id [FK→users], resource_type, resource_id, file_type, period_year, period_quarter, action, result, ip_hash, user_agent_hash)
AUDIT_LOGS (id [PK], user_id [FK→users], action, entity_type, entity_id, old_values, new_values, ip_address)
ANNOUNCEMENTS (id [PK], title, category, content, is_pinned, is_published, published_at, version)
COMMUNITY_EVENTS (id [PK], title, description, location, event_date, start_time, end_time, is_published, version)
RT_OFFICERS (id [PK], name, position, phone, order_index, photo_url, is_active)
EMERGENCY_CONTACTS (id [PK], name, category, phone_number, description, order_index, is_active)
ROUND_SCHEDULES (id [PK], day_of_week, shift_name, officer_names, pos_location, notes, is_active)
```

Aturan integritas penting: `citizens.user_id` dan `users.warga_id` satu-ke-satu; `resident_dues` unik per (citizen, tahun, bulan); `financial_reports` unik per (tahun, triwulan, revisi); `document_sequences` unik per (jenis, tahun, bulan); transaksi kas yang sudah terbit tidak dihapus, hanya di-*reverse*.

---

### 3. Use Case Diagram & Spesifikasi Aktor

Use case dipecah per modul agar terbaca. Notasi mengikuti UML: aktor di luar batas sistem, use case berupa elips berawalan kata kerja, `«include»` untuk langkah yang selalu dijalankan, `«extend»` untuk perilaku opsional, dan panah segitiga kosong untuk pewarisan aktor (Superadmin mewarisi seluruh wewenang Admin). Pengunjung adalah pengguna publik tanpa login; hak akses tiap peran diambil dari `User::hasPermission()`.

**3.1. Akun dan Registrasi Warga**

![Use case akun dan registrasi warga](diagrams/usecase-1-akun-registrasi.svg)

**3.2. Layanan Surat Pengantar**

![Use case layanan surat pengantar](diagrams/usecase-2-surat-pengantar.svg)

**3.3. Keuangan dan Iuran RT**

![Use case keuangan dan iuran RT](diagrams/usecase-3-keuangan-rt.svg)

**3.4. Pengaduan dan Keamanan Lingkungan**

![Use case pengaduan dan keamanan lingkungan](diagrams/usecase-4-pengaduan-keamanan.svg)

*Pengurus RT* pada diagram ini mewakili peran yang memiliki izin `complaint.manage`: Ketua RT, Sekretaris RT, dan Admin.

**3.5. Informasi RT dan Administrasi Sistem**

![Use case informasi RT dan administrasi sistem](diagrams/usecase-5-informasi-administrasi.svg)

**Spesifikasi aktor**

| Aktor | Peran sistem | Wewenang utama |
|---|---|---|
| Pengunjung | tanpa login | Melihat kas publik, warta, agenda, struktur pengurus, ronda, dan kontak darurat; mendaftar akun; melacak surat dengan nomor tiket; memeriksa keaslian surat lewat QR |
| Warga | `WARGA` | Mengajukan dan memantau surat, melihat iuran, mengirim pengaduan dan laporan keamanan, mengelola profil |
| Sekretaris RT | `SEKRETARIS` | Memverifikasi surat dan pendaftar baru, menerbitkan dokumen, mengelola data warga, templat surat, pengumuman, agenda, dan struktur pengurus |
| Ketua RT | `KETUA_RT` | Mengesahkan atau menolak surat, menanggapi pengaduan, menerbitkan laporan triwulan, meninjau kas dan audit unduhan (kas hanya dibaca) |
| Bendahara RT | `BENDAHARA` | Membuat tagihan iuran, mencatat pembayaran, transaksi, dan pembelian, menerbitkan atau mengoreksi transaksi, menyusun laporan triwulan |
| Petugas Keamanan | `PETUGAS_KEAMANAN` | Menindaklanjuti laporan keamanan, mengelola jadwal ronda dan kontak darurat |
| Admin | `ADMIN` | Mengelola akun pengguna, data warga, pengumuman, agenda, struktur pengurus, pengaduan, dan melihat audit log |
| Superadmin | `SUPERADMIN` | Seluruh wewenang Admin, ditambah mengelola semua peran dan izin |

---

### 4. Activity Diagram

#### 4.1. Registrasi Mandiri & Verifikasi Warga
```mermaid
flowchart TD
    A([Mulai: buka /register]) --> B[Isi NIK, No. KK, Nama, Username, Password]
    B --> C[POST /api/v1/auth/check-citizen: validasi langsung]
    C --> D{NIK, nama + KK, username belum dipakai?}
    D -- Tidak --> E[Tampilkan pesan kesalahan per kolom] --> B
    D -- Ya --> F[POST /api/v1/register]
    F --> G{NIK sudah ada di data sensus RT?}
    G -- Ya --> H[Tautkan akun ke data warga: status aktif]
    G -- Tidak --> I[Buat warga baru + KK: status pending_verification]
    I --> J[Notifikasi ke Sekretaris]
    J --> K{Sekretaris periksa data}
    K -- Setuju --> L[Status warga aktif]
    K -- Tolak --> M[Pendaftaran ditolak + catatan]
    H --> N([Masuk ke dashboard])
    L --> N
    M --> O([Selesai])
```

#### 4.2. Login, Penguncian & Lupa Password
```mermaid
flowchart TD
    A([Buka /login]) --> B[Isi username / email / telepon / NIK + password]
    B --> C{Terlalu banyak gagal? 5x dalam 5 menit}
    C -- Ya --> D[Tolak: coba lagi dalam N detik]
    C -- Tidak --> E{Kredensial valid?}
    E -- Tidak --> F[Catat percobaan gagal] --> B
    E -- Ya --> G{Akun aktif?}
    G -- Tidak --> H[Tolak: hubungi pengurus RT]
    G -- Ya --> I[Sesi dibuat + last_login_at] --> J([Dashboard sesuai peran])
    B -. Lupa password .-> K[Isi username + NIK + No. KK]
    K --> L{Cocok dengan database?}
    L -- Tidak --> M[Data tidak cocok]
    L -- Ya --> N[Buat token sekali pakai 20 menit, simpan hash di password_reset_requests]
    N --> O{Akun punya email dan SMTP aktif?}
    O -- Ya --> P[Kirim tautan lewat email]
    O -- Tidak --> Q[Tampilkan tautan di layar]
    P --> R[Buka /reset-password/token, isi password baru]
    Q --> R
    R --> S[Token ditandai terpakai] --> A
```

#### 4.3. Birokrasi Surat Pengantar 2-Tier
```mermaid
stateDiagram-v2
    [*] --> draft: Warga pilih jenis surat, isi formulir, unggah lampiran
    draft --> submitted: Ajukan (notifikasi ke Sekretaris)
    submitted --> verified: Sekretaris verifikasi berkas (notifikasi ke Ketua RT)
    submitted --> rejected: Sekretaris tolak + alasan
    verified --> approved: Ketua RT sahkan (nomor surat RT 04, hash SHA-256, token QR)
    verified --> rejected: Ketua RT tolak + alasan
    approved --> completed: Dokumen PDF diterbitkan dan diunduh
    completed --> [*]
    rejected --> [*]
    note right of approved
        Semua transisi memakai kunci baris
        dan optimistic locking (kolom version)
    end note
```

#### 4.4. Iuran Warga ke Buku Kas Transparan
```mermaid
flowchart TD
    A([Awal bulan]) --> B[Bendahara generate tagihan resident_dues: status UNPAID]
    B --> C[Warga melihat Iuran Saya + notifikasi]
    C --> D[Warga bayar: tunai / transfer]
    D --> E[Bendahara catat due_payments + bukti + nomor kuitansi]
    E --> F[Transaksi kas income langsung status published, terhubung ke pembayaran]
    E --> G{Total bayar mencapai tagihan?}
    G -- Belum --> H[Status PARTIAL] --> D
    G -- Ya --> I[Status PAID]
    F --> J[Ringkasan kas publik + cache dibersihkan]
    K[Transaksi manual: pembelian, pengeluaran, pemasukan lain] --> L[Status draft]
    L --> M{Terbitkan?}
    M -- Ya --> N[Status published: tampil di kas publik] --> J
    N --> O{Perlu koreksi?}
    O -- Ya --> P[Reversal: transaksi lawan dibuat, asli ditandai reversed, alasan dicatat] --> J
    N --> Q[Laporan triwulan: revisi, checksum SHA-256, diterbitkan]
```

#### 4.5. Pengaduan & Laporan Keamanan
```mermaid
stateDiagram-v2
    [*] --> submitted: Warga kirim (opsional anonim), tiket dibuat
    submitted --> reviewed: Pengurus tinjau, prioritas ditetapkan
    reviewed --> processing: Ditugaskan ke pengurus / petugas
    processing --> resolved: Ditangani + tanggapan admin
    resolved --> closed: Ditutup
    submitted --> rejected: Tidak layak
    reviewed --> rejected
    closed --> [*]
    rejected --> [*]
    note right of submitted
        Identitas pelapor anonim tidak
        ditampilkan ke pengurus
    end note
```

---

### 5. Unified Modeling Language (UML) Diagrams

#### 5.1. UML Class Diagram
```mermaid
classDiagram
    class User {
        +int id
        +string name
        +string username
        +string email
        +UserRole role
        +bool is_active
        +tier() string
        +hasPermission(string) bool
        +isWarga() bool
        +citizen() Citizen
        +complaints() HasMany
        +findByLogin(string)$ User
    }
    class UserRole {
        <<enumeration>>
        SUPERADMIN
        ADMIN
        KETUA_RT
        SEKRETARIS
        BENDAHARA
        PETUGAS_KEAMANAN
        WARGA
        +tier() string
        +assignableRoles() array
    }
    class FamilyCard {
        +string no_kk_hash
        +string address
        +string rt
        +string rw
    }
    class Citizen {
        +string nik_hash
        +string full_name
        +string status_warga
        +int version
        +familyCard() FamilyCard
        +user() User
        +letters() HasMany
    }
    class LetterType {
        +string kode_surat
        +string nama_surat
        +json form_schema
        +json approval_flow
    }
    class Letter {
        +string ticket_number
        +string letter_number
        +string status
        +string verification_token
        +string document_hash
        +int version
    }
    class Complaint {
        +string ticket_number
        +string status
        +string priority
        +bool is_anonymous
    }
    class SecurityReport {
        +string ticket_number
        +string severity
        +string status
    }
    class FinanceTransaction {
        +string type
        +int amount_idr
        +string status
        +int original_transaction_id
    }
    class ResidentDue {
        +int period_year
        +int period_month
        +int amount_due_idr
        +string status
    }
    class DuePayment {
        +int amount_paid_idr
        +string receipt_number
    }
    class FinancialReport {
        +int year
        +int quarter
        +int revision
        +string checksum_sha256
    }
    class LetterService {
        +create() Letter
        +submit() Letter
        +verify() Letter
        +approve() Letter
        +complete() Letter
        +reject() Letter
    }
    class FinanceService {
        +create() FinanceTransaction
        +publish() FinanceTransaction
        +reverse() FinanceTransaction
        +getPublicSummary() array
    }
    class ResidentDueService {
        +generateMonthlyDues()
        +recordPayment() DuePayment
        +getPersonalSummary() array
    }
    class AuditService {
        +log() AuditLog
    }
    class NotifyService
    User --> UserRole : role
    User "1" -- "0..1" Citizen : akun-warga
    FamilyCard "1" o-- "*" Citizen : anggota
    Citizen "1" --> "*" Letter : mengajukan
    LetterType "1" --> "*" Letter : jenis
    User "1" --> "*" Complaint : melapor
    User "1" --> "*" SecurityReport : melapor
    Citizen "1" --> "*" ResidentDue : ditagih
    ResidentDue "1" --> "*" DuePayment : dibayar
    DuePayment "*" --> "0..1" FinanceTransaction : dicatat
    FinanceTransaction "0..1" --> "0..1" FinanceTransaction : reversal
    LetterService ..> Letter
    LetterService ..> AuditService
    LetterService ..> NotifyService
    FinanceService ..> FinanceTransaction
    FinanceService ..> AuditService
    ResidentDueService ..> DuePayment
    ResidentDueService ..> AuditService
    FinancialReport ..> FinanceTransaction : agregasi
```

#### 5.2. UML Sequence: Optimistic Concurrency Control (Pengesahan Surat)
```mermaid
sequenceDiagram
    actor K as Ketua RT
    participant UI as PWA PRISMA
    participant C as LetterController
    participant S as LetterService
    participant DB as MySQL
    K->>UI: Buka surat berstatus verified
    UI->>C: GET /api/v1/letters/{id}
    C->>DB: SELECT letter (version = N)
    DB-->>UI: JSON + version N
    K->>UI: Sahkan surat
    UI->>C: POST approve {version: N}
    C->>S: approve(letter, user, N)
    S->>DB: BEGIN, SELECT ... FOR UPDATE
    alt status verified dan version cocok
        S->>DB: UPDATE status approved, nomor surat, hash, token QR, version N+1
        S->>DB: INSERT audit_logs + notifikasi ke warga
        S->>DB: COMMIT
        C-->>UI: 200 OK
    else version berbeda atau status tidak sah
        S->>DB: ROLLBACK
        C-->>UI: 409 Conflict (data diubah pihak lain)
        UI-->>K: Muat ulang data terbaru
    end
```

#### 5.3. UML Sequence: Sinkronisasi Real-Time & Notifikasi
```mermaid
sequenceDiagram
    participant U as Browser / PWA
    participant SW as Service Worker
    participant A as API /api/v1
    participant D as MySQL
    U->>A: POST / PATCH / DELETE (sesi + CSRF)
    A->>D: Simpan (transaksi, version, audit log)
    A->>D: Buat notifikasi untuk pihak terkait
    A-->>U: 200 OK
    loop tiap 8 detik saat tab aktif
        U->>A: GET /dashboard/summary dan /notifications
        A-->>U: cards, recent, unread_notifications
    end
    A-->>SW: Web Push ke push_subscriptions
    SW-->>U: Tampilkan notifikasi sistem
    U->>A: GET /sync (sinkron inkremental setelah offline)
    A-->>U: Perubahan sejak sinkron terakhir
```
