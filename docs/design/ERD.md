# Entity-Relationship Diagram (ERD) & Kamus Arsitektur Data
## TOGAF ADM: Phase C — Data Architecture (Sprint 2 Baseline)
**Platform Layanan Publik Warga — MySQL Laragon Architecture**  
**Versi:** 1.1.0-snapshot | **Tanggal Pemutakhiran:** 16 Oktober 2026

---

## 1. Visual Entity-Relationship Diagram

Pemodelan diagram relasi entitas berikut mencerminkan skema fisik aktual basis data MySQL `layanan_warga` di lingkungan Laragon, mematuhi prinsip *single source of truth* untuk akun-warga (`citizens.user_id`), nama fisik tabel jenis surat (`jenis_surat`), serta integritas buku kas (`due_payments.finance_transaction_id`):

Dibangkitkan dari skema hasil migrasi (`php artisan migrate`), 27 tabel domain dipecah menjadi enam modul agar terbaca. Kolom `created_at` dan `updated_at` ada di semua tabel dan tidak digambar. Tabel framework (`cache`, `jobs`, `sessions`) tidak ditampilkan.

**Cara membaca:** kotak berheader warna adalah tabel modul tersebut; kotak abu-abu hanya penanda tabel dari modul lain (hanya PK). `+id` tebal adalah primary key, nama miring adalah foreign key, `{UK}` adalah kolom unik. Garis putus-putus berarti FK boleh kosong (*nullable*), garis utuh berarti FK wajib terisi. Ujung garis memakai notasi *crow's foot*: ‖ tepat satu, ○| nol atau satu, ○< nol atau banyak.


**1.1. Akun dan Akses**

![ERD akun dan akses](diagrams/erd-1-akun-akses.svg)

**1.2. Kependudukan**

![ERD kependudukan](diagrams/erd-2-kependudukan.svg)

**1.3. Surat Pengantar**

![ERD surat pengantar](diagrams/erd-3-surat.svg)

**1.4. Keuangan dan Iuran**

![ERD keuangan dan iuran](diagrams/erd-4-keuangan.svg)

**1.5. Pengaduan dan Keamanan**

![ERD pengaduan dan keamanan](diagrams/erd-5-pengaduan-keamanan.svg)

**1.6. Informasi RT dan Audit**

![ERD informasi RT dan audit](diagrams/erd-6-informasi-audit.svg)

`notifications` dan `personal_access_tokens` terhubung ke `users` secara polimorfik (`notifiable_id`, `tokenable_id`), bukan lewat foreign key database.


---

## 2. Inventarisasi Tabel Operasional Basis Data (MySQL `layanan_warga`)

Berikut adalah spesifikasi teknis lengkap dari 18 tabel operasional inti sistem:

