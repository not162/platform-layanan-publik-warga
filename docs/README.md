# Dokumentasi Platform Layanan Publik Warga

Struktur dokumentasi platform telah diklasifikasikan ke dalam 5 subdirektori tematik untuk memudahkan navigasi, audit teknis, serta tata kelola pengembangan sistem:

```
docs/
├── design/                 # Panduan visual, identitas brand, ERD, dan diagram alur
├── api_spec/               # Spesifikasi API, kontrak endpoints, dan skema OpenAPI
├── business/               # Persyaratan bisnis, regulasi data, alur persuratan, dan kas
├── plan_project/           # Perencanaan proyek, roadmap sprint, dan backlog
└── project_management/     # Tata kelola RBAC, setup cloud Firebase, dan arsitektur sistem
```

---

## 1. Desain & Arsitektur Visual (`docs/design/`)
Subfolder ini berisi panduan identitas visual, perancangan antarmuka pengguna, dan relasi data:
- **[BRAND_GUIDE.md](design/BRAND_GUIDE.md)**: Panduan warna, tipografi, logo, dan identitas visual resmi platform.
- **[DIAGRAMS.md](design/DIAGRAMS.md)**: Diagram arsitektur sistem dan interaksi antarkomponen.
- **[ERD.md](design/ERD.md)**: Entity Relationship Diagram database relasional sistem warga dan kepengurusan.
- **[UI_PLAN.md](design/UI_PLAN.md)**: Rencana antarmuka pengguna, wireframe, dan tata letak halaman dashboard.
- **[WEB_PERFORMANCE_SEO_GEO.md](design/WEB_PERFORMANCE_SEO_GEO.md)**: Optimasi Core Web Vitals, metadata SEO lokal, dan performa web.

---

## 2. Spesifikasi API (`docs/api_spec/`)
Subfolder ini mendokumentasikan antarmuka pemrograman aplikasi (API) untuk integrasi web dan mobile:
- **[API_SPEC.md](api_spec/API_SPEC.md)**: Rincian seluruh endpoint API v1 beserta format request dan response.
- **[API_V1_1_CONTRACT.md](api_spec/API_V1_1_CONTRACT.md)**: Kontrak spesifikasi API v1.1 mencakup penagihan iuran dan audit report.
- **[openapi.yaml](api_spec/openapi.yaml)**: Berkas spesifikasi OpenAPI 3.1 standar industri untuk dokumentasi Swagger UI interaktif (dapat diakses pada rute `/docs/api`).

---

## 3. Proses Bisnis & Regulasi (`docs/business/`)
Subfolder ini memuat aturan tata kelola lingkungan RT, regulasi data, dan alur operasional:
- **[SRS.md](business/SRS.md)**: Software Requirements Specification (Spesifikasi Kebutuhan Perangkat Lunak).
- **[DATA_CLASSIFICATION.md](business/DATA_CLASSIFICATION.md)**: Klasifikasi tingkat sensitivitas data warga (Publik, Internal, Rahasia/PII) dan enkripsi NIK.
- **[LETTER_WORKFLOW.md](business/LETTER_WORKFLOW.md)**: Alur penerbitan surat pengantar, verifikasi pengurus, tanda tangan elektronik, dan QR verifikasi.
- **[FINANCIAL_REPORTS_AND_BACKUP_STORE.md](business/FINANCIAL_REPORTS_AND_BACKUP_STORE.md)**: Regulasi laporan keuangan kas, pembatasan unduh rekapitulasi, dan penyimpanan backup terenkripsi.
- **[DOCUMENT_TEMPLATE.md](business/DOCUMENT_TEMPLATE.md)**: Standardisasi format cetak dokumen resmi RT dan variabel dinamis.
- **[STORAGE_MEMORY_PERFORMANCE_ANALYSIS.md](business/STORAGE_MEMORY_PERFORMANCE_ANALYSIS.md)**: Analisis efisiensi memori, kompresi berkas, dan kapasitas penyimpanan dokumen.

---

## 4. Perencanaan Proyek & Sprint (`docs/plan_project/`)
Subfolder ini memuat rencana kerja terstruktur, roadmap multi-sprint, dan status pengerjaan:
- **[PROJECT_PLAN.md](plan_project/PROJECT_PLAN.md)**: Rencana arsitektur makro, metodologi, dan linimasa peluncuran platform.
- **[SPRINT_PLAN.md](plan_project/SPRINT_PLAN.md)**: Rincian 10 Sprint pengembangan bertahap dari fondasi data hingga rilis produksi.

---

## 5. Tata Kelola & Infrastruktur (`docs/project_management/`)
Subfolder ini mengatur izin wewenang tim, keamanan role, dan integrasi cloud:
- **[RBAC.md](project_management/RBAC.md)**: Arsitektur Role-Based Access Control untuk seluruh peran (Warga, Ketua RT, Sekretaris, Bendahara, Petugas Keamanan, Admin, Superadmin).
- **[RBAC_MATRIX.md](project_management/RBAC_MATRIX.md)**: Matriks hak akses matriks granular per entitas modul.
- **[FIREBASE_FREE_TIER_SETUP.md](project_management/FIREBASE_FREE_TIER_SETUP.md)**: Panduan konfigurasi Firebase Spark Plan (gratis) untuk FCM Push Notification, Firestore, dan Firebase Storage.
- **[MICROSERVICES_ENTERPRISE_ARCHITECTURE.md](project_management/MICROSERVICES_ENTERPRISE_ARCHITECTURE.md)**: Strategi modularisasi arsitektur enterprise untuk skalabilitas masa depan.
