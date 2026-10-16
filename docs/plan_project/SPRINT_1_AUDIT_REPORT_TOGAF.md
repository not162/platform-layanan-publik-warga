# LAPORAN AUDIT PROSES LAYANAN DAN HAK AKSES (SPRINT 1)
## Penyelarasan TOGAF ADM: Phase B (Business Architecture)
**Platform Layanan Publik Warga — MySQL di Laragon**  
**Tanggal:** 16 Oktober 2026  
**Status Audit Gerbang:** **PASS** (Memenuhi Seluruh Kriteria Kualifikasi)

---

## 1. Identifikasi Audit & Hasil yang Diselesaikan

- **Sprint:** Sprint 1 — Proses Layanan dan Hak Akses (TOGAF Phase B — Business Architecture)
- **Komponen yang Dievaluasi:**
  1. Penyelarasan Hak Akses 7 Peran (Guest/Publik, Warga, Sekretaris, Bendahara, Petugas Keamanan, Ketua RT, Admin/Superadmin).
  2. Resolusi Perbedaan Hak Akses Pengaduan Tamu vs Warga Terautentikasi (ADR-06).
  3. Integrasi Lengkap Navbar & Drawer Layanan Warga sesuai 7 layanan pada referensi visual:
     - *INFORMASI UTAMA:* Warta & Pengumuman Baru, Transparansi Kas Lingkungan, Form Pengaduan Warga.
     - *PELAYANAN & KEAMANAN:* Agenda Kegiatan Warga, Jadwal Ronda & Kamling, Kontak Darurat 24 Jam, Struktur Pengurus RT 01.
  4. Verifikasi Konsistensi Atribut Warga: `id`, `username`, `nik`, `alamat`, dan `tanggal_lahir` pada seluruh Endpoint API dan Real-time Database Broadcasting.
  5. Pengujian Fungsional Otomatis & Penjaminan Kualitas (Quality Assurance).

---

## 2. Keputusan Arsitektur Bisnis & Penanganan Kesenjangan (ADR)

### ADR-06: Kebijakan Akses Pengaduan (Guest vs Authenticated Citizen dengan Opsi Anonim)
- **Konteks:** Matriks RBAC lama mencantumkan Tamu (*Guest*) dapat membuat pengaduan (`C`), padahal rute API mewajibkan otentikasi Sanctum dan validasi data warga.
- **Keputusan:**  
  1. Pengajuan keluhan lingkungan **wajib dilakukan oleh akun warga yang terautentikasi** (`Guest: -`, `Citizen: C`).
  2. Hak kerahasiaan pelapor dijamin melalui opsi **Pengaduan Anonim (`is_anonymous = true`)**: nama dan foto pelapor disamarkan dari tampilan publik dan respons API warga lain, tetapi data internal tersimpan aman di database untuk akuntabilitas pengurus RT.
- **Dampak:** Mencegah spam bot dan laporan palsu (*hoax*), menjaga kerukunan antarwarga, dan menyelaraskan kontrak rute `routes/api.php` dan `routes/web.php` dengan dokumentasi SRS.

