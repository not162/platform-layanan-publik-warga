# LAPORAN AUDIT IMPLEMENTASI LAYANAN INTI DAN KEUANGAN (SPRINT 4)
## Penyelarasan TOGAF ADM: Phase G — Implementation Governance
**Platform Layanan Publik Warga — MySQL di Lingkungan Laragon**  
**Tanggal:** 16 Oktober 2026  
**Status Audit Gerbang Sprint 4:** **PASS** (Lolos 100% Pengujian Eksekusi & Integritas Tata Kelola)

---

## 1. Ringkasan Eksekutif & Tujuan Pelaksanaan

Sprint 4 merupakan implementasi inti dari **TOGAF Phase G (Implementation Governance)** pada domain operasional warga dan tata kelola keuangan RT/RW. Tujuan utama sprint ini adalah menegakkan aturan bisnis yang anti-fraud, menjaga privasi warga dalam menyampaikan aspirasi/laporan, serta menjamin keabsahan dokumen persuratan administratif.

Fokus implementasi pada Sprint 4 mencakup dua klaster utama:

### Bagian 4A: Pelayanan & Komunitas Warga
1. **Birokrasi Persuratan 2-Tier Terstruktur:** Alur persuratan resmi dimulai dari draf warga (`submitted`), diverifikasi secara administratif oleh Sekretaris RT (`verified`), disahkan dengan tanda tangan digital token QR oleh Ketua RT (`approved`), hingga diarsipkan tuntas (`completed`). Upaya memotong rantai birokrasi (misal: langsung approve sebelum verifikasi) ditolak dengan `HTTP 409 Conflict`.
2. **Finalitas Dokumen Selesai (*Completed Finality Protection*):** Surat yang telah disahkan dan diselesaikan (`completed`) berstatus final secara hukum administratif dan dilindungi dari pembalikan (*anti-revert*) kembali ke draf atau status perantara (`HTTP 409 Conflict`).
3. **Isolasi Kepemilikan Dokumen (*Tenant/Owner Isolation*):** Warga hanya dapat membaca, mengoreksi, dan menghapus dokumen surat miliknya sendiri. Warga lain yang mencoba mengakses surat tetangganya langsung diblokir (`HTTP 403 Forbidden`).
4. **Perlindungan Anonimitas Keluhan (*Whistleblower Privacy*):** Sistem mendukung pelaporan pengaduan berstatus anonim (`is_anonymous = true`). Identitas user pelapor secara otomatis disembunyikan/dikosongkan pada payload respons API untuk mencegah intimidasi sosial, dengan alur penanganan administratif yang transparan (`submitted` ➔ `in_progress` ➔ `resolved`).
5. **Penanganan Insiden Keamanan Lingkungan:** Laporan gangguan keamanan oleh warga langsung ditindaklanjuti oleh petugas ronda/keamanan berwenang (`security.manage`) dengan pencatatan penugasan (`assigned_to`) dan deskripsi resolusi barang bukti.

### Bagian 4B: Keuangan & Pembukuan Kas (Jurnal Kas Anti-Fraud)
1. **Pencegahan Pembayaran Ganda & Pembayaran Berlebih (*Overpayment Prevention*):** Pembayaran iuran warga (`due_payments`) menggunakan transaksi basis data dengan penguncian baris (`lockForUpdate()`). Pembayaran yang melebihi sisa tagihan secara otomatis ditolak dengan `HTTP 422 Unprocessable Content`. Pembayaran cicilan (`PARTIAL`) dan pelunasan tepat nominal (`PAID`) tercatat presisi.
2. **Akuntabilitas Aktor Pencatat Kas:** Seluruh mutasi kas penerimaan iuran dan belanja mencatat ID pengguna terotentikasi yang bertugas (`created_by = $receiver->id`), menghilangkan ketergantungan pada ID statis.
3. **Integritas Jurnal Kas & Kebijakan Anti-Hapus (*Reversal Anti-Fraud Flow*):** Transaksi kas yang telah dipublikasikan (`published`) dilarang keras untuk dihapus (`HTTP 403 Forbidden`). Koreksi pembukuan wajib dilakukan melalui mekanisme penerbitan jurnal pembalik (*reversal transaction*) yang bertindak sebagai pos penyeimbang (*offsetting ledger*), dan rekaman pembalik tersebut juga dilindungi dari penghapusan (`HTTP 403 Forbidden`).
4. **Integrasi Pengadaan Belanja Beritem (*Itemized Purchases*):** Pencatatan nota belanja lingkungan RT menguraikan rincian kuantitas, satuan, dan harga satuan secara teritemisasi, yang langsung terhubung dan menerbitkan mutasi kas pengeluaran (`type = expense`) pada Buku Kas Umum secara otomatis.

---

## 2. Tabel 1: Matriks Hasil Pengujian Otomatis Sprint 4 (Test Execution Matrix)

Pengujian komprehensif dijalankan pada suite pengujian otomatis `tests/Feature/Sprint4CoreServicesAndFinanceTest.php`:

