# Diagram Sistem PRISMA RT 04

Kumpulan diagram Mermaid (ERD, LRS, use case, activity, UML) yang selaras dengan kode dan skema database pada rilis redesign PWA. Ringkasan alur aplikasi ada di [README](../../README.md#alur-aplikasi).

> ERD dan LRS dibangkitkan dari migrasi. Jika skema berubah, perbarui bagian ini bersama README.

### 1. Entity Relationship Diagram (ERD)

Dibangkitkan dari skema hasil migrasi (`php artisan migrate`), 27 tabel domain dipecah menjadi enam modul agar terbaca. Kolom `created_at` dan `updated_at` ada di semua tabel dan tidak digambar. Tabel framework (`cache`, `jobs`, `sessions`) tidak ditampilkan.

**Cara membaca:** kotak berheader warna adalah tabel modul tersebut; kotak abu-abu hanya penanda tabel dari modul lain (hanya PK). `+id` tebal adalah primary key, nama miring adalah foreign key, `{UK}` adalah kolom unik. Garis putus-putus berarti FK boleh kosong (*nullable*), garis utuh berarti FK wajib terisi. Ujung garis memakai notasi *crow's foot*: ‖ tepat satu, ○| nol atau satu, ○< nol atau banyak.

**1.1. Akun dan Akses**

![ERD akun dan akses](diagrams/erd-1-akun-akses.svg)

**1.2. Kependudukan**

![ERD kependudukan](diagrams/erd-2-kependudukan.svg)

**1.3. Surat Pengantar**

![ERD surat pengantar](diagrams/erd-3-surat.svg)

**1.4. Keuangan dan Iuran**

![ERD keuangan dan iuran](diagrams/erd-4-keuangan.svg)

**1.5. Pengaduan dan Keamanan**

![ERD pengaduan dan keamanan](diagrams/erd-5-pengaduan-keamanan.svg)

**1.6. Informasi RT dan Audit**

![ERD informasi RT dan audit](diagrams/erd-6-informasi-audit.svg)

`notifications` dan `personal_access_tokens` terhubung ke `users` secara polimorfik (`notifiable_id`, `tokenable_id`), bukan lewat foreign key database.

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

Setiap diagram memakai swimlane per pelaku. Notasi: lingkaran hitam adalah awal, lingkaran bertanda target adalah akhir, belah ketupat oranye adalah keputusan atau penggabungan, bar hitam adalah fork/join (aksi paralel), dan kotak hijau adalah objek data. Alur ditulis dari kode (`AuthController`, `LetterService`, `ResidentDueService`, `FinanceService`, `ComplaintController`).

**4.1. Registrasi Mandiri & Verifikasi Warga**

![Activity registrasi mandiri warga](diagrams/activity-1-registrasi-warga.svg)

**4.2. Login & Penguncian Percobaan**

![Activity login](diagrams/activity-2-login.svg)

**4.3. Lupa & Atur Ulang Kata Sandi**

![Activity lupa kata sandi](diagrams/activity-3-lupa-password.svg)

**4.4. Birokrasi Surat Pengantar 2-Tier**

![Activity surat pengantar](diagrams/activity-4-surat-pengantar.svg)

**4.5. Pembayaran Iuran ke Buku Kas**

![Activity pembayaran iuran](diagrams/activity-5-pembayaran-iuran.svg)

**4.6. Transaksi Kas, Koreksi, & Laporan Triwulan**

![Activity buku kas dan laporan triwulan](diagrams/activity-6-buku-kas-laporan.svg)

**4.7. Pengaduan Lingkungan**

![Activity pengaduan](diagrams/activity-7-pengaduan.svg)

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

#### 5.2. UML Sequence Diagram

Notasi mengikuti template UML: kotak peserta berwarna, garis hidup putus-putus, bar aktivasi, panah penuh untuk pemanggilan, panah putus-putus untuk balasan, serta fragmen `alt`, `opt`, dan `loop` dengan kondisi penjaga. Urutan pesan ditulis dari kode (`AuthController`, `LetterService`, `ResidentDueService`, `prisma.js`), bukan dari rancangan awal.

**5.2.1. Registrasi Mandiri Warga**

![Sequence registrasi mandiri warga](diagrams/sequence-1-registrasi-warga.svg)

**5.2.2. Login dan Penguncian Percobaan**

![Sequence login](diagrams/sequence-2-login.svg)

**5.2.3. Lupa dan Atur Ulang Kata Sandi**

![Sequence lupa password](diagrams/sequence-3-lupa-password.svg)

**5.2.4. Pengesahan Surat dengan Optimistic Locking**

![Sequence pengesahan surat](diagrams/sequence-4-pengesahan-surat.svg)

**5.2.5. Pencatatan Pembayaran Iuran ke Buku Kas**

![Sequence pembayaran iuran](diagrams/sequence-5-pembayaran-iuran.svg)

**5.2.6. Pembaruan Dashboard Real-Time (Polling)**

![Sequence pembaruan dashboard real-time](diagrams/sequence-6-sinkronisasi-realtime.svg)

Dashboard memakai polling 8 detik (dashboard) dan 10 detik (notifikasi) yang berhenti saat tab tersembunyi. Endpoint `GET /api/v1/sync` untuk sinkronisasi inkremental disediakan bagi klien lain (mis. aplikasi Android) dan tidak dipanggil oleh PWA web.