### ADR-07: Konsolidasi Navigasi Mandiri Warga (Integrasi 7 Layanan Terpadu)
- **Konteks:** Dashboard warga sebelumnya hanya memuat tabel permohonan surat, sementara 7 layanan esensial RT (warta, kas, pengaduan, agenda, ronda, darurat, pengurus) terpisah di halaman publik.
- **Keputusan:** Menghadirkan *tab bar* dan *off-canvas navigation drawer* di dalam [`resources/views/dashboard/warga.blade.php`](file:///c:/Users/user/platform-layanan-publik-warga/resources/views/dashboard/warga.blade.php) yang mengelompokkan layanan menjadi dua pilar utama:
  - **INFORMASI UTAMA:** Warta Pengumuman, Transparansi Kas Lingkungan, Form & Riwayat Pengaduan.
  - **PELAYANAN & KEAMANAN:** Agenda Kegiatan Warga, Jadwal Ronda Siskamling, Kontak Darurat 24 Jam, Struktur Pengurus RT 01.
- **Dampak:** Warga dapat mengakses seluruh administrasi lingkungan dari satu dasbor terpadu tanpa berpindah-pindah konteks.

---

## 3. Matriks Traceability: Identitas & Hak Akses Warga

| Atribut / Kebutuhan | Sumber Tabel / Kolom | Format API / Aksesor Model | Perilaku Otorisasi & Keamanan | Bukti Pengujian |
| :--- | :--- | :--- | :--- | :--- |
| **ID** | `users.id` & `citizens.id` | `'id' => $user->id`, `'citizen_id' => $citizen->id` | ID unik integer, relasi `citizens.user_id` $\rightarrow$ `users.id` (1-to-1). | `Sprint1BusinessArchitectureAndNavbarTest::test_warga_role_access_exposes_id_username_nik_alamat_tanggal_lahir_without_errors` |
| **Username** | `users.name` / `email` | `$user->username`, `'username' => $this->name` | Alias kanonikal `username` $\equiv$ `name`; endpoint menerima login via email, username, nomor HP, maupun NIK 16 digit. | `test_warga_can_login_using_16_digit_nik` |
| **NIK** | `citizens.nik` (Encrypted), `citizens.nik_hash` (SHA-256) | `'nik' => $nikMasked`, `'nik_masked' => '3171********0001'` | Sesuai UU PDP & NFR-01: Plaintext NIK dilarang keras bocor di payload JSON publik/API; hanya digit tersamar yang diekspos ke publik. | `CitizenModuleTest::test_warga_can_view_own_profile_with_masked_nik` |
| **Alamat** | `family_cards.address` | `$citizen->alamat`, `'alamat' => $this->familyCard?->address` | Null-safe accessor; jika data KK belum terhubung, bernilai default aman tanpa melempar 500 error. | `test_warga_role_access_exposes_id_username_nik_alamat_tanggal_lahir_without_errors` |
| **Tanggal Lahir** | `citizens.date_of_birth` | `$citizen->tanggal_lahir`, `'tanggal_lahir' => 'YYYY-MM-DD'` | Format standar ISO date string; null-safe formatting. | `test_warga_role_access_exposes_id_username_nik_alamat_tanggal_lahir_without_errors` |
| **Real-time Channel** | WebSocket Private Channels | `users.{id}`, `warga.{id}`, `citizens.{id}`, `admin-channel` | Otorisasi channel ketat: warga hanya dapat mendengar event miliknya sendiri; penolakan 403 jika mendengarkan `admin-channel`. | `test_realtime_broadcast_channel_authorization_for_warga` |

---

## 4. Kriteria dan Bukti Eksekusi (Audit Gates)

| Kriteria Audit Sprint 1 | Target Kualifikasi | Bukti Hasil Pemeriksaan Aktual | Status |
| :--- | :--- | :--- | :---: |
| **1. Konsistensi Dokumen & Rute** | SRS & Matriks RBAC selaras 100% | [`docs/project_management/RBAC_MATRIX.md`](file:///c:/Users/user/platform-layanan-publik-warga/docs/project_management/RBAC_MATRIX.md) & [`docs/business/SRS.md`](file:///c:/Users/user/platform-layanan-publik-warga/docs/business/SRS.md) diperbarui; resolusi pengaduan login warga tuntas. | **PASS** |
| **2. Fitur Navbar Layanan Warga** | 7 layanan dalam gambar terpasang di dasbor warga | [`resources/views/dashboard/warga.blade.php`](file:///c:/Users/user/platform-layanan-publik-warga/resources/views/dashboard/warga.blade.php) & [`DashboardController.php`](file:///c:/Users/user/platform-layanan-publik-warga/app/Http/Controllers/DashboardController.php) memuat warta, kas, pengaduan, agenda, ronda, darurat, dan pengurus. | **PASS** |
| **3. Form Pengaduan Web** | Warga dapat melapor dari dasbor | Rute `POST /dashboard/complaints` via `ComplaintController::storeWeb()` sukses menyimpan tiket dan redirect status. | **PASS** |
| **4. Konsistensi Kolom Kependudukan** | `id`, `username`, `nik`, `alamat`, `tanggal_lahir` aman | Aksesor Model dan `CitizenResource` / `UserResource` teruji 100% tanpa missing field / error. | **PASS** |
| **5. Real-time Channel Authorization** | Channel privat warga terdaftar | `routes/channels.php` memuat otorisasi `users.{id}`, `warga.{id}`, dan `citizens.{id}` dengan evaluasi hak akses yang lulus tes. | **PASS** |
| **6. Pengujian Regresi Keseluruhan** | Seluruh test suite lulus tanpa kegagalan | **182 tests, 789 assertions, 100% PASS** dalam 12,86 detik. | **PASS** |

---

## 5. Ringkasan & Keputusan Lanjut

- **Status Gerbang Sprint 1:** **PASS** (Lolos Penuh)
- **Catatan & Rekomendasi:**  
  Fondasi arsitektur bisnis (Phase B) telah formal dan sinkron antara dokumen rancangan, antarmuka Blade, kontroler Laravel, dan basis data MySQL. Lapisan otorisasi data kependudukan telah diverifikasi aman dari kebocoran NIK serta bebas dari kesalahan *null pointer* pada alamat/tanggal lahir.
- **Rekomendasi Langkah Berikutnya:**  
  Melanjutkan ke **Sprint 2 — Rancangan Database, API, UML, dan Migrasi (TOGAF Phase C: Data/Application, Phase D: Technology, Phase E: Opportunities & Solutions, Phase F: Migration Planning)**.