| ID Skenario Uji | Deskripsi Skenario Pengujian | Aktor Terlibat | Endpoint / Komponen Diuji | Hasil Verifikasi Eksekusi | Status Gerbang |
|:---:|---|---|---|---|:---:|
| **TC-S4-01** | **Birokrasi Persuratan 2-Tier & Finalitas Dokumen Selesai**<br>Memverifikasi rantai persuratan Warga ➔ Verifikasi Sekretaris ➔ Pengesahan Ketua RT ➔ Completed, pencegahan pemotongan alur, dan proteksi anti-revert. | Warga, Sekretaris RT, Ketua RT | `POST /api/v1/letters`<br>`POST /api/v1/letters/{id}/submit`<br>`POST /api/v1/admin/letters/{id}/verify`<br>`POST /api/v1/admin/letters/{id}/approve`<br>`POST /api/v1/admin/letters/{id}/complete` | Alur sekuensial berhasil: status `completed`<br>Approve ilegal loncat tahap: `HTTP 409`<br>Revert dokumen final ditolak: `HTTP 409` | **PASS** |
| **TC-S4-02** | **Isolasi Kepemilikan Dokumen Persuratan**<br>Memastikan Warga B dilarang membaca, mengajukan ulang, maupun menghapus draf surat milik Warga A. | Warga A (Pemilik), Warga B (Pihak Luar) | `GET /api/v1/letters/{id}`<br>`POST /api/v1/letters/{id}/submit`<br>`DELETE /api/v1/letters/{id}` | Akses oleh Warga B diblokir total: `HTTP 403 Forbidden` pada seluruh aksi | **PASS** |
| **TC-S4-03** | **Pelaporan Pengaduan & Proteksi Anonimitas Warga**<br>Memverifikasi laporan bertanda `is_anonymous` menyembunyikan profil user pada API, dan admin dapat mengeskalasi status ke `in_progress`. | Warga Pelapor, Admin Lingkungan | `POST /api/v1/complaints`<br>`GET /api/v1/complaints/{id}`<br>`PATCH /api/v1/admin/complaints/{id}/status` | Objek user bernilai `null` pada respons<br>Update status `in_progress`: `HTTP 200 OK` | **PASS** |
| **TC-S4-04** | **Laporan Insiden Keamanan & Penanganan Petugas**<br>Menguji pelaporan insiden darurat oleh warga dan resolusi penanganan oleh petugas keamanan (Hansip/Ronda) dengan kontrol versi optimistik. | Warga, Petugas Keamanan (Hansip) | `POST /api/v1/security-reports`<br>`PATCH /api/v1/admin/security-reports/{id}` | Laporan terbit status `reported`<br>Hansip menangani: status `resolved`, assigned_to dan bukti tercatat | **PASS** |
| **TC-S4-05** | **Pembayaran Iuran & Pencegahan Overpayment**<br>Menguji pembayaran parsial, penolakan pembayaran melebihi saldo sisa tagihan (*overpay*), pelunasan, dan pencatatan aktor kas. | Bendahara RT, Warga Pembayar | `POST /api/v1/admin/finance/payments`<br>`Model ResidentDue, DuePayment` | Bayar 30rb: status `PARTIAL`<br>Overpay 30rb (sisa 20rb): ditolak `HTTP 422`<br>Pelunasan 20rb: status `PAID`<br>Aktor kas tercatat `admin_id` | **PASS** |
| **TC-S4-06** | **Alur Jurnal Kas Pembalik Anti-Fraud (*Reversal*)**<br>Memastikan transaksi kas terbitan dilarang dihapus, pembatalan menerbitkan jurnal lawan (*expense*), dan jurnal pembalik dilarang dihapus. | Bendahara RT | `POST /api/v1/admin/finance/transactions`<br>`POST /api/v1/admin/finance/transactions/{id}/publish`<br>`DELETE /api/v1/admin/finance/{id}`<br>`POST /api/v1/admin/finance/transactions/{id}/reverse` | Hapus transaksi terbitan: ditolak `HTTP 403`<br>Reversal terbit otomatis bernilai `expense`<br>Hapus reversal: ditolak `HTTP 403` | **PASS** |
| **TC-S4-07** | **Pencatatan Belanja Beritem & Integrasi Buku Kas**<br>Memverifikasi kalkulasi otomatis subtotal item belanja nota dan pencatatan otomatis transaksi pengeluaran kas. | Bendahara RT | `POST /api/v1/admin/finance/purchases`<br>`Model Purchase, PurchaseItem` | Total IDR 295.000 terhitung otomatis<br>Relasi `financeTransaction` terhubung jenis `expense` | **PASS** |

---

## 3. Tabel 2: Rekaman Audit Penyelarasan Tata Kelola Keuangan & Layanan Warga

