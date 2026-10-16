# Spesifikasi Kebutuhan Perangkat Lunak (SRS) & Proses Bisnis
**Platform Layanan Publik Warga RT 01 / RW 05 Kemayoran**  
*TOGAF Phase B — Business Architecture (Sprint 1)*  
*Tanggal Evaluasi: 16 Oktober 2026*

---

## 1. Pendahuluan & Lingkup Bisnis

Dokumen ini mendefinisikan Kebutuhan Fungsional (*Functional Requirements* / FR), Kebutuhan Non-Fungsional (*Non-Functional Requirements* / NFR), Aturan Bisnis (*Business Rules* / BR), dan Pemetaan Proses Layanan Warga pada lingkungan RT 01 / RW 05 Kelurahan Kemayoran.

Aplikasi dirancang sebagai portal layanan publik mandiri berbasis web (*mobile-first*) yang menghubungkan warga dengan pengurus RT secara transparan, akuntabel, dan terlacak (*audit-traceable*).

---

## 2. Definisi Aktor & Tanggung Jawab (7 Aktor)

1. **Publik / Tamu (Guest):** Pengunjung tanpa autentikasi yang dapat melihat pengumuman resmi, agenda kegiatan, jadwal ronda, transparansi ringkasan kas agregat, kontak darurat, serta melacak status surat via nomor tiket atau QR verifikasi publik.
2. **Warga (Citizen):** Warga RT 01 terdaftar yang memiliki hak mengajukan surat pengantar resmi, mengunduh berkas surat yang disahkan, membayar & memeriksa tagihan iuran kas pribadi, menyampaikan pengaduan fasilitas lingkungan (dengan opsi anonim), dan memperbarui data kontak pribadi.
3. **Sekretaris:** Pengurus yang bertanggung jawab atas antrean verifikasi berkas surat warga (Tier 1 Verification), verifikasi pendaftaran warga baru, pengelolaan master data kependudukan (warga & kartu keluarga), serta penerbitan warta pengumuman.
4. **Bendahara:** Pengurus yang mengelola pembukuan kas lingkungan (pencatatan penerimaan & pengeluaran), verifikasi setoran iuran warga, penyusunan draf laporan keuangan triwulan, dan pembuatan arsip cadangan kas (*backup store*).
5. **Petugas Keamanan:** Petugas yang mengelola jadwal ronda malam, pos kamling, serta memantau dan menindaklanjuti laporan gangguan ketertiban & keamanan lingkungan.
6. **Ketua RT:** Pimpinan lingkungan yang berwenang menandatangani secara digital dan mengesahkan (*Tier 2 Approval*) surat pengantar resmi, mengesahkan laporan keuangan triwulan untuk dipublikasikan, serta mengawasi tata kelola RT.
7. **Superadmin / Admin:** Pengelola sistem yang memiliki hak konfigurasi teknis, audit log, manajemen template jenis surat dinamis, dan pemeliharaan server/database.

---

## 3. Pemetaan Proses Bisnis Layanan (Current vs Target)

### 3.1. Alur Pengajuan Surat Pengantar RT (2-Tier Approval)
- **Kondisi Lama (Manual):** Warga datang ke rumah Ketua RT atau Sekretaris membawa fotokopi KK/KTP, menunggu pengetikan surat manual, cap basah, dan rawan salah ketik atau nomor surat ganda.
- **Kondisi Target (Digital Terlacak):**
  1. Warga login ke portal, memilih jenis surat, mengisi keperluan, dan menekan submit.
  2. Status menjadi `submitted`. Sekretaris RT memeriksa kelengkapan data (Tier 1 Verification). Jika valid, status menjadi `verified`. Jika ada kekurangan, ditolak (`rejected`) dengan catatan perbaikan.
  3. Ketua RT menerima antrean surat terverifikasi, memeriksa, dan mengesahkan (`approved`). Sistem mengunci dokumen (*idempotent lock*) dan membubuhkan token verifikasi digital QR code.
  4. Warga langsung dapat mengunduh surat resmi dalam format Microsoft Word (`.docx`) dan PDF (`.pdf`) dari dashboard mereka. Status otomatis menjadi `completed`.

### 3.2. Alur Pengaduan & Aspirasi Lingkungan
- **Kondisi Lama (Manual):** Pengaduan disampaikan lisan atau WhatsApp grup yang sering terlewat, tidak tercatat status penanganannya, dan warga enggan melapor karena takut dihakimi tetangga.
- **Kondisi Target:**
  1. Pengajuan pengaduan **wajib dilakukan oleh pengguna login terverifikasi** untuk menjamin akuntabilitas data dan mencegah bot/laporan palsu.
  2. Warga dapat mengaktifkan opsi **Kirim Sebagai Anonim (`is_anonymous = true`)**: identitas warga disembunyikan sepenuhnya dari tampilan dan respons publik, tetapi tetap dapat ditindaklanjuti oleh pengurus.
  3. Status penanganan bergerak transparan: `submitted` $\rightarrow$ `reviewed` $\rightarrow$ `in_progress` $\rightarrow$ `resolved`.

