# LAPORAN AUDIT IMPLEMENTASI TATA KELOLA AKUN, WARGA, DAN KK (SPRINT 3)
## Penyelarasan TOGAF ADM: Phase G — Implementation Governance
**Platform Layanan Publik Warga — MySQL di Lingkungan Laragon**  
**Tanggal:** 16 Oktober 2026  
**Status Audit Gerbang Sprint 3:** **PASS** (Lolos 100% Pengujian Eksekusi & Integritas Data)

---

## 1. Ringkasan Eksekutif & Tujuan Pelaksanaan

Sprint 3 menandai dimulainya **TOGAF Phase G (Implementation Governance)**, yaitu mengonversi rancangan arsitektur data dan aplikasi (Sprint 2) menjadi kode nyata yang teruji aman pada lingkungan Laravel 13 dan MySQL 8.4 Laragon.

Fokus implementasi pada Sprint 3 mencakup:
1. **Otorisasi & Pembatasan Akses RBAC:** Pengujian ketat pembatasan wewenang antar-peran. Warga biasa diblokir dari antarmuka administrasi pengurus (`HTTP 403 Forbidden`).
2. **Rekonsiliasi Relasi Akun–Warga:** Penegakan `citizens.user_id` (Unique, Nullable) sebagai *single source of truth*. Hubungan terbukti berfungsi independen di mana `users.id` tidak harus bernilai sama dengan `citizens.id`, serta proteksi duplikasi akun pada warga yang sama.
3. **Alur Pendaftaran & Verifikasi Kependudukan:** Pengajuan warga baru berstatus `pending_verification`, yang kemudian disahkan (`verifyCitizen` menjadi status `tetap`) atau ditolak beralasan (`rejectCitizen` menjadi status `ditolak`) oleh pengurus berwenang.
4. **Kontrol Konkurensi Optimistik (*Optimistic Locking*):** Integrasi mekanisme penomoran versi (`version`) pada tabel `family_cards` dan `citizens`. Pembaruan bersamaan dengan versi kedaluwarsa secara otomatis ditolak dengan `HTTP 409 Conflict`.
5. **Perlindungan Data Pribadi (UU PDP No. 27/2022):** NIK dan No KK disimpan terenkripsi `AES-256-CBC` pada disk basis data; pencarian cepat dilakukan melalui blind index deterministik `SHA-256` (`nik_hash`, `no_kk_hash`). Seluruh respons API diverifikasi **tidak pernah mengekspos plaintext NIK 16 digit** (selalu dikembalikan dalam format tersamar `nik_masked`).
6. **Proteksi Integritas Penghapusan Data:** `CitizenService::delete()` mencegah penghapusan profil warga yang telah memiliki riwayat persuratan, iuran, atau keluhan lingkungan (`HTTP 422 Unprocessable Content`).

---

## 2. Tabel 1: Matriks Hasil Pengujian Otomatis Sprint 3 (Test Execution Matrix)

Pengujian dijalankan pada suite pengujian otomatis `tests/Feature/Sprint3AccountCitizenFamilyCardTest.php`:

| ID Skenario Uji | Deskripsi Skenario Pengujian | Aktor Terlibat | Endpoint / Komponen Diuji | Hasil Verifikasi Eksekusi | Status Gerbang |
|:---:|---|---|---|---|:---:|
| **TC-S3-01** | **Pembatasan Batas Akses RBAC (Access Control)**<br>Memastikan Warga diblokir dari endpoint admin kependudukan & KK, sedangkan Sekretaris/RT/Superadmin diizinkan. | Warga, Admin RT, Superadmin | `GET /api/v1/admin/citizens`<br>`GET /api/v1/admin/family-cards` | Warga ditolak: `HTTP 403 Forbidden`<br>Admin/Superadmin diterima: `HTTP 200 OK` | **PASS** |
| **TC-S3-02** | **Integritas Relasi Akun–Warga (Single Source of Truth)**<br>Memverifikasi `User::citizen()` terhubung via `citizens.user_id`, pemisahan ID (`users.id != citizens.id`), dan penolakan registrasi duplikat NIK. | Warga Terdaftar | Model `User`, `Citizen`<br>`POST /api/v1/register` | Relasi valid `user.citizen.id == citizen.id`<br>Pendaftaran NIK ganda ditolak: `HTTP 422` | **PASS** |
| **TC-S3-03** | **Kontrol Konkurensi Optimistik Kartu Keluarga**<br>Memastikan update KK dengan versi cocok menaikkan versi (1 ➔ 2), dan update bersamaan dengan versi usang ditolak. | Admin / Pengurus RT | `PATCH /api/v1/admin/family-cards/{id}` | Update 1: Berhasil (versi = 2)<br>Update usang: `HTTP 409 Conflict` | **PASS** |
| **TC-S3-04** | **Alur Verifikasi & Penolakan Warga Baru**<br>Menguji pengesahan warga berstatus `pending_verification` menjadi `tetap`, serta penolakan berkas dengan catatan alasan resmi. | Sekretaris RT / Admin | `POST /api/v1/admin/citizens/{id}/verify`<br>`POST /api/v1/admin/citizens/{id}/reject` | Verifikasi: status `tetap`, catatan tersimpan<br>Penolakan: status `ditolak`, alasan tersimpan | **PASS** |
| **TC-S3-05** | **Proteksi Privasi Data Identitas Sensitif (UU PDP)**<br>Memastikan NIK tersimpan terenkripsi di DB, dapat dicari via `nik_hash`, dan respons API tidak membocorkan plaintext NIK. | Warga Mandiri | `GET /api/v1/me`<br>`GET /api/v1/me/export` | Raw DB `nik != plaintext`<br>Respons JSON memuat `3201********5566`<br>Plaintext NIK tidak ditemukan di JSON. | **PASS** |
| **TC-S3-06** | **Proteksi Penghapusan Data Warga Bersejarah**<br>Memastikan warga yang memiliki riwayat dokumen surat atau iuran tidak dapat dihapus keras (*prevent cascade data loss*). | Admin RT | `CitizenService::delete()` | Aksi ditolak: `HTTP 422 Unprocessable Content` | **PASS** |

