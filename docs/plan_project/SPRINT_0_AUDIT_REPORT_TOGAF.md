# LAPORAN AUDIT AWAL DAN VISI ARSITEKTUR (SPRINT 0)
## Penyelarasan TOGAF ADM: Preliminary Phase & Phase A (Architecture Vision)
**Platform Layanan Publik Warga — Lingkungan MySQL Laragon**  
**Tanggal:** 16 Oktober 2026  
**Status Audit:** **PASS** (Lolos Verifikasi Lingkungan & Ruang Lingkup)

---

## 1. Visi Arsitektur (Architecture Vision)

> **Visi Sistem:**  
> Membangun platform layanan publik digital tingkat rukun tetangga (RT/RW) yang andal, transparan, dan berdaulat data melalui arsitektur modular terpadu berbasis Laravel dan MySQL lokal Laragon; memfasilitasi birokrasi persuratan 2-tier nirkertas, pelaporan keluhan warga yang responsif dengan proteksi privasi anonim, akuntabilitas pembukuan iuran dan kas kasir tanpa kebocoran transaksi, serta pembaruan status layar secara near-real-time yang aman dan dapat diaudit penuh sesuai tata kelola TOGAF ADM.

---

## 2. Pemangku Kepentingan (Stakeholders) & Wewenang Keputusan

| Peran Pemangku Kepentingan | Entitas / Aktor | Tanggung Jawab & Wewenang |
| :--- | :--- | :--- |
| **Pemilik Proyek (Project Owner)** | Pengurus RT/RW (Ketua RT) | Memutuskan proses bisnis final, menyetujui perubahan scope, pengesahan surat resmi, dan penerimaan UAT. |
| **Pengembang Utama (Developer)** | Tim Pengembang Laravel | Implementasi teknis kode, perancangan basis data, eksekusi migrasi, pemeliharaan tes otomatis, dan dokumentasi arsitektur. |
| **Pengelola Operasional Harian** | Sekretaris & Bendahara RT | Verifikasi berkas permohonan surat (Sekretaris), pembukuan kas/iuran (Bendahara), dan validasi kuitansi warga. |
| **Petugas Keamanan** | Hansip / Keamanan Lingkungan | Penanganan laporan insiden keamanan lapangan dan koordinasi darurat. |
| **Pengguna Layanan (Warga)** | Warga Lingkungan RT | Pengajuan surat, pembayaran iuran kas, penyampaian pengaduan/keluhan, dan pemantauan transparansi kas. |
| **Penguji / Perwakilan Pengguna** | Tim Penguji Teknis Mandiri | Menjalankan skenario uji fungsional, black-box testing, dan pencatatan audit gerbang sprint tanpa rekayasa klaim. |

---

## 3. Prinsip-Prinsip Arsitektur TOGAF (Architecture Principles)

1. **Prinsip 1: Pemisahan Identitas Akun dan Data Kependudukan**  
   Akun sistem (`users.id`) dan data kependudukan (`citizens.id`) memiliki daur hidup berbeda. Relasi tunggal ditegakkan melalui `citizens.user_id` (Unique, Nullable) sebagai *single source of truth*, tanpa ketergantungan ganda pada `users.warga_id`.
2. **Prinsip 2: Otorisasi Berbasis Peran dan Kepemilikan Data (RBAC & Tenant Isolation)**  
   Hak akses melekat pada peran dan kepemilikan data. Warga A dilarang keras melihat atau memodifikasi draf surat, keluhan, dan data personal Warga B (HTTP 403 Forbidden).
3. **Prinsip 3: Integritas dan Keterlacakan Audit (Auditability & Non-Repudiation)**  
   Setiap transaksi bernilai hukum (surat resmi bertanda tangan digital) dan keuangan (kas masuk/keluar) dicatat dalam `audit_logs` dan tidak boleh dihapus secara diam-diam (*hard delete*).
4. **Prinsip 4: Konsistensi Pembukuan dan Imutabilitas Kas (Financial Ledger Integrity)**  
   Setiap penerimaan pembayaran iuran warga (`due_payments`) wajib terhubung ke transaksi kas umum (`finance_transactions`) dengan relasi *foreign key restrict*. Pembatalan transaksi wajib melalui mekanisme *reversal transaction*.
5. **Prinsip 5: Keselarasan Bahasa dan Pemodelan (Ubiquitous Language)**  
   Seluruh istilah antarmuka, status, tabel, endpoint API, dan diagram UML wajib selaras menggunakan istilah baku sederhana (*Warga, Kartu Keluarga, Surat, Pengaduan, Iuran, Laporan*).

---

## 4. Verifikasi Aktual Lingkungan Eksekusi