| No | Nama Tabel Fisik | Primary Key (PK) | Foreign Keys (FK) & Relasi | Kolom Nullable | Kunci Unik (Unique Constraints) | Kardinalitas | Aturan Hapus (Delete Rules) |
|:--:|---|---|---|---|---|:---:|---|
| **1** | `users` | `id` (Bigint Unsigned Auto-Increment) | *Tidak ada* | `email_verified_at`, `phone`, `last_login_at`, `warga_id` | `email` (UK), `warga_id` (Legacy UK) | 1 : 0..1 ke `citizens` | - |
| **2** | `family_cards` | `id` (Bigint Unsigned Auto-Increment) | *Tidak ada* | `postal_code` | `no_kk_hash` (UK) | 1 : N ke `citizens` | - |
| **3** | `citizens` | `id` (Bigint Unsigned Auto-Increment) | `family_card_id` -> `family_cards.id`<br>`user_id` -> `users.id` | `user_id`, `education`, `occupation`, `marital_status`, `address`, `rt`, `rw`, `phone` | `nik_hash` (UK), `user_id` (UK) | N : 1 ke `family_cards`<br>0..1 : 1 ke `users` | `family_card_id`: **CASCADE**<br>`user_id`: **SET NULL** |
| **4** | `jenis_surat` | `id` (Bigint Unsigned Auto-Increment) | *Tidak ada* | `deskripsi`, `template_fields`, `required_attachments` | `kode_surat` (UK) | 1 : N ke `letters` | - |
| **5** | `letters` | `id` (Bigint Unsigned Auto-Increment) | `citizen_id` -> `citizens.id`<br>`jenis_surat_id` -> `jenis_surat.id`<br>`verified_by` -> `users.id`<br>`approved_by` -> `users.id` | `letter_number`, `rejection_reason`, `submitted_at`, `verified_at`, `verified_by`, `approved_at`, `approved_by`, `completed_at`, `verification_token`, `document_hash`, `pdf_path` | `ticket_number` (UK), `letter_number` (UK), `verification_token` (UK) | N : 1 ke `citizens`<br>N : 1 ke `jenis_surat` | `citizen_id`: **CASCADE**<br>`jenis_surat_id`: **SET NULL**<br>`verified_by`: **SET NULL**<br>`approved_by`: **SET NULL** |
| **6** | `letter_attachments` | `id` (Bigint Unsigned Auto-Increment) | `letter_id` -> `letters.id` | *Tidak ada* | *Tidak ada* | N : 1 ke `letters` | `letter_id`: **CASCADE** |
| **7** | `resident_dues` | `id` (Bigint Unsigned Auto-Increment) | `citizen_id` -> `citizens.id` | `notes` | Composite `(citizen_id, period_year, period_month)` (UK) | 1 : N ke `due_payments` | `citizen_id`: **CASCADE** |
| **8** | `due_payments` | `id` (Bigint Unsigned Auto-Increment) | `resident_due_id` -> `resident_dues.id`<br>`finance_transaction_id` -> `finance_transactions.id`<br>`received_by` -> `users.id` | `proof_path`, `received_by`, `notes` | `receipt_number` (UK) | N : 1 ke `resident_dues`<br>N : 1 ke `finance_transactions` | `resident_due_id`: **CASCADE**<br>`finance_transaction_id`: **RESTRICT**<br>`received_by`: **SET NULL** |
| **9** | `finance_transactions` | `id` (Bigint Unsigned Auto-Increment) | `created_by` -> `users.id`<br>`original_transaction_id` -> `finance_transactions.id` | `created_by`, `original_transaction_id` | `transaction_number` (UK) | 1 : N ke `due_payments`<br>1 : N ke `purchases` | `created_by`: **SET NULL**<br>`original_transaction_id`: **SET NULL** |
| **10** | `purchases` | `id` (Bigint Unsigned Auto-Increment) | `finance_transaction_id` -> `finance_transactions.id`<br>`created_by` -> `users.id` | `proof_invoice_path`, `finance_transaction_id`, `created_by` | `purchase_number` (UK) | 1 : N ke `purchase_items` | `finance_transaction_id`: **SET NULL**<br>`created_by`: **SET NULL** |
| **11** | `purchase_items` | `id` (Bigint Unsigned Auto-Increment) | `purchase_id` -> `purchases.id` | *Tidak ada* | *Tidak ada* | N : 1 ke `purchases` | `purchase_id`: **CASCADE** |
| **12** | `financial_reports` | `id` (Bigint Unsigned Auto-Increment) | `published_by` -> `users.id` | `published_by`, `published_at` | Composite `(year, quarter, revision)` (UK) | 1 : 1 per Triwulan Revisi | `published_by`: **SET NULL** |
| **13** | `complaints` | `id` (Bigint Unsigned Auto-Increment) | `citizen_id` -> `citizens.id`<br>`user_id` -> `users.id`<br>`assigned_admin_id` -> `users.id` | `citizen_id`, `user_id`, `assigned_admin_id`, `admin_response`, `resolved_at`, `attachment_path` | `ticket_number` (UK) | N : 1 ke `citizens`<br>N : 1 ke `users` | `citizen_id`: **SET NULL**<br>`user_id`: **SET NULL**<br>`assigned_admin_id`: **SET NULL** |
| **14** | `security_reports` | `id` (Bigint Unsigned Auto-Increment) | `reporter_user_id` -> `users.id`<br>`citizen_id` -> `citizens.id`<br>`assigned_to` -> `users.id` | `reporter_user_id`, `citizen_id`, `assigned_to`, `incident_action_notes`, `resolved_at` | `ticket_number` (UK) | N : 1 ke `users` | `reporter_user_id`: **SET NULL**<br>`citizen_id`: **SET NULL**<br>`assigned_to`: **SET NULL** |
| **15** | `audit_logs` | `id` (Bigint Unsigned Auto-Increment) | `user_id` -> `users.id` | `user_id`, `old_values`, `new_values`, `ip_address`, `user_agent` | *Tidak ada* | N : 1 ke `users` | `user_id`: **SET NULL** |
| **16** | `admin_permissions` | `id` (Bigint Unsigned Auto-Increment) | `user_id` -> `users.id` | *Tidak ada* | Composite `(user_id, permission)` (UK) | N : 1 ke `users` | `user_id`: **CASCADE** |
| **17** | `document_sequences`| `id` (Bigint Unsigned Auto-Increment) | *Tidak ada* | *Tidak ada* | Composite `(document_type, year, month)` (UK) | Singleton Generator | - |
| **18** | `download_audits` | `id` (Bigint Unsigned Auto-Increment) | `actor_user_id` -> `users.id` | `actor_user_id`, `ip_address` | *Tidak ada* | N : 1 ke `users` | `actor_user_id`: **SET NULL** |

