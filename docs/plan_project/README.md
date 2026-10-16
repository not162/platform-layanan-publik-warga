# Dokumentasi Rencana, Audit TOGAF ADM, & Runbook Produksi (`docs/plan_project/`)

Subfolder ini merinci seluruh artefak arsitektur, rencana proyek, laporan audit gerbang TOGAF ADM (Phase A s.d. Phase H), dan panduan operasional produksi untuk **Platform Layanan Publik Warga (PRISMA RT 04)** berbasis **Laravel 13, PHP 8.4, dan MySQL 8.4 di Lingkungan Laragon**.

---

## 1. Rencana Induk & Cetak Biru Proyek

1. **[PROJECT_PLAN.md](PROJECT_PLAN.md)**:
   - Rencana induk proyek, deskripsi ruang lingkup pekerjaan, target pencapaian (*deliverables*), alokasi arsitektur sistem, dan standar penerimaan (*acceptance criteria*).
2. **[SPRINT_PLAN.md](SPRINT_PLAN.md)**:
   - Rincian pembagian sprint pelaksanaan Waterfall bertahap dari Sprint 0 hingga Sprint 6.
3. **[PRODUCTION_RUNBOOK_AND_ROLLBACK.md](PRODUCTION_RUNBOOK_AND_ROLLBACK.md)**:
   - Panduan operasional produksi, langkah deployment, pencadangan snapshot terenkripsi hash SHA-256, *disaster recovery*, dan prosedur pembatalan (*rollback runbook*).

---

## 2. Laporan Audit Resmi TOGAF ADM (Sprint 0 — Sprint 6)

Setiap tahapan sprint telah diaudit secara faktual melalui pembacaan basis data nyata, kueri skema, pengujian otomatis penuh, dan ditutup dengan keputusan gerbang resmi:

| Laporan Audit Gerbang | Fase TOGAF ADM yang Diselaraskan | Ruang Lingkup & Fokus Utama | Keputusan Gerbang |
|---|---|---|:---:|
| **[SPRINT_0_AUDIT_REPORT_TOGAF.md](SPRINT_0_AUDIT_REPORT_TOGAF.md)** | **Preliminary Phase & Phase A** (Architecture Vision) | Audit awal, koneksi MySQL Laragon port 3306, sanitasi environment, dan visi arsitektur. | **PASS** |
| **[SPRINT_1_AUDIT_REPORT_TOGAF.md](SPRINT_1_AUDIT_REPORT_TOGAF.md)** | **Phase B** (Business Architecture) | Proses layanan warga, integrasi 7 navbar warga, rekonsiliasi matriks peran RBAC. | **PASS** |
| **[SPRINT_2_AUDIT_REPORT_TOGAF.md](SPRINT_2_AUDIT_REPORT_TOGAF.md)** | **Phase C, D, E, F** (Data, App, Tech, Opps) | ERD fisik 18 tabel InnoDB, diagram UML modular, spesifikasi REST API, migrasi terurut. | **PASS** |
| **[SPRINT_3_AUDIT_REPORT_TOGAF.md](SPRINT_3_AUDIT_REPORT_TOGAF.md)** | **Phase G** (Implementation Governance - Part 1) | Fondasi kependudukan, penegakan `citizens.user_id` single source of truth, enkripsi NIK UU PDP, optimistic locking KK. | **PASS** |
| **[SPRINT_4_AUDIT_REPORT_TOGAF.md](SPRINT_4_AUDIT_REPORT_TOGAF.md)** | **Phase G** (Implementation Governance - Part 2) | Rantai persuratan 2-tier (Sekretaris verifikasi, RT approve), finalitas dokumen, aduan anonim, anti-overpayment iuran, jurnal pembalik kas anti-fraud. | **PASS** |
| **[SPRINT_5_AUDIT_REPORT_TOGAF.md](SPRINT_5_AUDIT_REPORT_TOGAF.md)** | **Phase G & Phase H** (Near-Real-Time & UAT) | REST API v1 dashboard summary berbasis peran, incremental sync `/sync`, caching ETag `304 Not Modified`, broadcast event Pusher, UAT lintas-peran. | **PASS** |
| **[SPRINT_6_AUDIT_REPORT_TOGAF.md](SPRINT_6_AUDIT_REPORT_TOGAF.md)** | **Phase H** (Architecture Change Management & Release) | Pre-flight release check, health check `/up`, disaster recovery snapshot SHA-256, 205 tests 100% lulus, template `.env.production.example`. | **PASS (v1.0.0 APPROVED)** |

---

## 3. Hasil Pengujian Keseluruhan (*Test Suite Metrics*)

- **Total Pengujian Otomatis:** 205 Tests
- **Total Pernyataan Evaluasi (*Assertions*):** 976 Assertions
- **Tingkat Kelulusan (*Pass Rate*):** 100% PASSED (0 Failures, 0 Errors)
- **Kepatuhan Gaya Penulisan Kode (*Pint Styling*):** Passed
- **Audit Kerentanan Dependensi (*Composer Audit*):** 0 Vulnerabilities Found
