# Entity-Relationship Diagram (ERD) & Kamus Arsitektur Data
## TOGAF ADM: Phase C — Data Architecture (Sprint 2 Baseline)
**Platform Layanan Publik Warga — MySQL Laragon Architecture**  
**Versi:** 1.1.0-snapshot | **Tanggal Pemutakhiran:** 16 Oktober 2026

---

## 1. Visual Entity-Relationship Diagram (Mermaid)

Pemodelan diagram relasi entitas berikut mencerminkan skema fisik aktual basis data MySQL `layanan_warga` di lingkungan Laragon, mematuhi prinsip *single source of truth* untuk akun-warga (`citizens.user_id`), nama fisik tabel jenis surat (`jenis_surat`), serta integritas buku kas (`due_payments.finance_transaction_id`):

```mermaid
erDiagram
    USERS ||--o| CITIZENS : "1-to-0..1 via citizens.user_id (UNIQUE)"
    USERS ||--o{ ADMIN_PERMISSIONS : "has permissions (CASCADE)"
    USERS ||--o{ AUDIT_LOGS : "performed action (SET NULL)"
    USERS ||--o{ COMPLAINTS : "submits complaint (SET NULL)"
    USERS ||--o{ SECURITY_REPORTS : "reports incident (SET NULL)"
    USERS ||--o{ FINANCE_TRANSACTIONS : "records ledger (SET NULL)"
    USERS ||--o{ FINANCIAL_REPORTS : "publishes report (SET NULL)"
    USERS ||--o{ PURCHASES : "records purchase (SET NULL)"
    USERS ||--o{ PUSH_SUBSCRIPTIONS : "subscribes webpush (SET NULL)"
    USERS ||--o{ DUE_PAYMENTS : "receives payment (SET NULL)"

    FAMILY_CARDS ||--o{ CITIZENS : "contains family members (CASCADE)"
    
    CITIZENS ||--o{ LETTERS : "requests letter (CASCADE)"
    CITIZENS ||--o{ RESIDENT_DUES : "assessed dues (CASCADE)"
    CITIZENS ||--o{ COMPLAINTS : "files formal complaint (SET NULL)"
    CITIZENS ||--o{ SECURITY_REPORTS : "files security report (SET NULL)"

    JENIS_SURAT ||--o{ LETTERS : "categorizes (SET NULL)"

    LETTERS ||--o{ LETTER_ATTACHMENTS : "has requirements (CASCADE)"
    LETTERS }o--|| USERS : "verified_by (SET NULL)"
    LETTERS }o--|| USERS : "approved_by (SET NULL)"

    RESIDENT_DUES ||--o{ DUE_PAYMENTS : "payment entries (CASCADE)"

    FINANCE_TRANSACTIONS ||--o{ DUE_PAYMENTS : "backed by cash entry (RESTRICT)"
    FINANCE_TRANSACTIONS ||--o{ PURCHASES : "allocated expenditure (SET NULL)"
    FINANCE_TRANSACTIONS ||--o| FINANCE_TRANSACTIONS : "reversal_of (SET NULL)"

    PURCHASES ||--o{ PURCHASE_ITEMS : "contains items (CASCADE)"

    %% ================= ENTITY DEFINITIONS =================
    USERS {
        bigint id PK
        string name "Nama Akun / Username"
        string email UK "Email unik terverifikasi"
        timestamp email_verified_at "Nullable"
        string password "Bcrypt hashed"
        string role "superadmin, admin, rt, rw, bendahara, warga"
        string phone "Nullable"
        boolean is_active "Default true"
        timestamp last_login_at "Nullable"
        bigint warga_id UK "LEGACY (Deprecated, Nullable)"
        timestamp created_at
        timestamp updated_at
    }

    FAMILY_CARDS {
        bigint id PK
        text no_kk "Encrypted AES-256-CBC"
        char(64) no_kk_hash UK "SHA-256 Deterministic Lookup"
        string head_of_family_name
        string address
        string rt
        string rw
        string postal_code "Nullable"
        string province
        string city
        string district
        string village
        int version "Optimistic Locking"
        timestamp created_at
        timestamp updated_at
    }

    CITIZENS {
        bigint id PK
        bigint family_card_id FK "family_cards.id (CASCADE)"
        bigint user_id FK_UK "users.id (1:0..1, SET NULL)"
        text nik "Encrypted AES-256-CBC"
        char(64) nik_hash UK "SHA-256 Deterministic Lookup"
        string name "Nama lengkap warga"
        string place_of_birth
        date date_of_birth
        string gender "L / P"
        string religion
        string education "Nullable"
        string occupation "Nullable"
        string marital_status "Nullable"
        string family_relationship_status "Kepala Keluarga, Istri, Anak, dll"
        string citizenship "WNI / WNA"
        string address "Nullable (fallback ke family_card)"
        string rt "Nullable"
        string rw "Nullable"
        string phone "Nullable"
        string status "tetap, kontrak, kos, pindah, meninggal"
        boolean is_active "Default true"
        int version "Optimistic Locking"
        timestamp created_at
        timestamp updated_at
    }

    JENIS_SURAT {
        bigint id PK
        string kode_surat UK "SKD, SKU, SKTM, dll"
        string nama_surat "Nama resmi jenis surat"
        text deskripsi "Nullable"
        json template_fields "Definisi isian dinamis"
        json required_attachments "Daftar syarat berkas wajib"
        boolean is_active "Default true"
        int version "Optimistic Locking"
        timestamp created_at
        timestamp updated_at
    }

    LETTERS {
        bigint id PK
        bigint citizen_id FK "citizens.id (CASCADE)"
        bigint jenis_surat_id FK "jenis_surat.id (SET NULL)"
        string type "Nama/Kategori Surat"
        string ticket_number UK "SRT-YYYYMMDD-XXXX"
        string letter_number UK "Nomor Surat Resmi RT (Nullable)"
        string status "draft, submitted, verified, approved, completed, rejected"
        json form_data "Isian dinamis warga"
        text rejection_reason "Nullable"
        timestamp submitted_at "Nullable"
        timestamp verified_at "Nullable"
        bigint verified_by FK "users.id (SET NULL)"
        timestamp approved_at "Nullable"
        bigint approved_by FK "users.id (SET NULL)"
        timestamp completed_at "Nullable"
        string verification_token UK "QR Code Token (Nullable)"
        string document_hash "SHA-256 PDF Hash (Nullable)"
        string pdf_path "Nullable (storage/letters/completed)"
        int version "Optimistic Locking"
        timestamp created_at
        timestamp updated_at
    }

    LETTER_ATTACHMENTS {
        bigint id PK
        bigint letter_id FK "letters.id (CASCADE)"
        string attachment_type "KTP, KK, Surat Pengantar RT Asal"
        string file_path "storage/letters/attachments"
        string original_file_name
        string mime_type
        bigint file_size
        timestamp created_at
        timestamp updated_at
    }

    RESIDENT_DUES {
        bigint id PK
        bigint citizen_id FK "citizens.id (CASCADE)"
        smallint period_year "Tahun Buku (e.g. 2026)"
        tinyint period_month "Bulan Buku (1-12)"
        bigint amount_due_idr "Nominal tagihan dalam IDR"
        date due_date "Jatuh tempo pembayaran"
        string status "UNPAID, PARTIAL, PAID, OVERDUE"
        text notes "Nullable"
        timestamp created_at
        timestamp updated_at
    }

    DUE_PAYMENTS {
        bigint id PK
        bigint resident_due_id FK "resident_dues.id (CASCADE)"
        bigint finance_transaction_id FK "finance_transactions.id (RESTRICT)"
        bigint amount_paid_idr "Nominal terbayar dalam IDR"
        timestamp paid_at "Waktu bayar"
        string payment_method "cash, transfer, qris"
        string receipt_number UK "KW-YYYYMM-XXXX"
        string proof_path "storage/proofs (Nullable)"
        bigint received_by FK "users.id (SET NULL)"
        string status "PAID"
        text notes "Nullable"
        timestamp created_at
        timestamp updated_at
    }

    FINANCE_TRANSACTIONS {
        bigint id PK
        string transaction_number UK "TXN-YYYYMM-XXXX"
        string type "income, expense"
        string category "iuran_kebersihan, keamanan, perbaikan, kas_sosial"
        bigint amount_idr "Nominal presisi integer IDR"
        text description
        string status "draft, published, reversed"
        timestamp transaction_date
        bigint created_by FK "users.id (SET NULL)"
        bigint original_transaction_id FK "finance_transactions.id (SET NULL)"
        boolean is_reversal "Default false"
        int version "Optimistic Locking"
        timestamp created_at
        timestamp updated_at
    }

    PURCHASES {
        bigint id PK
        string purchase_number UK "PO-YYYYMM-XXXX"
        string vendor_name
        bigint total_amount_idr "Total belanja dalam IDR"
        date purchase_date
        string proof_invoice_path "Nullable"
        bigint finance_transaction_id FK "finance_transactions.id (SET NULL)"
        bigint created_by FK "users.id (SET NULL)"
        string status "draft, verified, published"
        timestamp created_at
        timestamp updated_at
    }

    PURCHASE_ITEMS {
        bigint id PK
        bigint purchase_id FK "purchases.id (CASCADE)"
        string item_name
        int quantity
        string unit "pcs, sak, meter, paket"
        bigint unit_price_idr
        bigint subtotal_idr
        timestamp created_at
        timestamp updated_at
    }

    FINANCIAL_REPORTS {
        bigint id PK
        smallint year "Tahun Buku"
        tinyint quarter "Triwulan 1-4"
        int revision "Revisi 1, 2, ..."
        bigint total_income_idr
        bigint total_expense_idr
        bigint ending_balance_idr
        string status "draft, published, archived"
        bigint published_by FK "users.id (SET NULL)"
        timestamp published_at "Nullable"
        timestamp created_at
        timestamp updated_at
    }

    COMPLAINTS {
        bigint id PK
        bigint citizen_id FK "citizens.id (SET NULL, Nullable)"
        bigint user_id FK "users.id (SET NULL, Nullable)"
        string ticket_number UK "ADU-YYYYMMDD-XXXX"
        string category "kebersihan, keamanan, fasilitas, ketertiban"
        string title
        text description
        string location
        boolean is_anonymous "Default false"
        string status "submitted, reviewed, processing, resolved, closed, rejected"
        string priority "rendah, sedang, tinggi, darurat"
        bigint assigned_admin_id FK "users.id (SET NULL)"
        text admin_response "Nullable"
        timestamp resolved_at "Nullable"
        string attachment_path "Nullable"
        int version "Optimistic Locking"
        timestamp created_at
        timestamp updated_at
    }

    SECURITY_REPORTS {
        bigint id PK
        string ticket_number UK "SEC-YYYYMMDD-XXXX"
        bigint reporter_user_id FK "users.id (SET NULL, Nullable)"
        bigint citizen_id FK "citizens.id (SET NULL, Nullable)"
        string incident_type "pencurian, keributan, kebakaran, orang_mencurigakan"
        string title
        text description
        string location
        string status "open, investigating, resolved, false_alarm"
        bigint assigned_to FK "users.id (SET NULL, Nullable)"
        text incident_action_notes "Nullable"
        timestamp resolved_at "Nullable"
        int version "Optimistic Locking"
        timestamp created_at
        timestamp updated_at
    }

    AUDIT_LOGS {
        bigint id PK
        bigint user_id FK "users.id (SET NULL, Nullable)"
        string action "CREATE, UPDATE, DELETE, VERIFY, APPROVE, REVERSE"
        string entity_type "App\\Models\\Citizen, Letter, dll"
        bigint entity_id
        json old_values "Nullable"
        json new_values "Nullable"
        string ip_address "Nullable"
        string user_agent "Nullable"
        timestamp created_at
    }
```

