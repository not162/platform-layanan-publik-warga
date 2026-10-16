# LAPORAN AUDIT RANCANGAN SISTEM & PERENCANAAN MIGRASI (SPRINT 2)
## Penyelarasan TOGAF ADM: Phase C (Data & Application), Phase D (Technology), Phase E (Opportunities & Solutions), Phase F (Migration Planning)
**Platform Layanan Publik Warga — MySQL di Lingkungan Laragon**  
**Tanggal:** 16 Oktober 2026  
**Status Audit Gerbang Sprint 2:** **PASS** (Lolos Verifikasi Rancangan Lengkap & Kesiapan Implementasi)

---

## 1. Ringkasan Eksekutif & Tujuan Sprint 2

Sprint 2 berfokus pada penerjemahan kebutuhan bisnis (fase B) menjadi **cetak biru rancangan teknis yang implementable**, mencakup pemodelan basis data relasional (ERD & LRS), pemodelan sistem UML modular (Use Case, Activity, Class, Sequence), katalog API v1 komprehensif, arsitektur teknologi infrastruktur (Laragon MySQL vs Rilis), serta rencana bertahap migrasi data dan prosedur pemulihan (*rollback plan*).

Prinsip utama yang ditegakkan pada Sprint 2:
1. **Integritas Relasi Basis Data:** Menetapkan `citizens.user_id` (Unique, Nullable) sebagai *single source of truth* akun-warga dan mempertahankan `citizens.family_card_id` ke `family_cards.id`.
2. **Kesesuaian Nama Fisik:** Menggunakan nama tabel aktual `jenis_surat` dan kolom relasi `letters.jenis_surat_id` tanpa menggunakan istilah rekaan di dokumentasi.
3. **Akuntabilitas Keuangan (*Anti-Fraud Ledger*):** Menegakkan relasi `due_payments.finance_transaction_id` dengan aturan hapus `RESTRICT`, melarang manipulasi data yang sudah diterbitkan, serta menerapkan jurnal pembalik (*reversal*).
4. **Pemisahan Aktor UML:** Menempatkan pengurus (*Superadmin, Admin, RT, RW, Bendahara, Sekretaris, Keamanan*) di sisi kiri dan pengguna (*Warga Tetap, Warga Baru, Publik/Pemeriksa*) di sisi kanan di luar batas sistem, dengan use case berbentuk elips di dalam batas sistem.

---

## 2. Tabel 1: Analisis Kesenjangan Arsitektur 4 Domain (Baseline vs Target Gap Analysis)

