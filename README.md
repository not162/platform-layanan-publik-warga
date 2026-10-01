# Platform Layanan Publik Warga (Portal Digital RT/RW)

> Sistem Informasi Manajemen Administrasi, Pelayanan Surat Terpadu, Transparansi Kas Keuangan, dan Pengaduan Warga Berbasis Web & Progressive Web App (PWA).

---

## Daftar Isi
1. [Tentang Proyek](#tentang-proyek)
2. [Tech Stack & Database](#tech-stack--database)
3. [Daftar Akun & Kredensial Pengurus](#daftar-akun--kredensial-pengurus)
4. [Arsitektur & Visualisasi Diagram](#arsitektur--visualisasi-diagram)
   - [Use Case Diagram](#1-use-case-diagram)
   - [Entity Relationship Diagram (ERD)](#2-entity-relationship-diagram-erd)
   - [Logical Record Structure (LRS)](#3-logical-record-structure-lrs)
   - [Activity Diagram](#4-activity-diagram)
   - [Sequence Diagram](#5-sequence-diagram)
   - [Flowchart Algoritma Sistem](#6-flowchart-algoritma-sistem)
5. [Fitur Unggulan Antarmuka & Performa Web](#fitur-unggulan-antarmuka--performa-web)
   - [Carousel Warta & Agenda Warga](#1-carousel-warta--agenda-warga)
   - [Navbar 3 Menu Prioritas & Hamburger Drawer](#2-navbar-3-menu-prioritas--hamburger-drawer)
   - [Optimasi Core Web Vitals, LCP & CLS/LFS](#3-optimasi-core-web-vitals-lcp--clslfs)
   - [Metadata SEO & Geolocation (GEO)](#4-metadata-seo--geolocation-geo)
6. [Persiapan Integrasi Firebase Versi Gratis (Spark Plan)](#persiapan-integrasi-firebase-versi-gratis-spark-plan)
7. [Panduan Instalasi & Menjalankan Proyek](#panduan-instalasi--menjalankan-proyek)
8. [Pengujian (Automated Testing)](#pengujian-automated-testing)
9. [Dokumentasi Teknis & Spesifikasi API](#dokumentasi-teknis--spesifikasi-api)

---

## Tentang Proyek

**Platform Layanan Publik Warga** dirancang untuk mendigitalkan birokrasi di tingkat RT/RW secara terstruktur, transparan, dan aman. Platform ini memiliki fitur-fitur unggulan:
- **Verifikasi Warga Instan**: Pencocokan NIK berbasis satu arah (*One-Way Hashing SHA-256*) yang melindungi privasi NIK warga dari kebocoran data.
- **Birokrasi Surat Digital 2-Tier**: Verifikasi berkas administratif oleh **Sekretaris**, dilanjutkan dengan persetujuan/tanda tangan digital oleh **Ketua RT**.
- **Buku Kas Anti-Fraud (*Immutable Ledger*)**: Setiap transaksi kas yang telah dipublikasikan tidak dapat diubah atau dihapus sembarangan, melainkan harus melalui proses pembalik (*reversal ledger*) yang tercatat di audit log.
- **Real-Time Notification**: Terintegrasi dengan **Pusher Channels** dan **Web Push Notifications (PWA)** sehingga warga dan pengurus menerima notifikasi langsung di perangkat masing-masing.
- **Tampilan Ramah Pengguna**: Mengusung *Clean Light Theme Only*, tipografi Google Fonts Inter, ikonografi Lucide, dan interaktivitas Alpine.js dengan umpan balik animasi (efek *shake* kartu dan pesan validasi visual).

---

## Tech Stack & Database

| Komponen | Teknologi | Keterangan |
|---|---|---|
| **Backend Framework** | **Laravel 11 / 12 / 13** (PHP 8.4) | Arsitektur MVC, RESTful API Resource, Service Layer, Sanctum Auth |
| **Database Produksi** | **MySQL / MariaDB via phpMyAdmin** | Relasi antar tabel dengan Foreign Key Constraints & Indexing |
| **Database Development/Testing**| **SQLite (In-Memory / File)** | Digunakan untuk eksekusi Feature Testing dan CI/CD cepat |
| **Frontend Styling** | **Tailwind CSS v4** | Desain responsif modern berorientasi Light Mode elegan |
| **Frontend Interactivity** | **Alpine.js v3** | Reactive client-side validation, NIK counter, password toggle, animasi *shake* |
| **Ikonografi & Font** | **Lucide Icons & Google Fonts (Inter)** | Tampilan bersih, profesional, dan mudah dibaca oleh semua usia warga |
| **Real-time Broadcasting** | **Pusher Channels & Laravel Echo** | Siaran instan saat ada warga baru mendaftar atau status surat diperbarui |
| **Mobile & Offline Support** | **Progressive Web App (PWA)** | Service Worker Cache-First, Web App Manifest, Fallback Halaman Offline |
| **Code Formatter & QA** | **Laravel Pint & PHPUnit** | Standardisasi PSR-12 dan 53 Automated Feature Tests (100% Passed) |

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
| **WARGA** | Pengguna Publik | *(Sesuai registrasi warga)* | *(Ditentukan warga)* | **Layanan Mandiri Warga**:<br>• Mengajukan surat pengantar mandiri (`letter.create`).<br>• Tracking status surat & verifikasi keaslian surat via token.<br>• Mengirimkan aduan lingkungan lengkap dengan input rincian jalan & lokasi serta izin akses upload foto kamera (khusus akun warga terverifikasi).<br>• Melihat laporan transparansi kas & pengumuman RT. |

---

## Arsitektur & Visualisasi Diagram

### 1. Use Case Diagram
Diagram use case memetakan interaksi seluruh aktor (Warga, Sekretaris, Bendahara, Ketua RT, dan Superadmin) dengan fitur sistem.

```mermaid
flowchart LR
    Warga["Warga"]
    Sekretaris["Sekretaris RT"]
    Bendahara["Bendahara RT"]
    KetuaRT["Ketua RT"]
    Superadmin["Superadmin"]

    subgraph Portal Layanan Publik Warga
        UC1["Daftar Akun & Verifikasi NIK"]
        UC2["Login & Manajemen Profil"]
        UC3["Pengajuan Surat Pengantar"]
        UC4["Lacak & Verifikasi Surat"]
        UC5["Kirim Keluhan / Aspirasi"]
        UC6["Lihat Kas Transparan & Pengumuman"]
        
        UC7["Kelola Master Data Warga"]
        UC8["Verifikasi Berkas Surat"]
        UC9["Kelola Pengumuman & Agenda RT"]
        
        UC10["Kelola Transaksi Kas RT"]
        UC11["Reversal / Koreksi Kas"]
        UC12["Publikasi Laporan Kas"]
        
        UC13["Persetujuan Akhir Surat"]
        UC14["Tindak Lanjut Aduan Warga"]
        
        UC15["Manajemen Pengguna & Izin Akses"]
        UC16["Audit Log & Sistem Pengaturan"]
    end

    Warga --> UC1
    Warga --> UC2
    Warga --> UC3
    Warga --> UC4
    Warga --> UC5
    Warga --> UC6

    Sekretaris --> UC2
    Sekretaris --> UC7
    Sekretaris --> UC8
    Sekretaris --> UC9

    Bendahara --> UC2
    Bendahara --> UC10
    Bendahara --> UC11
    Bendahara --> UC12

    KetuaRT --> UC2
    KetuaRT --> UC13
    KetuaRT --> UC14
    KetuaRT --> UC6

    Superadmin --> UC15
    Superadmin --> UC16
    Superadmin -.-> UC7
    Superadmin -.-> UC10
    Superadmin -.-> UC13
```

---

### 2. Entity Relationship Diagram (ERD)
Diagram relasi entitas basis data MySQL yang memperlihatkan struktur tabel, tipe data, serta **kardinalitas relasi secara eksplisit (*One-to-One [1:1]*, *One-to-Many [1:N]*, dan resolusi *Many-to-Many [M:N]*)**.

```mermaid
erDiagram
    %% =======================================================
    %% RELASI KARDINALITAS BASIS DATA
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
        bigint user_id FK "Relasi 1:1 ke USERS"
        varchar nik_hash UK "SHA-256 Blind Index"
        varchar full_name
        varchar birth_place
        date birth_date
        varchar gender
        text address
        varchar phone_number
        varchar occupation
        varchar religion
        varchar status_warga
        int version "Optimistic Locking"
        datetime created_at
    }

    USERS {
        bigint id PK
        bigint warga_id FK "Relasi 1:1 ke CITIZENS"
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
| **`CITIZENS`** | **`USERS`** | **`1 : 1`** (One-to-One) | `CITIZENS.user_id` ➔ `USERS.id`<br>`USERS.warga_id` ➔ `CITIZENS.id` | **Satu** data kependudukan warga memiliki tepat **satu** akun autentikasi portal (relasi bi-directional untuk integritas identitas). |
| **`USERS`** | **`ADMIN_PERMISSIONS`** | **`1 : N`** *(Pecahan M:N)* | `USERS.id` ➔ `ADMIN_PERMISSIONS.user_id` | **Satu** akun pengguna dapat memiliki **banyak** hak akses granular (*role-permission assignment*). |
| **`USERS`** <br>*(Konseptual)* | **`PERMISSIONS`** <br>*(Konseptual)* | **`M : N`** *(Many-to-Many)* | Diurai via Junction Table:<br>**`ADMIN_PERMISSIONS`** | **Banyak** User dapat memiliki **banyak** Permission yang sama. Relasi Many-to-Many dinormalisasi menjadi dua relasi One-to-Many (1:N) melalui tabel pivot `ADMIN_PERMISSIONS`. |
| **`LETTER_TYPES`** | **`LETTERS`** | **`1 : N`** (One-to-Many) | `LETTER_TYPES.id` ➔ `LETTERS.letter_type_id` | **Satu** jenis formulir surat (SKTM, SKU, dll.) digunakan sebagai template permohonan oleh **banyak** surat warga. |
| **`USERS`** | **`LETTERS`** | **`1 : N`** (One-to-Many) | `USERS.id` ➔ `LETTERS.user_id` | **Satu** akun warga dapat mengajukan **banyak** surat pengantar sepanjang waktu. |
| **`LETTERS`** | **`LETTER_ATTACHMENTS`**| **`1 : N`** (One-to-Many) | `LETTERS.id` ➔ `LETTER_ATTACHMENTS.letter_id` | **Satu** berkas surat permohonan dapat memiliki **banyak** lampiran dokumen pendukung (KTP, KK, Bukti Bayar, dll.). |
| **`USERS`** | **`COMPLAINTS`** | **`1 : N`** (One-to-Many) | `USERS.id` ➔ `COMPLAINTS.user_id` | **Satu** warga dapat menyampaikan **banyak** laporan keluhan aspirasi lingkungan RT. |
| **`USERS`** | **`FINANCE_TRANSACTIONS`** | **`1 : N`** (One-to-Many) | `USERS.id` ➔ `FINANCE_TRANSACTIONS.recorded_by` | **Satu** pengurus kas (Bendahara) dapat mencatat dan mengelola **banyak** mutasi kas masuk/keluar. |
| **`USERS`** | **`AUDIT_LOGS`** | **`1 : N`** (One-to-Many) | `USERS.id` ➔ `AUDIT_LOGS.user_id` | **Satu** pengguna dapat memicu **banyak** baris pencatatan jejak audit audit trail sistem. |
| **`USERS`** | **`PUSH_SUBSCRIPTIONS`**| **`1 : N`** (One-to-Many) | `USERS.id` ➔ `PUSH_SUBSCRIPTIONS.user_id` | **Satu** pengguna dapat mendaftarkan **banyak** token browser/perangkat mobile untuk WebPush Notification. |
| **`USERS` (Warga)** <br>*(Konseptual)* | **`USERS` (Pengurus)** <br>*(Konseptual)* | **`M : N`** *(Many-to-Many)* | Diurai via Associative Entity:<br>**`LETTERS`** & **`COMPLAINTS`** | **Banyak** warga dapat dilayani oleh **banyak** pengurus (Sekretaris & Ketua RT). Relasi M:N ini dihubungkan secara ternormalisasi melalui entitas transaksi `LETTERS` (`user_id`, `verified_by`, `approved_by`). |

---

### 3. Logical Record Structure (LRS)
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
│ FK   family_card_id ───────────────────────────────────┼── (Relasi 1:N dari FAMILY_CARDS)                │ FK   warga_id ─────────────────────────────────────────┤ (Relasi 1:1 ke CITIZENS)
│ FK   user_id ──────────────────────────────────────────┼── (Relasi 1:1 ke USERS)                         │      name, email (Unique), password (Bcrypt)           │
│      nik_hash (Unique, SHA-256), full_name             │                                                 │      role (SUPERADMIN, KETUA_RT, BENDAHARA, ..), active│
│      birth_place, birth_date, gender, address, phone   │                                                 └────────┬──────────────┬──────────────┬──────────────┬───┘
│      occupation, religion, status_warga, version       │                                                          │ [1:N]        │ [1:N]        │ [1:N]        │ [1:N]
└───────────────────────────┬────────────────────────────┘                                                          │              │              │              │
                            │ [1:N]                                                                                 ▼              ▼              ▼              │
                            ▼                                                                              ┌────────────────┐┌────────────┐┌─────────────┐       │
┌────────────────────────────────────────────────────────┐                                                 │ADMIN_PERMISSION││ AUDIT_LOGS ││PUSH_SUBSCRIP│       │
│                        LETTERS                         │                                                 ├────────────────┤├────────────┤├─────────────┤       │
├────────────────────────────────────────────────────────┤                                                 │PK  id          ││PK  id      ││PK  id       │       │
│ PK   id                                                │                                                 │FK  user_id     ││FK  user_id ││FK  user_id  │       │
│      ticket_number (Unique), tracking_token (Unique)   │                                                 │    permission  ││    action  ││    endpoint │       │
│ FK   user_id ──────────────────────────────────────────┼── (Relasi 1:N dari USERS Pemohon)               └────────────────┘└────────────┘└─────────────┘       │
│ FK   citizen_id ───────────────────────────────────────┼── (Relasi 1:N dari Profil CITIZENS)              [M:N Pivot Table]                                    │
│ FK   letter_type_id ◄──────────┐                       │                                                                                                       │
│      purpose, status (submitted..approved), version    │                                                 ┌──────────────────────────────────────────────┐      │
│ FK   verified_by (Users.id)    │ [1:N]                 │                                                 │             FINANCE_TRANSACTIONS             │◄─────┘
│ FK   approved_by (Users.id)    │                       │                                                 ├──────────────────────────────────────────────┤
└───────────────────────────┬────┴───────────────────────┘                                                 │ PK   id                                      │
                            │                                                                              │      transaction_number (Unique)             │
                            │ [1:N] (One-to-Many)                                                          │      type (income, expense), category, amount│
                            ▼                                                                              │      description, date, status, reversal     │
┌────────────────────────────────────────────────────────┐  ┌───────────────────────────────────────────┐  │ FK   recorded_by (Users.id) ─────────────────┼── (Relasi 1:N Pencatat)
│                   LETTER_ATTACHMENTS                   │  │               LETTER_TYPES                │  │      version (Optimistic Lock)               │
├────────────────────────────────────────────────────────┤  ├───────────────────────────────────────────┤  └──────────────────────────────────────────────┘
│ PK   id                                                │  │ PK   id                                   │
│ FK   letter_id ────────────────────────────────────────┤  │      code (Unique, SKTM/SKU), name, desc  │  ┌──────────────────────────────────────────────┐
│      file_path, file_name, file_type, file_size        │  │      required_documents (JSON), is_active │  │                  COMPLAINTS                  │
└────────────────────────────────────────────────────────┘  └─────────────────────┬─────────────────────┘  ├──────────────────────────────────────────────┤
                                                                                  │ [1:N]                  │ PK   id                                      │
                                                                                  └────────────────────────┤      ticket_number (Unique), tracking_token  │
                                                                                                           │ FK   user_id (Users.id) ─────────────────────┼── (Relasi 1:N Pembuat Aduan)
                                                                                                           │ FK   citizen_id (Citizens.id)                │
                                                                                                           │      category, title, description, status    │
                                                                                                           │ FK   handled_by (Users.id)                   │
                                                                                                           └──────────────────────────────────────────────┘
```

#### Notasi Relasional LRS (Relational Mapping Notation)
Menunjukkan struktur relasi antar tabel secara formal (`PK = Primary Key Garis Bawah`, `FK = Foreign Key Prefix #`):

1. **`FAMILY_CARDS`** (<u>id</u>, *nomor_kk*, kepala_keluarga_name, alamat, rt, rw, kode_pos, created_at)
2. **`CITIZENS`** (<u>id</u>, **#family_card_id** *(1:N)*, **#user_id** *(1:1)*, *nik_hash*, full_name, birth_place, birth_date, gender, address, phone_number, occupation, religion, status_warga, version, created_at)
3. **`USERS`** (<u>id</u>, **#warga_id** *(1:1)*, name, *email*, password, role, is_active, last_login_at, created_at)
4. **`ADMIN_PERMISSIONS`** (<u>id</u>, **#user_id** *(1:N / Resolusi M:N)*, permission, created_at)
   - *Mekanisme Normalisasi M:N:* Menguraikan relasi Many-to-Many antara kumpulan Pengguna (`USERS`) dan Hak Otorisasi (`PERMISSIONS`) menjadi tabel asosiatif independen.
5. **`LETTER_TYPES`** (<u>id</u>, *code*, name, description, required_documents, is_active, created_at)
6. **`LETTERS`** (<u>id</u>, *ticket_number*, *tracking_token*, **#user_id** *(1:N)*, **#citizen_id** *(1:N)*, **#letter_type_id** *(1:N)*, purpose, status, remarks, rejection_reason, **#verified_by** *(1:N)*, **#approved_by** *(1:N)*, verified_at, approved_at, version, created_at)
   - *Mekanisme Normalisasi M:N:* Menghubungkan relasi Many-to-Many antara Warga Pemohon dengan Multi-Pengurus Penyetuju (Sekretaris & Ketua RT) dalam alur verifikasi surat bertingkat.
7. **`LETTER_ATTACHMENTS`** (<u>id</u>, **#letter_id** *(1:N)*, file_path, file_name, file_type, file_size, created_at)
8. **`COMPLAINTS`** (<u>id</u>, *ticket_number*, *tracking_token*, **#user_id** *(1:N)*, **#citizen_id** *(1:N)*, category, title, description, is_anonymous, status, response, **#handled_by** *(1:N)*, version, created_at)
9. **`FINANCE_TRANSACTIONS`** (<u>id</u>, *transaction_number*, type, category, amount, description, transaction_date, status, reversal_reason, **#recorded_by** *(1:N)*, version, created_at)
10. **`AUDIT_LOGS`** (<u>id</u>, **#user_id** *(1:N)*, action, entity_type, entity_id, old_values, new_values, created_at)
11. **`PUSH_SUBSCRIPTIONS`** (<u>id</u>, **#user_id** *(1:N)*, endpoint, public_key, auth_token, created_at)
12. **`ANNOUNCEMENTS`** (<u>id</u>, title, category, content, is_pinned, is_published, published_at, version, created_at)
13. **`COMMUNITY_EVENTS`** (<u>id</u>, title, description, location, event_date, start_time, end_time, is_published, created_at)

---

### 4. Activity Diagram

#### A. Alur Pengajuan dan Persetujuan Surat Pengantar Warga
```mermaid
flowchart TD
    Start(["Mulai: Warga Butuh Surat"]) --> A1["Warga Login ke Portal"]
    A1 --> A2["Pilih Jenis Surat & Isi Keperluan"]
    A2 --> A3["Upload Dokumen Persyaratan KTP/KK"]
    A3 --> A4["Klik Kirim Permohonan"]
    A4 --> A5[("Sistem Simpan Status: SUBMITTED")]
    A5 --> A6["Generate Nomor Tiket & Token Pelacakan"]
    A6 --> A7["Notifikasi Realtime ke Dashboard Sekretaris"]
    
    A7 --> B1["Sekretaris Buka Menu Verifikasi Surat"]
    B1 --> B2{"Berkas Lengkap & Sesuai?"}
    
    B2 -- "Tidak Sesuai" --> B3["Sekretaris Isi Alasan Penolakan"]
    B3 --> B4[("Status Diperbarui: REJECTED")]
    B4 --> B5["Kirim Notifikasi Alasan ke Warga"]
    B5 --> EndTolak(["Selesai: Surat Ditolak"])
    
    B2 -- "Lengkap" --> B6["Sekretaris Klik Verifikasi Berkas"]
    B6 --> B7[("Status Diperbarui: VERIFIED")]
    B7 --> B8["Notifikasi Otomatis ke Akun Ketua RT"]
    
    B8 --> C1["Ketua RT Buka Menu Persetujuan Surat"]
    C1 --> C2{"Disetujui Ketua RT?"}
    
    C2 -- "Ditolak" --> C3["Ketua RT Masukkan Alasan Penolakan"]
    C3 --> B4
    
    C2 -- "Disetujui" --> C4["Ketua RT Beri Tanda Tangan & Setujui"]
    C4 --> C5[("Status Diperbarui: APPROVED")]
    C5 --> C6["Generate QR Code Keaslian & PDF Surat"]
    C6 --> C7["Warga Dapat Mengunduh Surat Resmi"]
    C7 --> EndSukses(["Selesai: Surat Siap Digunakan"])
```

#### B. Alur Pencatatan & Pembatalan Transaksi Kas RT (Immutable Reversal)
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

### 5. Sequence Diagram

#### A. Registrasi Akun Warga & Verifikasi NIK Instan via Pusher
```mermaid
sequenceDiagram
    autonumber
    actor W as Warga
    participant FE as Frontend (Blade/Alpine.js)
    participant Auth as AuthController
    participant Model as Citizen Model
    participant DB as MySQL Database
    participant Push as Pusher Channel
    participant Adm as Admin Dashboard

    W->>FE: Isi Form (NIK 16 Digit, Nama, Email, Password)
    FE->>FE: Validasi Format Client & Cek Kesamaan Password
    FE->>Auth: POST /api/v1/register (Payload Data)
    
    Auth->>Auth: Hash SHA-256 (NIK Input)
    Auth->>Model: Query Citizen::where('nik_hash', hash)
    Model->>DB: SELECT * FROM citizens WHERE nik_hash = ?
    DB-->>Model: Return Data Citizen / Null
    
    alt NIK Tidak Terdaftar dalam Sensus RT
        Model-->>Auth: null
        Auth-->>FE: HTTP 422: "NIK tidak terdaftar dalam data RT"
        FE->>FE: Trigger Animasi Shake Card + Border Merah + Focus Field
        FE-->>W: Muncul Banner Alert: "Verifikasi Gagal"
    else NIK Sudah Pernah Terhubung ke Akun Lain
        Model-->>Auth: citizen.user_id != null
        Auth-->>FE: HTTP 422: "NIK ini sudah terhubung dengan akun lain"
        FE->>FE: Trigger Shake Animation
        FE-->>W: Muncul Banner Alert: "Akun Sudah Terdaftar"
    else NIK Sah & Belum Memiliki Akun
        Auth->>DB: INSERT INTO users (name, email, password, role='WARGA')
        DB-->>Auth: User Created (user_id)
        Auth->>DB: UPDATE citizens SET user_id = user.id
        Auth->>Push: Dispatch Event: CitizenRegistered(user)
        Push-->>Adm: Broadcast realtime di 'admin-channel' ("Warga Baru Terdaftar")
        Auth-->>FE: HTTP 201: Access Token Sanctum + User Profile
        FE->>FE: Simpan Token di LocalStorage & Tampilkan Pesan Sukses
        FE-->>W: Redirect Otomatis ke Dashboard Warga
    end
```

#### B. Pengajuan Surat Warga hingga Persetujuan Ketua RT
```mermaid
sequenceDiagram
    autonumber
    actor W as Warga
    participant FE as Frontend Portal
    participant LC as LetterController
    participant LS as LetterService
    participant DB as MySQL Database
    actor S as Sekretaris RT
    actor RT as Ketua RT

    W->>FE: Submit Form Pengajuan Surat + Unggah Syarat
    FE->>LC: POST /api/v1/letters
    LC->>LS: create(data, user_id)
    LS->>DB: INSERT INTO letters (status='submitted', ticket_number, version=1)
    LS->>DB: INSERT INTO letter_attachments (...)
    DB-->>LC: Letter Model Created
    LC-->>FE: HTTP 201 Created (Nomor Tiket Diterbitkan)
    FE-->>W: Tampilkan Bukti Pengajuan & Token Pelacakan
    
    Note over S,LC: Tahap 1: Verifikasi Administratif oleh Sekretaris
    S->>FE: Buka Menu Verifikasi & Review Dokumen
    FE->>LC: PATCH /api/v1/admin/letters/{id}/verify
    LC->>LS: verify(letter, sekretaris_id)
    LS->>DB: UPDATE letters SET status='verified', verified_by=?, verified_at=NOW()
    DB-->>LC: Success
    LC-->>FE: Status Berubah menjadi 'VERIFIED'
    
    Note over RT,LC: Tahap 2: Pengesahan Akhir oleh Ketua RT
    RT->>FE: Buka Menu Persetujuan Surat
    FE->>LC: PATCH /api/v1/admin/letters/{id}/approve
    LC->>LS: approve(letter, ketua_rt_id)
    LS->>DB: UPDATE letters SET status='approved', approved_by=?, approved_at=NOW()
    DB-->>LC: Success
    LC-->>FE: Status Berubah menjadi 'APPROVED'
    FE-->>W: Notifikasi Realtime: Surat Siap Diunduh / Diambil
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
    Query --> Check{"Ditemukan?"}
    
    Check -- "Tidak" --> E2["Tolak: NIK Belum Terdaftar di Sensus RT"]
    E2 --> Shake
    
    Check -- "Ya" --> Linked{"Sudah punya akun (user_id != null)?"}
    Linked -- "Ya" --> E3["Tolak: Akun NIK Ini Sudah Aktif"]
    E3 --> Shake
    
    Linked -- "Tidak" --> Link["Hubungkan user_id ke data citizen"]
    Link --> Create["Buat Akun User Baru (Role: WARGA)"]
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

### Hasil Ringkasan Pengujian:
```text
PASS  Tests\Feature\CitizenModuleTest
PASS  Tests\Feature\CitizenServiceRoleMatrixTest
PASS  Tests\Feature\ComplaintModuleTest
PASS  Tests\Feature\DocumentWorkflowAndGenerationTest
PASS  Tests\Feature\ExampleTest
PASS  Tests\Feature\FinanceModuleTest
PASS  Tests\Feature\LetterModuleTest
PASS  Tests\Feature\NotificationModuleTest
PASS  Tests\Feature\PublicContentModuleTest
PASS  Tests\Feature\PwaModuleTest
PASS  Tests\Feature\RoleAndAuthorizationTest
PASS  Tests\Feature\SecurityReportModuleTest

Tests:    90 passed (337 assertions)
Duration: 8.45s
Status:   100% OK
```

---

## Dokumentasi Teknis & Spesifikasi API

Dokumentasi arsitektur, RBAC, alur kerja dokumen, Core Web Vitals, Firebase, dan API lengkap tersedia di direktori `docs/`:

1. **[Optimasi Web Performa, Core Web Vitals, SEO & GEO (`docs/WEB_PERFORMANCE_SEO_GEO.md`)](docs/WEB_PERFORMANCE_SEO_GEO.md)**
   - Priority hints (`fetchpriority="high"`), eliminasi CLS/LFS via dimensi gambar eksplisit, mobile-first design, SEO meta tags, dan Schema.org JSON-LD Geolocation.
2. **[Panduan Arsitektur & Setup Firebase Versi Gratis / Spark Plan (`docs/FIREBASE_FREE_TIER_SETUP.md`)](docs/FIREBASE_FREE_TIER_SETUP.md)**
   - Strategi pemanfaatan Firebase Spark Plan $0/bulan untuk Web Push FCM unlimited, Cloud Storage 5GB, Cloud Firestore, Firebase Hosting, dan keamanan security rules.
3. **[Spesifikasi API Lengkap (`docs/API_SPEC.md`)](docs/API_SPEC.md)**
   - Daftar RESTful endpoints lengkap dengan method, request payload, query params, response schema, validation rules, dan error codes.
   - Meliputi Public APIs, Authenticated Warga APIs, dan Role-Secured Administrative APIs.
4. **[Matriks Akses & RBAC (`docs/RBAC.md`)](docs/RBAC.md)**
   - Normalisasi permission menggunakan *singular resource name* (`letter.read`, `letter.create`, `letter.verify`, `letter.approve`, `security.manage`, dll.).
   - Matriks perbandingan hak akses antara `WARGA`, `ADMIN`, `SEKRETARIS`, `KETUA_RT`, `BENDAHARA`, `PETUGAS_KEAMANAN`, dan `SUPERADMIN`.
   - Prinsip *Least Privilege* dan *Superadmin Bypass*.
5. **[Workflow & State Machine Persuratan (`docs/LETTER_WORKFLOW.md`)](docs/LETTER_WORKFLOW.md)**
   - Diagram status eksplisit: `draft` ➔ `submitted` ➔ `verified` ➔ `approved` ➔ `completed` (dan `rejected`).
   - Penomoran surat bebas tabrakan konkurensi berbasis `document_sequences` dengan `SELECT ... FOR UPDATE`.
   - Pencegahan race condition dan konflik versi via *Optimistic Locking* (`version` check, return `409 Conflict`).
6. **[Sistem Template Dokumen & Idempotensi (`docs/DOCUMENT_TEMPLATE.md`)](docs/DOCUMENT_TEMPLATE.md)**
   - 5 Blank document templates terstandarisasi (`resources/views/documents/templates/`):
     - `surat-keterangan.blade.php` (SK-UMUM)
     - `surat-kematian.blade.php` (SK-KEMATIAN)
     - `surat-pindah.blade.php` (SK-PINDAH)
     - `surat-keterangan-tidak-mampu.blade.php` (SKTM)
     - `laporan-keamanan.blade.php` (Security Incident Report)
   - Penyimpanan privat di `storage/app/private/` dengan hash SHA-256 dan token verifikasi publik tanpa bocor data pribadi (PII).
7. **[Arsitektur Enterprise Microservices & Client-Side Offloading (`docs/MICROSERVICES_ENTERPRISE_ARCHITECTURE.md`)](docs/MICROSERVICES_ENTERPRISE_ARCHITECTURE.md)**
   - Strategi enterprise: Komputasi rendering & penyimpanan file Word (`.docx`) dan PDF dialihkan ke **Client-Side (Front-End Compute)** untuk mencegah lonjakan CPU server, kehabisan memori (*OOM*), dan kemacetan rute API (*504 Gateway Timeout*).
   - Layanan frontend `CitizenDocumentExporter` (`public/js/citizen-document-exporter.js`) dengan verifikasi Anti-Tamper SHA-256 via Web Crypto API.
   - Caching dokumen di storage browser (LocalStorage / IndexedDB / PWA Cache) untuk akses offline dan unduh ulang instan tanpa beban server.
8. **[Spesifikasi OpenAPI 3.0 & Swagger UI Interaktif (`docs/openapi.yaml`)](docs/openapi.yaml)**
   - Akses antarmuka interaktif langsung via browser: **`/docs/api`** atau **`/api/documentation`**.
   - Raw OpenAPI Schema: **`/docs/openapi.yaml`**.
   - Penegakan tipe data ketat (*Strongly Typed Contract*): Enums (`UserRole`, `LetterStatus`, `SecurityReportSeverity`, `SecurityReportCategory`), Format `date-time` / `binary` file upload, regex pattern NIK 16 digit, skema respons terstruktur, dan otentikasi Sanctum Bearer token.

---

## Lisensi
Proyek ini dikembangkan di bawah lisensi [MIT License](LICENSE). Hak Cipta &copy; 2026 Pengurus Lingkungan RT & Pengembang Sistem.
