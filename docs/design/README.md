# Dokumentasi Desain Sistem & Rekayasa Perangkat Lunak (`docs/design/`)

Subfolder ini memuat pemodelan arsitektur perangkat lunak, perancangan antarmuka pengguna (UI/UX), diagram sistem UML terstruktur, dan pemodelan data relasional untuk **PRISMA RT 04 Kemayoran**.

---

## Berkas Dokumentasi dalam Subfolder Ini

1. **[ERD.md](ERD.md)**:
   - Entity Relationship Diagram database relasional MySQL 8.x yang memetakan seluruh entitas: `users`, `citizens`, `family_cards`, `letters`, `finance_transactions`, `resident_dues`, `complaints`, `security_reports`, hingga `audit_logs`.
2. **[DIAGRAMS.md](DIAGRAMS.md)**:
   - Diagram Sequence untuk penanganan *Optimistic Concurrency Control* dan State Machine Activity Diagram untuk alur persetujuan surat pengantar.
3. **[BRAND_GUIDE.md](BRAND_GUIDE.md)**:
   - Pedoman identitas visual resmi PRISMA RT 04 (warna primer Navy `#1B365D`, Teal `#3A9696`, aksen gradien Blue-Cyan, tipografi Plus Jakarta Sans, dan logo resmi).
4. **[UI_PLAN.md](UI_PLAN.md)**:
   - Rencana tata letak antarmuka publik responsif, kartu layanan interaktif, drawer navigasi mobile, dan komponen Blade modular berbasis Tailwind CSS v4.
5. **[WEB_PERFORMANCE_SEO_GEO.md](WEB_PERFORMANCE_SEO_GEO.md)**:
   - Optimasi Core Web Vitals (LCP, CLS, INP), metadata SEO lokal (JSON-LD Local Governance), dan koordinat geolokasi Kemayoran, Jakarta Pusat.
6. **[CIRCULAR_FRAMEWORK_METHODS_AND_ALGORITHMS.md](CIRCULAR_FRAMEWORK_METHODS_AND_ALGORITHMS.md)**:
   - **Penerapan Metode, Algoritma & Standarisasi Kerangka Diagram Lingkaran Sistem**: Detail 8 segmen keliling (Decentralized, Trust, Timestamp, Hash/Signature, Ethics, Autonomous Agent, Governance, AI) dan 3 inti pusat (Governance, Timestamp, Privacy), formula matematika hashing, dan verifikasi integritas.

---

## 5 Unsur Pemodelan Rekayasa Perangkat Lunak Utama

1. **Entity Relationship Diagram (ERD)**: Relasi relasional dengan foreign key terindeks dan integritas cascade.
2. **Logical Record Structure (LRS)**: Representasi tabel ternormalisasi bentuk ketiga (3NF) dengan tipe data presisi tinggi (`DECIMAL(15,2)` untuk nilai moneter).
3. **Use Case Diagram**: Pemetaan interaksi antara Aktor (Warga, Pengurus, Superadmin) dan subsistem layanan.
4. **Activity Diagram**: Alur aktivitas bisnis mulai dari verifikasi NIK hingga penerbitan dokumen resmi ber-QR Code.
5. **UML Diagrams (Class & Sequence Diagram)**: Struktur kelas controller-service-model dan urutan interaksi antarobjek sistem.
