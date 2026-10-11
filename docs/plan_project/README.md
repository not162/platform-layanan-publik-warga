# Dokumentasi Rencana & Metodologi Proyek (`docs/plan_project/`)

Subfolder ini merinci linimasa pengembangan perangkat lunak menggunakan kombinasi metodologi **Waterfall (Perancangan Komprehensif Awal) + Incremental Sprint (Implementasi & Pengujian Bertahap)** untuk **PRISMA RT 04 Kemayoran**.

---

## Berkas Dokumentasi dalam Subfolder Ini

1. **[PROJECT_PLAN.md](PROJECT_PLAN.md)**:
   - Rencana induk proyek, deskripsi ruang lingkup pekerjaan, target pencapaian (*deliverables*), alokasi arsitektur sistem, dan standar penerimaan (*acceptance criteria*).
2. **[SPRINT_PLAN.md](SPRINT_PLAN.md)**:
   - Rincian pembagian sprint pengembangan bertahap dari Sprint 0 hingga Sprint 6:
     - **Sprint 0**: Audit Keamanan, Sanitasi Kredensial, dan Karantina Secrets.
     - **Sprint 1**: Fondasi Antarmuka UI & Komponen Blade PRISMA RT 04.
     - **Sprint 2**: Alur Autentikasi, Registrasi Mandiri Warga, dan Penegakan RBAC.
     - **Sprint 3**: Layanan Warga (Birokrasi Surat 2-Tier, Pengaduan, dan Keamanan).
     - **Sprint 4**: Akuntabilitas Keuangan (Buku Kas & Iuran) dan Informasi Komunitas.
     - **Sprint 5**: PWA, Optimasi Redis Cache & Asynchronous Queue Workers.
     - **Sprint 6**: Pengujian Menyeluruh (PHPUnit 150 Tests), Pint Styling & Validasi Staging.

---

## Prinsip Alur Kerja Percabangan Git

Pengembangan mematuhi isolasi lingkungan ketat:
```
Feature Branch (feature/refactor-prisma-laravel13)
       │
       ▼
Development Branch (development)
       │
       ▼
Staging Environment (staging - Validasi QA & UAT)
       │
       ▼
Production Environment (main / production-stable - Persetujuan Resmi)
```
- **Aturan Ketat:** Dilarang commit langsung ke cabang `main` atau `production-stable`.
- **Database Terpisah:** Database pengujian/staging terisolasi penuh dari database produksi nyata.
