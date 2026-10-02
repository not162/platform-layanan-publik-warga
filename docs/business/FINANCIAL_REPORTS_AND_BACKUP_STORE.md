# Dokumentasi Tata Kelola Laporan Keuangan RT & Backup Store Data

## 1. Ringkasan Eksekutif

Modul Keuangan RT dirancang untuk transparansi akuntabilitas kas lingkungan serta keamanan data mutlak dengan arsitektur penyimpanan privat dan backup store kriptografis. Peran **Bendahara**, **Sekretaris**, dan **Ketua RT** (didukung oleh **Superadmin**) memiliki kewenangan khusus untuk mengunduh laporan keuangan periodik dan menjalankan pencadangan data *immutable ledger*.

---

## 2. Struktur Subfolder Khusus Laporan & Backup Keuangan

Semua arsip laporan keuangan dan cadangan data disimpan di disk penyimpanan lokal privat (`storage/app/private/financial-reports/`) yang terisolasi dari akses publik:

```text
storage/app/private/financial-reports/
├── monthly/
│   ├── Laporan_Kas_RT_{year}_{month}.html   # Format resmi cetak / kop RT
│   └── Laporan_Kas_RT_{year}_{month}.csv    # Format tabular rekapitulasi data
├── citizen-dues/
│   └── Kas_Pembayaran_Warga_{year}_{month}.html # Rekapitulasi iuran warga per bulan
└── backups/
    └── ledger_backup_{Ymd_His}_{sha256}.json   # Snapshot ledger kas terenkripsi & ber-hash SHA-256
```

---

## 3. Matriks Hak Akses & Peran (RBAC)

| Peran (Role) | Unduh Laporan Kas Bulanan | Unduh Rekap Iuran Warga | Ekspor CSV | Buat Backup Store (SHA-256) | Unduh Arsip Backup |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Bendahara** |  Ya |  Ya |  Ya |  Ya |  Ya |
| **Sekretaris** |  Ya |  Ya |  Ya |  Ya |  Ya |
| **Ketua RT** |  Ya |  Ya |  Ya |  Ya |  Ya |
| **Superadmin** |  Ya |  Ya |  Ya |  Ya |  Ya |
| **Warga / Tamu** | ❌ 403 Forbidden | ❌ 403 Forbidden | ❌ 403 Forbidden | ❌ 403 Forbidden | ❌ 403 Forbidden |

---

## 4. Fitur Utama

### 4.1. Laporan Keuangan Bulanan RT (`monthly/`)
- Menampilkan ringkasan total pemasukan, total pengeluaran, dan saldo kas akhir periode.
- Dilengkapi kop surat resmi RT/RW serta tanda tangan pengesahan Ketua RT dan Bendahara.
- Tersedia dalam format **HTML (Siap Cetak / Print-friendly)** dan **CSV**.

### 4.2. Rekapitulasi Pembayaran Iuran Warga Per Bulan (`citizen-dues/`)
- Memetakan seluruh warga terdaftar dari basis data kependudukan.
- Menampilkan status iuran per keluarga/warga: **LUNAS** (dengan nominal dan tanggal bayar) atau **BELUM BAYAR**.
- Menghitung persentase kepatuhan dan total kas terhimpun secara otomatis.

### 4.3. Immutable Cryptographic Backup Store (`backups/`)
- Membuat snapshot data mutasi kas secara berkala dalam format JSON terstruktur.
- Setiap berkas backup diverifikasi secara kriptografis menggunakan algoritma hashing **SHA-256 (64-karakter)**.
- Nama berkas memuat stempel waktu dan checksum SHA-256 untuk mendeteksi *tampering* atau manipulasi data:
  ```text
  ledger_backup_20261002_003000_e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855.json
  ```
- Setiap eksekusi backup store dicatat secara otomatis ke dalam **Audit Log** (`finance.backup_store_created`).

---

## 5. Daftar Endpoint API & Web Route

### 5.1. Web Session Routes (Akses Langsung Browser Dashboard)
- `GET /dashboard/finance/reports/monthly?year={year}&month={month}&format={html|csv}`: Unduh Laporan Kas Bulanan.
- `GET /dashboard/finance/reports/citizen-dues?year={year}&month={month}`: Unduh Kas Pembayaran Warga Per Bulan.
- `POST /dashboard/finance/backups`: Memicu pencadangan baru (*Backup Store*).
- `GET /dashboard/finance/backups`: Daftar riwayat snapshot backup store.
- `GET /dashboard/finance/backups/{filename}`: Mengunduh berkas backup tertentu.

### 5.2. RESTful API Routes (Header `Authorization: Bearer <token>`)
- `GET /api/v1/admin/finance/reports/monthly`
- `GET /api/v1/admin/finance/reports/citizen-dues`
- `POST /api/v1/admin/finance/backups` (alias: `POST /api/v1/admin/finance/backup-store`)
- `GET /api/v1/admin/finance/backups`
- `GET /api/v1/admin/finance/backups/{filename}`

---

## 6. Prosedur Verifikasi Integritas Data (SHA-256)

Untuk memverifikasi keaslian berkas backup store di terminal:

```bash
# Windows PowerShell:
Get-FileHash -Algorithm SHA256 storage/app/private/financial-reports/backups/ledger_backup_*.json

# Linux / MacOS:
sha256sum storage/app/private/financial-reports/backups/ledger_backup_*.json
```
Pastikan hash yang dihasilkan sesuai dengan string hash pada nama berkas dan catatan di tabel `audit_logs`.
