# Platform Layanan Publik Warga PRISMA RT 04 (Laravel 13)

> **PRISMA RT 04 Kemayoran**: Platform Realisasi Informasi, Sistem Manajemen & Administrasi Warga Berbasis Web & Progressive Web App (PWA).  
> **Visual Reference:** [https://prisma-rose.vercel.app/](https://prisma-rose.vercel.app/)  
> **Framework:** PHP 8.4+ & Laravel 13.x | **Database:** MySQL 8.x | **UI:** CSS & JavaScript native (PWA) | **Runtime:** FrankenPHP / Docker (Railway)

---

## Mulai Cepat (3 Langkah)

> Butuh **PHP 8.4+**, **Composer**, dan **MySQL** (Laragon/XAMPP). Node.js **tidak** diperlukan.

```bash
# 1. Pasang dependensi & siapkan konfigurasi
composer install
copy .env.example .env          # Linux/Mac: cp .env.example .env
php artisan key:generate

# 2. Buat database di phpMyAdmin / HeidiSQL: layanan_publik_warga (utf8mb4_unicode_ci), lalu isi tabel + data demo
php artisan migrate --seed

# 3. Jalankan
php artisan serve               # buka http://localhost:8000
```

Di `/login` klik salah satu chip **akun demo** (Superadmin, Admin, Ketua RT, Sekretaris, Bendahara, Keamanan, Warga; password `password`).
Pengaturan MySQL ada di `.env` (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`); zona waktu default `Asia/Jakarta`.

### Peta Singkat: apa ada di mana?

| Ingin mengubah... | Buka |
|---|---|
| Tampilan / warna / navbar | `public/css/prisma.css`, `resources/views/layouts/*.blade.php` |
| Kolom tabel & form tiap halaman CRUD | `public/js/pages.js` |
| Aturan validasi, hak akses, notifikasi tiap modul | `app/Support/ManagedRegistry.php` |
| Menu & halaman per peran | `app/Support/Navigation.php`, `app/Models/User.php` (`hasPermission`) |
| Registrasi, login, lupa password | `app/Http/Controllers/Api/V1/AuthController.php`, `public/js/auth.js` |
| Notifikasi | `app/Services/NotifyService.php`, `public/js/prisma.js` |
| Diagram 5 unsur | `resources/views/app/system.blade.php` (menu *Pemodelan Sistem*) |

### Pemecahan Masalah

| Gejala | Solusi |
|---|---|
| `could not find driver` / `Connection refused` | Nyalakan MySQL di Laragon/XAMPP; pastikan ekstensi `pdo_mysql` aktif dan `.env` benar |
| `Unknown database 'layanan_publik_warga'` | Buat database dulu (langkah 2), lalu `php artisan migrate --seed` |
| Halaman putih / `No application encryption key` | Jalankan `php artisan key:generate` |
| Data lama / error setelah `git pull` | `php artisan migrate` lalu `php artisan optimize:clear` |
| Ingin mengulang data demo dari nol | `php artisan migrate:fresh --seed` (**menghapus semua data** di database itu) |
| Install PWA tidak muncul | PWA butuh HTTPS atau `localhost`; di iPhone: Share â†’ Add to Home Screen |
| Tes gagal | `php artisan test` memakai SQLite memori, tidak menyentuh database MySQL Anda |

---

## Daftar Isi
0. [Mulai Cepat](#mulai-cepat-3-langkah)
1. [Tentang Proyek](#tentang-proyek)
2. [Identitas Komunitas & Referensi Desain](#identitas-komunitas--referensi-desain)
3. [Arsitektur Sistem & Tech Stack](#arsitektur-sistem--tech-stack)
4. [Hak Akses: 3 Tingkat & 7 Peran](#hak-akses-3-tingkat--7-peran)
5. [Pemodelan Rekayasa Perangkat Lunak (5 Unsur Utama)](#pemodelan-rekayasa-perangkat-lunak-5-unsur-utama)
   - [1. Entity Relationship Diagram (ERD)](#1-entity-relationship-diagram-erd)
   - [2. Logical Record Structure (LRS)](#2-logical-record-structure-lrs)
   - [3. Use Case Diagram & Spesifikasi Aktor](#3-use-case-diagram--spesifikasi-aktor)
   - [4. Activity Diagram (Alur Verifikasi NIK & Persuratan)](#4-activity-diagram)
   - [5. Unified Modeling Language (UML) Diagrams](#5-unified-modeling-language-uml-diagrams)
6. [Fitur Unggulan Antarmuka & Layanan Publik](#fitur-unggulan-antarmuka--layanan-publik)
7. [Klasifikasi Dokumentasi Teknis (5 Pilar Subfolder `docs/`)](#klasifikasi-dokumentasi-teknis-5-pilar-subfolder-docs)
8. [Panduan Instalasi & Menjalankan Proyek](#panduan-instalasi--menjalankan-proyek)
9. [Pengujian Otomatis (Automated Testing)](#pengujian-otomatis-automated-testing)
10. [Keamanan & Perlindungan Data Pribadi (UU PDP)](#keamanan--perlindungan-data-pribadi-uu-pdp)

---

## Tentang Proyek

**PRISMA RT 04** (*Platform Realisasi Informasi, Sistem Manajemen & Administrasi*) adalah platform digital layanan publik warga RT 04 / RW 09 Kemayoran, Jakarta Pusat. Sistem ini mengintegrasikan seluruh administrasi lingkungan warga ke dalam satu ekosistem terpadu:

- **Pendaftaran Mandiri & Verifikasi NIK Real-Time**: Pencocokan 16 digit NIK secara instan dengan deteksi warga sensus terdaftar (*pre-registered*), pengajuan warga baru (*pending verification*), dan pencegahan duplikasi akun.
- **Birokrasi Surat Digital 2-Tier**: Verifikasi berkas administratif oleh **Sekretaris**, persetujuan/tanda tangan digital oleh **Ketua RT**, penomoran surat otomatis anti-bentrok, dan verifikasi keaslian via token QR Code.
- **Buku Kas Anti-Fraud (*Immutable Financial Ledger*)**: Setiap penerimaan dan pengeluaran dicatat dengan presisi `DECIMAL(15,2)`. Transaksi terbit bersifat *append-only* dengan mekanisme *reversal audit* jika terdapat koreksi.
- **Pengaduan Warga & Laporan Keamanan**: Penyampaian keluhan fasilitas lingkungan dan laporan darurat dengan bukti foto, pelacakan status tiket, dan koordinasi petugas keamanan pos ronda.
- **Transparansi Komunitas**: Publikasi warta pengumuman, agenda kegiatan kerja bakti, struktur pengurus RT, jadwal ronda kamling, dan kontak darurat 24 jam.
- **Progressive Web App (PWA)**: Akses cepat seperti aplikasi mobile asli dengan Web App Manifest, Service Worker caching cerdas, dan halaman *offline fallback*.

---

## Identitas Komunitas & Referensi Desain

- **Entitas Lingkungan:** Sekretariat PRISMA RT 04 / RW 09 Kemayoran
- **Alamat Sekretariat:** Rumah - Gg. Bugis No. 95, RT 04 / RW 09, Kebon Kosong, Kec. Kemayoran, Kota Jakarta Pusat, DKI Jakarta 10620
- **Koordinat Wilayah (GEO):** Latitude `-6.1618`, Longitude `106.8456`
- **Saluran Bantuan WhatsApp:** `+62 878-7200-4448`
- **Kontak IT & Pengurus:** Swandaru Tirta (`swandrax@gmail.com`)
- **Visual & Functional Reference:** [https://prisma-rose.vercel.app/](https://prisma-rose.vercel.app/)

---

## Arsitektur Sistem & Tech Stack

Platform dibangun secara murni menggunakan arsitektur monolitik modern **MVC + Service Layer + ViewModel** dengan backend PHP Laravel 13 dan frontend Blade Components:

| Lapisan Sistem | Teknologi | Keterangan & Rincian Implementasi |
|---|---|---|
| **Backend Framework** | **Laravel 13.x** (PHP 8.4.1+) | Native Laravel 13 dengan Eloquent ORM, Form Requests, Policies, dan Gates |
| **Frontend Templating** | **Laravel Blade + Components** | Komponen UI modular, semantic HTML5, WCAG accessible |
| **CSS / Design System** | **CSS native (`public/css/prisma.css`)** | Tema gelap/terang, token warna, navbar desktop + bottom-nav mobile; tanpa langkah build |
| **Interaktivitas UI** | **JavaScript native (`public/js/*.js`)** | `fetch` + polling real-time, validasi registrasi langsung ke MySQL, modal CRUD, toast notifikasi |
| **Database Transaksional**| **MySQL 8.x** | Sumber kebenaran utama (*Single Source of Truth*), nilai moneter `DECIMAL(15,2)`, foreign keys |
| **Cache & Queue Engine** | **Redis 7.x** | Penanganan cache aplikasi, session driver, queue worker, dan API rate limiting |
| **Autentikasi & API** | **Session + Laravel Sanctum** | Stateful web session untuk browser dan Bearer Token untuk REST API `/api/v1` |
| **Dokumentasi API** | **OpenAPI 3.1 & Swagger UI** | Tersedia interaktif di rute `/docs/api` bersumber dari `docs/api_spec/openapi.yaml` |
| **Containerization** | **FrankenPHP & Docker** | Image `dunglas/frankenphp:1-php8.4-bookworm` dengan Caddy web server |
| **Hosting & Deploy** | **Railway-Compatible** | Konfigurasi deklaratif via `Dockerfile` dan `railway.json` |
| **Code Style & QA** | **Laravel Pint & PHPUnit** | PSR-12 standard formatting dan 150 Automated Feature Tests (100% Passed) |

---

## Hak Akses: 3 Tingkat & 7 Peran

| Tingkat akses | Peran (`role`) | Username demo | Wewenang utama |
|---|---|---|---|
| **Superadmin** | `SUPERADMIN` | `superadmin` | Kontrol penuh: semua modul, manajemen semua peran, audit log, halaman Pemodelan Sistem |
| **Admin** | `ADMIN` | `admin` | Kelola pengguna (selain Superadmin/Admin), data warga, pengumuman, agenda, pengurus, ronda, kontak, audit |
| **Admin** | `KETUA_RT` | `ketua_rt` | Pengesahan surat (tahap akhir), supervisi pengaduan, keamanan, dan kas |
| **Admin** | `SEKRETARIS` | `sekretaris` | Verifikasi surat & pendaftar baru, data warga, pengumuman, agenda, struktur pengurus |
| **Admin** | `BENDAHARA` | `bendahara` | Catat/terbitkan/koreksi transaksi kas |
| **Admin** | `PETUGAS_KEAMANAN` | `keamanan` | Tindak lanjut laporan keamanan, jadwal ronda, kontak darurat |
| **User** | `WARGA` | `warga` | Hanya data sendiri: ajukan surat, pengaduan, laporan keamanan |

Semua akun demo berpassword `password` (hanya environment `local`/`testing`). Matriks hak akses lengkap yang dihitung langsung dari kode tersedia di menu **Pemodelan Sistem â†’ Matriks Hak Akses**.

**Registrasi warga** memakai **NIK (16 digit), No. KK (16 digit), Nama, dan Username**. Aturan yang ditegakkan di API *dan* di database MySQL:
- satu NIK hanya satu akun;
- kombinasi **nama + No. KK** hanya boleh terdaftar sekali (indeks unik `citizens_kk_name_unique`);
- username unik (tidak peka huruf besar/kecil);
- password sengaja **tidak** dibuat unik antar-pengguna (itu akan membocorkan password pengguna lain); yang ditegakkan adalah password minimal 8 karakter yang di-hash bcrypt.

**Lupa password**: `/forgot-password` memverifikasi username + NIK + No. KK terhadap database, lalu **mengirim tautan reset sekali pakai (20 menit) ke email akun**. Akun tanpa email, atau bila SMTP belum diatur, memakai cadangan: tautan ditampilkan di layar. Token disimpan ter-hash di `password_reset_requests`. Login dikunci sementara 5 menit setelah 5 kali gagal per username. Akun pengurus tidak memakai jalur ini; Superadmin mengatur ulang lewat Manajemen Pengguna.

---

## Pemodelan Rekayasa Perangkat Lunak (5 Unsur Utama)

Sebagai kepatuhan pada standar dokumentasi rekayasa perangkat lunak (*Software Engineering Documentation*), sistem ini didokumentasikan secara komprehensif melalui **5 Unsur Pemodelan Sistem**:

### 1. Entity Relationship Diagram (ERD)

```mermaid
erDiagram
    FAMILY_CARDS ||--o{ CITIZENS : "1:N (1 KK menampung N Warga)"
    CITIZENS ||--o| USERS : "1:1 (1 Warga memiliki 0..1 Akun Login)"
    
    USERS ||--o{ ADMIN_PERMISSIONS : "1:N (Resolusi M:N User ke Hak Akses)"
    USERS ||--o{ PUSH_SUBSCRIPTIONS : "1:N (1 User memiliki N Token Notifikasi)"
    USERS ||--o{ LETTERS : "1:N (1 Warga mengajukan N Permohonan Surat)"
    LETTER_TYPES ||--o{ LETTERS : "1:N (1 Format Surat digunakan N Permohonan)"
    LETTERS ||--o{ LETTER_ATTACHMENTS : "1:N (1 Surat memuat N Berkas Lampiran)"
    
    USERS ||--o{ COMPLAINTS : "1:N (1 Warga membuat N Aduan Keluhan)"
    USERS ||--o{ SECURITY_REPORTS : "1:N (1 Pengguna melaporkan N Insiden)"
    USERS ||--o{ FINANCE_TRANSACTIONS : "1:N (1 Bendahara mencatat N Transaksi Kas)"
    USERS ||--o{ RESIDENT_DUES : "1:N (Tagihan iuran bulanan per warga)"
    RESIDENT_DUES ||--o{ DUE_PAYMENTS : "1:N (Riwayat pembayaran iuran)"
    USERS ||--o{ AUDIT_LOGS : "1:N (1 User memicu N Rekaman Jejak Audit)"

    FAMILY_CARDS {
        bigint id PK
        varchar nomor_kk UK "Nomor KK 16 Digit"
        varchar kepala_keluarga_name
        text alamat
        varchar rt
        varchar rw
        varchar kode_pos
    }

    CITIZENS {
        bigint id PK
        bigint family_card_id FK "Relasi ke FAMILY_CARDS"
        bigint user_id FK "Relasi 1:1 ke USERS (Unique)"
        varchar nik_hash UK "SHA-256 Blind Index"
        varchar full_name
        varchar full_name_key "UK bersama family_card_id"
        date birth_date
        varchar gender
        text address
        varchar phone
        varchar status_warga "tetap / pending_verification"
        int version "Optimistic Locking"
    }

    USERS {
        bigint id PK
        bigint warga_id FK "Relasi ke CITIZENS (Unique)"
        varchar name
        varchar username UK "Login utama"
        varchar email UK "Opsional"
        varchar password "Bcrypt Hash"
        enum role "SUPERADMIN,ADMIN,KETUA_RT,BENDAHARA,SEKRETARIS,WARGA"
        boolean is_active
        datetime last_login_at
    }

    LETTERS {
        bigint id PK
        bigint user_id FK
        bigint citizen_id FK
        bigint letter_type_id FK
        varchar ticket_number UK "Nomor Tiket Publik"
        varchar reference_number "Nomor Surat Resmi RT"
        varchar status "draft,submitted,verified,approved,rejected,completed"
        varchar verification_token UK "Token QR Code"
        varchar document_hash "SHA-256 Validasi Integritas"
        json form_data
    }

    FINANCE_TRANSACTIONS {
        bigint id PK
        bigint user_id FK "Bendahara Pencatat"
        varchar transaction_type "income / expense"
        decimal amount "DECIMAL(15,2)"
        varchar category
        text description
        date transaction_date
        boolean is_published
        boolean is_reversal
    }
```

---

### 2. Logical Record Structure (LRS)

Representasi tabel ternormalisasi bentuk ketiga (3NF) dengan foreign key eksplisit:
```
FAMILY_CARDS (id [PK], nomor_kk [UK], kepala_keluarga_name, alamat, rt, rw, kode_pos, created_at)
CITIZENS (id [PK], family_card_id [FK], user_id [FK, UK], nik_hash [UK], full_name, birth_date, gender, address, phone, status_warga, version, created_at)
USERS (id [PK], warga_id [FK, UK], name, email [UK], password, role, is_active, last_login_at, created_at)
LETTER_TYPES (id [PK], code [UK], name, description, required_attachments, is_active, created_at)
LETTERS (id [PK], user_id [FK], citizen_id [FK], letter_type_id [FK], ticket_number [UK], reference_number, status, verification_token [UK], document_hash, form_data, created_at)
LETTER_ATTACHMENTS (id [PK], letter_id [FK], file_path, original_name, mime_type, file_size, created_at)
FINANCE_TRANSACTIONS (id [PK], user_id [FK], transaction_type, amount, category, description, transaction_date, is_published, is_reversal, reversal_of_id [FK], created_at)
RESIDENT_DUES (id [PK], citizen_id [FK], due_month, due_year, amount, status, created_at)
DUE_PAYMENTS (id [PK], resident_due_id [FK], payment_reference [UK], amount, payment_method, payment_date, created_at)
COMPLAINTS (id [PK], user_id [FK], title, description, category, status, photo_path, created_at)
SECURITY_REPORTS (id [PK], user_id [FK], incident_type, location, description, incident_time, status, photo_path, created_at)
AUDIT_LOGS (id [PK], user_id [FK], event, auditable_type, auditable_id, old_values, new_values, ip_address, user_agent, created_at)
```

---

### 3. Use Case Diagram & Spesifikasi Aktor

```mermaid
flowchart LR
    subgraph Aktor
        W[Warga Lingkungan]
        S[Sekretaris RT]
        K[Ketua RT]
        B[Bendahara RT]
        SA[Superadmin]
    end

    subgraph Modul Layanan PRISMA RT 04
        UC1(Registrasi Mandiri & Aktivasi NIK)
        UC2(Pengajuan Surat Pengantar)
        UC3(Verifikasi Berkas Surat & Warga Baru)
        UC4(Pengesahan & Tanda Tangan Surat)
        UC5(Pelacakan Tiket Surat & Verifikasi QR)
        UC6(Pencatatan Buku Kas & Tagihan Iuran)
        UC7(Tinjau Transparansi Keuangan Publik)
        UC8(Pelaporan Aduan & Keamanan)
        UC9(Manajemen User, RBAC & Audit Trail)
    end

    W --> UC1
    W --> UC2
    W --> UC5
    W --> UC7
    W --> UC8

    S --> UC3
    S --> UC5

    K --> UC4
    K --> UC7
    K --> UC8

    B --> UC6
    B --> UC7

    SA --> UC9
```

---

### 4. Activity Diagram

#### Alur Pendaftaran Mandiri Warga & Validasi NIK Real-Time
```mermaid
stateDiagram-v2
    [*] --> InputNIK: Warga Memasukkan 16 Digit NIK
    InputNIK --> CheckCitizen: AJAX /api/v1/auth/check-citizen
    
    CheckCitizen --> PreRegistered: NIK Terdata di Master Sensus RT
    CheckCitizen --> NewApplicant: NIK Belum Terdata di Master RT
    CheckCitizen --> AlreadyActive: NIK Sudah Terhubung Akun User

    AlreadyActive --> RedirectLogin: Tampilkan Alert & Buka /login
    
    PreRegistered --> FormAktivasi: Kunci Nama KTP, Isi Email & Password
    FormAktivasi --> SubmitAktivasi: POST /api/v1/register
    SubmitAktivasi --> AkunAktif: User Role 'warga' Langsung Aktif

    NewApplicant --> FormLengkap: Isi Nama, Email, Password, Berkas KTP
    FormLengkap --> SubmitBaru: POST /api/v1/register
    SubmitBaru --> PendingVerification: Status Citizen 'pending_verification'
    PendingVerification --> ReviewSekretaris: Sekretaris Memeriksa Berkas KTP
    ReviewSekretaris --> Disetujui: Warga Diverifikasi
    
    AkunAktif --> [*]
    Disetujui --> [*]
    RedirectLogin --> [*]
```

#### Alur Birokrasi Surat Pengantar Digital 2-Tier
```mermaid
stateDiagram-v2
    [*] --> DrafPermohonan: Warga Memilih Jenis Surat & Mengisi Formulir
    DrafPermohonan --> UnggahLampiran: Unggah Berkas Persyaratan (KTP/KK)
    UnggahLampiran --> KirimPermohonan: Warga Menyerahkan Surat (Status: SUBMITTED)
    
    KirimPermohonan --> MejaSekretaris: Notifikasi Masuk ke Sekretaris RT
    MejaSekretaris --> VerifikasiBerkas: Sekretaris Memeriksa Keabsahan Berkas
    
    VerifikasiBerkas --> DitolakSekretaris: Berkas Kurang Lengkap (Status: REJECTED)
    VerifikasiBerkas --> Diverifikasi: Berkas Valid (Status: VERIFIED)
    
    Diverifikasi --> MejaKetuaRT: Surat Masuk Antrean Pengesahan Ketua RT
    MejaKetuaRT --> Disahkan: Ketua RT Menyetujui (Status: APPROVED)
    MejaKetuaRT --> DitolakKetuaRT: Ditolak dengan Catatan (Status: REJECTED)
    
    Disahkan --> TerbitkanDokumen: Nomor Surat Terbit, Hash SHA-256 & QR Token Dibuat
    TerbitkanDokumen --> Selesai: Surat Siap Diunduh PDF Resmi (Status: COMPLETED)
    
    Selesai --> [*]
    DitolakSekretaris --> [*]
    DitolakKetuaRT --> [*]
```

---

### 5. Unified Modeling Language (UML) Diagrams

#### 5.1. UML Class Diagram
```mermaid
classDiagram
    class User {
        +bigint id
        +string name
        +string email
        +string password
        +UserRole role
        +boolean is_active
        +citizen() Citizen
        +hasPermission(string permission) bool
    }

    class Citizen {
        +bigint id
        +string nik
        +string nik_hash
        +string full_name
        +string status_warga
        +int version
        +user() User
        +letters() HasMany
    }

    class Letter {
        +bigint id
        +string ticket_number
        +string reference_number
        +string status
        +string verification_token
        +string document_hash
        +verify(User actor) void
        +approve(User actor) void
        +reject(User actor, string reason) void
    }

    class FinanceTransaction {
        +bigint id
        +string transaction_type
        +decimal amount
        +string category
        +boolean is_published
        +publish() void
        +reverse(string reason) FinanceTransaction
    }

    class LetterService {
        +createLetter(Citizen citizen, array data) Letter
        +verifyLetter(Letter letter, User verifier) Letter
        +approveLetter(Letter letter, User approver) Letter
    }

    class FinanceService {
        +recordTransaction(array data) FinanceTransaction
        +getPublicSummary() array
    }

    User "1" <--> "1" Citizen : BelongsTo / HasOne
    Citizen "1" --> "*" Letter : Submits
    LetterService ..> Letter : Manages
    FinanceService ..> FinanceTransaction : Manages
```

#### 5.2. UML Sequence Diagram: Optimistic Concurrency Control
```mermaid
sequenceDiagram
    actor Admin as Pengurus RT
    participant Form as Antarmuka Web
    participant Controller as CitizenController
    participant Service as CitizenService
    participant DB as MySQL Database

    Admin->>Form: Buka Formulir Edit Warga
    Form->>Controller: GET /api/v1/admin/citizens/1
    Controller->>DB: SELECT * FROM citizens WHERE id = 1
    DB-->>Controller: Citizen Record (version = 1)
    Controller-->>Form: JSON Data (version = 1)
    
    Admin->>Form: Simpan Perubahan
    Form->>Controller: PATCH /api/v1/admin/citizens/1 {..., version: 1}
    Controller->>Service: update(citizen, data, expectedVersion=1)
    Service->>DB: SELECT version FROM citizens WHERE id = 1 FOR UPDATE
    DB-->>Service: Current Version (version = 1)
    
    alt Versi Cocok (version == expectedVersion)
        Service->>DB: UPDATE citizens SET ..., version = 2 WHERE id = 1
        DB-->>Service: OK
        Service-->>Controller: Updated Record
        Controller-->>Form: HTTP 200 OK (Data Berhasil Disimpan)
        Form-->>Admin: Notifikasi Sukses
    else Konflik Konkurensi (version != expectedVersion)
        Service-->>Controller: ConcurrencyConflictException
        Controller-->>Form: HTTP 409 Conflict (Data telah diubah pihak lain)
        Form-->>Admin: Dialog Peringatan & Muat Ulang Data Terbaru
    end
```

---

## Fitur Unggulan Antarmuka & Layanan Publik

1. **Aestetika Modern PRISMA RT 04:**
   - Navigasi responsif dengan logo gradien Blue-Cyan dan drawer mobile ramah layar sentuh.
   - Kartu tindakan cepat (*"Apa yang ingin Anda lakukan?"*): Urus Surat, Cek Kas RT, Lapor Masalah, Jadwal Kegiatan, Hubungi Pengurus via WhatsApp.
2. **Umpan Balik Validasi Interaktif:**
   - Animasi *shake* lembut saat input formulir tidak valid.
   - Notifikasi toast mengambang untuk status sukses dan peringatan sistem.
3. **Optimasi Core Web Vitals:**
   - *Preconnect* tipografi Google Fonts via Bunny Fonts untuk menghilangkan FOIT (*Flash of Invisible Text*).
   - *Zero Cumulative Layout Shift* (CLS = 0) dengan dimensi gambar eksplisit.
4. **Metadata SEO & GEO Wilayah:**
   - Tag OpenGraph dan Twitter Cards terpasang rapi.
   - Schema.org Structured Data (`GovernmentOrganization` dan `WebSite`) terindeks di wilayah Kemayoran, Jakarta Pusat.

---

## API CRUD Real-Time (MySQL)

Seluruh data dibaca/ditulis langsung ke MySQL lewat `/api/v1` (autentikasi: sesi browser, atau Bearer token Sanctum dari `POST /api/v1/login`).

| Method | Endpoint | Keterangan |
|---|---|---|
| `GET` | `/api/v1/manage/{resource}?q=&page=&per_page=&status=&mine=1` | Daftar (cari, filter, paginasi, `updated_since`) |
| `GET` | `/api/v1/manage/{resource}/{id}` | Detail |
| `POST` | `/api/v1/manage/{resource}` | Tambah |
| `PATCH` / `PUT` | `/api/v1/manage/{resource}/{id}` | Ubah (kirim `version` â†’ `409 Conflict` bila basi) |
| `DELETE` | `/api/v1/manage/{resource}/{id}` | Hapus |
| `POST` | `/api/v1/manage/{resource}/{id}/{aksi}` | Aksi alur kerja: `letters`: `submit`, `verify`, `approve`, `complete`, `reject`; `finance`: `publish`, `reverse`; `citizens`: `verify`, `reject` |

`{resource}`: `users`, `citizens`, `announcements`, `events`, `officers`, `schedules`, `contacts`, `letters`, `complaints`, `security-reports`, `finance`, `dues` (iuran: `POST` = buat tagihan bulanan, aksi `pay` = catat pembayaran), `audit-logs`. Hak akses tiap resource ditegakkan per peran (lihat matriks di menu *Pemodelan Sistem*); warga hanya dapat melihat datanya sendiri.

Berkas privat disajikan setelah otorisasi: `GET /api/v1/files/letter-attachments/{id}`, `/files/complaints/{id}`, `/files/ktp/{id}`; surat sah diunduh lewat `GET /api/v1/letters/{id}/download?format=pdf|docx` (setelah disahkan).

**Ekspor CSV** (Excel-ready, UTF-8 BOM, aman dari formula injection, maks. 5.000 baris, tercatat di audit log): `GET /api/v1/manage/{resource}/export?status=&q=` — tombol *Ekspor CSV* ada di halaman Warga, Kas, Iuran, Surat, Pengaduan, dan Audit. **Foto profil:** `POST|DELETE /api/v1/me/photo`.

Endpoint pendukung: `GET /api/v1/portal` (beranda publik tanpa cache), `GET /api/v1/dashboard/summary`, `GET|POST|DELETE /api/v1/notifications`, `PATCH /api/v1/me/account`, `POST /api/v1/me/password`, `POST /api/v1/auth/check-citizen`, `POST /api/v1/auth/forgot-password`, `POST /api/v1/auth/reset-password`.

**Real-time:** UI memakai polling `fetch` (8 detik untuk tabel/dashboard, 10 detik untuk notifikasi, 20 detik untuk beranda publik) yang berhenti otomatis saat tab tidak aktif; respons API dilengkapi `ETag`. Setiap perubahan memicu notifikasi database (`notifications`) + toast/alert, badge lonceng, dan notifikasi browser bila diizinkan.

---

## Klasifikasi Dokumentasi Teknis (5 Pilar Subfolder `docs/`)

Dokumentasi proyek diklasifikasikan ke dalam 5 subfolder tematik yang dapat ditinjau melalui [docs/README.md](docs/README.md):

1. **[`docs/api_spec/`](docs/api_spec/README.md)**:
   - [openapi.yaml](docs/api_spec/openapi.yaml): Spesifikasi OpenAPI 3.1 untuk Swagger UI interaktif (`/docs/api`).
   - [API_SPEC.md](docs/api_spec/API_SPEC.md): Katalog endpoint RESTful JSON v1.
   - [API_V1_1_CONTRACT.md](docs/api_spec/API_V1_1_CONTRACT.md): Kontrak penagihan iuran dan audit laporan keuangan.
2. **[`docs/business/`](docs/business/README.md)**:
   - [AUTH_REGISTER_AND_LOGIN_SPEC.md](docs/business/AUTH_REGISTER_AND_LOGIN_SPEC.md): **Spesifikasi mendalam alur registrasi, validasi NIK, aktivasi akun, dan login.**
   - [SRS.md](docs/business/SRS.md): Software Requirements Specification.
   - [LETTER_WORKFLOW.md](docs/business/LETTER_WORKFLOW.md): Alur kerja surat 2-tier dan QR verification token.
   - [FINANCIAL_REPORTS_AND_BACKUP_STORE.md](docs/business/FINANCIAL_REPORTS_AND_BACKUP_STORE.md): Regulasi buku kas anti-fraud & backup store.
   - [DATA_CLASSIFICATION.md](docs/business/DATA_CLASSIFICATION.md): Standar privasi UU PDP No. 27/2022.
3. **[`docs/design/`](docs/design/README.md)**:
   - [BRAND_GUIDE.md](docs/design/BRAND_GUIDE.md): Pedoman identitas visual resmi PRISMA RT 04.
   - [ERD.md](docs/design/ERD.md): Skema 24 tabel database relasional.
   - [DIAGRAMS.md](docs/design/DIAGRAMS.md): Sequence Diagram dan Activity Diagram.
   - [UI_PLAN.md](docs/design/UI_PLAN.md): Rencana antarmuka responsif ramah segala usia.
   - [WEB_PERFORMANCE_SEO_GEO.md](docs/design/WEB_PERFORMANCE_SEO_GEO.md): Optimasi Core Web Vitals dan SEO lokal.
   - [CIRCULAR_FRAMEWORK_METHODS_AND_ALGORITHMS.md](docs/design/CIRCULAR_FRAMEWORK_METHODS_AND_ALGORITHMS.md): **Metode, algoritma & standarisasi Kerangka Diagram Lingkaran Sistem.**
4. **[`docs/plan_project/`](docs/plan_project/README.md)**:
   - [PROJECT_PLAN.md](docs/plan_project/PROJECT_PLAN.md): Master rencana proyek dan ruang lingkup.
   - [SPRINT_PLAN.md](docs/plan_project/SPRINT_PLAN.md): Pembagian 6 sprint berkelanjutan.
5. **[`docs/project_management/`](docs/project_management/README.md)**:
   - [RBAC.md](docs/project_management/RBAC.md): Penegakan hak akses berbasis peran.
   - [RBAC_MATRIX.md](docs/project_management/RBAC_MATRIX.md): Matriks wewenang modular per tindakan.
   - [FIREBASE_FREE_TIER_SETUP.md](docs/project_management/FIREBASE_FREE_TIER_SETUP.md): Evaluasi cloud notification.

---

## Panduan Instalasi & Menjalankan Proyek (Laragon / XAMPP di Laptop Lain)

Aplikasi ini bersifat *self-contained* dan siap dijalankan di perangkat/laptop baru mana pun menggunakan stack lokal standar seperti **Laragon** atau **XAMPP**.

### 1. Kebutuhan Sistem & Prasyarat
- **PHP** versi **>= 8.4** *(Wajib PHP 8.4+ karena menggunakan Laravel 13)*
  - Ekstensi PHP yang aktif: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `gd`, `zip`.
- **Composer** versi **>= 2.5**
- *(Node.js/NPM tidak diperlukan â€” UI memakai CSS & JavaScript native tanpa langkah build.)*
- **MySQL Database Server** versi **8.x** (atau MariaDB bawaan Laragon/XAMPP)
- **Redis Server** *(Opsional â€” aplikasi telah dikonfigurasi otomatis menggunakan driver `database` untuk session, cache, dan queue jika Redis tidak terpasang).*

> ðŸ’¡ **Tips Pengguna Laragon / XAMPP (Cara Memastikan PHP 8.4):**
> 1. Jika Laragon/XAMPP di laptop Anda masih menggunakan PHP 8.1 atau 8.2, unduh paket zip **PHP 8.4 VS17 x64 Thread Safe** dari [windows.php.net/download](https://windows.php.net/download/).
> 2. Ekstrak ke folder `C:\laragon\bin\php\php-8.4.x-Win32-x64`.
> 3. Buka Laragon âž¡ï¸ Klik Kanan di mana saja âž¡ï¸ **PHP** âž¡ï¸ **Version** âž¡ï¸ Pilih versi **8.4.x**.

---

### 2. Langkah demi Langkah Menjalankan di Laptop Baru (5 Menit)

#### Langkah 1: Clone Repositori dari GitHub
Buka terminal (Git Bash / PowerShell / Command Prompt):
```bash
git clone https://github.com/not162/platform-layanan-publik-warga.git
cd platform-layanan-publik-warga
```

#### Langkah 2: Instal Dependensi Backend
```bash
composer install
```

#### Langkah 3: Konfigurasi File Lingkungan (`.env`)
Salin file konfigurasi contoh dan buat kunci enkripsi aplikasi:
```bash
# Di Windows PowerShell / CMD:
copy .env.example .env

# Generate application encryption key:
php artisan key:generate
```

Buka file `.env` di teks editor, lalu sesuaikan koneksi database MySQL Laragon/XAMPP Anda:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=layanan_publik_warga
DB_USERNAME=root
DB_PASSWORD=
```
*(Driver session, queue, dan cache secara default sudah diset ke `database`, sehingga tidak memerlukan konfigurasi Redis tambahan di laptop lokal).*

#### Langkah 4: Buat Database & Jalankan Migrasi + Seeder
1. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`) atau HeidiSQL bawaan Laragon.
2. Buat database baru dengan nama: **`layanan_publik_warga`** (Collation: `utf8mb4_unicode_ci`).
3. Jalankan migrasi dan pengisian data awal melalui terminal:
```bash
php artisan migrate --seed
```
*âœ¨ Perintah ini menyusun seluruh tabel relasional dan menginjeksi akun demo (**Superadmin**, **Admin**, **Ketua RT**, **Sekretaris**, **Bendahara**, **Keamanan**, **Warga**) beserta data contoh beranda, kas, surat, dan pengaduan.*

#### Langkah 5: Jalankan Server Lokal
```bash
php artisan serve
```
Atau gunakan Laragon: arahkan *document root* ke folder `public/`.

Buka browser di laptop Anda:
ðŸŒ **`http://localhost:8000`**

---

### 3. Pintasan Kredensial Pengujian Cepat
Pada halaman `/login` (environment `local`) tersedia chip **Pintasan akun demo** satu klik: Superadmin, Admin, Ketua RT, Sekretaris, Bendahara, Keamanan, dan Warga (password `password`).

Untuk mencoba aktivasi akun dari master RT: buka `/register`, isi NIK `3171045505920002`, No. KK `3171040409950001`, nama `Siti Aminah`, lalu pilih username.

### 4. Mengirim Email (reset password)
Default `MAIL_MAILER=log` (email ditulis ke `storage/logs/laravel.log`). Untuk email sungguhan, isi `.env`, contoh Gmail (gunakan *App Password*):
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=akun@gmail.com
MAIL_PASSWORD=app-password-16-karakter
MAIL_FROM_ADDRESS=akun@gmail.com
```

### 5. Deploy ke Production
Seeder demo **tidak berjalan** di `APP_ENV=production` (tidak ada akun/password bawaan). Urutan:
```bash
# .env production: APP_ENV=production, APP_DEBUG=false, APP_URL=https://domain-anda, DB_*, SESSION_SECURE_COOKIE=true, MAIL_* (SMTP)
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan app:create-superadmin admin.rt --name="Nama Anda" --email=anda@domain.com   # password diminta interaktif
php artisan storage:link   # hanya bila memakai disk public
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
Catatan: login web memakai cookie sesi + CSRF (host dari `APP_URL` otomatis terdaftar sebagai domain stateful Sanctum; domain tambahan: `SANCTUM_STATEFUL_DOMAINS`). Backup rutin database MySQL dan folder `storage/app/private` (KTP, lampiran). PWA mensyaratkan HTTPS.

### 6. Membuka Data Langsung di phpMyAdmin
Setiap aksi CRUD di UI/API langsung ditulis ke MySQL. Buka `http://localhost/phpmyadmin` â†’ database `layanan_publik_warga` â†’ tabel `users`, `citizens`, `letters`, `finance_transactions`, `announcements`, `notifications`, `audit_logs`, dst. Halaman akan menampilkan perubahan tanpa perlu cache apa pun.

---

## Pengujian Otomatis (Automated Testing)

Pengujian fitur (*Feature Tests*) dan unit (*Unit Tests*) dijalankan menggunakan **PHPUnit 12**:

```bash
# Eksekusi ringkas pengujian:
php artisan test --compact
```

### Bukti Hasil Pengujian Terkini:
```text
   Tests:    190 passed (976 assertions)
   Status:   100% OK
```
Cakupan baru: iuran, lampiran & foto, unduh PDF, email reset, penguncian login, registrasi (NIK/KK/nama/username unik), lupa & reset password, akses per peran (3 tingkat), CRUD `/api/v1/manage/*`, alur surat 2-tier + notifikasi, buku kas, manajemen pengguna, dan PWA (manifest, service worker, offline).

---

## Keamanan & Perlindungan Data Pribadi (UU PDP)

Sistem mematuhi standar Undang-Undang Perlindungan Data Pribadi (UU PDP No. 27/2022):
1. **Blind Indexing SHA-256:** NIK mentah di-hash secara satu arah untuk indeks pencarian tanpa mengekspos angka NIK pada query log.
2. **Penyamaran Data Publik:** NIK yang ditampilkan di antarmuka publik atau pencarian kesamaan nama selalu disamarkan (contoh: `317103********0001`).
3. **Isolasi Rahasia:** Kredensial database dan API keys tidak di-hardcode ke dalam kode sumber, melainkan diatur melalui *Environment Variables* terenkripsi di server produksi.
4. **Audit Trail Komprehensif:** Setiap tindakan login, modifikasi berkas, persetujuan surat, dan posting kas tercatat permanen di tabel `audit_logs`.

---

## Lisensi
Proyek ini dikembangkan di bawah lisensi [MIT License](LICENSE).  
Hak Cipta &copy; 2026 **Pengurus PRISMA RT 04 / RW 09 Kemayoran** & Pengembang Sistem.