| Parameter Komponen | Spesifikasi Dokumen Lama | Hasil Pemeriksaan Nyata (Aktual) | Status Verifikasi |
| :--- | :--- | :--- | :---: |
| **Sistem Operasi** | Windows / Linux | Windows 11 (Laragon Architecture) | **TERVERIFIKASI** |
| **PHP Runtime** | PHP ^8.2 / ^8.3 | **PHP 8.4.12** (`C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe`) | **TERVERIFIKASI** |
| **Composer Package Manager** | Composer 2.x | **Composer 2.9.4** | **TERVERIFIKASI** |
| **Laravel Framework** | Laravel 11 (di SRS lama) | **Laravel Framework 13.34.0** (dari `composer.lock` & `php artisan --version`) | **TERVERIFIKASI** |
| **Database Server** | MySQL / SQLite | **MySQL 8.4.3** (Laragon Port 3306, user `root`, password empty) | **TERVERIFIKASI** |
| **Database Aktif Lokal** | `database.sqlite` | **`layanan_warga`** (Terkoneksi via PDO & didaftarkan pada phpMyAdmin) | **TERVERIFIKASI** |
| **Alat Administrasi DB** | phpMyAdmin | phpMyAdmin Laragon (URL: `http://localhost/phpmyadmin`) | **TERVERIFIKASI** |
| **Status Migrasi MySQL** | Belum Dijalankan | **35 File Migrasi Berhasil 100%** di MySQL `layanan_warga` | **TERVERIFIKASI** |
| **Status Seeder Awal** | - | **8 Akun Pengurus & 4 Jenis Surat** Tersemai via `db:seed` | **TERVERIFIKASI** |
| **Cadangan Data Lama** | - | `database/backup_before_mysql.sqlite` (Tersimpan aman) | **TERVERIFIKASI** |

---

## 5. Batas Lingkup Rilis Pertama (Scope Baseline)

Lingkup rilis pertama yang disepakati untuk siklus 6 Sprint (Waterfall ber-timebox):
1. **Modul Akun, Warga, dan Kartu Keluarga**:
   - Pendaftaran mandiri warga baru, verifikasi berkas oleh pengurus RT.
   - Manajemen Kartu Keluarga dengan kontrol konkurensi optimistik (`version`).
   - Enkripsi NIK & No KK (AES-256-CBC) dan Blind Index SHA-256 (`nik_hash`, `no_kk_hash`).
2. **Modul Pelayanan Persuratan (Surat Pengantar)**:
   - Pengajuan draf surat, unggah lampiran syarat ke penyimpanan lokal privat.
   - Alur verifikasi 2-tier: Sekretaris (Verifikasi) -> Ketua RT (Persetujuan & TTD digital).
   - Penerbitan surat PDF/Word resmi berstempel QR Code dan cryptographic document hash.
   - Validasi publik melalui token verifikasi tanpa login.
3. **Modul Pengaduan Warga (Aspirasi & Keluhan Lingkungan)**:
   - Pengajuan keluhan lingkungan dengan bukti foto kamera.
   - Dukungan opsi pelapor anonim (*anonymous complaint*) yang menyembunyikan identitas warga.
4. **Modul Keuangan & Iuran Warga**:
   - Penetapan tagihan iuran bulanan warga (`resident_dues`).
   - Pencatatan pembayaran kas (`due_payments`) yang otomatis terhubung ke jurnal kas umum (`finance_transactions`).
   - Pembuatan laporan berkala dan transparansi kas publik.
5. **Modul Keamanan Lingkungan & Informasi Publik**:
   - Laporan insiden ronda dan kontak darurat RT/RW.
   - Warta pengumuman, agenda kegiatan, dan susunan kepengurusan RT.
6. **Audit & Log Sistem**:
   - Pencatatan otomatis setiap aksi krusial ke tabel `audit_logs`.

---

## 6. Tabel 1: Analisis Kesenjangan Arsitektur (Baseline vs Target Gap Analysis)

