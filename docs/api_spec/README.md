# Dokumentasi API PRISMA RT 04 (`docs/api_spec/`)

Bagian ini memuat spesifikasi antarmuka pemrograman aplikasi (API) untuk **PRISMA RT 04 Kemayoran**, yang dirancang dengan standar RESTful JSON v1.

---

## Berkas Dokumentasi dalam Subfolder Ini

1. **[openapi.yaml](openapi.yaml)**:
   - Spesifikasi formal **OpenAPI 3.1** yang mencakup seluruh endpoint, skema request/response, autentikasi Sanctum Bearer token, dan respon error terstandardisasi.
   - Dapat diakses secara interaktif via peramban web pada rute `/docs/api` (Swagger UI).
2. **[API_SPEC.md](API_SPEC.md)**:
   - Rincian katalog endpoint API v1 untuk layanan persuratan, pengaduan warga, laporan keamanan, transparansi kas keuangan, dan profil warga.
3. **[API_V1_1_CONTRACT.md](API_V1_1_CONTRACT.md)**:
   - Kontrak spesifikasi tambahan API v1.1 untuk penagihan iuran bulanan warga (*resident dues*), pembayaran iuran, rekonsiliasi kas, dan unduh laporan berkala.

---

## Ringkasan Endpoint Utama

### 1. Autentikasi & Registrasi
- `POST /api/v1/auth/check-citizen` : Validasi instan NIK 16 digit dan deteksi warga terdaftar/antrean.
- `POST /api/v1/register` : Registrasi akun warga mandiri (aktivasi warga sensus maupun pengajuan baru).
- `POST /api/v1/login` : Autentikasi kredensial warga & pengurus, menghasilkan Sanctum Bearer token.
- `POST /api/v1/logout` : Revokasi token akses aktif.

### 2. Layanan Administrasi Surat
- `GET /api/v1/letter-types` : Katalog jenis surat pengantar resmi RT.
- `POST /api/v1/letters` : Pengajuan permohonan surat pengantar baru oleh warga.
- `GET /api/v1/letters/{id}` : Detail status permohonan dan lampiran dokumen.
- `GET /api/v1/public/track/{ticket}` : Pelacakan status permohonan publik via nomor tiket resmi.
- `GET /api/v1/public/letter/verify/{token}` : Verifikasi keaslian dokumen digital via QR Code token.

### 3. Keuangan & Transparansi Kas
- `GET /api/v1/finance` : Daftar transaksi kas masuk dan keluar yang telah dipublikasikan.
- `GET /api/v1/finance/summary` : Ringkasan saldo kas, total penerimaan, dan pengeluaran berjalan.
- `GET /api/v1/me/dues` : Daftar tagihan iuran kebersihan & keamanan warga yang login.
- `GET /api/v1/me/payments` : Riwayat pembayaran iuran warga.

### 4. Aduan & Laporan Keamanan
- `GET|POST /api/v1/complaints` : Pengajuan dan penelusuran aduan fasilitas lingkungan.
- `GET|POST /api/v1/security-reports` : Pelaporan insiden darurat dan koordinasi petugas ronda.

### 5. Informasi Publik Komunitas
- `GET /api/v1/public/overview` : Data agregat portal utama (warta, kas, kontak darurat, pengurus).
- `GET /api/v1/public/announcements` : Pengumuman resmi RT 04 yang sedang aktif.
- `GET /api/v1/public/events` : Jadwal kegiatan kerja bakti, pertemuan, dan agenda warga.
- `GET /api/v1/public/officers` : Struktur organisasi kepengurusan RT 04 Kemayoran.
- `GET /api/v1/public/emergency-contacts` : Kontak darurat penting (Polsek, Damkar, Puskesmas, Satpam).
