# PANDUAN OPERASIONAL PRODUKSI, DISASTER RECOVERY, DAN ROLLBACK (RUNBOOK)
## Penyelarasan TOGAF ADM: Phase H — Architecture Change Management & Deployment Governance
**Platform Layanan Publik Warga — Versi Rilis 1.0.0**  
**Tanggal:** 16 Oktober 2026

---

## 1. Arsitektur Lingkungan Produksi

Lingkungan produksi disiapkan untuk menjamin ketersediaan tinggi (*high availability*), integritas data keuangan anti-fraud, dan kepatuhan penuh terhadap Undang-Undang Perlindungan Data Pribadi (UU PDP No. 27/2022).

```text
       [ Klien Web / Mobile ]
                 │ (HTTPS)
                 ▼
         [ Nginx Reverse Proxy ]
                 │
      ┌──────────┴──────────┐
      ▼                     ▼
[ Laravel PHP 8.4 ]   [ WebSockets / Pusher ]
  (APP_DEBUG=false)   (Real-Time Broadcasting)
      │
      ├─────────────────────┬─────────────────────┐
      ▼                     ▼                     ▼
[ MySQL 8.4 InnoDB ]  [ Local Private Store ]  [ Audit Trail Log ]
 (layanan_warga_prod)   (storage/app/private)   (Tabel audit_logs)
```

---

## 2. Prosedur Perilisan Produksi (*Deployment Runbook*)

### A. Persiapan Pra-Rilis (*Pre-Flight Checklist*)
1. Pastikan seluruh 205 pengujian otomatis berstatus lulus 100%:
   ```bash
   php artisan test --compact
   ```
2. Pastikan tidak ada kerentanan keamanan pada pustaka pihak ketiga:
   ```bash
   composer audit
   ```
3. Pastikan konfigurasi `.env` produksi berada di luar repositori Git dan memiliki nilai:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `SESSION_SECURE_COOKIE=true`
   - `SESSION_ENCRYPT=true`

### B. Langkah Eksekusi Deployment
1. Unduh pustaka produksi tanpa dependensi pengujian:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
2. Kompilasi aset antarmuka frontend:
   ```bash
   npm ci
   npm run build
   ```
3. Jalankan migrasi basis data secara aman:
   ```bash
   php artisan migrate --force
   ```
4. Optimalkan performa cache konfigurasi, rute, dan tampilan:
   ```bash
   php artisan config:cache
   php artisan route:cache
   劈p artisan view:cache
   php artisan event:cache
   ```
5. Mulai ulang worker antrean (jika menggunakan queue worker):
   ```bash
   php artisan queue:restart
   ```

---

## 3. Rencana Pemulihan Bencana (*Disaster Recovery & Backup Rehearsal*)

### A. Pembuatan Snapshot Data & Checksum Kriptografis
Sistem menyediakan fitur pencadangan snapshot terenkripsi dan terverifikasi hash `SHA-256` melalui endpoint:
- **Endpoint:** `POST /api/v1/admin/finance/backup-store`
- **Wewenang:** Bendahara, Sekretaris, Ketua RT, Superadmin.
- **Lokasi Penyimpanan:** `storage/app/private/financial-reports/backups/`
- **Output:** Berkas JSON terformat dengan metadata integritas dan checksum hash SHA-256 yang dicatat dalam log audit.

### B. Prosedur Pencadangan Harian Basis Data (*Database Backup*)
Jalankan pencadangan terjadwal pada tingkat server basis data setiap hari pukul 02:00 WIB:
```bash
mysqldump -u prod_warga_user -p --single-transaction --quick --lock-tables=false \
  layanan_warga_production > /var/backups/warga/db_backup_$(date +\%Y\%m\%d_\%H\%M\%S).sql
```

### C. Prosedur Pemulihan (*Database Restoration Procedure*)
Jika terjadi kegagalan perangkat keras atau kerusakan data:
1. Aktifkan mode pemeliharaan (*maintenance mode*):
   ```bash
   php artisan down --secret="pemulihan-darurat-rt04"
   ```
2. Pulihkan berkas SQL cadangan terakhir:
   ```bash
   mysql -u prod_warga_user -p layanan_warga_production < /var/backups/warga/db_backup_TERAKHIR.sql
   ```
3. Validasi keutuhan data:
   - Jalankan kueri verifikasi jumlah baris pada tabel `citizens`, `family_cards`, `letters`, dan `finance_transactions`.
   - Periksa kesesuaian checksum SHA-256 berkas cadangan snapshot.
4. Nonaktifkan mode pemeliharaan:
   ```bash
   php artisan up
   ```

---

## 4. Pemantauan Kesehatan Sistem (*Health Check & Monitoring*)

1. **Pemeriksaan Detak Jantung Aplikasi (*Liveness Probe*):**
   - **URL:** `GET /up`
   - **Ekspektasi:** Status `HTTP 200 OK`. Jika mengembalikan kode status selain 200, lakukan restart pada container atau web server PHP-FPM.
2. **Pemeriksaan Log Insiden:**
   - Seluruh kesalahan aplikasi tingkat fatal dicatat pada `storage/logs/laravel.log`.
   - Pastikan tidak ada data NIK plaintext atau kredensial yang tercetak pada file log.

---

## 5. Prosedur Pembatalan Cepat (*Rollback Runbook*)

### A. Kriteria Pemicu Rollback (*Rollback Triggers*)
Rollback wajib dieksekusi segera apabila salah satu kondisi kritis berikut terdeteksi dalam waktu 30 menit pasca deployment:
1. Terjadi kebocoran otorisasi (*privilege escalation*) di mana akun Warga dapat membuka data admin atau data warga lain.
2. Migrasi skema database gagal atau merusak integritas kunci asing (*foreign key corruption*).
3. Terjadi lonjakan kesalahan `HTTP 500 Internal Server Error` melampaui ambang batas 1% dari total trafik.
4. Alur inti birokrasi surat, pengaduan, atau pembukuan kas tidak dapat diakses sama sekali.

### B. Langkah Eksekusi Rollback
1. Masuk ke mode pemeliharaan:
   ```bash
   php artisan down
   ```
2. Kembalikan versi Git ke tag stabil sebelumnya (misal: `v0.9.0-rc` atau commit stabil terakhir):
   ```bash
   git checkout tags/v0.9.0-rc
   ```
3. Kembalikan migrasi jika deployment terakhir memuat migrasi baru:
   ```bash
   php artisan migrate:rollback --step=1
   ```
4. Bersihkan seluruh cache aplikasi:
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   ```
5. Kembalikan dependensi ke versi stabil sebelumnya:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
6. Buka kembali akses aplikasi:
   ```bash
   php artisan up
   ```
7. Buat laporan insiden perubahan (*incident post-mortem report*) sesuai standar TOGAF Phase H.