| Parameter Tata Kelola | Kondisi Sebelum Sprint 4 | Implementasi Standar Sprint 4 | Bukti Verifikasi Sistem | Status Evaluasi |
|---|---|---|---|:---:|
| **Integritas Pembukuan Kas** | Potensi penghapusan catatan kas oleh bendahara/admin tanpa jejak jejak audit (*tampering risk*). | Transaksi kas `published` dan `reversed` dilarang dihapus secara permanen; pembatalan wajib melalui jurnal pembalik (*double-entry offsetting journal*). | Pengujian `TC-S4-06` memverifikasi penolakan `HTTP 403` dan pencatatan audit log `finance.reversed`. | **COMPLIANT** |
| **Pencegahan Pembayaran Iuran Anomali** | Potensi *race condition* pembayaran ganda atau *overpayment* yang merugikan saldo warga. | Penguncian baris `lockForUpdate()` dalam transaksi DB dan validasi `paidAmount + newAmount <= amount_due_idr`. | Pengujian `TC-S4-05` memastikan overpayment dan duplikasi bayar tagihan lunas ditolak `HTTP 422`. | **COMPLIANT** |
| **Akuntabilitas Aktor Transaksi** | Fallback nilai statis `1` jika ID kasir/penerima tidak disediakan. | Menggunakan ID dari pengguna yang terotentikasi (`$receiver?->id`) pada `ResidentDueService` dan `PurchaseService`. | Rekaman `finance_transactions.created_by` sesuai dengan ID aktor penerima riil. | **COMPLIANT** |
| **Kerahasiaan Pengaduan Warga** | Laporan sensitif berisiko mengekspos nama pelapor kepada pihak lain. | Opsi `is_anonymous = true` menyembunyikan relasi pengguna dari respons API warga dan admin. | Pengujian `TC-S4-03` membuktikan atribut `user` bernilai `null` pada pembacaan detail keluhan. | **COMPLIANT** |
| **Rantai Pengesahan Surat** | Status surat berpotensi dimanipulasi langsung menjadi selesai tanpa verifikasi. | Penegakan status bertingkat: `submitted` ➔ `verified` ➔ `approved` ➔ `completed` dengan token verifikasi QR. | Pengujian `TC-S4-01` menolak loncatan status dan menolak pembalikan surat yang telah selesai. | **COMPLIANT** |

---

## 4. Tabel 3: Gerbang Audit Sprint 4 (Sprint 4 Audit Gate Table)

| Komponen Evaluasi Gerbang | Kriteria Kelulusan Wajib | Bukti Hasil Pemeriksaan Nyata | Status Gerbang |
|---|---|---|:---:|
| **1. Alur Birokrasi & Finalitas Surat** | Rantai 2-tier (Sekretaris verifikasi, RT approve) ditegakkan; surat selesai dilindungi dari perubahan status. | Teruji pada `TC-S4-01`: status sekuensial berjalan tertib, loncatan tahap dan *revert* ditolak dengan `HTTP 409 Conflict`. | **PASS** |
| **2. Isolasi Akses Dokumen Warga** | Warga tidak diizinkan mengakses dokumen milik warga lain. | Teruji pada `TC-S4-02`: seluruh percobaan baca/ubah/hapus oleh akun non-pemilik ditolak `HTTP 403 Forbidden`. | **PASS** |
| **3. Perlindungan Whistleblower & Aduan** | Identitas pelapor keluhan anonim disembunyikan; petugas dapat menindaklanjuti keluhan. | Teruji pada `TC-S4-03`: atribut pengguna tersembunyi dan transisi status administratif berjalan sukses. | **PASS** |
| **4. Integritas Pembayaran & Anti-Overpay** | Pembayaran iuran aman dari *double payment* dan *overpayment* melalui penguncian konkurensi. | Teruji pada `TC-S4-05`: `lockForUpdate()` aktif, overpay ditolak `HTTP 422`, status tagihan beralih `PARTIAL` ➔ `PAID`. | **PASS** |
| **5. Jurnal Kas Anti-Fraud & Reversal** | Transaksi terpublikasi dilarang dihapus; pembatalan menghasilkan jurnal pengimbang. | Teruji pada `TC-S4-06`: penghapusan ditolak `HTTP 403`, alur pembatalan menerbitkan transaksi pengurang (*expense*). | **PASS** |
| **6. Otomasi Pengeluaran Kas Belanja** | Belanja beritem menghitung total subtotal otomatis dan terhubung ke Buku Kas Umum. | Teruji pada `TC-S4-07`: subtotal 295.000 IDR terhitung akurat dan entri `FinanceTransaction` terbit otomatis. | **PASS** |
| **7. Kualitas Kode & Regresi Penuh** | Seluruh test suite lulus 100% dan lolos standar formatting Laravel Pint. | **195 tests, 872 assertions, 100% PASSED**; Laravel Pint: `fixed & passed`. | **PASS** |

---

### Keputusan Resmi Gerbang Sprint 4: **PASS**
*Seluruh kriteria kelulusan TOGAF ADM Phase G untuk domain Pelayanan Inti dan Keuangan Kas telah terpenuhi 100%. Implementasi terbukti aman, anti-fraud, dan siap dilanjutkan ke **Sprint 5 — Real-Time, Pengujian Beban, dan Penutupan Proyek (Phase G & H: WebSockets Pusher/Reverb, Optimasi Read-Heavy Cache, & Hardening UAT)**.*