---

## 2. Inventarisasi Tabel Operasional Basis Data (MySQL `layanan_warga`)

Berikut adalah spesifikasi teknis lengkap dari 18 tabel operasional inti sistem:

| No | Nama Tabel Fisik | Primary Key (PK) | Foreign Keys (FK) & Relasi | Kolom Nullable | Kunci Unik (Unique Constraints) | Kardinalitas | Aturan Hapus (Delete Rules) |
|:--:|---|---|---|---|---|:---:|---|
| **1** | `users` | `id` (Bigint Unsigned Auto-Increment) | *Tidak ada* | `email_verified_at`, `phone`, `last_login_at`, `warga_id` | `email` (UK), `warga_id` (Legacy UK) | 1 : 0..1 ke `citizens` | - |
| **2** | `family_cards` | `id` (Bigint Unsigned Auto-Increment) | *Tidak ada* | `postal_code` | `no_kk_hash` (UK) | 1 : N ke `citizens` | - |
| **3** | `citizens` | `id` (Bigint Unsigned Auto-Increment) | `family_card_id` -> `family_cards.id`<br>`user_id` -> `users.id` | `user_id`, `education`, `occupation`, `marital_status`, `address`, `rt`, `rw`, `phone` | `nik_hash` (UK), `user_id` (UK) | N : 1 ke `family_cards`<br>0..1 : 1 ke `users` | `family_card_id`: **CASCADE**<br>`user_id`: **SET NULL** |
| **4** | `jenis_surat` | `id` (Bigint Unsigned Auto-Increment) | *Tidak ada* | `deskripsi`, `template_fields`, `required_attachments` | `kode_surat` (UK) | 1 : N ke `letters` | - |
| **5** | `letters` | `id` (Bigint Unsigned Auto-Increment) | `citizen_id` -> `citizens.id`<br>`jenis_surat_id` -> `jenis_surat.id`<br>`verified_by` -> `users.id`<br>`approved_by` -> `users.id` | `letter_number`, `rejection_reason`, `submitted_at`, `verified_at`, `verified_by`, `approved_at`, `approved_by`, `completed_at`, `verification_token`, `document_hash`, `pdf_path` | `ticket_number` (UK), `letter_number` (UK), `verification_token` (UK) | N : 1 ke `citizens`<br>N : 1 ke `jenis_surat` | `citizen_id`: **CASCADE**<br>`jenis_surat_id`: **SET NULL**<br>`verified_by`: **SET NULL**<br>`approved_by`: **SET NULL** |
| **6** | `letter_attachments` | `id` (Bigint Unsigned Auto-Increment) | `letter_id` -> `letters.id` | *Tidak ada* | *Tidak ada* | N : 1 ke `letters` | `letter_id`: **CASCADE** |
| **7** | `resident_dues` | `id` (Bigint Unsigned Auto-Increment) | `citizen_id` -> `citizens.id` | `notes` | Composite `(citizen_id, period_year, period_month)` (UK) | 1 : N ke `due_payments` | `citizen_id`: **CASCADE** |
| **8** | `due_payments` | `id` (Bigint Unsigned Auto-Increment) | `resident_due_id` -> `resident_dues.id`<br>`finance_transaction_id` -> `finance_transactions.id`<br>`received_by` -> `users.id` | `proof_path`, `received_by`, `notes` | `receipt_number` (UK) | N : 1 ke `resident_dues`<br>N : 1 ke `finance_transactions` | `resident_due_id`: **CASCADE**<br>`finance_transaction_id`: **RESTRICT**<br>`received_by`: **SET NULL** |
| **9** | `finance_transactions` | `id` (Bigint Unsigned Auto-Increment) | `created_by` -> `users.id`<br>`original_transaction_id` -> `finance_transactions.id` | `created_by`, `original_transaction_id` | `transaction_number` (UK) | 1 : N ke `due_payments`<br>1 : N ke `purchases` | `created_by`: **SET NULL**<br>`original_transaction_id`: **SET NULL** |
| **10** | `purchases` | `id` (Bigint Unsigned Auto-Increment) | `finance_transaction_id` -> `finance_transactions.id`<br>`created_by` -> `users.id` | `proof_invoice_path`, `finance_transaction_id`, `created_by` | `purchase_number` (UK) | 1 : N ke `purchase_items` | `finance_transaction_id`: **SET NULL**<br>`created_by`: **SET NULL** |
| **11** | `purchase_items` | `id` (Bigint Unsigned Auto-Increment) | `purchase_id` -> `purchases.id` | *Tidak ada* | *Tidak ada* | N : 1 ke `purchases` | `purchase_id`: **CASCADE** |
| **12** | `financial_reports` | `id` (Bigint Unsigned Auto-Increment) | `published_by` -> `users.id` | `published_by`, `published_at` | Composite `(year, quarter, revision)` (UK) | 1 : 1 per Triwulan Revisi | `published_by`: **SET NULL** |
| **13** | `complaints` | `id` (Bigint Unsigned Auto-Increment) | `citizen_id` -> `citizens.id`<br>`user_id` -> `users.id`<br>`assigned_admin_id` -> `users.id` | `citizen_id`, `user_id`, `assigned_admin_id`, `admin_response`, `resolved_at`, `attachment_path` | `ticket_number` (UK) | N : 1 ke `citizens`<br>N : 1 ke `users` | `citizen_id`: **SET NULL**<br>`user_id`: **SET NULL**<br>`assigned_admin_id`: **SET NULL** |
| **14** | `security_reports` | `id` (Bigint Unsigned Auto-Increment) | `reporter_user_id` -> `users.id`<br>`citizen_id` -> `citizens.id`<br>`assigned_to` -> `users.id` | `reporter_user_id`, `citizen_id`, `assigned_to`, `incident_action_notes`, `resolved_at` | `ticket_number` (UK) | N : 1 ke `users` | `reporter_user_id`: **SET NULL**<br>`citizen_id`: **SET NULL**<br>`assigned_to`: **SET NULL** |
| **15** | `audit_logs` | `id` (Bigint Unsigned Auto-Increment) | `user_id` -> `users.id` | `user_id`, `old_values`, `new_values`, `ip_address`, `user_agent` | *Tidak ada* | N : 1 ke `users` | `user_id`: **SET NULL** |
| **16** | `admin_permissions` | `id` (Bigint Unsigned Auto-Increment) | `user_id` -> `users.id` | *Tidak ada* | Composite `(user_id, permission)` (UK) | N : 1 ke `users` | `user_id`: **CASCADE** |
| **17** | `document_sequences`| `id` (Bigint Unsigned Auto-Increment) | *Tidak ada* | *Tidak ada* | Composite `(document_type, year, month)` (UK) | Singleton Generator | - |
| **18** | `download_audits` | `id` (Bigint Unsigned Auto-Increment) | `actor_user_id` -> `users.id` | `actor_user_id`, `ip_address` | *Tidak ada* | N : 1 ke `users` | `actor_user_id`: **SET NULL** |

