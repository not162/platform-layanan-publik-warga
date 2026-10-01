# 🏛️ Platform Layanan Publik Warga (Portal Digital RT/RW)

> Sistem Informasi Manajemen Administrasi, Pelayanan Surat Terpadu, Transparansi Kas Keuangan, dan Pengaduan Warga Berbasis Web & Progressive Web App (PWA).

---

## 📋 Daftar Isi
1. [Tentang Proyek](#-tentang-proyek)
2. [Tech Stack & Database](#-tech-stack--database)
3. [Daftar Akun & Kredensial Pengurus](#-daftar-akun--kredensial-pengurus)
4. [Arsitektur & Visualisasi Diagram](#-arsitektur--visualisasi-diagram)
   - [Use Case Diagram](#1-use-case-diagram)
   - [Entity Relationship Diagram (ERD)](#2-entity-relationship-diagram-erd)
   - [Logical Record Structure (LRS)](#3-logical-record-structure-lrs)
   - [Activity Diagram](#4-activity-diagram)
   - [Sequence Diagram](#5-sequence-diagram)
   - [Flowchart Algoritma Sistem](#6-flowchart-algoritma-sistem)
5. [Panduan Instalasi & Menjalankan Proyek](#-panduan-instalasi--menjalankan-proyek)
6. [Pengujian (Automated Testing)](#-pengujian-automated-testing)

---

## 📖 Tentang Proyek

**Platform Layanan Publik Warga** dirancang untuk mendigitalkan birokrasi di tingkat RT/RW secara terstruktur, transparan, dan aman. Platform ini memiliki fitur-fitur unggulan:
- **Verifikasi Warga Instan**: Pencocokan NIK berbasis satu arah (*One-Way Hashing SHA-256*) yang melindungi privasi NIK warga dari kebocoran data.
- **Birokrasi Surat Digital 2-Tier**: Verifikasi berkas administratif oleh **Sekretaris**, dilanjutkan dengan persetujuan/tanda tangan digital oleh **Ketua RT**.
- **Buku Kas Anti-Fraud (*Immutable Ledger*)**: Setiap transaksi kas yang telah dipublikasikan tidak dapat diubah atau dihapus sembarangan, melainkan harus melalui proses pembalik (*reversal ledger*) yang tercatat di audit log.
- **Real-Time Notification**: Terintegrasi dengan **Pusher Channels** dan **Web Push Notifications (PWA)** sehingga warga dan pengurus menerima notifikasi langsung di perangkat masing-masing.
- **Tampilan Ramah Pengguna**: Mengusung *Clean Light Theme Only*, tipografi Google Fonts Inter, ikonografi Lucide, dan interaktivitas Alpine.js dengan umpan balik animasi (efek *shake* kartu dan pesan validasi visual).

---

## 🛠️ Tech Stack & Database

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

## 👥 Daftar Akun & Kredensial Pengurus

Sistem telah dilengkapi data awal pengurus RT (**Seeders**) yang siap digunakan untuk login tanpa perlu membuat akun tiruan (*dummy*):

| Role Pengguna | Kategori | Email Login | Password Default | Wewenang & Hak Akses Fitur |
|---|---|---|---|---|
| **SUPERADMIN** | Sistem Administrator | `superadmin@warga.local` | `password` | **Akses Penuh Tanpa Batas**:<br>• Manajemen sistem, permission, role user.<br>• Akses semua modul data warga, surat, keuangan, dan audit trail. |
| **KETUA_RT** | Pengurus Inti | `ketua_rt@warga.local` | `password` | **Persetujuan Akhir & Supervisi**:<br>• Pengesahan/Persetujuan akhir Surat Pengantar (`letter.approve`).<br>• Monitoring transparansi kas & tindak lanjut aduan warga.<br>• Supervisi kependudukan lingkungan RT. |
| **SEKRETARIS** | Pengurus Administrasi | `sekretaris@warga.local` | `password` | **Administrasi & Pengumuman**:<br>• Verifikasi kelengkapan berkas surat warga (`letter.verify`).<br>• Pengelolaan master data kependudukan RT (`citizens.manage`).<br>• Buat, edit, dan publikasi agenda pengumuman (`announcements.manage`). |
| **BENDAHARA** | Pengurus Keuangan | `bendahara@warga.local` | `password` | **Tata Kelola Keuangan Kas** (`finance.manage`):<br>• Pencatatan kas masuk (iuran warga) & kas keluar.<br>• Publikasi laporan transparansi kas ke portal publik.<br>• Reversal audit transaksi kas jika ada koreksi data. |
| **WARGA** | Pengguna Publik | *(Sesuai registrasi warga)* | *(Ditentukan warga)* | **Layanan Mandiri Warga**:<br>• Mengajukan surat pengantar mandiri.<br>• Tracking status surat & verifikasi keaslian surat via token.<br>• Mengirimkan aduan/keluhan (bisa anonim).<br>• Melihat laporan transparansi kas & pengumuman RT. |

---

## 📊 Arsitektur & Visualisasi Diagram

### 1. Use Case Diagram
Diagram use case memetakan interaksi seluruh aktor (Warga, Sekretaris, Bendahara, Ketua RT, dan Superadmin) dengan fitur sistem.

```mermaid
flowchart LR
    Warga((fa:fa-user Warga))
    Sekretaris((fa:fa-id-badge Sekretaris))
    Bendahara((fa:fa-calculator Bendahara))
    KetuaRT((fa:fa-user-tie Ketua RT))
    Superadmin((fa:fa-crown Superadmin))

    subgraph Portal Layanan Publik Warga
        UC1[Daftar Akun & Verifikasi NIK]
        UC2[Login & Manajemen Profil]
        UC3[Pengajuan Surat Pengantar]
        UC4[Lacak & Verifikasi Surat]
        UC5[Kirim Keluhan / Aspirasi]
        UC6[Lihat Kas Transparan & Pengumuman]
        
        UC7[Kelola Master Data Warga]
        UC8[Verifikasi Berkas Surat]
        UC9[Kelola Pengumuman & Agenda RT]
        
        UC10[Kelola Transaksi Kas RT]
        UC11[Reversal / Koreksi Kas]
        UC12[Publikasi Laporan Kas]
        
        UC13[Persetujuan Akhir Surat]
        UC14[Tindak Lanjut Aduan Warga]
        
        UC15[Manajemen Pengguna & Izin Akses]
        UC16[Audit Log & Sistem Pengaturan]
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
Diagram relasi entitas basis data MySQL yang memperlihatkan struktur tabel, tipe data, serta kardinalitas relasi (*one-to-one*, *one-to-many*).

```mermaid
erDiagram
    FAMILY_CARDS ||--o{ CITIZENS : "memiliki anggota"
    CITIZENS ||--o| USERS : "terhubung ke akun"
    USERS ||--o{ ADMIN_PERMISSIONS : "memiliki hak akses"
    USERS ||--o{ PUSH_SUBSCRIPTIONS : "memiliki perangkat push"
    USERS ||--o{ LETTERS : "mengajukan surat"
    LETTER_TYPES ||--o{ LETTERS : "kategori surat"
    LETTERS ||--o{ LETTER_ATTACHMENTS : "memiliki lampiran"
    USERS ||--o{ COMPLAINTS : "mengirim keluhan"
    USERS ||--o{ FINANCE_TRANSACTIONS : "mencatat transaksi kas"
    USERS ||--o{ AUDIT_LOGS : "memicu aktivitas sistem"

    FAMILY_CARDS {
        bigint id PK
        varchar nomor_kk UK
        varchar kepala_keluarga_name
        text alamat
        varchar rt
        varchar rw
        varchar kode_pos
        datetime created_at
    }

    CITIZENS {
        bigint id PK
        bigint family_card_id FK
        bigint user_id FK
        varchar nik_hash UK "SHA-256 Hash"
        varchar full_name
        varchar birth_place
        date birth_date
        varchar gender
        text address
        varchar phone_number
        varchar occupation
        varchar religion
        varchar status_warga
        int version
        datetime created_at
    }

    USERS {
        bigint id PK
        bigint warga_id FK
        varchar name
        varchar email UK
        varchar password
        enum role "SUPERADMIN,ADMIN,KETUA_RT,BENDAHARA,SEKRETARIS,WARGA"
        boolean is_active
        datetime last_login_at
        datetime created_at
    }

    ADMIN_PERMISSIONS {
        bigint id PK
        bigint user_id FK
        varchar permission
        datetime created_at
    }

    LETTER_TYPES {
        bigint id PK
        varchar code UK
        varchar name
        text description
        json required_documents
        boolean is_active
        datetime created_at
    }

    LETTERS {
        bigint id PK
        varchar ticket_number UK
        varchar tracking_token UK
        bigint user_id FK
        bigint citizen_id FK
        bigint letter_type_id FK
        text purpose
        enum status "draft,submitted,verified,approved,rejected"
        text remarks
        text rejection_reason
        bigint verified_by FK
        bigint approved_by FK
        datetime verified_at
        datetime approved_at
        int version
        datetime created_at
    }

    LETTER_ATTACHMENTS {
        bigint id PK
        bigint letter_id FK
        varchar file_path
        varchar file_name
        varchar file_type
        int file_size
        datetime created_at
    }

    FINANCE_TRANSACTIONS {
        bigint id PK
        varchar transaction_number UK
        enum type "income,expense"
        varchar category
        decimal amount
        text description
        date transaction_date
        enum status "draft,published,reversed"
        text reversal_reason
        bigint recorded_by FK
        int version
        datetime created_at
    }

    COMPLAINTS {
        bigint id PK
        varchar ticket_number UK
        varchar tracking_token UK
        bigint user_id FK
        bigint citizen_id FK
        varchar category
        varchar title
        text description
        boolean is_anonymous
        enum status "submitted,in_review,in_progress,resolved,rejected"
        text response
        bigint handled_by FK
        int version
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
        bigint user_id FK
        varchar action
        varchar entity_type
        bigint entity_id
        json old_values
        json new_values
        datetime created_at
    }

    PUSH_SUBSCRIPTIONS {
        bigint id PK
        bigint user_id FK
        text endpoint
        varchar public_key
        varchar auth_token
        datetime created_at
    }
```

---

### 3. Logical Record Structure (LRS)
Representasi relasional tabel skema database dengan relasi kunci utama (*Primary Key*) dan kunci tamu (*Foreign Key*):

```
┌──────────────────────────────────────────────┐
│                 FAMILY_CARDS                 │
├──────────────────────────────────────────────┤
│ PK  id                                       │
│     nomor_kk (Unique)                        │
│     kepala_keluarga_name, alamat, rt, rw     │
└──────────────────────┬───────────────────────┘
                       │ 1:N
┌──────────────────────▼───────────────────────┐          1:1          ┌──────────────────────────────────────────────┐
│                   CITIZENS                   ├───────────────────────►│                    USERS                     │
├──────────────────────────────────────────────┤                       ├──────────────────────────────────────────────┤
│ PK  id                                       │                       │ PK  id                                       │
│ FK  family_card_id                           │                       │ FK  warga_id ────────────────────────────────┤ (balik)
│ FK  user_id ─────────────────────────────────┼───────────────────────┤     name, email (Unique), password           │
│     nik_hash (Unique, SHA-256), full_name    │                       │     role (SUPERADMIN..WARGA), is_active      │
│     gender, address, phone_number, version   │                       └───────┬──────────────┬──────────────┬────────┘
└──────────────────────┬───────────────────────┘                               │ 1:N          │ 1:N          │ 1:N
                       │ 1:N                                                   │              │              │
                       ▼                                                       ▼              ▼              ▼
┌──────────────────────────────────────────────┐                       ┌──────────────┐┌──────────────┐┌──────────────┐
│                   LETTERS                    │                       │ADMIN_PERMISS ││AUDIT_LOGS    ││PUSH_SUBSCRIPT│
├──────────────────────────────────────────────┤                       ├──────────────┤├──────────────┤├──────────────┤
│ PK  id                                       │                       │PK id         ││PK id         ││PK id         │
│     ticket_number (Unique), tracking_token   │                       │FK user_id    ││FK user_id    ││FK user_id    │
│ FK  user_id                                  │                       │   permission ││   action...  ││   endpoint.. │
│ FK  citizen_id                               │                       └──────────────┘└──────────────┘└──────────────┘
│ FK  letter_type_id ────────┐                 │
│     status (submitted..approved), version    │                       ┌──────────────────────────────────────────────┐
│ FK  verified_by, FK approved_by              │                       │             FINANCE_TRANSACTIONS             │
└──────────────────────┬─────┴─────────────────┘                       ├──────────────────────────────────────────────┤
                       │ 1:N                                           │ PK  id                                       │
                       ▼                                               │     transaction_number (Unique)              │
┌──────────────────────────────────────────────┐                       │     type (income/expense), amount, status    │
│              LETTER_ATTACHMENTS              │                       │ FK  recorded_by (Users.id)                   │
├──────────────────────────────────────────────┤                       │     reversal_reason, version                 │
│ PK  id                                       │                       └──────────────────────────────────────────────┘
│ FK  letter_id                                │
│     file_path, file_name, file_size          │
└──────────────────────────────────────────────┘
```

---

### 4. Activity Diagram

#### A. Alur Pengajuan dan Persetujuan Surat Pengantar Warga
```mermaid
flowchart TD
    Start([Mulai: Warga Butuh Surat]) --> A1[Warga Login ke Portal]
    A1 --> A2[Pilih Jenis Surat & Isi Keperluan]
    A2 --> A3[Upload Dokumen Persyaratan KTP/KK]
    A3 --> A4[Klik Kirim Permohonan]
    A4 --> A5[(Sistem Simpan Status: SUBMITTED)]
    A5 --> A6[Generate Nomor Tiket & Token Pelacakan]
    A6 --> A7[Notifikasi Realtime ke Dashboard Sekretaris]
    
    A7 --> B1[Sekretaris Buka Menu Verifikasi Surat]
    B1 --> B2{Berkas Lengkap & Sesuai?}
    
    B2 -- Tidak Sesuai --> B3[Sekretaris Isi Alasan Penolakan]
    B3 --> B4[(Status Diperbarui: REJECTED)]
    B4 --> B5[Kirim Notifikasi Alasan ke Warga]
    B5 --> EndTolak([Selesai: Surat Ditolak])
    
    B2 -- Lengkap --> B6[Sekretaris Klik Verifikasi Berkas]
    B6 --> B7[(Status Diperbarui: VERIFIED)]
    B7 --> B8[Notifikasi Otomatis ke Akun Ketua RT]
    
    B8 --> C1[Ketua RT Buka Menu Persetujuan Surat]
    C1 --> C2{Disetujui Ketua RT?}
    
    C2 -- Ditolak --> C3[Ketua RT Masukkan Alasan Penolakan]
    C3 --> B4
    
    C2 -- Disetujui --> C4[Ketua RT Beri Tanda Tangan & Setujui]
    C4 --> C5[(Status Diperbarui: APPROVED)]
    C5 --> C6[Generate QR Code Keaslian & PDF Surat]
    C6 --> C7[Warga Dapat Mengunduh Surat Resmi]
    C7 --> EndSukses([Selesai: Surat Siap Digunakan])
```

#### B. Alur Pencatatan & Pembatalan Transaksi Kas RT (Immutable Reversal)
```mermaid
flowchart TD
    Start([Mulai: Transaksi Kas RT]) --> K1[Bendahara Input Pemasukan / Pengeluaran]
    K1 --> K2[(Sistem Simpan Status: DRAFT)]
    K2 --> K3{Perlu Publikasi Transparansi?}
    
    K3 -- Belum --> K4[Bisa Diedit / Dihapus oleh Bendahara]
    K4 --> EndDraft([Tetap sebagai Draft])
    
    K3 -- Ya, Publikasikan --> K5[Bendahara Klik Publikasikan Laporan]
    K5 --> K6[(Status Diperbarui: PUBLISHED)]
    K6 --> K7[Cache Laporan Publik Dibersihkan Otomatis]
    K7 --> K8[Laporan Tampil Real-time di Portal Warga]
    
    K8 --> K9{Ditemukan Kesalahan Angka di Kemudian Hari?}
    K9 -- Tidak Ada --> EndPublikasi([Selesai: Kas Transparan Selesai])
    
    K9 -- Ada Kesalahan Input --> K10[Sistem Tolak Hard Delete / Update Langsung]
    K10 --> K11[Bendahara Ajukan Transaksi Reversal Pembalik]
    K11 --> K12[Wajib Mengisi Alasan Pembatalan & Optimistic Lock Check]
    K12 --> K13[(Transaksi Asal Ditandai: REVERSED)]
    K13 --> K14[(Sistem Buat Transaksi Pembalik Baru Secara Otomatis)]
    K14 --> K15[(Catat Riwayat Lengkap di Tabel Audit Logs)]
    K15 --> EndReversal([Selesai: Rekonsiliasi Kas Bersih Sesuai Standar Akuntansi])
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
    Start([Mulai: Input NIK]) --> In[Warga Input 16 Digit NIK]
    In --> V1{Apakah NIK tepat 16 digit angka?}
    V1 -- Tidak --> E1[Tolak: Tampilkan Error Format NIK]
    E1 --> Shake[Jalankan Efek Shake Input]
    Shake --> End1([Selesai])
    
    V1 -- Ya --> Hash[Lakukan Enkripsi One-Way: hash('sha256', NIK)]
    Hash --> Query[(Cari di tabel citizens WHERE nik_hash = hash)]
    Query --> Check{Ditemukan?}
    
    Check -- Tidak --> E2[Tolak: NIK Belum Terdaftar di Sensus RT]
    E2 --> Shake
    
    Check -- Ya --> Linked{user_id != null?}
    Linked -- Ya --> E3[Tolak: Akun NIK Ini Sudah Aktif]
    E3 --> Shake
    
    Linked -- Tidak --> Link[Hubungkan user_id ke baris citizen]
    Link --> Create[Buat Akun User Baru dengan Role: WARGA]
    Create --> Event[Pusher Broadcast: CitizenRegistered]
    Event --> Token[Generate Personal Sanctum Token]
    Token --> Sukses([Verifikasi Sukses: Masuk ke Dashboard])
```

#### B. Algoritma Optimistic Locking & Audit Trail Pembukuan Kas RT
```mermaid
flowchart TD
    Start([Mulai: Update Transaksi Kas]) --> Q1[Ambil Data Transaksi + Input Versi Saat Ini]
    Q1 --> Lock[Lock Baris Transaksi di Database]
    Lock --> C1{status == 'published'?}
    
    C1 -- Ya, Mau Dihapus Langsung --> RejectDel[Larangan: Data Kas Terpublikasi Tidak Boleh Hard Delete]
    RejectDel --> ReversalReq[Arahkan Menggunakan Alur Reversal Transaksi]
    
    C1 -- Update / Reversal --> C2{version_input == db.version?}
    C2 -- Tidak Sama --> Conflict[Gagal: 409 Conflict. Data telah diubah pengguna lain secara simultan]
    
    C2 -- Sama Sesuai --> Exec[Lakukan Operasi Reversal / Perubahan Data]
    Exec --> IncVersion[Naikkan Version: db.version = db.version + 1]
    IncVersion --> Audit[(Tulis Riwayat di Tabel audit_logs: Old Values vs New Values)]
    Audit --> CacheFlush[Hapus Cache Ringkasan Kas Publik]
    CacheFlush --> Commit[(Database Commit Transaction)]
    Commit --> Done([Selesai: Transaksi Bersih & Sesuai Mutasi Kas])
```

---

## 🚀 Panduan Instalasi & Menjalankan Proyek

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

## 🧪 Pengujian (Automated Testing)

Aplikasi memiliki rangkaian pengujian unit dan fitur (*Feature Tests*) dengan cakupan menyeluruh untuk menjamin keandalan sistem:

```bash
# Menjalankan seluruh pengujian:
php artisan test
```

### Hasil Ringkasan Pengujian:
```text
PASS  Tests\Feature\CitizenModuleTest
PASS  Tests\Feature\ComplaintModuleTest
PASS  Tests\Feature\ExampleTest
PASS  Tests\Feature\FinanceModuleTest
PASS  Tests\Feature\LetterModuleTest
PASS  Tests\Feature\NotificationModuleTest
PASS  Tests\Feature\PublicContentModuleTest
PASS  Tests\Feature\PwaModuleTest
PASS  Tests\Feature\RoleAndAuthorizationTest

Tests:    53 passed (184 assertions)
Duration: 8.36s
Status:   100% OK
```

---

## 📄 Lisensi
Proyek ini dikembangkan di bawah lisensi [MIT License](LICENSE). Hak Cipta &copy; 2026 Pengurus Lingkungan RT & Pengembang Sistem.