| Domain TOGAF | Kondisi Awal (Baseline) | Target Arsitektur | Kesenjangan (Gap) | Tindakan Korektif Terpilih |
| :--- | :--- | :--- | :--- | :--- |
| **Bisnis (Phase B)** | Matriks RBAC menyebut tamu bisa mengirim aduan, namun rute API mewajibkan login. | Pengaduan hanya diajukan oleh pengguna terotentikasi; opsi anonim menyembunyikan identitas dari publik. | Kontradiksi dokumen SRS dan implementasi rute. | Selaraskan SRS dan RBAC Matrix: aduan wajib login, flag `is_anonymous` aktif. |
| **Data (Phase C)** | Redundansi `users.warga_id` dan `citizens.user_id`; `due_payments` tidak memiliki FK ke kas; tabel bernama `jenis_surat` tetapi digambar `letter_types`. | `citizens.user_id` single source of truth; `due_payments.finance_transaction_id` restrict FK; ERD memakai nama fisik `jenis_surat`. | Inkonsistensi kardinalitas data dan risiko orphan/cascade data keuangan. | Buat migrasi lanjutan, hapus ketergantungan `warga_id`, hubungkan `due_payments` ke `finance_transactions`. |
| **Aplikasi (Phase C)** | State machine surat berisiko di-rollback ke draf setelah selesai; channel broadcast `CitizenRegistered` masih berupa channel publik (`admin-channel`). | Surat `completed` berstatus final dan menolak draf; broadcast menggunakan `PrivateChannel` berotorisasi ketat. | Kebocoran data registrasi warga baru dan rusaknya keabsahan surat resmi. | Pasang proteksi HTTP 409 pada controller/service surat; ubah event broadcast ke `PrivateChannel` dengan callback auth di `routes/channels.php`. |
| **Teknologi (Phase D)** | Dokumen menyebut Laravel 11 di SQLite; Pusher eksternal berstatus blocked tanpa kunci API. | Laravel 13.34.0 di PHP 8.4.12 dan MySQL 8.4.3 Laragon; fallback broadcaster yang aman. | Inkonsistensi versi framework di dokumentasi; kegagalan push real-time jika Pusher offline. | Perbarui dokumen ke Laravel 13/MySQL Laragon; terapkan near-real-time polling dan graceful fallback. |

---

## 7. Tabel 2: Rekaman Keputusan Arsitektur (Architecture Decision Records - ADR)

| ID Keputusan | Permasalahan Arsitektur | Pilihan Solusi | Alasan & Rasionalisasi | Dampak & Implikasi | Penanggung Jawab | Tanggal |
| :--- | :--- | :--- | :--- | :--- | :--- | :---: |
| **ADR-01** | Dual foreign key relasi akun–warga (`users.warga_id` vs `citizens.user_id`). | Menetapkan `citizens.user_id` sebagai *single source of truth*. | Mengikuti kaidah DDD di mana akun pengguna adalah induk otentikasi, warga adalah profil kependudukan. | Relasi `User::citizen()` menggunakan `citizens.user_id`. Kolom lama `users.warga_id` tidak lagi dipakai. | Tim Pengembang | 16 Okt 2026 |
| **ADR-02** | Nama tabel jenis surat dalam skema fisik database vs dokumentasi. | Mempertahankan nama tabel fisik database `jenis_surat`. | Tabel migrasi fisik bernama `jenis_surat`. Menghindari migrasi rename tabel yang berisiko data loss. | Model Eloquent memakai `protected $table = 'jenis_surat';`. ERD dan UML mencantumkan `jenis_surat`. | Tim Pengembang | 16 Okt 2026 |
| **ADR-03** | Pembukuan pembayaran iuran warga tanpa referensi jurnal kas. | Menambahkan kolom `due_payments.finance_transaction_id` dengan foreign key restrict. | Mencegah transaksi kas yatim (*orphan records*) dan menjamin prinsip double-entry pembukuan RT. | Migrasi `2026_10_16_083242` ditambahkan. Kas tidak dapat dihapus jika masih ada rujukan kuitansi iuran. | Tim Pengembang | 16 Okt 2026 |
| **ADR-04** | Kebocoran data pada siaran registrasi warga real-time. | Mengalihkan `CitizenRegistered` dari `Channel` publik ke `PrivateChannel('admin-channel')`. | NIK dan nama warga yang mendaftar adalah informasi rahasia yang hanya boleh diterima pengurus RT berwenang. | Wajib menyertakan token autentikasi saat berlangganan WebSocket; otorisasi diverifikasi di `routes/channels.php`. | Tim Pengembang | 16 Okt 2026 |
| **ADR-05** | Inkonsistensi string izin permission RBAC pada rute keuangan. | Menyediakan enum case dan middleware untuk `manage` dan `create`. | Mencegah penolakan akses `403 Forbidden` pada Bendahara saat memproses setoran iuran dan pembelian barang. | Enum `Permission` dan middleware rute mendukung kedua format secara harmonis. | Tim Pengembang | 16 Okt 2026 |

---

## 8. Tabel 3: Matriks Keterlacakan Kebutuhan (Requirements Traceability Matrix - RTM)

