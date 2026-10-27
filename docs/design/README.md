# Dokumentasi Desain Sistem & Rekayasa Perangkat Lunak (`docs/design/`)

Subfolder ini memuat pemodelan arsitektur perangkat lunak, perancangan antarmuka pengguna (UI/UX), diagram sistem UML terstruktur, dan pemodelan data relasional untuk **PRISMA RT 04 Kemayoran**.

---

## Berkas Dokumentasi dalam Subfolder Ini

1. **[SYSTEM_MODELING_5_ELEMENTS.md](SYSTEM_MODELING_5_ELEMENTS.md)** *(dokumen induk)*:
   - Pemetaan **5 unsur pemodelan** (ERD, LRS, Use Case, Activity, UML) ke fase **Waterfall** proyek, penelusuran ke kode Laravel dan tes, serta aturan pemeliharaan diagram.
2. **[LRS.md](LRS.md)**:
   - Logical Record Structure sebagai dokumen terpisah: diagram per modul, aturan transformasi ERD ke LRS, matriks FK beserta `ON DELETE`, daftar tabel, dan catatan normalisasi 3NF.
   - Pasangannya, [ERD.md](ERD.md): Entity Relationship Diagram 27 tabel dalam enam modul.
3. **[DIAGRAMS.md](DIAGRAMS.md)**:
   - Kumpulan diagram: ERD, use case, activity (swimlane), class, dan sequence. Berkas gambar ada di `diagrams/`.
4. **[BRAND_GUIDE.md](BRAND_GUIDE.md)**:
   - Pedoman identitas visual resmi PRISMA RT 04 (warna primer Navy `#1B365D`, Teal `#3A9696`, aksen gradien Blue-Cyan, tipografi Plus Jakarta Sans, dan logo resmi).
5. **[UI_PLAN.md](UI_PLAN.md)**:
   - Rencana tata letak antarmuka publik responsif, kartu layanan interaktif, drawer navigasi mobile, dan komponen Blade modular berbasis Tailwind CSS v4.
6. **[WEB_PERFORMANCE_SEO_GEO.md](WEB_PERFORMANCE_SEO_GEO.md)**:
   - Optimasi Core Web Vitals (LCP, CLS, INP), metadata SEO lokal (JSON-LD Local Governance), dan koordinat geolokasi Kemayoran, Jakarta Pusat.
7. **[CIRCULAR_FRAMEWORK_METHODS_AND_ALGORITHMS.md](CIRCULAR_FRAMEWORK_METHODS_AND_ALGORITHMS.md)**:
   - **Penerapan Metode, Algoritma & Standarisasi Kerangka Diagram Lingkaran Sistem**: Detail 8 segmen keliling (Decentralized, Trust, Timestamp, Hash/Signature, Ethics, Autonomous Agent, Governance, AI) dan 3 inti pusat (Governance, Timestamp, Privacy), formula matematika hashing, dan verifikasi integritas.

---

## 5 Unsur Pemodelan dan Fase Waterfall

| No | Unsur | Fase Waterfall | Dokumen |
|---|---|---|---|
| 1 | ERD | Perancangan | [ERD.md](ERD.md) |
| 2 | LRS | Perancangan | [LRS.md](LRS.md) |
| 3 | Use Case | Analisis kebutuhan | [DIAGRAMS.md](DIAGRAMS.md) bagian 3 |
| 4 | Activity | Perancangan | [DIAGRAMS.md](DIAGRAMS.md) bagian 4 |
| 5 | UML (Class, Sequence) | Perancangan | [DIAGRAMS.md](DIAGRAMS.md) bagian 5 |

Penelusuran lengkap ke implementasi, pengujian, dan pemeliharaan: [SYSTEM_MODELING_5_ELEMENTS.md](SYSTEM_MODELING_5_ELEMENTS.md).