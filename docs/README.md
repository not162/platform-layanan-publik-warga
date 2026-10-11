# Indeks Dokumentasi Teknis PRISMA RT 04 Kemayoran

Selamat datang di portal dokumentasi arsitektur, rekayasa perangkat lunak, dan tata kelola sistem **PRISMA RT 04 Kemayoran** (*Platform Realisasi Informasi, Sistem Manajemen & Administrasi*).

Seluruh dokumentasi diklasifikasikan ke dalam **5 subdirektori tematik (5 Pilar Dokumentasi)** untuk memudahkan peninjauan teknis, audit keamanan, dan pengembangan berkelanjutan:

```
docs/
├── 1. api_spec/             # Spesifikasi API, kontrak RESTful JSON, dan skema OpenAPI 3.1
├── 2. business/             # Aturan bisnis, alur registrasi/login, regulasi UU PDP, dan persuratan
├── 3. design/               # Identitas visual PRISMA, ERD relasional, dan diagram alur sistem
├── 4. plan_project/         # Metodologi Waterfall + Incremental Sprint, roadmap, dan backlog
└── 5. project_management/   # Matriks RBAC (Peran & Wewenang), keamanan, dan arsitektur enterprise
```

---

## 1. Spesifikasi API & Integrasi ([`docs/api_spec/`](api_spec/README.md))
- **[README.md](api_spec/README.md)**: Ringkasan katalog endpoint dan panduan pengujian API.
- **[openapi.yaml](api_spec/openapi.yaml)**: Berkas spesifikasi resmi **OpenAPI 3.1** yang dapat diakses secara interaktif via rute `/docs/api`.
- **[API_SPEC.md](api_spec/API_SPEC.md)**: Rincian lengkap endpoint API v1 untuk seluruh modul layanan.
- **[API_V1_1_CONTRACT.md](api_spec/API_V1_1_CONTRACT.md)**: Kontrak penagihan iuran warga dan audit reporting v1.1.

---

## 2. Proses Bisnis & Regulasi Lingkungan ([`docs/business/`](business/README.md))
- **[README.md](business/README.md)**: Ikhtisar tata kelola operasional dan kebijakan administrasi RT.
- **[AUTH_REGISTER_AND_LOGIN_SPEC.md](business/AUTH_REGISTER_AND_LOGIN_SPEC.md)**: Spesifikasi mendalam alur pendaftaran mandiri warga (NIK 16 digit), aktivasi warga sensus, alur verifikasi berkas KTP, dan autentikasi multi-identifier.
- **[SRS.md](business/SRS.md)**: Software Requirements Specification (Kebutuhan Fungsional & Non-Fungsional).
- **[LETTER_WORKFLOW.md](business/LETTER_WORKFLOW.md)**: Alur birokrasi surat digital 2-tier, tanda tangan digital, QR Code token verifikasi, dan audit log.
- **[FINANCIAL_REPORTS_AND_BACKUP_STORE.md](business/FINANCIAL_REPORTS_AND_BACKUP_STORE.md)**: Kebijakan buku kas anti-fraud (*immutable ledger*) dan pencadangan terenkripsi.
- **[DATA_CLASSIFICATION.md](business/DATA_CLASSIFICATION.md)**: Klasifikasi data sensitif kependudukan sesuai UU PDP No. 27/2022.
- **[DOCUMENT_TEMPLATE.md](business/DOCUMENT_TEMPLATE.md)**: Standardisasi format cetak dokumen resmi RT.
- **[STORAGE_MEMORY_PERFORMANCE_ANALYSIS.md](business/STORAGE_MEMORY_PERFORMANCE_ANALYSIS.md)**: Analisis kompresi berkas dan kapasitas penyimpanan.

---

## 3. Desain Sistem & Rekayasa Perangkat Lunak ([`docs/design/`](design/README.md))
- **[README.md](design/README.md)**: Panduan desain arsitektur dan sistem antarmuka PRISMA.
- **[BRAND_GUIDE.md](design/BRAND_GUIDE.md)**: Identitas brand resmi PRISMA RT 04 (Warna, tipografi, logo, dan panduan komponen).
- **[ERD.md](design/ERD.md)**: Entity Relationship Diagram 24 tabel relasional MySQL 8.x.
- **[DIAGRAMS.md](design/DIAGRAMS.md)**: Sequence Diagram kontrol konkurensi optimistik dan Activity Diagram persuratan.
- **[UI_PLAN.md](design/UI_PLAN.md)**: Rencana antarmuka responsif mobile-first dengan Tailwind CSS v4.
- **[WEB_PERFORMANCE_SEO_GEO.md](design/WEB_PERFORMANCE_SEO_GEO.md)**: Optimasi Core Web Vitals, JSON-LD Schema, dan SEO lokal Kemayoran.
- **[CIRCULAR_FRAMEWORK_METHODS_AND_ALGORITHMS.md](design/CIRCULAR_FRAMEWORK_METHODS_AND_ALGORITHMS.md)**: Penerapan metode, algoritma & standarisasi Kerangka Diagram Lingkaran Sistem (Decentralized, Trust, Timestamp, Hash/Signature, Ethics, Autonomous Agent, Governance, AI).

---

## 4. Perencanaan Proyek & Sprint ([`docs/plan_project/`](plan_project/README.md))
- **[README.md](plan_project/README.md)**: Kerangka metodologi Waterfall + Incremental Sprint dan tata kelola git branch.
- **[PROJECT_PLAN.md](plan_project/PROJECT_PLAN.md)**: Rencana induk proyek, ruang lingkup, dan acceptance criteria.
- **[SPRINT_PLAN.md](plan_project/SPRINT_PLAN.md)**: Pembagian sprint bertahap dari Sprint 0 hingga Sprint 6.

---

## 5. Tata Kelola & Keamanan Sistem ([`docs/project_management/`](project_management/README.md))
- **[README.md](project_management/README.md)**: Tata kelola wewenang dan keamanan platform.
- **[RBAC.md](project_management/RBAC.md)**: Arsitektur Role-Based Access Control Laravel Policies dan Gates.
- **[RBAC_MATRIX.md](project_management/RBAC_MATRIX.md)**: Matriks hak akses matriks granular per peran dan entitas.
- **[MICROSERVICES_ENTERPRISE_ARCHITECTURE.md](project_management/MICROSERVICES_ENTERPRISE_ARCHITECTURE.md)**: Strategi arsitektur jangka panjang.
- **[FIREBASE_FREE_TIER_SETUP.md](project_management/FIREBASE_FREE_TIER_SETUP.md)**: Audit dan status integrasi cloud notification.