### 3.3. Alur Iuran Kas & Transparansi Keuangan
- **Kondisi Lama:** Catatan buku tulis manual bendahara, kuitansi kertas mudah hilang, dan warga tidak tahu pasti penggunaan dana iuran RT.
- **Kondisi Target:**
  1. Warga dapat memeriksa status pembayaran iuran bulanan dan mengunduh bukti kuitansi digital.
  2. Bendahara mencatat mutasi kas secara kanonikal (`amount_idr`) dengan relasi foreign key yang terikat aman (`restrictOnDelete`).
  3. Laporan keuangan kuartal diagregasi otomatis dan disahkan oleh Ketua RT sebelum ditampilkan pada grafik transparansi kas publik.

---

## 4. Kebutuhan Fungsional (*Functional Requirements* / FR)

- **FR-01 (Kependudukan):** Sistem menyediakan manajemen master data warga dan kartu keluarga dengan relasi tunggal `citizens.user_id` $\rightarrow$ `users.id` yang unik.
- **FR-02 (Autentikasi & RBAC):** Sistem mengamankan endpoint dengan Laravel Sanctum, pembatasan hak akses berbasis peran (7 peran), dan otorisasi policy granular.
- **FR-03 (Surat Pengantar):** Sistem menerapkan alur verifikasi 2-tier (Sekretaris $\rightarrow$ Ketua RT) dengan penomoran dokumen unik bebas tabrakan (*atomic sequence lock*).
- **FR-04 (Unduhan Multi-Format):** Sistem dapat mengekspor surat resmi ke dalam format Microsoft Word (`.docx`) dan PDF (`.pdf`) hanya setelah statusnya disahkan.
- **FR-05 (Pengaduan Warga):** Sistem menerima laporan pengaduan dari warga terautentikasi dengan dukungan foto bukti dan penyamaran identitas (*anonymity masking*).
- **FR-06 (Keuangan & Kas):** Sistem mencatat transaksi kas RT, mengikat pembayaran iuran ke jurnal kas, dan menerapkan mekanisme pembatalan jurnal (*reversal ledger*) tanpa menghapus riwayat audit.
- **FR-07 (Informasi Publik):** Sistem menyediakan warta pengumuman, agenda kegiatan, jadwal ronda siskamling, kontak darurat 24 jam, dan susunan pengurus RT yang dapat diakses publik maupun warga.
- **FR-08 (Audit Trail):** Sistem mencatat setiap operasi manipulasi data (Create/Update/Delete/Verify/Approve) ke dalam tabel `audit_logs`.

---

## 5. Kebutuhan Non-Fungsional (*Non-Functional Requirements* / NFR)

- **NFR-01 (Privasi & Perlindungan Data):** NIK dan Nomor KK dienkripsi pada database (*encrypted cast*) dan diindeks melalui hash SHA-256 (`nik_hash`, `no_kk_hash`). Raw plaintext NIK dilarang keras bocor di endpoint API publik.
- **NFR-02 (Performa & Pengindeksan):** Kueri basis data diindeks pada foreign key dan kolom pencarian, menggunakan pagination pada listing data, dan menerapkan eager loading (`with(...)`) untuk mencegah masalah N+1.
- **NFR-03 (Integritas Konkurensi):** Menggunakan kolom `version` (optimistic locking) serta `lockForUpdate` pada penomoran surat serentak untuk mencegah kondisi balapan (*race condition*).
- **NFR-04 (Aksesibilitas & UI Responsif):** Tampilan web dashboard ramah gawai (*mobile-first*), mendukung navigasi laci (*off-canvas drawer*), dan ramah aksesibilitas.

---

## 6. Kriteria Penerimaan Berbasis Skenario (*Acceptance Criteria*)

```gherkin
Feature: Isolasi Data Surat Antar Warga
  Scenario: Warga dilarang membaca atau mengunduh surat milik warga lain
    Given Akun Warga A dan Akun Warga B terdaftar di sistem
    And Surat dengan ID 10 adalah milik Warga A
    When Warga B mengirim GET request ke "/api/v1/letters/10"
    Then Sistem mengembalikan status HTTP 403 Forbidden
    And Pesan kesalahan menyatakan akses ditolak

Feature: Pengesahan Surat Pengantar 2-Tier
  Scenario: Sekretaris memverifikasi berkas dan Ketua RT mengesahkan surat
    Given Surat berstatus "submitted"
    When Sekretaris mengirimkan verifikasi via "/api/v1/admin/letters/{id}/verify"
    Then Status surat berubah menjadi "verified"
    When Ketua RT mengirimkan persetujuan via "/api/v1/admin/letters/{id}/approve"
    Then Status surat berubah menjadi "approved"
    And Token verifikasi digital terbentuk
    And Berkas Word (.docx) dan PDF (.pdf) dapat diunduh oleh warga pemilik surat

Feature: Pengaduan Warga dengan Anonimitas
  Scenario: Warga mengirim pengaduan dengan opsi anonim
    Given Akun Warga terautentikasi
    When Warga mengirim POST "/api/v1/complaints" dengan "is_anonymous" = true
    Then Pengaduan berhasil tersimpan dengan nomor tiket unik
    And Pada response publik nama pelapor ditampilkan sebagai "Anonim"
```
