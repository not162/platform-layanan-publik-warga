# Platform Layanan Publik Warga (Portal Digital RT/RW)

> Sistem Informasi Manajemen Administrasi, Pelayanan Surat Terpadu, Transparansi Kas Keuangan, dan Pengaduan Warga Berbasis Web & Progressive Web App (PWA).

---

## Daftar Isi
1. [Tentang Proyek](#tentang-proyek)
2. [Lingkungan Deployment (Production & Staging)](#lingkungan-deployment-production--staging)
   - [Analisis Teknis: Mengapa Sebelumnya Klik Staging Malah Mendownload File?](#analisis-teknis-mengapa-sebelumnya-klik-staging-malah-mendownload-file)
3. [Tech Stack & Database](#tech-stack--database)
4. [Daftar Akun & Kredensial Pengurus](#daftar-akun--kredensial-pengurus)
5. [Pemodelan Sistem & Rekayasa Perangkat Lunak (5 Unsur Utama)](#pemodelan-sistem--rekayasa-perangkat-lunak-5-unsur-utama)
   - [1. Entity Relationship Diagram (ERD)](#1-entity-relationship-diagram-erd)
   - [2. Logical Record Structure (LRS)](#2-logical-record-structure-lrs)
   - [3. Use Case Diagram & Spesifikasi Use Case](#3-use-case-diagram--spesifikasi-use-case)
   - [4. Activity Diagram](#4-activity-diagram)
   - [5. Unified Modeling Language (UML) Diagrams](#5-unified-modeling-language-uml-diagrams)
     - [5.1. UML Class Diagram](#51-uml-class-diagram)
     - [5.2. UML Sequence Diagram](#52-uml-sequence-diagram)
     - [5.3. UML State Machine Diagram](#53-uml-state-machine-diagram)
   - [6. Flowchart Algoritma Sistem](#6-flowchart-algoritma-sistem)
6. [Fitur Unggulan Antarmuka & Performa Web](#fitur-unggulan-antarmuka--performa-web)
   - [Carousel Warta & Agenda Warga](#1-carousel-warta--agenda-warga)
   - [Navbar 3 Menu Prioritas & Hamburger Drawer](#2-navbar-3-menu-prioritas--hamburger-drawer)
   - [Optimasi Core Web Vitals, LCP & CLS/LFS](#3-optimasi-core-web-vitals-lcp--clslfs)
   - [Metadata SEO & Geolocation (GEO)](#4-metadata-seo--geolocation-geo)
7. [Persiapan Integrasi Firebase Versi Gratis (Spark Plan)](#persiapan-integrasi-firebase-versi-gratis-spark-plan)
8. [Panduan Instalasi & Menjalankan Proyek](#panduan-instalasi--menjalankan-proyek)
9. [Pengujian (Automated Testing)](#pengujian-automated-testing)
10. [Klasifikasi Dokumentasi Teknis (`docs/`)](#klasifikasi-dokumentasi-teknis-docs)

---

## Tentang Proyek

**Platform Layanan Publik Warga** dirancang untuk mendigitalkan birokrasi di tingkat RT/RW secara terstruktur, transparan, dan aman. Platform ini memiliki fitur-fitur unggulan:
- **Verifikasi Warga Instan & Jalur Pendaftaran Mandiri**: Pencocokan NIK berbasis *One-Way Hashing SHA-256* bagi warga sensus terdaftar, serta formulir pengajuan warga baru (*Pending Verification*) dengan proteksi deteksi NIK ganda real-time.
- **Birokrasi Surat Digital 2-Tier**: Verifikasi berkas administratif oleh **Sekretaris**, dilanjutkan dengan persetujuan/tanda tangan digital oleh **Ketua RT**, dengan opsi unduh format resmi **PDF** maupun **Word (.docx)**.
- **Buku Kas Anti-Fraud (*Immutable Ledger*)**: Setiap transaksi kas yang telah dipublikasikan tidak dapat diubah atau dihapus sembarangan, melainkan harus melalui proses pembalik (*reversal ledger*) yang tercatat di audit log.
- **Real-Time Notification**: Terintegrasi dengan **Pusher Channels** dan **Web Push Notifications (PWA)** sehingga warga dan pengurus menerima notifikasi langsung di perangkat masing-masing.
- **Tampilan Ramah Pengguna**: Mengusung *Clean Light Theme Only*, tipografi Google Fonts Inter, ikonografi Lucide, dan interaktivitas Alpine.js dengan umpan balik animasi (efek *shake* kartu dan pesan validasi visual).

---

## Lingkungan Deployment (Production & Staging)

Sistem ini mendukung arsitektur multi-environment yang sinkron secara berkala:

| Parameter | 🚀 Production Environment (Railway) | 🧪 Staging Environment (Vercel) |
|---|---|---|
| **Public URL** | [platform-layanan-publik-warga.up.railway.app](https://platform-layanan-publik-warga.up.railway.app) | [platform-layanan-publik-warga.vercel.app](https://platform-layanan-publik-warga.vercel.app) |
| **Backend Runtime** | PHP 8.4 + Laravel 12 MVC Framework | Python 3.12 + FastAPI Serverless Functions (`api/index.py`) |
| **Database Engine** | MySQL 8.0 Enterprise Relational DB | PostgreSQL 16 (Neon Serverless Cloud Database) |
| **Frontend Rendering** | Blade Engine + Tailwind CSS v4 + Alpine.js | Interactive Staging Dashboard (`public/index.html`) + API Tester |
| **API Documentation** | OpenAPI 3.1 Swagger UI (`/docs/api`) | Interactive Swagger UI (`/api/docs`) |
| **Target Pengguna** | Warga RT & Pengurus Aktif (Operasional Nyata) | Pengujian Fitur, QA, UAT, dan Integrasi Serverless |

### Analisis Teknis: Mengapa Sebelumnya Klik Staging Malah Mendownload File?

Saat pertama kali URL Staging diakses pada platform Vercel, browser pengguna mengalami gejala **otomatis mengunduh berkas (*download binary/stream*)** alih-alih menampilkan halaman web. Berikut analisis teknis dan solusi yang telah diselesaikan:

1. **Akar Permasalahan (*Root Cause*)**:
   - Struktur repositori berbasis **Laravel** memiliki direktori publik standar yaitu `public/`, di mana berkas entry-point utamanya adalah `public/index.php`.
   - Vercel secara default mendeteksi repositori web dan menetapkan direktori keluaran statis pada `outputDirectory: "public"`.
   - Tanpa adanya runtime serverless PHP bawaan pada infrastruktur dasar Vercel, server Vercel memperlakukan berkas `index.php` sebagai **berkas statis non-eksekutabel**.
   - Akibatnya, server web Vercel mengirimkan header respons HTTP:
     ```http
     Content-Type: application/octet-stream (atau application/x-httpd-php)
     Content-Disposition: attachment; filename="index.php"
     ```
     Header ini memaksa browser mengunduh isi mentah script `index.php` ke folder download komputer pengguna.

2. **Solusi Arsitektur yang Diterapkan**:
   - **Penyediaan `public/index.html` Interaktif**: Dibuat berkas antarmuka `public/index.html` bergaya modern (Tailwind CSS, Lucide Icons) dengan fitur *Live API Sandbox Tester*, pintasan Swagger Docs, dan status koneksi Neon PostgreSQL. Dalam aturan prioritas Vercel, berkas `index.html` selalu disajikan di rute root `/` sebagai dokumen web (`Content-Type: text/html; charset=utf-8`), sehingga file `index.php` tidak lagi disajikan sebagai unduhan.
   - **Transisi Backend Staging ke FastAPI Serverless**: Dibuat modul Python 3.12 berbasis FastAPI di `api/index.py` yang menangani semua endpoint API staging (`/api/v1/check-citizen`, `/api/v1/register`, `/api/v1/letters/download`, dll.) dan terhubung langsung ke **PostgreSQL Neon**.
   - **Konfigurasi Routing Vercel (`vercel.json`)**: Ditetapkan rewrite rules yang memisahkan lalu lintas statis (`/` ke `public/index.html`) dan lalu lintas backend (`/api/(.*)` ke `api/index.py`), memastikan pengalaman pengguna mulus tanpa unduhan tidak terduga.

---

## Tech Stack & Database

| Komponen | Teknologi | Keterangan |
|---|---|---|
| **Backend Framework** | **Laravel 11 / 12 / 13** (PHP 8.4) & **FastAPI** (Python 3.12) | Laravel MVC di Production (Railway), FastAPI Serverless di Staging (Vercel) |
| **Database Produksi** | **MySQL / MariaDB via phpMyAdmin** | Relasi antar tabel dengan Foreign Key Constraints & Indexing |
| **Database Staging** | **PostgreSQL 16 (Neon Serverless Cloud)** | Fully Managed Serverless Cloud Postgres dengan Connection Pooling |
| **Database Development/Testing**| **SQLite (In-Memory / File)** | Digunakan untuk eksekusi Feature Testing dan CI/CD cepat |
| **Frontend Styling** | **Tailwind CSS v4** | Desain responsif modern berorientasi Light Mode elegan |
| **Frontend Interactivity** | **Alpine.js v3** | Reactive client-side validation, NIK counter, password toggle, animasi *shake* |
| **Ikonografi & Font** | **Lucide Icons & Google Fonts (Inter)** | Tampilan bersih, profesional, dan mudah dibaca oleh semua usia warga |
| **Real-time Broadcasting** | **Pusher Channels & Laravel Echo** | Siaran instan saat ada warga baru mendaftar atau status surat diperbarui |
| **Mobile & Offline Support** | **Progressive Web App (PWA)** | Service Worker Cache-First, Web App Manifest, Fallback Halaman Offline |
| **Code Formatter & QA** | **Laravel Pint & PHPUnit** | Standardisasi PSR-12 dan 150 Automated Feature Tests (100% Passed) |

### Konfigurasi Koneksi Database (MySQL / phpMyAdmin)
Untuk menghubungkan aplikasi ke MySQL via **phpMyAdmin / XAMPP**:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=layanan_publik_warga
DB_USERNAME=root
DB_PASSWORD=
```

---

## Daftar Akun & Kredensial Pengurus

Sistem telah dilengkapi data awal pengurus RT (**Seeders**) yang siap digunakan untuk login tanpa perlu membuat akun tiruan (*dummy*):

| Role Pengguna | Kategori | Email Login | Password Default | Wewenang & Hak Akses Fitur |
|---|---|---|---|---|
| **SUPERADMIN** | Sistem Administrator | `superadmin@warga.local` | `password` | **Akses Penuh Tanpa Batas**:<br>• Manajemen sistem, permission, role user.<br>• Akses semua modul data warga, surat, keuangan, subfolder laporan, dan audit trail. |
| **KETUA_RT** | Pengurus Inti | `ketua_rt@warga.local` | `password` | **Persetujuan Akhir & Supervisi**:<br>• Pengesahan/Persetujuan akhir Surat Pengantar (`letter.approve`).<br>• Monitoring transparansi kas, tinjau laporan keuangan RT, & tindak lanjut aduan warga.<br>• Supervisi kependudukan lingkungan RT. |
| **SEKRETARIS** | Pengurus Administrasi | `sekretaris@warga.local` | `password` | **Administrasi, Pengumuman & Akses Laporan Kas**:<br>• Verifikasi kelengkapan berkas surat warga (`letter.verify`).<br>• Pengelolaan master data kependudukan RT (`citizen.manage`).<br>• Template surat (`letter.template.manage`) & Agenda Pengumuman (`announcement.manage`).<br>• Akses unduh Laporan Kas RT Bulanan & Rekapitulasi Kas Pembayaran Iuran Warga dari subfolder privat. |
| **BENDAHARA** | Pengurus Keuangan | `bendahara@warga.local` | `password` | **Tata Kelola Keuangan Kas & Backup Store** (`finance.manage`, `finance.report`):<br>• Pencatatan kas masuk (iuran warga) & kas keluar.<br>• Unduh Laporan Kas RT Bulanan (.HTML / .CSV) & Kas Pembayaran Iuran Warga Per Bulan dari subfolder privat `storage/app/private/financial-reports/`.<br>• Jalankan snapshot *Immutable Backup Store* data keuangan terenkripsi dengan verifikasi *Checksum SHA-256*.<br>• Reversal audit transaksi kas jika ada koreksi data. |
| **PETUGAS_KEAMANAN** | Keamanan Lingkungan | `keamanan@warga.local` | `password` | **Pengelolaan Keamanan & Ketertiban** (`security.manage`):<br>• Manajemen & penugasan laporan insiden keamanan (`security_reports`).<br>• Investigasi dan resolusi tiket keamanan warga.<br>• Pengelolaan jadwal ronda lingkungan & kontak darurat. |
| **WARGA** | Pengguna Publik | *(Sesuai registrasi warga)* | *(Ditentukan warga)* | **Layanan Mandiri Warga**:<br>• Mengajukan surat pengantar mandiri (`letter.create`).<br>• Tracking status surat & verifikasi keaslian surat via token.<br>• Unduh dokumen resmi dalam format **PDF** atau **Word (.docx)**.<br>• Mengirimkan aduan lingkungan lengkap dengan foto kamera.<br>• Melihat laporan transparansi kas & pengumuman RT. |

---

## Pemodelan Sistem & Rekayasa Perangkat Lunak (5 Unsur Utama)

Sebagai pemenuhan standar dokumentasi rekayasa perangkat lunak (*Software Engineering Documentation*), sistem ini didokumentasikan secara komprehensif melalui **5 Unsur Pemodelan Sistem**:
1. **Entity Relationship Diagram (ERD)**
2. **Logical Record Structure (LRS)**
3. **Use Case Diagram & Spesifikasi Use Case**
4. **Activity Diagram**
5. **Unified Modeling Language (UML) Diagrams** (Class, Sequence, dan State Machine Diagram)

---

### 1. Entity Relationship Diagram (ERD)

Diagram relasi entitas basis data relasional yang memperlihatkan struktur tabel lengkap, tipe data, kunci primer/asing, serta **kardinalitas relasi secara eksplisit (*One-to-One [1:1]*, *One-to-Many [1:N]*, dan resolusi *Many-to-Many [M:N]*)**.

```mermaid
erDiagram
    %% =======================================================
    %% KARDINALITAS RELASI BASIS DATA
    %% [1:N]  = One-to-Many  (||--o{)
    %% [1:1]  = One-to-One   (||--o|)
    %% [M:N]  = Many-to-Many terurai melalui Junction/Pivot Table
    %% =======================================================

    FAMILY_CARDS ||--o{ CITIZENS : "1:N (1 KK menampung N Warga)"
    CITIZENS ||--o| USERS : "1:1 (1 Warga memiliki 0..1 Akun Login)"
    
    USERS ||--o{ ADMIN_PERMISSIONS : "1:N (Resolusi M:N User ke Hak Akses)"
    USERS ||--o{ PUSH_SUBSCRIPTIONS : "1:N (1 User memiliki N Token Perangkat)"
    USERS ||--o{ LETTERS : "1:N (1 Warga mengajukan N Permohonan Surat)"
    LETTER_TYPES ||--o{ LETTERS : "1:N (1 Format Surat digunakan N Permohonan)"
    LETTERS ||--o{ LETTER_ATTACHMENTS : "1:N (1 Surat memuat N Berkas Lampiran)"
    
    USERS ||--o{ COMPLAINTS : "1:N (1 Warga membuat N Aduan Keluhan)"
    USERS ||--o{ FINANCE_TRANSACTIONS : "1:N (1 Bendahara mencatat N Transaksi Kas)"
    USERS ||--o{ AUDIT_LOGS : "1:N (1 User memicu N Rekaman Jejak Audit)"

    FAMILY_CARDS {
        bigint id PK
        varchar nomor_kk UK "Nomor KK 16 Digit"
        varchar kepala_keluarga_name
        text alamat
        varchar rt
        varchar rw
        varchar kode_pos
        datetime created_at
    }

    CITIZENS {
        bigint id PK
        bigint family_card_id FK "Relasi 1:N dari FAMILY_CARDS"
        bigint user_id FK "Relasi 1:1 ke USERS (Unique)"
        varchar nik_hash UK "SHA-256 Blind Index"
        varchar full_name
        varchar birth_place
        date birth_date
        varchar gender
        text address
        varchar phone_number
        varchar occupation
        varchar religion
        varchar status_warga "verified / pending_verification"
        varchar ktp_file_path "Bukti Identitas Pendaftar Baru"
        int version "Optimistic Locking"
        datetime created_at
    }

    USERS {
        bigint id PK
        bigint warga_id FK "Relasi 1:1 ke CITIZENS (Unique)"
        varchar name
        varchar email UK "Email Login Unik"
        varchar password "Bcrypt Hash"
        enum role "SUPERADMIN,ADMIN,KETUA_RT,BENDAHARA,SEKRETARIS,WARGA"
        boolean is_active
        datetime last_login_at
        datetime created_at
    }

    ADMIN_PERMISSIONS {
        bigint id PK
        bigint user_id FK "Relasi 1:N dari USERS (Pivot M:N Akses)"
        varchar permission "Nama Otorisasi Spesifik"
        datetime created_at
    }

    LETTER_TYPES {
        bigint id PK
        varchar code UK "Kode Surat Unik (SKTM, SKU, dll)"
        varchar name
        text description
        json required_documents "Daftar Syarat Berkas"
        boolean is_active
        datetime created_at
    }

    LETTERS {
        bigint id PK
        varchar ticket_number UK "No. Tiket Pelacakan Unik"
        varchar tracking_token UK "Token Akses Publik"
        bigint user_id FK "Relasi 1:N Pemohon (USERS)"
        bigint citizen_id FK "Relasi 1:N Profil Pemohon (CITIZENS)"
        bigint letter_type_id FK "Relasi 1:N Jenis Surat (LETTER_TYPES)"
        text purpose
        enum status "draft,submitted,verified,approved,rejected"
        text remarks
        text rejection_reason
        bigint verified_by FK "Relasi Petugas Verifikator (USERS)"
        bigint approved_by FK "Relasi Pejabat Penyetuju (USERS)"
        datetime verified_at
        datetime approved_at
        int version "Optimistic Locking"
        datetime created_at
    }

    LETTER_ATTACHMENTS {
        bigint id PK
        bigint letter_id FK "Relasi 1:N dari Surat Induk (LETTERS)"
        varchar file_path
        varchar file_name
        varchar file_type
        int file_size
        datetime created_at
    }

    FINANCE_TRANSACTIONS {
        bigint id PK
        varchar transaction_number UK "Nomor Bukti Transaksi Unik"
        enum type "income,expense"
        varchar category
        decimal amount "Presisi Keuangan 15,2"
        text description
        date transaction_date
        enum status "draft,published,reversed"
        text reversal_reason
        bigint recorded_by FK "Relasi 1:N Pencatat (USERS)"
        int version "Optimistic Locking"
        datetime created_at
    }

    COMPLAINTS {
        bigint id PK
        varchar ticket_number UK "Nomor Aduan Unik"
        varchar tracking_token UK "Token Pelacakan Publik"
        bigint user_id FK "Relasi 1:N Pelapor (USERS)"
        bigint citizen_id FK "Relasi 1:N Data Warga (CITIZENS)"
        varchar category
        varchar title
        text description
        boolean is_anonymous
        enum status "submitted,in_review,in_progress,resolved,rejected"
        text response
        bigint handled_by FK "Relasi Pengurus Penangan (USERS)"
        int version "Optimistic Locking"
        datetime created_at
    }

    ANNOUNCEMENTS {
        bigint id PK
        varchar title
        varchar category
        text content
        boolean is_pinned
        boolean is_published
        datetime published_at
        int version
        datetime created_at
    }

    COMMUNITY_EVENTS {
        bigint id PK
        varchar title
        text description
        varchar location
        date event_date
        time start_time
        time end_time
        boolean is_published
        datetime created_at
    }

    AUDIT_LOGS {
        bigint id PK
        bigint user_id FK "Relasi 1:N Pelaku Aksi (USERS)"
        varchar action
        varchar entity_type
        bigint entity_id
        json old_values "Snapshot Sebelum Update"
        json new_values "Snapshot Setelah Update"
        datetime created_at
    }

    PUSH_SUBSCRIPTIONS {
        bigint id PK
        bigint user_id FK "Relasi 1:N Perangkat Pemilik (USERS)"
        text endpoint
        varchar public_key
        varchar auth_token
        datetime created_at
    }
```

#### Matriks Klasifikasi Kardinalitas Relasi (ERD)

| Entitas Asal (Parent) | Entitas Tujuan (Child) | Kardinalitas | Kunci Relasi (PK ➔ FK) | Penjelasan Logika Bisnis & Mekanisme Relasi |
| :--- | :--- | :---: | :--- | :--- |
| **`FAMILY_CARDS`** | **`CITIZENS`** | **`1 : N`** (One-to-Many) | `FAMILY_CARDS.id` ➔ `CITIZENS.family_card_id` | **Satu** Kartu Keluarga menaungi **banyak** anggota keluarga warga RT. Satu warga wajib terdaftar pada tepat satu KK. |
| **`CITIZENS`** | **`USERS`** | **`1 : 1`** (One-to-One) | `CITIZENS.user_id` ➔ `USERS.id`<br>`USERS.warga_id` ➔ `CITIZENS.id` | **Satu** data kependudukan warga memiliki tepat **satu** akun login portal (relasi bi-directional dengan batas `UNIQUE` constraint). |
| **`USERS`** | **`ADMIN_PERMISSIONS`** | **`1 : N`** *(Pecahan M:N)* | `USERS.id` ➔ `ADMIN_PERMISSIONS.user_id` | **Satu** akun pengguna dapat memiliki **banyak** hak akses granular (*role-permission assignment*). |
| **`LETTER_TYPES`** | **`LETTERS`** | **`1 : N`** (One-to-Many) | `LETTER_TYPES.id` ➔ `LETTERS.letter_type_id` | **Satu** jenis formulir surat (SKTM, SKU, dll.) digunakan sebagai template permohonan oleh **banyak** surat warga. |
| **`USERS`** | **`LETTERS`** | **`1 : N`** (One-to-Many) | `USERS.id` ➔ `LETTERS.user_id` | **Satu** akun warga dapat mengajukan **banyak** surat pengantar sepanjang waktu. |
| **`LETTERS`** | **`LETTER_ATTACHMENTS`**| **`1 : N`** (One-to-Many) | `LETTERS.id` ➔ `LETTER_ATTACHMENTS.letter_id` | **Satu** berkas surat permohonan dapat memiliki **banyak** lampiran dokumen pendukung (KTP, KK, Bukti Bayar, dll.). |
| **`USERS`** | **`COMPLAINTS`** | **`1 : N`** (One-to-Many) | `USERS.id` ➔ `COMPLAINTS.user_id` | **Satu** warga dapat menyampaikan **banyak** laporan keluhan aspirasi lingkungan RT. |
| **`USERS`** | **`FINANCE_TRANSACTIONS`** | **`1 : N`** (One-to-Many) | `USERS.id` ➔ `FINANCE_TRANSACTIONS.recorded_by` | **Satu** pengurus kas (Bendahara) dapat mencatat dan mengelola **banyak** mutasi kas masuk/keluar. |
| **`USERS`** | **`AUDIT_LOGS`** | **`1 : N`** (One-to-Many) | `USERS.id` ➔ `AUDIT_LOGS.user_id` | **Satu** pengguna dapat memicu **banyak** baris pencatatan jejak audit audit trail sistem. |
| **`USERS`** | **`PUSH_SUBSCRIPTIONS`**| **`1 : N`** (One-to-Many) | `USERS.id` ➔ `PUSH_SUBSCRIPTIONS.user_id` | **Satu** pengguna dapat mendaftarkan **banyak** token browser/perangkat mobile untuk WebPush Notification. |

---

### 2. Logical Record Structure (LRS)

Representasi relasional tabel skema database dengan relasi kunci utama (*Primary Key* / `PK`), kunci tamu (*Foreign Key* / `FK`), serta penanda kardinalitas relasi **`[1:1]`**, **`[1:N]`**, dan **`[M:N (Tabel Pivot/Perantara)]`**:

```
┌────────────────────────────────────────────────────────┐
│                      FAMILY_CARDS                      │
├────────────────────────────────────────────────────────┤
│ PK   id                                                │
│      nomor_kk (Unique, 16 Digit)                       │
│      kepala_keluarga_name, alamat, rt, rw, kode_pos    │
└───────────────────────────┬────────────────────────────┘
                            │
                            │ [1:N] (One-to-Many)
                            ▼
┌────────────────────────────────────────────────────────┐                [1:1] (One-to-One)               ┌────────────────────────────────────────────────────────┐
│                        CITIZENS                        ├────────────────────────────────────────────────►│                         USERS                          │
├────────────────────────────────────────────────────────┤                                                 ├────────────────────────────────────────────────────────┤
│ PK   id                                                │                                                 │ PK   id                                                │
│ FK   family_card_id ───────────────────────────────────┼── (Relasi 1:N dari FAMILY_CARDS)                │ FK   warga_id (Unique) ────────────────────────────────┤ (Relasi 1:1 ke CITIZENS)
│ FK   user_id (Unique) ─────────────────────────────────┼── (Relasi 1:1 ke USERS)                         │      name, email (Unique), password (Bcrypt)           │
│      nik_hash (Unique, SHA-256), full_name             │                                                 │      role (SUPERADMIN, KETUA_RT, BENDAHARA, ..), active│
│      birth_place, birth_date, gender, address, phone   │                                                 └────────┬──────────────┬──────────────┬──────────────┬───┘
│      occupation, religion, status_warga, ktp_file_path │                                                          │ [1:N]        │ [1:N]        │ [1:N]        │ [1:N]
│      version (Optimistic Locking)                      │                                                          ▼              ▼              ▼              │
└───────────────────────────┬────────────────────────────┘                                                 ┌────────────────┐┌────────────┐┌─────────────┐       │
                            │ [1:N]                                                                        │ADMIN_PERMISSION││ AUDIT_LOGS ││PUSH_SUBSCRIP│       │
                            ▼                                                                              ├────────────────┤├────────────┤├─────────────┤       │
┌────────────────────────────────────────────────────────┐                                                 │PK  id          ││PK  id      ││PK  id       │       │
│                        LETTERS                         │                                                 │FK  user_id     ││FK  user_id ││FK  user_id  │       │
├────────────────────────────────────────────────────────┤                                                 │    permission  ││    action  ││    endpoint │       │
│ PK   id                                                │                                                 └────────────────┘└────────────┘└─────────────┘       │
│      ticket_number (Unique), tracking_token (Unique)   │                                                  [M:N Pivot Table]                                    │
│ FK   user_id ──────────────────────────────────────────┼── (Relasi 1:N dari USERS Pemohon)                                                                     │
│ FK   citizen_id ───────────────────────────────────────┼── (Relasi 1:N dari Profil CITIZENS)              ┌──────────────────────────────────────────────┐      │
│ FK   letter_type_id ◄──────────┐                       │                                                 │             FINANCE_TRANSACTIONS             │◄─────┘
│      purpose, status (submitted..approved), version    │                                                 ├──────────────────────────────────────────────┤
│ FK   verified_by (Users.id)    │ [1:N]                 │                                                 │ PK   id                                      │
│ FK   approved_by (Users.id)    │                       │                                                 │      transaction_number (Unique)             │
│      output: PDF / Word .docx  │                       │                                                 │      type (income, expense), category, amount│
└───────────────────────────┬────┴───────────────────────┘                                                 │      description, date, status, reversal     │
                            │                                                                              │ FK   recorded_by (Users.id) ─────────────────┼── (Relasi 1:N Pencatat)
                            │ [1:N] (One-to-Many)                                                          │      version (Optimistic Lock)               │
                            ▼                                                                              └──────────────────────────────────────────────┘
┌────────────────────────────────────────────────────────┐  ┌───────────────────────────────────────────┐
│                   LETTER_ATTACHMENTS                   │  │               LETTER_TYPES                │  ┌──────────────────────────────────────────────┐
├────────────────────────────────────────────────────────┤  ├───────────────────────────────────────────┤  │                  COMPLAINTS                  │
│ PK   id                                                │  │ PK   id                                   │  ├──────────────────────────────────────────────┤
│ FK   letter_id ────────────────────────────────────────┤  │      code (Unique, SKTM/SKU), name, desc  │  │ PK   id                                      │
│      file_path, file_name, file_type, file_size        │  │      required_documents (JSON), is_active │  │      ticket_number (Unique), tracking_token  │
└────────────────────────────────────────────────────────┘  └─────────────────────┬─────────────────────┘  │ FK   user_id (Users.id) ─────────────────────┼── (Relasi 1:N Pembuat Aduan)
                                                                                  │ [1:N]                  │ FK   citizen_id (Citizens.id)                │
                                                                                  └────────────────────────┤      category, title, description, status    │
                                                                                                           │ FK   handled_by (Users.id)                   │
                                                                                                           └──────────────────────────────────────────────┘
```

#### Notasi Relasional LRS (Relational Mapping Notation)
Menunjukkan struktur relasi antar tabel secara formal (`PK = Primary Key Garis Bawah`, `FK = Foreign Key Prefix #`):

1. **`FAMILY_CARDS`** (<u>id</u>, *nomor_kk*, kepala_keluarga_name, alamat, rt, rw, kode_pos, created_at)
2. **`CITIZENS`** (<u>id</u>, **#family_card_id** *(1:N)*, **#user_id** *(1:1 Unique)*, *nik_hash*, full_name, birth_place, birth_date, gender, address, phone_number, occupation, religion, status_warga, ktp_file_path, version, created_at)
3. **`USERS`** (<u>id</u>, **#warga_id** *(1:1 Unique)*, name, *email*, password, role, is_active, last_login_at, created_at)
4. **`ADMIN_PERMISSIONS`** (<u>id</u>, **#user_id** *(1:N / Resolusi M:N)*, permission, created_at)
5. **`LETTER_TYPES`** (<u>id</u>, *code*, name, description, required_documents, is_active, created_at)
6. **`LETTERS`** (<u>id</u>, *ticket_number*, *tracking_token*, **#user_id** *(1:N)*, **#citizen_id** *(1:N)*, **#letter_type_id** *(1:N)*, purpose, status, remarks, rejection_reason, **#verified_by** *(1:N)*, **#approved_by** *(1:N)*, verified_at, approved_at, version, created_at)
7. **`LETTER_ATTACHMENTS`** (<u>id</u>, **#letter_id** *(1:N)*, file_path, file_name, file_type, file_size, created_at)
8. **`COMPLAINTS`** (<u>id</u>, *ticket_number*, *tracking_token*, **#user_id** *(1:N)*, **#citizen_id** *(1:N)*, category, title, description, is_anonymous, status, response, **#handled_by** *(1:N)*, version, created_at)
9. **`FINANCE_TRANSACTIONS`** (<u>id</u>, *transaction_number*, type, category, amount, description, transaction_date, status, reversal_reason, **#recorded_by** *(1:N)*, version, created_at)
10. **`AUDIT_LOGS`** (<u>id</u>, **#user_id** *(1:N)*, action, entity_type, entity_id, old_values, new_values, created_at)
11. **`PUSH_SUBSCRIPTIONS`** (<u>id</u>, **#user_id** *(1:N)*, endpoint, public_key, auth_token, created_at)
12. **`ANNOUNCEMENTS`** (<u>id</u>, title, category, content, is_pinned, is_published, published_at, version, created_at)
13. **`COMMUNITY_EVENTS`** (<u>id</u>, title, description, location, event_date, start_time, end_time, is_published, created_at)

---

### 3. Use Case Diagram & Spesifikasi Use Case

Diagram use case memetakan interaksi seluruh aktor (Warga Terdaftar, Pemohon Warga Baru, Sekretaris, Bendahara, Ketua RT, dan Superadmin) dengan fitur sistem.

```mermaid
flowchart LR
    Warga["Warga Terdaftar"]
    PemohonBaru["Pemohon Warga Baru"]
    Sekretaris["Sekretaris RT"]
    Bendahara["Bendahara RT"]
    KetuaRT["Ketua RT"]
    Superadmin["Superadmin"]

    subgraph Portal Layanan Publik Warga
        subgraph Modul Autentikasi & Kependudukan
            UC1["Pengecekan NIK Real-time"]
            UC2["Daftar Akun Warga Terdata (Instan)"]
            UC3["Pengajuan Warga Baru (Upload KTP)"]
            UC4["Verifikasi Data Kependudukan Baru"]
        end

        subgraph Modul Pelayanan Persuratan 2-Tier
            UC5["Pengajuan Surat Pengantar Mandiri"]
            UC6["Lacak Status Surat via Token"]
            UC7["Verifikasi Berkas Surat (Tahap 1)"]
            UC8["Persetujuan & Tanda Tangan (Tahap 2)"]
            UC9["Unduh Berkas Surat (PDF / Word DOCX)"]
        end

        subgraph Modul Transparansi Keuangan RT
            UC10["Kelola & Catat Transaksi Kas Masuk/Keluar"]
            UC11["Reversal Ledger Anti-Fraud"]
            UC12["Pantau Transparansi Kas Publik"]
        end

        subgraph Modul Aspirasi & Fasilitas
            UC13["Kirim Laporan Pengaduan & Foto Kamera"]
            UC14["Tindak Lanjut & Resolusi Aduan"]
            UC15["Lihat Warta & Agenda RT"]
        end

        subgraph Modul Tata Kelola Sistem
            UC16["Manajemen Pengguna & Hak Akses (RBAC)"]
            UC17["Audit Trail & Log Aktivitas"]
        end
    end

    %% Relasi Aktor
    PemohonBaru --> UC1
    PemohonBaru --> UC3

    Warga --> UC1
    Warga --> UC2
    Warga --> UC5
    Warga --> UC6
    Warga --> UC9
    Warga --> UC12
    Warga --> UC13
    Warga --> UC15

    Sekretaris --> UC4
    Sekretaris --> UC7
    Sekretaris --> UC15

    KetuaRT --> UC4
    KetuaRT --> UC8
    KetuaRT --> UC12
    KetuaRT --> UC14

    Bendahara --> UC10
    Bendahara --> UC11
    Bendahara --> UC12

    Superadmin --> UC16
    Superadmin --> UC17
    Superadmin -.-> UC4
    Superadmin -.-> UC8
    Superadmin -.-> UC10
```

#### Spesifikasi Use Case (Use Case Specifications)

| ID Use Case | Nama Use Case | Aktor Utama | Kondisi Awal (*Pre-condition*) | Alur Utama (*Main Scenario*) | Kondisi Akhir (*Post-condition*) |
|---|---|---|---|---|---|
| **UC-01** | Pengecekan NIK Real-time | Warga / Pemohon | Pengguna berada di halaman registrasi portal | 1. Input 16 digit NIK.<br>2. Sistem kueri database via hashing SHA-256.<br>3. Menampilkan status: terdaftar, sudah punya akun, atau belum ada di sensus RT. | UI memberikan umpan balik instan; tombol "Ajukan Warga Baru" muncul hanya jika NIK belum terdaftar. |
| **UC-02** | Pengajuan Warga Baru | Pemohon Baru | NIK belum tercatat di data sensus RT | 1. Pemohon isi nama, NIK, alamat, telepon, dan unggah foto KTP.<br>2. Sistem simpan data dengan status `pending_verification`. | Notifikasi terkirim ke Pengurus; akun belum dapat mengakses surat sampai disetujui. |
| **UC-03** | Pengajuan Surat Pengantar | Warga Terdaftar | Warga telah login dan berstatus `verified` | 1. Pilih jenis surat (SKTM, SKU, dll).<br>2. Lengkapi keperluan & unggah berkas pendukung.<br>3. Klik kirim permohonan. | Diterbitkan nomor tiket unik dan status surat menjadi `submitted`. |
| **UC-04** | Verifikasi Berkas (Tier 1) | Sekretaris RT | Surat berstatus `submitted` | 1. Sekretaris buka menu verifikasi.<br>2. Evaluasi kesesuaian berkas lampiran.<br>3. Klik verifikasi berkas (atau tolak dengan alasan). | Status berubah menjadi `verified` dan masuk ke antrian Ketua RT. |
| **UC-05** | Pengesahan Surat (Tier 2) | Ketua RT | Surat berstatus `verified` | 1. Ketua RT tinjau draf surat.<br>2. Berikan persetujuan akhir & bubuhkan stempel digital.<br>3. Sistem generate token verifikasi resmi. | Status surat menjadi `approved`; QR Code & berkas final siap diunduh warga. |
| **UC-06** | Unduh Surat (PDF / DOCX) | Warga Terdaftar | Surat berstatus `approved` | 1. Warga klik tombol unduh pada dashboard atau riwayat.<br>2. Pilih format dokumen: **Unduh PDF** atau **Unduh Word (.docx)**.<br>3. Sistem stream binary file langsung ke perangkat. | Dokumen resmi terunduh dengan format yang dipilih warga; jika surat belum disetujui, muncul modal edukatif. |
| **UC-07** | Reversal Transaksi Kas | Bendahara RT | Transaksi kas berstatus `published` memiliki koreksi | 1. Bendahara pilih transaksi yang keliru.<br>2. Masukkan alasan pembatalan resmi.<br>3. Sistem generate transaksi pembalik bertanda lawan otomatis. | Transaksi asal menjadi `reversed`; saldo terkoreksi tanpa manipulasi history; audit log tercatat. |

---

### 4. Activity Diagram

#### A. Alur Validasi NIK & Pendaftaran Warga (Dual-Flow: Sensus vs Warga Baru)
```mermaid
flowchart TD
    Start(["Mulai: Warga Mengakses Portal"]) --> A1["Buka Menu Registrasi"]
    A1 --> A2["Ketik 16 Digit NIK"]
    A2 --> A3["Client-side Input Check (Alpine.js / Debounce 400ms)"]
    A3 --> A4[("API: GET /api/v1/check-citizen?nik=...")]
    
    A4 --> DecisionNIK{"Hasil Pemeriksaan NIK di Database"}
    
    DecisionNIK -- "NIK Sudah Terdaftar & Sudah Punya Akun" --> E1["Tampilkan Alert Merah: Akun Sudah Terdaftar"]
    E1 --> E1A["Sembunyikan Tombol Pengajuan Warga Baru"]
    E1A --> E1B["Arahkan ke Halaman Login"]
    E1B --> EndGagal(["Selesai"])

    DecisionNIK -- "NIK Terdaftar di Sensus RT & Belum Punya Akun" --> B1["Tampilkan Badge Hijau: Terdata di RT"]
    B1 --> B2["Sembunyikan Tombol Pengajuan Warga Baru"]
    B2 --> B3["Warga Lengkapi Email & Password"]
    B3 --> B4["POST /api/v1/register (Jalur Cepat)"]
    B4 --> B5[("Simpan Akun USERS & Link CITIZENS (status: verified)")]
    B5 --> B6["Broadcast Pusher: CitizenRegistered"]
    B6 --> EndSukses1(["Selesai: Langsung Masuk Dashboard"])

    DecisionNIK -- "NIK Belum Terdata di Sensus RT" --> C1["Tampilkan Banner Kuning: NIK Belum Terdaftar"]
    C1 --> C2["Tampilkan Tombol: 'Ajukan Sebagai Warga Baru'"]
    C2 --> C3["Warga Klik Tombol & Buka Modal Pengajuan"]
    C3 --> C4["Isi Data Lengkap + Unggah Foto KTP"]
    C4 --> C5["POST /api/v1/register/applicant"]
    C5 --> C6[("Simpan Data Warga (status: pending_verification)")]
    C6 --> C7["Notifikasi Dikirim ke Pengurus RT"]
    C7 --> C8["Pengurus Verifikasi Fisik & Dokumen KTP"]
    C8 --> DecisionApprove{"Pengurus Menyetujui?"}
    
    DecisionApprove -- "Ya" --> D1[("Ubah Status: verified / Role: WARGA")]
    D1 --> EndSukses2(["Selesai: Akun Aktif & Siap Digunakan"])
    
    DecisionApprove -- "Tidak" --> D2[("Hapus / Tolak Permohonan")]
    D2 --> EndTolak(["Selesai: Akses Ditolak"])
```

#### B. Alur Pengajuan, Verifikasi 2-Tier, dan Unduh Surat (Format PDF / DOCX)
```mermaid
flowchart TD
    Start(["Mulai: Warga Butuh Surat Pengantar"]) --> A1["Warga Login ke Portal (Status Verified)"]
    A1 --> A2["Pilih Jenis Surat (SKTM, SKU, Domisili, dll)"]
    A2 --> A3["Upload Berkas Persyaratan (KTP, KK, Bukti PBB)"]
    A3 --> A4["Klik Kirim Permohonan"]
    A4 --> A5[("Sistem Simpan Status: SUBMITTED (Tier 0)")]
    A5 --> A6["Generate Nomor Tiket & Token Pelacakan"]
    A6 --> A7["Notifikasi Real-time ke Dashboard Sekretaris"]
    
    A7 --> B1["Sekretaris RT Buka Menu Verifikasi (Tier 1)"]
    B1 --> B2{"Berkas Persyaratan Lengkap & Valid?"}
    
    B2 -- "Tidak Lengkap" --> B3["Sekretaris Input Alasan Penolakan"]
    B3 --> B4[("Status Surat: REJECTED")]
    B4 --> B5["Notifikasi Alasan Penolakan ke Warga"]
    B5 --> EndTolakSurat(["Selesai: Surat Ditolak"])
    
    B2 -- "Lengkap & Sah" --> B6["Sekretaris Klik 'Verifikasi Berkas'"]
    B6 --> B7[("Status Surat: VERIFIED (Tier 1 Passed)")]
    B7 --> B8["Notifikasi Otomatis ke Akun Ketua RT"]
    
    B8 --> C1["Ketua RT Buka Menu Persetujuan (Tier 2)"]
    C1 --> C2{"Ketua RT Menyetujui Pengajuan?"}
    
    C2 -- "Ditolak" --> C3["Ketua RT Masukkan Alasan Penolakan"]
    C3 --> B4
    
    C2 -- "Disetujui" --> C4["Ketua RT Berikan Pengesahan & TTD Digital"]
    C4 --> C5[("Status Surat: APPROVED (Final)")]
    C5 --> C6["Generate Token Keaslian Surat & QR Code"]
    C6 --> C7["Warga Memilih Format Unduhan"]
    
    C7 --> DecisionFormat{"Pilihan Format Warga"}
    DecisionFormat -- "Klik Unduh PDF" --> D1["API Mengalirkan File PDF Resmi (Header Content-Disposition)"]
    DecisionFormat -- "Klik Unduh Word" --> D2["API Mengalirkan File Word .docx Lengkap Template RT"]
    
    D1 --> EndDownload(["Selesai: Berkas Tersimpan di Perangkat Warga"])
    D2 --> EndDownload
```

#### C. Alur Tata Kelola Kas RT & Reversal Ledger Anti-Fraud
```mermaid
flowchart TD
    Start(["Mulai: Transaksi Kas RT"]) --> K1["Bendahara Input Pemasukan / Pengeluaran"]
    K1 --> K2[("Sistem Simpan Status: DRAFT")]
    K2 --> K3{"Perlu Publikasi Transparansi?"}
    
    K3 -- "Belum" --> K4["Bisa Diedit / Dihapus oleh Bendahara"]
    K4 --> EndDraft(["Tetap sebagai Draft"])
    
    K3 -- "Ya, Publikasikan" --> K5["Bendahara Klik Publikasikan Laporan"]
    K5 --> K6[("Status Diperbarui: PUBLISHED")]
    K6 --> K7["Cache Laporan Publik Dibersihkan Otomatis"]
    K7 --> K8["Laporan Tampil Real-time di Portal Warga"]
    
    K8 --> K9{"Ditemukan Kesalahan Angka di Kemudian Hari?"}
    K9 -- "Tidak Ada" --> EndPublikasi(["Selesai: Kas Transparan Selesai"])
    
    K9 -- "Ada Kesalahan Input" --> K10["Sistem Tolak Hard Delete / Update Langsung"]
    K10 --> K11["Bendahara Ajukan Transaksi Reversal Pembalik"]
    K11 --> K12["Wajib Mengisi Alasan Pembatalan & Optimistic Lock Check"]
    K12 --> K13[("Transaksi Asal Ditandai: REVERSED")]
    K13 --> K14[("Sistem Buat Transaksi Pembalik Baru Secara Otomatis")]
    K14 --> K15[("Catat Riwayat Lengkap di Tabel Audit Logs")]
    K15 --> EndReversal(["Selesai: Rekonsiliasi Kas Bersih Sesuai Standar Akuntansi"])
```

---

### 5. Unified Modeling Language (UML) Diagrams

#### 5.1. UML Class Diagram
Diagram kelas memetakan domain model utama, tipe data atribut, visibilitas method (`+` public, `-` private, `#` protected), dan relasi asosiasi/komposisi:

```mermaid
classDiagram
    direction TB

    class User {
        +bigint id
        +bigint warga_id
        +string name
        +string email
        +string password
        +string role
        +boolean is_active
        +datetime last_login_at
        +login(credentials) bool
        +logout() void
        +hasPermission(permission) bool
        +citizen() Citizen
    }

    class Citizen {
        +bigint id
        +bigint family_card_id
        +bigint user_id
        +string nik_hash
        +string full_name
        +string birth_place
        +date birth_date
        +string gender
        +string phone_number
        +string status_warga
        +string ktp_file_path
        +int version
        +checkNikHash(hash) bool
        +linkAccount(userId) void
        +isVerified() bool
    }

    class FamilyCard {
        +bigint id
        +string nomor_kk
        +string kepala_keluarga_name
        +string alamat
        +string rt
        +string rw
        +getMembers() Collection
    }

    class Letter {
        +bigint id
        +string ticket_number
        +string tracking_token
        +bigint user_id
        +bigint citizen_id
        +bigint letter_type_id
        +string purpose
        +string status
        +string remarks
        +string rejection_reason
        +bigint verified_by
        +bigint approved_by
        +datetime verified_at
        +datetime approved_at
        +int version
        +submit() void
        +verify(sekretarisId) bool
        +approve(ketuaRtId) bool
        +reject(reason, userId) bool
        +downloadPdf() BinaryResponse
        +downloadDocx() BinaryResponse
    }

    class LetterType {
        +bigint id
        +string code
        +string name
        +text description
        +json required_documents
        +boolean is_active
        +letters() Collection
    }

    class LetterAttachment {
        +bigint id
        +bigint letter_id
        +string file_path
        +string file_name
        +string file_type
        +int file_size
        +getStorageUrl() string
    }

    class FinanceTransaction {
        +bigint id
        +string transaction_number
        +string type
        +string category
        +decimal amount
        +string description
        +date transaction_date
        +string status
        +string reversal_reason
        +bigint recorded_by
        +int version
        +publish() bool
        +reverse(reason, userId) FinanceTransaction
    }

    class Complaint {
        +bigint id
        +string ticket_number
        +string tracking_token
        +bigint user_id
        +bigint citizen_id
        +string category
        +string title
        +text description
        +boolean is_anonymous
        +string status
        +text response
        +bigint handled_by
        +resolve(response, userId) bool
    }

    FamilyCard "1" *-- "0..*" Citizen : menaungi
    Citizen "1" <--> "0..1" User : terhubung (1:1)
    User "1" --> "0..*" Letter : mengajukan
    Citizen "1" --> "0..*" Letter : subjek pemohon
    LetterType "1" --> "0..*" Letter : mengkategorikan
    Letter "1" *-- "0..*" LetterAttachment : memiliki berkas
    User "1" --> "0..*" FinanceTransaction : mencatat
    User "1" --> "0..*" Complaint : melapor
    Citizen "1" --> "0..*" Complaint : profil pelapor
```

#### 5.2. UML Sequence Diagram

##### A. Pengecekan NIK Real-time & Pendaftaran Warga Baru
```mermaid
sequenceDiagram
    autonumber
    actor W as Warga / Pemohon
    participant FE as Frontend Client (Alpine.js)
    participant API as API Controller (/api/v1)
    participant Model as Citizen Model
    participant DB as Relational Database
    participant Push as Pusher Broadcasting
    participant Adm as Dashboard Pengurus RT

    W->>FE: Input NIK 16 Digit
    FE->>API: GET /api/v1/check-citizen?nik=...
    API->>API: Hash SHA-256 (NIK)
    API->>Model: where('nik_hash', hash)
    Model->>DB: SELECT * FROM citizens WHERE nik_hash = ?
    DB-->>Model: Return Data Citizen / Null
    
    alt NIK Tidak Ditemukan di Sensus RT
        Model-->>API: null
        API-->>FE: HTTP 200 { exists: false, is_linked: false }
        FE-->>W: Tampilkan Banner Info + Tombol "Ajukan Warga Baru"
        W->>FE: Klik "Ajukan Warga Baru" & Unggah KTP
        FE->>API: POST /api/v1/register/applicant
        API->>DB: INSERT citizens (status='pending_verification', ktp_file_path=...)
        API->>DB: INSERT users (role='WARGA', is_active=false)
        API->>Push: Broadcast: NewCitizenApplicant
        Push-->>Adm: Alert: "Ada Permohonan Warga Baru Perlu Verifikasi"
        API-->>FE: HTTP 201: "Permohonan terkirim, menunggu verifikasi pengurus"
    else NIK Ditemukan & Sudah Memiliki Akun
        Model-->>API: citizen (user_id != null)
        API-->>FE: HTTP 200 { exists: true, is_linked: true }
        FE-->>W: Tampilkan Alert Merah: "NIK ini sudah aktif. Silakan Login."
        FE->>FE: Sembunyikan Tombol "Ajukan Warga Baru"
    else NIK Ditemukan di Sensus RT & Belum Punya Akun
        Model-->>API: citizen (user_id == null)
        API-->>FE: HTTP 200 { exists: true, is_linked: false, full_name: '...' }
        FE-->>W: Tampilkan Konfirmasi: "Data Anda Terverifikasi di RT"
        FE->>FE: Sembunyikan Tombol "Ajukan Warga Baru"
        W->>FE: Lengkapi Password & Submit
        FE->>API: POST /api/v1/register
        API->>DB: INSERT users & UPDATE citizens SET user_id = ?
        API-->>FE: HTTP 201: Auth Token + Redirect ke Dashboard
    end
```

##### B. Siklus Pengajuan Surat 2-Tier hingga Unduh Dokumen (PDF / DOCX)
```mermaid
sequenceDiagram
    autonumber
    actor W as Warga
    participant FE as Frontend Portal
    participant LC as LetterController
    participant LS as LetterService
    participant DB as Relational Database
    actor S as Sekretaris RT (Tier 1)
    actor RT as Ketua RT (Tier 2)

    W->>FE: Isi Form Surat & Unggah Lampiran
    FE->>LC: POST /api/v1/letters
    LC->>LS: create(payload, user_id)
    LS->>DB: INSERT INTO letters (status='submitted', ticket_number)
    DB-->>LC: Letter Record Created
    LC-->>FE: HTTP 201 Created (Tiket Diterbitkan)
    FE-->>W: Notifikasi Permohonan Terkirim

    Note over S,LC: Tier 1: Verifikasi Administratif oleh Sekretaris
    S->>FE: Review Berkas Lampiran
    FE->>LC: PATCH /api/v1/admin/letters/{id}/verify
    LC->>LS: verify(letter, sekretaris_id)
    LS->>DB: UPDATE letters SET status='verified', verified_by=?, verified_at=NOW()
    DB-->>LC: Verified Success
    LC-->>FE: Status Berubah menjadi 'VERIFIED'

    Note over RT,LC: Tier 2: Pengesahan & TTD oleh Ketua RT
    RT->>FE: Tinjau Berkas Terverifikasi
    FE->>LC: PATCH /api/v1/admin/letters/{id}/approve
    LC->>LS: approve(letter, ketua_rt_id)
    LS->>DB: UPDATE letters SET status='approved', approved_by=?, approved_at=NOW()
    DB-->>LC: Approved Success
    LC-->>FE: Status Berubah menjadi 'APPROVED'

    Note over W,LC: Pengunduhan Berkas Resmi oleh Warga
    alt Warga Klik Unduh Format PDF
        W->>FE: Klik "Unduh PDF"
        FE->>LC: GET /api/v1/letters/{id}/download?format=pdf
        LC->>LS: generatePdf(letter)
        LS-->>LC: Binary Stream (application/pdf)
        LC-->>FE: HTTP 200 (File Stream: Surat_Pengantar.pdf)
        FE-->>W: Berkas PDF Langsung Terunduh
    else Warga Klik Unduh Format Word (.docx)
        W->>FE: Klik "Unduh Word (.docx)"
        FE->>LC: GET /api/v1/letters/{id}/download?format=docx
        LC->>LS: generateDocx(letter)
        LS-->>LC: Binary Stream (application/vnd.openxmlformats-officedocument...)
        LC-->>FE: HTTP 200 (File Stream: Surat_Pengantar.docx)
        FE-->>W: Berkas Word Langsung Terunduh
    else Surat Belum Berstatus Approved
        W->>FE: Klik Unduh
        FE->>LC: GET /api/v1/letters/{id}/download
        LC-->>FE: HTTP 422: "Surat belum disetujui oleh Ketua RT"
        FE-->>W: Tampilkan Modal Edukatif: "Surat Masih dalam Tahap Proses/Persetujuan"
    end
```

#### 5.3. UML State Machine Diagram

##### A. State Machine Permohonan Surat (Lifecycle Status Surat)
```mermaid
stateDiagram-v2
    [*] --> Draft : Warga Membuat Draf Surat
    Draft --> Submitted : Warga Melengkapi Syarat & Mengirim
    
    state Submitted {
        [*] --> MenungguVerifikasiSekretaris
        MenungguVerifikasiSekretaris --> ReviewDokumen : Sekretaris Evaluasi Lampiran
    }
    
    Submitted --> Verified : Berkas Lengkap & Terverifikasi (Tier 1 Pass)
    Submitted --> Rejected : Berkas Kurang / Tidak Sesuai (Catatan Penolakan)
    
    state Verified {
        [*] --> AntrianKetuaRT
        AntrianKetuaRT --> TinjauSubstansi : Ketua RT Meninjau Pengajuan
    }
    
    Verified --> Approved : Ketua RT Menyetujui & Tanda Tangan (Tier 2 Pass)
    Verified --> Rejected : Ditolak Ketua RT (Catatan Penolakan)
    
    state Approved {
        [*] --> DokumenSiapUnduh
        DokumenSiapUnduh --> StreamPDF : Warga Pilih Unduh Format .PDF
        DokumenSiapUnduh --> StreamDOCX : Warga Pilih Unduh Format .DOCX
    }
    
    Approved --> [*] : Dokumen Digunakan Resmi
    Rejected --> [*] : Proses Berakhir (Warga Dapat Mengajukan Ulang)
```

##### B. State Machine Status Kependudukan & Akun Warga
```mermaid
stateDiagram-v2
    [*] --> BelumTerdaftar : Pendatang Baru Tanpa Data Sensus RT
    [*] --> TerdataSensus : Warga Tercatat di Master Data RT

    BelumTerdaftar --> PendingVerification : Form Pendaftaran Warga Baru + Upload KTP
    
    state PendingVerification {
        [*] --> MenungguVerifikasiPengurus
        MenungguVerifikasiPengurus --> CekKTPFisik : Pengurus RT Verifikasi Domisili
    }

    PendingVerification --> VerifiedActive : Disetujui Pengurus (Role: WARGA Aktif)
    PendingVerification --> RejectedApplicant : Ditolak (Data Tidak Valid / Fiktif)

    TerdataSensus --> VerifiedActive : Verifikasi NIK Instan (SHA-256 Match)
    
    state VerifiedActive {
        [*] --> HakAksesLayanan
        HakAksesLayanan --> PengajuanSurat
        HakAksesLayanan --> PelaporanAduan
        HakAksesLayanan --> UnduhDokumenResmi
    }

    RejectedApplicant --> [*] : Pendaftaran Batal
    VerifiedActive --> [*]
```

---

### 6. Flowchart Algoritma Sistem

#### A. Algoritma Verifikasi Hashing NIK (Proteksi Privasi Warga)
```mermaid
flowchart TD
    Start(["Mulai: Input NIK"]) --> In["Warga Input 16 Digit NIK"]
    In --> V1{"Apakah NIK tepat 16 digit angka?"}
    V1 -- "Tidak" --> E1["Tolak: Tampilkan Error Format NIK"]
    E1 --> Shake["Jalankan Efek Shake Input"]
    Shake --> End1(["Selesai"])
    
    V1 -- "Ya" --> Hash["Lakukan Enkripsi One-Way: SHA-256 NIK"]
    Hash --> Query[("Cari di tabel citizens WHERE nik_hash = hash")]
    Query --> Check{"Ditemukan di Database?"}
    
    Check -- "Tidak" --> E2["Status: Belum Terdaftar di Sensus RT"]
    E2 --> ShowBtn["Munculkan Tombol 'Ajukan Sebagai Warga Baru'"]
    ShowBtn --> End1
    
    Check -- "Ya" --> Linked{"Sudah punya akun (user_id != null)?"}
    Linked -- "Ya" --> E3["Tolak: Akun NIK Ini Sudah Aktif (Arahkan Login)"]
    E3 --> HideBtn1["Sembunyikan Tombol 'Ajukan Warga Baru'"]
    HideBtn1 --> Shake
    
    Linked -- "Tidak" --> Link["Status: Terdata di Sensus RT"]
    Link --> HideBtn2["Sembunyikan Tombol 'Ajukan Warga Baru'"]
    HideBtn2 --> Create["Buat Akun User Baru (Role: WARGA)"]
    Create --> Event["Pusher Broadcast: Event CitizenRegistered"]
    Event --> Token["Generate Personal Sanctum Token"]
    Token --> Sukses(["Verifikasi Sukses: Masuk ke Dashboard"])
```

#### B. Algoritma Optimistic Locking & Audit Trail Pembukuan Kas RT
```mermaid
flowchart TD
    Start(["Mulai: Update Transaksi Kas"]) --> Q1["Ambil Data Transaksi + Input Versi Saat Ini"]
    Q1 --> Lock["Lock Baris Transaksi di Database"]
    Lock --> C1{"Status Transaksi == 'published'?"}
    
    C1 -- "Ya, Mau Dihapus Langsung" --> RejectDel["Larangan: Data Kas Terpublikasi Tidak Boleh Hard Delete"]
    RejectDel --> ReversalReq["Arahkan Menggunakan Alur Reversal Transaksi"]
    
    C1 -- "Update / Reversal" --> C2{"Versi Input == Versi Database?"}
    C2 -- "Tidak Sama" --> Conflict["Gagal: 409 Conflict (Data telah diubah pengguna lain)"]
    
    C2 -- "Sama Sesuai" --> Exec["Lakukan Operasi Reversal / Perubahan Data"]
    Exec --> IncVersion["Naikkan Versi: version = version + 1"]
    IncVersion --> Audit[("Tulis Riwayat di Tabel audit_logs: Old vs New")]
    Audit --> CacheFlush["Hapus Cache Ringkasan Kas Publik"]
    CacheFlush --> Commit[("Database Commit Transaction")]
    Commit --> Done(["Selesai: Transaksi Bersih & Sesuai Mutasi Kas"])
```

---

## Fitur Unggulan Antarmuka & Performa Web

### 1. Carousel Warta & Agenda Warga
- Menampilkan warta, agenda kegiatan warga, dan informasi penting lingkungan secara dinamis dan berputar otomatis.
- Dilengkapi teks penjelasan lengkap, badge kategori, jadwal, penunjuk lokasi, dan tombol aksi terarah.
- Didukung kontrol interaktif: tombol navigasi sebelumnya/selanjutnya, indikator titik slide, indikator jumlah informasi, swipe gesture pada layar sentuh mobile, dan jeda otomatis saat kursor mouse melintas (*pause on hover*).

### 2. Navbar 3 Menu Prioritas & Hamburger Drawer
- **Desktop Viewport:** Memprioritaskan 3 tautan menu paling krusial:
  1. `Warta & Agenda`: Navigasi cepat ke warta dan pengumuman terbaru.
  2. `Kas RT`: Menuju ringkasan transparansi keuangan lingkungan.
  3. `Lapor Pengaduan`: Tombol aksi cepat untuk membuka modal pengaduan warga.
  Ditambah tombol otentikasi `Masuk Portal` atau `Dashboard Warga`.
- **Hamburger Drawer (Off-Canvas):** Seluruh tautan sekunder (Agenda Kegiatan, Jadwal Ronda Kamling, Kontak Darurat 24 Jam, Struktur Pengurus RT, dan Swagger OpenAPI Spec) tertata rapi di dalam drawer geser dengan latar belakang blur dan dukungan tombol keyboard `Escape`.

### 3. Optimasi Core Web Vitals, LCP & CLS/LFS
- **Priority Hints:** Logo utama dimuat dengan `<link rel="preload" as="image" fetchpriority="high">` untuk mempercepat Largest Contentful Paint (LCP).
- **Zero Cumulative Layout Shift (CLS = 0):** Seluruh aset gambar (logo horizontal, simbol, favicon) memiliki atribut eksplisit `width` dan `height` serta `decoding="async"`.
- **Preconnect Font:** Koneksi awal asinkron ke server tipografi Google Fonts via Bunny Fonts untuk menghilangkan FOIT (*Flash of Invisible Text*).
- **GPU Accelerated Transitions:** Animasi slider carousel menggunakan properti CSS `transform: translate3d(...)` dan `will-change: transform` guna memastikan render 60 FPS tanpa Layout Shift Frequency (LFS).

### 4. Metadata SEO & Geolocation (GEO)
- **Search Engine Optimization:** Tag meta lengkap meliputi canonical URL, OpenGraph, Twitter Cards, dan meta robot (`index, follow, max-image-preview:large`).
- **Geolocation Indexing:** Menetapkan metadata geografis wilayah RT/RW (`geo.region`, `geo.placename`, `geo.position`, dan `ICBM`).
- **Structured Data (Schema.org JSON-LD):** Entitas `GovernmentOrganization` dan `WebSite` dengan koordinat lintang/bujur dan fungsionalitas pencarian pelacakan tiket surat terintegrasi.

---

## Persiapan Integrasi Firebase Versi Gratis (Spark Plan)

Proyek ini telah dikonfigurasi agar dapat berjalan secara optimal menggunakan **Firebase Spark Plan (Paket Gratis $0/bulan)**:

1. **Firebase Cloud Messaging (FCM) - 100% Gratis Tanpa Batas**:
   - Pengiriman Web Push Notifications status persuratan dan pengumuman darurat warga tanpa batasan kuota.
   - Didukung berkas Service Worker khusus: `public/firebase-messaging-sw.js` dan inisialisasi klien `public/js/firebase-init.js`.
2. **Cloud Firestore (1 GB Free / 50k Reads / 20k Writes per hari)**:
   - Sinkronisasi real-time warta dan pendaftaran token perangkat warga.
   - Aturan keamanan terperinci pada `firestore.rules` dan indeks kueri pada `firestore.indexes.json`.
3. **Cloud Storage (5 GB Free Storage)**:
   - Penyimpanan berkas lampiran surat dan bukti foto aduan warga dengan pembatasan ukuran maksimal 5 MB pada `storage.rules`.
4. **Firebase Hosting (10 GB Free Storage & CDN Global)**:
   - Konfigurasi caching aset statis 1 tahun dan rewrite PWA pada `firebase.json`.
5. **Panduan Lengkap**: Lihat panduan implementasi detail pada [docs/FIREBASE_FREE_TIER_SETUP.md](docs/FIREBASE_FREE_TIER_SETUP.md).

---

## Panduan Instalasi & Menjalankan Proyek

### 1. Kebutuhan Sistem (*Prerequisites*)
- **PHP** versi **>= 8.2** (Disarankan PHP 8.4)
- **Composer** versi **>= 2.5**
- **Node.js** versi **>= 18** & **NPM**
- **MySQL Database Server** (XAMPP / Laragon / Standalone MySQL)

### 2. Langkah Instalasi Langkah demi Langkah

1. **Clone Repositori**:
   ```bash
   git clone https://github.com/not162/platform-layanan-publik-warga.git
   cd platform-layanan-publik-warga
   ```

2. **Instal Dependensi Backend (PHP / Composer)**:
   ```bash
   composer install
   ```

3. **Instal Dependensi Frontend (Node.js / NPM)**:
   ```bash
   npm install
   ```

4. **Konfigurasi Lingkungan (`.env`)**:
   Salin file konfigurasi contoh:
   ```bash
   cp .env.example .env
   ```
   Buka file `.env` dan sesuaikan koneksi database MySQL:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=layanan_publik_warga
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Generate Kunci Aplikasi**:
   ```bash
   php artisan key:generate
   ```

6. **Migrasi Database & Seeder Kredensial Pengurus**:
   ```bash
   php artisan migrate --seed
   ```
   *Perintah ini akan membuat seluruh tabel di MySQL dan langsung mengisi akun **Superadmin**, **Ketua RT**, **Sekretaris**, **Bendahara**, master jenis surat, dan data sensus warga awal.*

7. **Kompilasi Aset Frontend (Vite & Tailwind CSS)**:
   ```bash
   # Untuk produksi:
   npm run build

   # Atau untuk mode pengembangan (hot-reload):
   npm run dev
   ```

8. **Jalankan Server Lokal**:
   ```bash
   php artisan serve
   ```
   Buka portal di browser Anda: **`http://127.0.0.1:8000`**

---

## Pengujian (Automated Testing)

Aplikasi memiliki rangkaian pengujian unit dan fitur (*Feature Tests*) dengan cakupan menyeluruh untuk menjamin keandalan sistem dan RBAC matrix:

```bash
# Menjalankan seluruh pengujian:
php artisan test
```

### Hasil Ringkasan Pengujian Otomatis:
```text
PASS  Tests\Feature\ArchitectureAndRbacNormalizationTest
PASS  Tests\Feature\CitizenModuleTest
PASS  Tests\Feature\CitizenServiceRoleMatrixTest
PASS  Tests\Feature\ComplaintCameraPermissionTest
PASS  Tests\Feature\ComplaintModuleTest
PASS  Tests\Feature\DocumentWorkflowAndGenerationTest
PASS  Tests\Feature\ExampleTest
PASS  Tests\Feature\FinanceLedgerSprint2Test
PASS  Tests\Feature\FinanceModuleTest
PASS  Tests\Feature\FinanceReportBackupTest
PASS  Tests\Feature\KepengurusanLoginTest
PASS  Tests\Feature\LetterModuleTest
PASS  Tests\Feature\NotificationModuleTest
PASS  Tests\Feature\PublicContentModuleTest
PASS  Tests\Feature\PurchaseAndQuarterlyReportSprint4Test
PASS  Tests\Feature\PwaModuleTest
PASS  Tests\Feature\ResidentDuePersonalLedgerSprint3Test
PASS  Tests\Feature\RoleAndAuthorizationTest
PASS  Tests\Feature\SecurityReportModuleTest

Tests:    128 passed (545 assertions)
Duration: 10.20s
Status:   100% OK
```

---

## Klasifikasi Dokumentasi Teknis (`docs/`)

Seluruh dokumentasi arsitektur, spesifikasi API, kebutuhan bisnis, perencanaan proyek, dan tata kelola sistem telah dikelompokkan ke dalam 5 subdirektori tematik di [docs/](docs/README.md):

### 1. Desain & Arsitektur Visual ([docs/design/](docs/design/))
- **[Brand Guide & Identitas Visual](docs/design/BRAND_GUIDE.md)**: Standardisasi warna, tipografi, dan logo lingkungan RT.
- **[Diagram Arsitektur Sistem](docs/design/DIAGRAMS.md)**: Relasi antarkomponen platform warga dan kepengurusan.
- **[Entity Relationship Diagram (ERD)](docs/design/ERD.md)**: Skema database relasional kependudukan, surat, dan kas warga.
- **[Perencanaan Antarmuka UI](docs/design/UI_PLAN.md)**: Tata letak antarmuka responsif ramah segala kalangan.
- **[Optimasi Performa Web & SEO](docs/design/WEB_PERFORMANCE_SEO_GEO.md)**: Strategi Core Web Vitals, priority hints, dan Schema.org Geolocation.

### 2. Spesifikasi API ([docs/api_spec/](docs/api_spec/))
- **[Spesifikasi API v1 Lengkap](docs/api_spec/API_SPEC.md)**: Rincian RESTful endpoints, parameter, headers, payload, dan responses.
- **[Kontrak API v1.1](docs/api_spec/API_V1_1_CONTRACT.md)**: Spesifikasi mutasi penagihan iuran warga dan audit transaksi.
- **[Skema Standar OpenAPI 3.1](docs/api_spec/openapi.yaml)**: Definisi API untuk Swagger UI interaktif yang dapat diakses di rute `/docs/api`.

### 3. Persyaratan Bisnis & Regulasi ([docs/business/](docs/business/))
- **[Software Requirements Specification (SRS)](docs/business/SRS.md)**: Kebutuhan fungsional dan non-fungsional aplikasi layanan warga.
- **[Klasifikasi & Enkripsi Data Warga](docs/business/DATA_CLASSIFICATION.md)**: Enkripsi NIK dua arah (AES-256-CBC) dan proteksi data PII.
- **[Alur Kerja & State Machine Surat](docs/business/LETTER_WORKFLOW.md)**: Alur penerbitan surat pengantar, verifikasi pengurus, penomoran urut bebas bentrok, dan tanda tangan elektronik.
- **[Regulasi Laporan Keuangan & Backup](docs/business/FINANCIAL_REPORTS_AND_BACKUP_STORE.md)**: Pembatasan unduh rekapitulasi khusus kepengurusan, audit jejak unduh, dan backup terenkripsi.
- **[Standardisasi Format Dokumen](docs/business/DOCUMENT_TEMPLATE.md)**: Template cetak dokumen resmi RT dan parameter dinamis.
- **[Analisis Performa Memori & Storage](docs/business/STORAGE_MEMORY_PERFORMANCE_ANALYSIS.md)**: Efisiensi penyimpanan berkas dan kompresi lampiran.

### 4. Perencanaan Proyek ([docs/plan_project/](docs/plan_project/))
- **[Master Project Plan](docs/plan_project/PROJECT_PLAN.md)**: Rencana strategis implementasi menyeluruh.
- **[Sprint Plan Roadmap](docs/plan_project/SPRINT_PLAN.md)**: Rincian 10 Sprint pengembangan berkelanjutan.

### 5. Tata Kelola & Infrastruktur ([docs/project_management/](docs/project_management/))
- **[Role-Based Access Control (RBAC)](docs/project_management/RBAC.md)**: Penegakan wewenang berprinsip Least Privilege untuk setiap role.
- **[Matriks Hak Akses Granular](docs/project_management/RBAC_MATRIX.md)**: Pemetaan permission granular per entitas modul.
- **[Panduan Integrasi Firebase Versi Gratis](docs/project_management/FIREBASE_FREE_TIER_SETUP.md)**: Pemanfaatan Firebase Spark Plan $0/bulan untuk notifikasi FCM, Storage, dan Firestore.
- **[Arsitektur Enterprise & Client Offloading](docs/project_management/MICROSERVICES_ENTERPRISE_ARCHITECTURE.md)**: Penanganan rendering PDF/Word di sisi front-end guna efisiensi CPU server.

---

## Lisensi
Proyek ini dikembangkan di bawah lisensi [MIT License](LICENSE). Hak Cipta &copy; 2026 Pengurus Lingkungan RT & Pengembang Sistem.