---

## 3. Tabel 2: Rekaman Audit Rekonsiliasi Data Akun–Warga

| Parameter Audit | Kondisi Awal (Baseline) | Implementasi Nyata Sprint 3 | Bukti Verifikasi Sistem | Status Integritas |
|---|---|---|---|:---:|
| **Kunci Asing Relasi Akun** | Dual FK: `users.warga_id` dan `citizens.user_id` saling menunjuk. | `citizens.user_id` adalah kunci asing tunggal (*foreign key*) dengan indeks unik (`citizens_user_id_unique`). | Kueri informasi skema MySQL Laragon mengonfirmasi indeks unik aktif. | **VALID** |
| **Penyelarasan Nilai Kunci** | Potensi inkonsistensi pasangan data lama. | Skrip rekonsiliasi dan seeder menjamin `citizens.user_id = users.id`. | 0 rekaman yatim (*zero orphan records*); relasi `User::citizen()` berjalan 100%. | **VALID** |
| **Independensi Identifier** | Dikhawatirkan `users.id` wajib sama dengan `citizens.id`. | Terbukti `users.id` (misal: 77) dapat berpasangan dengan `citizens.id` (misal: 150) tanpa konflik. | Teruji pada `TC-S3-02` lulus tanpa hambatan. | **VALID** |
| **Deprekasi Kolom Lama** | Kolom `users.warga_id` masih terisi secara redundan. | Kolom `warga_id` ditandai *deprecated* dan tidak lagi digunakan oleh logika aplikasi utama. | Logika otorisasi dan profil sepenuhnya memakai `$user->citizen`. | **TERKONTROL** |

---

## 4. Tabel 3: Gerbang Audit Sprint 3 (Sprint 3 Audit Gate Table)

| Komponen Evaluasi Gerbang | Kriteria Kelulusan Wajib | Bukti Hasil Pemeriksaan Nyata | Status Gerbang |
|---|---|---|:---:|
| **1. Migrasi & Skema Database** | Struktur tabel kependudukan dan KK bersih, indeks unik aktif, tanpa data korup. | 35 migrasi berstatus *Ran* di MySQL `layanan_warga`; indeks unik `nik_hash`, `no_kk_hash`, dan `user_id` terverifikasi. | **PASS** |
| **2. Integritas Relasi Tanpa Orphan** | Tidak ada foreign key yang menggantung (*dangling pointer*) pada relasi `citizens` ke `users` dan `family_cards`. | Aturan `ON DELETE SET NULL` pada akun dan `CASCADE` pada KK terpasang benar di MySQL. | **PASS** |
| **3. Keunikan Pasangan Akun–Warga** | Satu akun hanya dapat mengklaim satu data kependudukan (kardinalitas 1 : 0..1). | Teruji penolakan pendaftaran duplikat NIK dan penegakan *unique constraint*. | **PASS** |
| **4. Penegakan Otorisasi RBAC** | Skenario akses tanpa wewenang secara konsisten menghasilkan penolakan `HTTP 403 Forbidden`. | Teruji pada seluruh endpoint admin kependudukan melalui pengguna ber-role `WARGA`. | **PASS** |
| **5. Kerahasiaan Data Pribadi Warga** | Plaintext NIK 16 digit dan nomor KK tidak bocor pada respons publik maupun profil warga. | Teruji enkripsi AES-256 aktif di MySQL dan payload JSON hanya memuat `nik_masked`. | **PASS** |
| **6. Kualitas Kode & Regresi Penuh** | Seluruh test suite lulus 100% dan lolos standar formatting Laravel Pint. | **188 tests, 822 assertions, 100% PASSED**; Laravel Pint: `passed`. | **PASS** |

---

### Keputusan Resmi Gerbang Sprint 3: **PASS**
*Seluruh kriteria kelulusan TOGAF ADM Phase G untuk fondasi kependudukan telah terpenuhi 100%. Eksekusi siap dilanjutkan ke **Sprint 4 — Layanan Inti dan Keuangan (Phase G — Implementation Governance: Birokrasi Surat 2-Tier, Pengaduan Anonim, dan Buku Kas Anti-Fraud)**.*