| Domain TOGAF | Kondisi Awal (Baseline) | Target Arsitektur Sprint 2 | Kesenjangan (Gap) | Solusi Terpilih | Alasan & Rasionalisasi | Risiko & Mitigasi | Ketergantungan (Dependencies) |
|---|---|---|---|---|---|---|---|
| **Bisnis (Phase B)** | Pengaduan dapat diajukan tanpa login tamu; surat dapat diubah kembali ke draf setelah selesai. | Pengaduan hanya diajukan warga terotentikasi (dengan opsi `is_anonymous`); status surat `completed` berstatus final dan *read-only*. | Inkonsistensi perlindungan data pelapor dan rusaknya keabsahan hukum surat resmi RT. | Terapkan proteksi state machine (HTTP 409 Conflict) dan flag `is_anonymous` pada pengaduan. | Menjamin penegakan birokrasi 2-tier dan perlindungan warga pelapor dari intimidasi. | **Risiko:** Warga enggan melapor jika dipaksa login.<br>**Mitigasi:** Opsi sembunyikan identitas dari publik/pengurus non-keamanan. | Modul Otentikasi Sanctum & Model `Complaint`. |
| **Data (Phase C)** | Redundansi `users.warga_id` vs `citizens.user_id`; tabel jenis surat disebut `letter_types`; iuran kas yatim tanpa FK transaksi. | `citizens.user_id` single source of truth; tabel fisik bernama `jenis_surat`; `due_payments.finance_transaction_id` FK RESTRICT. | Risiko konflik kardinalitas akun–warga, perbedaan istilah rancangan vs fisik, dan transaksi keuangan hilang diam-diam. | Migrasi skema fisik MySQL, jadwalkan deprekasi `users.warga_id`, buat FK `due_payments` ke `finance_transactions`. | Menjamin relasi konsisten (DDD) dan prinsip pembukuan berimbang RT/RW. | **Risiko:** Kegagalan migrasi jika ada data yatim lama.<br>**Mitigasi:** Audit data awal via seeder dan skrip validasi pra-migrasi. | Basis Data MySQL `layanan_warga` & Model Eloquent. |
| **Aplikasi (Phase C)** | Route API tersebar tanpa pembedaan tegas antara yang aktif vs usulan; siaran real-time berisiko bocor ke channel publik. | Spesifikasi OpenAPI v1.1 tersinkronisasi penuh; channel broadcast privat (`users.{id}`, `admin-channel`) berotorisasi ketat. | Kurangnya dokumentasi kontrak payload dan kerentanan penyadapan data identitas warga baru di WebSocket. | Perbarui `API_SPEC.md`, `openapi.yaml`, dan otorisasi di `routes/channels.php`. | Klien frontend/mobile memiliki kepastian tipe data JSON dan privasi NIK terjamin (UU PDP). | **Risiko:** Frontend Web/Mobile gagal parsing JSON.<br>**Mitigasi:** Standarisasi Envelope Pattern dan tipe data canonical (IDR integer). | Middleware `auth:sanctum` & Channel Authorization. |
| **Teknologi (Phase D)** | Dokumen menyebut SQLite/Laravel 11; WebSocket eksternal tanpa fallback jika koneksi internet terputus. | Laravel 13.34.0 di PHP 8.4.12 dan MySQL 8.4.3 Laragon; REST polling adaptif (ETag / 304 Not Modified) sebagai fallback. | Dokumentasi tidak sesuai lingkungan eksekusi aktual; latensi/kegagalan notifikasi saat jaringan RT offline. | Susun `TECHNOLOGY_ARCHITECTURE.md` membedakan lokal, staging, dan rilis produksi; aktifkan polling adaptif. | Aplikasi dapat berjalan mandiri di server lokal RT tanpa dependensi layanan cloud berbayar. | **Risiko:** Beban server tinggi akibat polling.<br>**Mitigasi:** Conditional GET (`If-None-Match`), interval 5-10 detik, mati saat tab tidak aktif. | Runtime PHP 8.4, Laragon MySQL, Native Fetch API. |

---

## 3. Tabel 2: Rekaman Keputusan Arsitektur Sprint 2 (Architecture Decision Records)