---

## 3. Resolusi Keputusan Desain Data Kritis (Sprint 2 Architectural Gates)

### 3.1 Penetapan Hubungan Tunggal Akun–Warga (*Single Source of Truth*)
1. **Aturan Utama:** Relasi antara akun otentikasi (`users`) dan profil kependudukan (`citizens`) diatur secara satu arah melalui kolom `citizens.user_id` (Bigint Unsigned, Nullable, Unique).
2. **Kardinalitas:** `users` 1 ke 0..1 `citizens`. Satu akun pengguna hanya dapat mengklaim satu data warga, dan satu data warga maksimal dihubungkan ke satu akun.
3. **Pemisahan Identifier:** Nilai `users.id` **TIDAK HARUS SAMA** dengan `citizens.id`. Sebagai contoh, Warga dengan `citizens.id = 15` dapat memiliki akun login dengan `users.id = 3`.
4. **Deprekasi `users.warga_id`:**
   - Kolom `users.warga_id` yang ada pada baseline awal telah ditandai sebagai **DEPRECATED**.
   - Model `User` menggunakan relasi `public function citizen(): HasOne { return $this->hasOne(Citizen::class, 'user_id'); }`.
   - Sebelum kolom `warga_id` dihapus fisik di Sprint 6, seluruh data pasangan lama diaudit guna menjamin tidak ada inkonsistensi pasangan (`users.warga_id != citizens.id`).
