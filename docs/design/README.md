# Dokumentasi Desain Sistem & Rekayasa Perangkat Lunak (`docs/design/`)

Subfolder ini memuat pemodelan arsitektur perangkat lunak, perancangan antarmuka pengguna (UI/UX), diagram sistem UML terstruktur, dan pemodelan data relasional untuk **PRISMA RT 04 Kemayoran**.

---

## Berkas Dokumentasi dalam Subfolder Ini

1. **[SYSTEM_MODELING_5_ELEMENTS.md](SYSTEM_MODELING_5_ELEMENTS.md)** *(Master Rekayasa Perangkat Lunak)*:
   - **Dokumentasi Lengkap 5 Unsur Pemodelan Sistem**: UML (Class, Sequence, Component), ERD Relasional, Logical Record Structure (LRS 3NF), Activity Diagram (Format Kotak Tabel Step-by-Step), dan Use Case Diagram dengan pemisahan tegas peran: **Superadmin & Admin di sisi kiri** dan **User & Warga di sisi kanan**.
2. **[ERD.md](ERD.md)**:
   - Entity Relationship Diagram database relasional MySQL 8.x yang memetakan seluruh entitas utama sistem.
3. **[DIAGRAMS.md](DIAGRAMS.md)**:
   - Diagram Sequence untuk penanganan *Optimistic Concurrency Control* dan State Machine Activity Diagram untuk alur persetujuan surat pengantar.
4. **[BRAND_GUIDE.md](BRAND_GUIDE.md)**:
   - Pedoman identitas visual resmi PRISMA RT 04 (warna primer Navy `#1B365D`, Teal `#3A9696`, aksen gradien Blue-Cyan, tipografi Plus Jakarta Sans, dan logo resmi).
5. **[UI_PLAN.md](UI_PLAN.md)**:
   - Rencana tata letak antarmuka publik responsif, kartu layanan interaktif, drawer navigasi mobile, dan komponen Blade modular berbasis Tailwind CSS v4.
6. **[WEB_PERFORMANCE_SEO_GEO.md](WEB_PERFORMANCE_SEO_GEO.md)**:
   - Optimasi Core Web Vitals (LCP, CLS, INP), metadata SEO lokal (JSON-LD Local Governance), dan koordinat geolokasi Kemayoran, Jakarta Pusat.
7. **[CIRCULAR_FRAMEWORK_METHODS_AND_ALGORITHMS.md](CIRCULAR_FRAMEWORK_METHODS_AND_ALGORITHMS.md)**:
   - **Penerapan Metode, Algoritma & Standarisasi Kerangka Diagram Lingkaran Sistem**: Detail 8 segmen keliling (Decentralized, Trust, Timestamp, Hash/Signature, Ethics, Autonomous Agent, Governance, AI) dan 3 inti pusat (Governance, Timestamp, Privacy), formula matematika hashing, dan verifikasi integritas.

---

## 5 Unsur Pemodelan Rekayasa Perangkat Lunak Utama

Seluruh rincian 5 unsur telah dikompilasi secara mendalam pada berkas **[SYSTEM_MODELING_5_ELEMENTS.md](SYSTEM_MODELING_5_ELEMENTS.md)**:

1. **Unified Modeling Language (UML)**: Class Diagram, Sequence Diagram (Birokrasi Persuratan 2-Tier & Concurrency Control), dan Component Architecture Diagram.
2. **Entity Relationship Diagram (ERD)**: Relasi relasional 15+ entitas dengan foreign key terindeks dan integritas cascade.
3. **Logical Record Structure (LRS)**: Representasi tabel ternormalisasi bentuk ketiga (3NF) dengan tipe data presisi moneter `DECIMAL(15,2)` dan pemetaan pointer PK-FK.
4. **Activity Diagram (Format Kotak Tabel Step-by-Step)**: Alur aktivitas bisnis berpetak (Superadmin/Admin di sisi kiri, Sistem di tengah, User/Warga di sisi kanan).
5. **Use Case Diagram & Spesifikasi Aktor**: Pemetaan interaksi antara Aktor Pengurus RT & Superadmin dengan Warga Lingkungan dan Instansi Publik.