| ID ADR | Topik Keputusan | Pilihan Solusi yang Ditetapkan | Alasan Teknis & Bisnis | Implikasi terhadap Kode & Desain | Status |
|:---:|---|---|---|---|:---:|
| **ADR-06** | Kebijakan Pengaduan Warga & Anonimitas | Wajib login warga terverifikasi, didukung flag `is_anonymous = true`. | Mencegah spam bot publik sekaligus melindungi identitas pelapor keluhan lingkungan. | Model `Complaint` menyimpan `user_id` pelapor secara privat, namun API publik menyembunyikan identitas. | **DITERAPKAN** |
| **ADR-07** | Integritas Buku Kas & Anti-Fraud | Foreign key `due_payments.finance_transaction_id` disetel ke `ON DELETE RESTRICT`; pembatalan via *reversal*. | Menjamin setiap kuitansi iuran memiliki jurnal kas fisik dan mencegah penghapusan sepihak oleh oknum bendahara. | Transaksi berstatus `published` bersifat *immutable*; kesalahan diatasi dengan membuat baris pembalik. | **DITERAPKAN** |
| **ADR-08** | Standarisasi Penamaan Fisik `jenis_surat` | Menetapkan nama tabel `jenis_surat` dan kolom relasi `letters.jenis_surat_id`. | Menyelaraskan skema fisik MySQL aktual dengan model Eloquent tanpa melakukan migrasi rename berisiko. | ERD, UML Class, LRS, dan dokumentasi menggunakan istilah `jenis_surat`. | **DITERAPKAN** |
| **ADR-09** | Hubungan Tunggal Akun–Warga | Menetapkan `citizens.user_id` (Unique, Nullable) sebagai sumber kebenaran tunggal (*single source of truth*). | Memisahkan konteks keamanan otentikasi (`users`) dari konteks kependudukan sensus RT (`citizens`). | Relasi `User::citizen()` menggunakan `citizens.user_id`; kolom lama `users.warga_id` dideprekasi. | **DITERAPKAN** |
| **ADR-10** | Strategi Komunikasi Real-Time | Menerapkan Near-Real-Time REST Polling (Conditional GET 304) dengan fallback Private WebSocket. | Menyederhanakan dependensi infrastruktur pada server lokal pengurus RT tanpa ketergantungan vendor pihak ketiga. | Header `ETag` dan query `updated_since` dioptimalkan; WebSocket `PrivateChannel` digunakan bila tersedia. | **DITERAPKAN** |

---

## 4. Paket Implementasi Bertahap (Sprint 3–6 Implementation Roadmap)

Pelaksanaan implementasi dibagi menjadi 4 paket kerja berurutan:

```
┌─────────────────────────┐      ┌─────────────────────────┐      ┌─────────────────────────┐      ┌─────────────────────────┐
│     PAKET 1: SPRINT 3   │      │     PAKET 2: SPRINT 4   │      │     PAKET 3: SPRINT 5   │      │     PAKET 4: SPRINT 6   │
│   (Minggu ke 5-6)       │      │   (Minggu ke 7-8)       │      │   (Minggu ke 9-10)      │      │   (Minggu ke 11-12)     │
├─────────────────────────┤      ├─────────────────────────┤      ├─────────────────────────┤      ├─────────────────────────┤
│ • Fondasi Identitas     │─────►│ • Birokrasi Surat 2-Tier│─────►│ • Buku Kas & Iuran Anti-│─────►│ • Staging UAT Rehearsal │
│ • Sensus Warga & KK     │      │ • QR Code & SHA-256 PDF │      │   Fraud (Reversal Flow) │      │ • Migrasi Produksi Final│
│ • Optimistic Lock KK    │      │ • Pengaduan & Anonimitas│      │ • Near-Real-Time Polling│      │ • Snapshot Backup Enkrip│
│ • Enkripsi NIK & KK     │      │ • Insiden Keamanan      │      │ • UAT & Penetrasi Keaman│      │ • Transisi Pemeliharaan │
└─────────────────────────┘      └─────────────────────────┘      └─────────────────────────┘      └─────────────────────────┘
```

### Rincian Paket Kerja:
1. **Paket 1 (Sprint 3 — Akun, Warga, dan Kartu Keluarga):**
   - Menuntaskan pemisahan identitas akun dan warga melalui `citizens.user_id`.
   - Mengimplementasikan kontrol konkurensi optimistik (`version`) pada pembaruan Kartu Keluarga.
   - Enkripsi simetris AES-256-CBC untuk NIK/KK dan pengindeksan blind index SHA-256 (`nik_hash`, `no_kk_hash`).
   - Penegakan izin RBAC kependudukan (`citizen.read`, `citizen.manage`).