5. **Hubungan Kartu Keluarga:** `citizens.family_card_id` tetap dipertahankan mengacu ke `family_cards.id` dengan relasi N:1 dan delete rule `CASCADE`.

### 3.2 Standardisasi Nama Fisik Tabel `jenis_surat`
- Sesuai keputusan **ADR-02**, basis data fisik MySQL menggunakan nama tabel `jenis_surat`.
- Dokumentasi teknis, ERD, dan diagram UML wajib menggunakan label `jenis_surat` (bukan istilah rekaan seperti `letter_types`) guna mencegah miskomunikasi antara perancang dan pengembang basis data.
- Model Eloquent mengonfigurasi properti: `protected $table = 'jenis_surat';`.

### 3.3 Penjaminan Integritas Buku Kas Transparan (*Financial Ledger Integrity*)
1. **Relasi Foreign Key:** Kolom `due_payments.finance_transaction_id` berelasi langsung ke `finance_transactions.id`.
2. **Aturan Hapus (Delete Rule) `RESTRICT`:**
   - Aturan penghapusan disetel ke `RESTRICT` (`ON DELETE RESTRICT`).
   - Apabila seorang pengurus mencoba menghapus transaksi kas yang menjadi dasar pembukuan kwitansi iuran warga, MySQL akan menolak transaksi tersebut (*foreign key constraint fails*).
3. **Mekanisme Reversal (Anti-Fraud):**
   - Transaksi kas yang berstatus `published` bersifat *immutable* (tidak dapat diubah/dihapus).
   - Koreksi salah input nominal wajib dilakukan melalui penerbitan transaksi pembalik bertanda `is_reversal = true` dengan referensi `original_transaction_id`.
   - Hal ini menjamin audit keuangan warga tetap 100% transparan dan tidak ada transaksi "hilang secara gaib".

### 3.4 Perlindungan Privasi Data Pribadi (UU PDP No. 27/2022)
- Kolom NIK (`citizens.nik`) dan Nomor KK (`family_cards.no_kk`) disimpan terenkripsi menggunakan algoritma simetris `AES-256-CBC`.
- Kolom pencarian cepat menggunakan hash deterministik non-reversibel `SHA-256` (`nik_hash`, `no_kk_hash`) berindeks unik (*Unique Index*).
- Respons JSON publik dilarang keras mengembalikan plaintext NIK; data hanya dikembalikan dalam format tersamar (`nik_masked`, contoh: `3201************`).