---

## 3. Resolusi Keputusan Desain Data Kritis (Sprint 2 Architectural Gates)

### 3.1 Penetapan Hubungan Tunggal Akun–Warga (*Single Source of Truth*)
1. **Aturan Utama:** Relasi antara akun otentikasi (`users`) dan profil kependudukan (`citizens`) diatur secara satu arah melalui kolom `citizens.user_id` (Bigint Unsigned, Nullable, Unique).
2. **Kardinalitas:** `users` 1 ke 0..1 `citizens`. Satu akun pengguna hanya dapat mengklaim satu data warga, dan satu data warga maksimal dihubungkan ke satu akun.
3. **Pemisahan Identifier:** Nilai `users.id` **TIDAK HARUS SAMA** dengan `citizens.id`. Sebagai contoh, Warga dengan `citizens.id = 15` dapat memiliki akun login dengan `users.id = 3`.
4. **Deprekasi `users.warga_id`:**
   - Kolom `users.warga_id` yang ada pada baseline awal telah ditandai sebagai **DEPRECATED**.
   - Model `User` menggunakan relasi `public function citizen(): HasOne { return $this->hasOne(Citizen::class, 'user_id'); }`.
   - Sebelum kolom `warga_id` dihapus fisik di Sprint 6, seluruh data pasangan lama diaudit guna menjamin tidak ada inkonsistensi pasangan (`users.warga_id != citizens.id`).
5. **Hubungan Kartu Keluarga:** `citizens.family_card_id` tetap dipertahankan mengacu ke `family_cards.id` dengan relasi N:1 dan delete rule `CASCADE`.

### 3.2 Standardisasi Nama Fisik Tabel `jenis_surat`
- Sesuai keputusan **ADR-02**, basis data fisik MySQL menggunakan nama tabel `jenis_surat`.
- Dokumentasi teknis, ERD, dan diagram UML wajib menggunakan label `jenis_surat` (bukan istilah rekaan seperti `letter_types`) guna mencegah miskomunikasi antara perancang dan pengembang basis data.
- Model Eloquent mengonfigurasi properti: `protected $table = 'jenis_surat';`.

### 3.3 Penjaminan Integritas Buku Kas Transparan (*Financial Ledger Integrity*)
1. **Relasi Foreign Key:** Kolom `due_payments.finance_transaction_id` berelasi langsung ke `finance_transactions.id`.
2. **Aturan Hapus (Delete Rule) `RESTRICT`:**
   - Aturan penghapusan disetel ke `RESTRICT` (`ON DELETE RESTRICT`).
   - Apabila seorang pengurus mencoba menghapus transaksi kas yang menjadi dasar pembukuan kwitansi iuran warga, MySQL akan menolak transaksi tersebut (*foreign key constraint fails*).
3. **Mekanisme Reversal (Anti-Fraud):**
   - Transaksi kas yang berstatus `published` bersifat *immutable* (tidak dapat diubah/dihapus).
   - Koreksi salah input nominal wajib dilakukan melalui penerbitan transaksi pembalik bertanda `is_reversal = true` dengan referensi `original_transaction_id`.
   - Hal ini menjamin audit keuangan warga tetap 100% transparan dan tidak ada transaksi "hilang secara gaib".

### 3.4 Perlindungan Privasi Data Pribadi (UU PDP No. 27/2022)
- Kolom NIK (`citizens.nik`) dan Nomor KK (`family_cards.no_kk`) disimpan terenkripsi menggunakan algoritma simetris `AES-256-CBC`.
- Kolom pencarian cepat menggunakan hash deterministik non-reversibel `SHA-256` (`nik_hash`, `no_kk_hash`) berindeks unik (*Unique Index*).
- Respons JSON publik dilarang keras mengembalikan plaintext NIK; data hanya dikembalikan dalam format tersamar (`nik_masked`, contoh: `3201************`).
