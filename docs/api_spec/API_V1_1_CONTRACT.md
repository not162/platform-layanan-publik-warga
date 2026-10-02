# Kontrak Arsitektur Data & Spesifikasi API v1.1

Dokumen ini mendefinisikan kontrak data, struktur relasional baru, taksonomi hak akses RBAC, dan spesifikasi antarmuka API v1.1 pada Platform Layanan Publik Warga.

---

## 1. Standar Format Data Universal (Canonical Contract)

1. **Nominal Mata Uang (Money):**
   - Wajib direpresentasikan sebagai **integer IDR** (contoh: `Rp 50.000` disimpan dan dikirim sebagai `50000`).
   - Tipe data kolom database: `unsignedBigInteger` atau `bigInteger`.
   - Dilarang menggunakan tipe `float` atau `double` untuk mencegah floating-point rounding error.

2. **Format Waktu dan Tanggal:**
   - Tanggal kalender: format ISO `YYYY-MM-DD` (contoh: `2026-10-01`).
   - Timestamp waktu: format ISO8601 lengkap `YYYY-MM-DDTHH:mm:ssZ` (contoh: `2026-10-01T08:30:00Z`).

3. **Status & Enumerasi (Enums):**
   - Transaksi Kas: `draft`, `published`, `reversed`.
   - Iuran Warga: `UNPAID`, `PARTIAL`, `PAID`, `OVERDUE`.
   - Pembelian RT: Status `draft`, `verified`, `published`.
   - Laporan Triwulan: `draft`, `published`, `archived`.
   - Hasil Unduhan: `success`, `denied`, `failed`.

4. **Penomoran Dokumen (Unique Identifier):**
   - Transaksi Kas: `TXN-{YYYYMM}-{000001}`
   - Kuitansi Pembayaran Iuran: `KW-{YYYYMM}-{000001}`
   - Laporan Triwulan: `{YYYY}-Q{1-4} Rev {1...}`

---

## 2. Taksonomi Hak Akses RBAC (Singular Resource Permissions)

Hak akses dinormalisasi menggunakan format tunggal (*singular*) dengan domain yang jelas:

| Kode Izin (*Permission*) | Deskripsi | Peran Pemegang (*Role*) |
|---|---|---|
| `finance.transaction.read` | Melihat daftar & detail transaksi buku kas | BENDAHARA, KETUA_RT, SEKRETARIS |
| `finance.transaction.create` | Membuat draf transaksi kas baru | BENDAHARA |
| `finance.transaction.publish` | Mempublikasikan transaksi kas | BENDAHARA |
| `finance.transaction.reverse` | Membuat jurnal pembalik (*reversal*) | BENDAHARA |
| `finance.dues.read` | Melihat data tagihan & status iuran warga | BENDAHARA, KETUA_RT, SEKRETARIS |
| `finance.dues.manage` | Menerbitkan & mengubah tagihan iuran | BENDAHARA |
| `finance.payment.read` | Melihat riwayat setoran iuran warga | BENDAHARA, KETUA_RT |
| `finance.payment.manage` | Merekam & memvalidasi setoran iuran | BENDAHARA |
| `finance.purchase.read` | Melihat transparansi pembelian & nota belanja | BENDAHARA, KETUA_RT, SEKRETARIS, WARGA (publik) |
| `finance.purchase.manage` | Mencatat pembelian beritem (*itemized purchase*) | BENDAHARA |
| `finance.report.read` | Melihat laporan keuangan triwulan | Seluruh Peran (Publik & Pengurus) |
| `finance.report.generate` | Mengkalkulasi draf laporan triwulan | BENDAHARA |
| `finance.report.publish` | Mengesahkan & mempublikasikan laporan triwulan | KETUA_RT |
| `finance.audit.read` | Melihat log audit perubahan keuangan | BENDAHARA, KETUA_RT |
| `download.audit.read` | Melihat riwayat unduhan berkas & dokumen | BENDAHARA, KETUA_RT, SEKRETARIS |

---

## 3. Peta Rute API v1.1

### 3.1 Endpoint Publik Warga
- `GET /api/v1/public/finance/summary?period=YYYY-QN`  
  *Mengembalikan agregat pemasukan, pengeluaran, saldo bersih, dan ringkasan iuran tanpa memuat daftar baris transaksi mentah.*
- `GET /api/v1/public/finance/transactions?period=YYYY-QN&cursor=...`  
  *Daftar transaksi kas publik menggunakan cursor pagination.*
- `GET /api/v1/public/finance/quarterly-reports`  
  *Daftar laporan resmi per triwulan yang telah berstatus `published`.*
- `GET /api/v1/public/finance/quarterly-reports/{year}/{quarter}`  
  *Detail revisi aktif laporan keuangan triwulan resmi.*
- `GET /api/v1/public/finance/purchases?period=YYYY-QN`  
  *Transparansi daftar belanja & pengadaan barang RT.*
- `GET /api/v1/public/finance/purchases/{id}`  
  *Detail nota pembelian RT lengkap dengan rincian item, kuantitas, harga satuan, dan bukti nota.*

### 3.2 Endpoint Warga Mandiri (`/api/v1/me`)
- `GET /api/v1/me/dues`  
  *Melihat daftar kewajiban iuran milik warga yang sedang login.*
- `GET /api/v1/me/dues/{id}`  
  *Melihat rincian tagihan iuran dan riwayat pembayaran parsial/lunas.*
- `GET /api/v1/me/payments`  
  *Riwayat bukti pembayaran iuran warga yang bersangkutan.*
- `GET /api/v1/me/finance/summary`  
  *Ringkasan kewajiban iuran: total tagihan tahun berjalan, total terbayar, dan sisa tunggakan.*

### 3.3 Endpoint Manajemen Keuangan Pengurus (`/api/v1/admin/finance`)
- `GET /api/v1/admin/finance/transactions` (Izin: `finance.transaction.read`)
- `POST /api/v1/admin/finance/transactions` (Izin: `finance.transaction.create`)
- `POST /api/v1/admin/finance/transactions/{id}/publish` (Izin: `finance.transaction.publish`)
- `POST /api/v1/admin/finance/transactions/{id}/reverse` (Izin: `finance.transaction.reverse`)
- `GET /api/v1/admin/finance/dues` (Izin: `finance.dues.read`)
- `POST /api/v1/admin/finance/dues` (Izin: `finance.dues.manage`)
- `GET /api/v1/admin/finance/payments` (Izin: `finance.payment.read`)
- `POST /api/v1/admin/finance/payments` (Izin: `finance.payment.manage`)
- `GET /api/v1/admin/finance/purchases` (Izin: `finance.purchase.read`)
- `POST /api/v1/admin/finance/purchases` (Izin: `finance.purchase.manage`)
- `GET /api/v1/admin/finance/reports/{year}/{quarter}` (Izin: `finance.report.read`)
- `POST /api/v1/admin/finance/reports/{year}/{quarter}/generate` (Izin: `finance.report.generate`)
- `POST /api/v1/admin/finance/reports/{year}/{quarter}/publish` (Izin: `finance.report.publish`)
- `GET /api/v1/admin/download-audits` (Izin: `download.audit.read`)
- `GET /api/v1/admin/download-audits/summary` (Izin: `download.audit.read`)