| ID Kebutuhan | Aktor Utama | Fitur Layanan | Tabel Basis Data | Endpoint API Utama | Metode Pengujian | Bukti Verifikasi |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **REQ-AUTH-01** | Warga | Pendaftaran Mandiri & Sesi | `users`, `citizens` | `POST /api/v1/register`, `/login` | Feature Test | `AuthSelfRegistrationTest.php` |
| **REQ-CIT-01** | Admin / Pengurus | Verifikasi & Profil Warga | `citizens`, `family_cards` | `GET/POST /api/v1/admin/citizens` | Feature Test & Policy | `Sprint1CitizenFamilyCardTest.php` |
| **REQ-KK-01** | Pengurus RT | Tata Kelola Kartu Keluarga | `family_cards` | `GET/POST /api/v1/admin/family-cards` | Optimistic Lock Test | `FamilyCardController.php` |
| **REQ-SRT-01** | Warga | Pengajuan Surat Pengantar | `letters`, `jenis_surat` | `POST /api/v1/letters` | Feature Test | `LetterModuleTest.php` |
| **REQ-SRT-02** | Sekretaris & RT | Verifikasi & Pengesahan Surat | `letters`, `audit_logs` | `PATCH /api/v1/admin/letters/{id}/...` | State Machine Test | `Sprint2LetterComplaintSecurityTest.php` |
| **REQ-ADU-01** | Warga (Anonim) | Pengaduan Keluhan Fasilitas | `complaints` | `POST /api/v1/complaints` | Privacy Isolation Test | `ComplaintModuleTest.php` |
| **REQ-KEU-01** | Warga & Bendahara | Tagihan & Setoran Iuran | `resident_dues`, `due_payments` | `POST /api/v1/admin/finance/payments` | Ledger Linkage Test | `Sprint3FinanceDueLedgerTest.php` |
| **REQ-KEU-02** | Publik / Warga | Transparansi Buku Kas RT | `finance_transactions` | `GET /api/v1/finance/summary` | Read Aggregate Test | `FinanceLedgerSprint2Test.php` |
| **REQ-NOTIF-01** | Pengurus RT | Siaran Notifikasi Privat | `push_subscriptions` | WebSocket `private-admin-channel` | Channel Auth Test | `Sprint4BroadcastingNotificationTest.php` |
| **REQ-INT-01** | Semua Aktor | Integritas Hubungan Sistem | Semua 33 Tabel MySQL | Seluruh Rute API v1 | Full Regression Test | `Sprint5DocumentationAndIntegrityTest.php` |

---

## 9. Tabel 4: Gerbang Audit Sprint 0 (Sprint Audit Gate)

| Komponen Audit | Kriteria Kelulusan | Bukti Pemeriksaan Nyata | Catatan & Masalah Tersisa | Status Gerbang |
| :--- | :--- | :--- | :--- | :---: |
| **Sumber Kode & Versi** | Versi PHP, Composer, dan Framework terdata pasti dari sistem aktual. | `php -v` (PHP 8.4.12), `composer -V` (2.9.4), `php artisan --version` (Laravel 13.34.0). | Dokumentasi lama yang menyebut Laravel 11 telah ditandai untuk diselaraskan. | **PASS** |
| **Koneksi Database MySQL** | Laravel terhubung ke MySQL Laragon port 3306 pada basis data `layanan_warga`. | `php artisan db:show` sukses membaca MySQL 8.4.3 Laragon; skrip PDO koneksi terkonfirmasi. | Database `layanan_warga` dapat diakses via phpMyAdmin lokal. | **PASS** |
| **Migrasi & Skema Database** | Seluruh 35 berkas migrasi berhasil dieksekusi tanpa error di MySQL. | `php artisan migrate:status` melaporkan 35 migrasi berstatus *Ran*. 33 tabel fisik terbentuk sempurna. | Skema relasi mencakup `citizens.user_id` dan `due_payments.finance_transaction_id`. | **PASS** |
| **Pencadangan Data** | Data cadangan SQLite sebelum migrasi tersimpan aman. | Berkas cadangan `database/backup_before_mysql.sqlite` tersedia secara fisik. | Data demo awal berhasil disemai via `db:seed`. | **PASS** |
| **Ruang Lingkup & Penanggung Jawab** | Visi arsitektur, batasan rilis, dan pemangku kepentingan terdokumentasi formal. | Bagian 1, 2, dan 5 dokumen ini telah dibekukan. | Siap melangkah ke Sprint 1 (Proses Layanan & Hak Akses). | **PASS** |

### Keputusan Gerbang Sprint 0: **PASS**
*Kriteria wajib terpenuhi seluruhnya. Eksekusi dilanjutkan ke Sprint 1 (Phase B — Business Architecture).*