2. **Paket 2 (Sprint 4 — Layanan Surat, Pengaduan, dan Keamanan):**
   - Alur birokrasi persuratan 2-tier: Sekretaris (Verifikasi Berkas) -> Ketua RT (Pengesahan & Tanda Tangan Digital).
   - Penguncian status surat: Surat `completed` berstatus final dan menolak transisi kembali ke `draft` (HTTP 409).
   - Penerbitan berkas PDF resmi dengan segel token QR Code dan hash integritas dokumen SHA-256.
   - Modul pengaduan warga dengan opsi anonim dan sistem penanganan insiden pos ronda keamanan.
3. **Paket 3 (Sprint 5 — Keuangan Transparan, Near-Real-Time, dan Pengujian UAT):**
   - Integrasi pencatatan pembayaran iuran warga (`due_payments`) ke transaksi kas umum (`finance_transactions`).
   - Penegakan relasi `ON DELETE RESTRICT` pada transaksi kas yang telah terhubung ke iuran.
   - Mekanisme jurnal pembalik (*reversal*) untuk koreksi salah input tanpa menghapus riwayat audit.
   - Optimasi polling near-real-time dengan respons conditional `304 Not Modified`.
   - Eksekusi pengujian otomatis menyeluruh (minimal 150 skenario uji) dan pengujian penetrasi hak akses.
4. **Paket 4 (Sprint 6 — Migrasi Staging, Rilis Produksi, dan Pemulihan):**
   - Rehearsal migrasi skema fisik MySQL pada lingkungan staging menggunakan data anonim.
   - Pencadangan basis data terenkripsi AES-256 sebelum rilis produksi.
   - Evaluasi kesiapan produksi: `APP_DEBUG=false`, HTTPS TLS aktif, rotasi kunci rahasia.
   - Prosedur pemulihan cepat (*rollback procedure*) jika terjadi kegagalan integritas data.

---

## 5. Rencana Migrasi Data, Pencadangan, dan Prosedur Pemulihan (Migration & Rollback Plan)

### 5.1 Urutan Migrasi Data
Untuk mencegah kegagalan *foreign key constraint*, urutan migrasi wajib mematuhi dependensi entitas:
1. **Fase 1 (Identitas & Pengguna):**
   - Eksekusi tabel `users` dan `family_cards`.
   - Eksekusi tabel `citizens` yang menunjuk ke `family_cards.id` dan `users.id`.
   - Jalankan skrip audit data untuk memverifikasi tidak ada duplikasi NIK hash sebelum membuat indeks unik.
2. **Fase 2 (Master Layanan & Persuratan):**
   - Eksekusi tabel `jenis_surat` dan semai 5 jenis surat baku RT.
   - Eksekusi tabel `letters` (menunjuk ke `citizens.id` dan `jenis_surat.id`) dan `letter_attachments`.
3. **Fase 3 (Keuangan & Kas Lingkungan):**
   - Eksekusi tabel `finance_transactions`.
   - Eksekusi tabel `resident_dues`.
   - Eksekusi tabel `due_payments` dengan foreign key ke `resident_dues.id` dan `finance_transactions.id` (`RESTRICT`).
   - Eksekusi tabel `purchases` dan `purchase_items`.
4. **Fase 4 (Interaksi Publik, Pengaduan, dan Log):**
   - Eksekusi tabel `complaints`, `security_reports`, `push_subscriptions`, dan `audit_logs`.

### 5.2 Prosedur Pencadangan (Backup Procedure)
- **Sebelum Migrasi:** Jalankan perintah dump basis data MySQL lokal Laragon:
  ```bash
  mysqldump -u root -p layanan_warga > database/backup_pre_migration_sprint2.sql
  ```
- **Pencadangan Terenkripsi:** Enkripsi file dump menggunakan kunci simetris AES-256 sebelum disimpan pada media penyimpanan aman.

### 5.3 Prosedur Pemulihan Cepat (Rollback Procedure)
Jika terjadi kegagalan migrasi atau rusaknya integritas referensial data:
1. Hentikan sementara proses web server (`php artisan down --secret="admin-recovery"`).
2. Pulihkan basis data dari berkas snapshot cadangan:
   ```bash
   mysql -u root -p layanan_warga < database/backup_pre_migration_sprint2.sql
   ```
3. Kembalikan branch kode ke tag commit stabil terakhir (`git checkout tags/v0.1.0-snapshot`).
4. Jalankan pengujian regresi untuk memastikan sistem kembali beroperasi normal.
5. Hidupkan kembali akses publik (`php artisan up`).

---

## 6. Tabel 3: Gerbang Audit Sprint 2 (Sprint 2 Audit Gate Table)

| Komponen Audit | Kriteria Kelulusan | Bukti Pemeriksaan Nyata (Aktual) | Catatan & Temuan Kritis | Status Gerbang |
|---|---|---|---|:---:|
| **1. Keselarasan Kebutuhan–UML–API–Database** | Seluruh istilah, nama tabel, status respons, dan relasi saling cocok tanpa kontradiksi. | ERD, LRS, UML Class, dan Use Case menggunakan tabel `jenis_surat`, `citizens.user_id`, dan `due_payments.finance_transaction_id`. | Seluruh dokumen di `docs/design/` dan `docs/api_spec/` telah diselaraskan. | **PASS** |
| **2. Pembedaan Kondisi Aktual vs Usulan** | Endpoint dan fitur terbagi jelas antara yang sudah berjalan (*Aktual*) dan yang direncanakan (*Usulan*). | Matriks Penyelarasan Rute pada `API_SPEC.md` Bagian 5 secara tegas menandai 37 rute **AKTUAL** dan 3 rute **USULAN**. | Tidak ada klaim fiktif atas fitur yang belum dibuat kodenya. | **PASS** |
| **3. Inventarisasi Basis Data Lengkap** | Seluruh 18 tabel operasional memiliki rincian PK, FK, Unique, Nullability, dan Delete Rules. | Dokumen `docs/design/ERD.md` Bagian 2 menyajikan inventarisasi fisik tabel MySQL `layanan_warga`. | Relasi `RESTRICT` pada kas iuran dan `SET NULL` pada akun warga terverifikasi pada basis data. | **PASS** |
| **4. Pemodelan UML Berorientasi Aktor** | Diagram memisahkan otoritas di sisi kiri, sistem di tengah, dan warga di sisi kanan; tersedia diagram modular. | `docs/design/SYSTEM_MODELING_5_ELEMENTS.md` Bagian 5 memuat 4 diagram Use Case modular bertata letak kiri-tengah-kanan. | Tidak ada penumpukan garis silang; use case berbentuk elips dalam batas sistem. | **PASS** |
| **5. Rencana Arsitektur Teknologi Terpisah** | Komponen runtime, peramban, penyimpanan, dan perbedaan lokal vs rilis terdokumentasi jelas. | Berkas `docs/design/TECHNOLOGY_ARCHITECTURE.md` menyajikan diagram deployment fisik dan matriks lokal vs staging vs prod. | Penanganan near-real-time polling dan fallback WebSocket terdefinisi. | **PASS** |
| **6. Rencana Migrasi Data & Pemulihan** | Tersedia urutan migrasi bertahap, audit pasangan data lama, dan skema pemulihan kegagalan. | Bagian 4 & 5 dokumen ini merinci urutan migrasi 4 fase, dump cadangan SQL, dan prosedur rollback. | Siap dieksekusi pada Sprint 3 tanpa risiko kehilangan data lama. | **PASS** |

---

### Keputusan Resmi Gerbang Sprint 2: **PASS**
*Seluruh kriteria kelulusan TOGAF ADM (Phase C, D, E, dan F) telah terpenuhi 100%. Rancangan sistem telah dibekukan dan siap dilanjutkan ke **Sprint 3 — Akun, Warga, dan Kartu Keluarga (Phase G — Implementation Governance)**.*
