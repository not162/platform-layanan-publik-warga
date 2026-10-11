# Dokumentasi Proses Bisnis & Regulasi Lingkungan (`docs/business/`)

Subfolder ini memuat seluruh aturan tata kelola lingkungan, regulasi perlindungan data pribadi (UU PDP), alur kerja birokrasi surat digital, dan kebijakan pengelolaan keuangan kas **PRISMA RT 04 Kemayoran**.

---

## Berkas Dokumentasi dalam Subfolder Ini

1. **[AUTH_REGISTER_AND_LOGIN_SPEC.md](AUTH_REGISTER_AND_LOGIN_SPEC.md)**:
   - Spesifikasi teknis dan fungsional alur pendaftaran mandiri warga (*self-registration*), verifikasi NIK real-time 16 digit, autentikasi multi-identifier (email/telepon), dan penanganan sesi.
2. **[SRS.md](SRS.md)**:
   - Software Requirements Specification (Spesifikasi Kebutuhan Perangkat Lunak) mencakup kebutuhan fungsional dan non-fungsional platform.
3. **[LETTER_WORKFLOW.md](LETTER_WORKFLOW.md)**:
   - Alur penerbitan surat pengantar berjenjang 2-tier: *Draft* → *Submitted* → *Under Review* (Sekretaris) → *Approved* (Ketua RT) → *Completed*, dilengkapi hashing integritas SHA-256 dan token QR verifikasi.
4. **[FINANCIAL_REPORTS_AND_BACKUP_STORE.md](FINANCIAL_REPORTS_AND_BACKUP_STORE.md)**:
   - Kebijakan buku kas anti-fraud (*immutable ledger*), pemisahan kas publik dan privat, audit trail, serta tata cara pencadangan (*backup store*) terenkripsi.
5. **[DATA_CLASSIFICATION.md](DATA_CLASSIFICATION.md)**:
   - Klasifikasi data kependudukan (Publik, Internal, Rahasia/PII) sesuai ketentuan Undang-Undang Perlindungan Data Pribadi (UU PDP No. 27/2022).
6. **[DOCUMENT_TEMPLATE.md](DOCUMENT_TEMPLATE.md)**:
   - Standardisasi format cetak dokumen resmi RT: Surat Keterangan Domisili, Tidak Mampu, Pengantar Kematian, Pindah Domisili, dan Keterangan Umum.
7. **[STORAGE_MEMORY_PERFORMANCE_ANALYSIS.md](STORAGE_MEMORY_PERFORMANCE_ANALYSIS.md)**:
   - Analisis performa penyimpanan berkas, kompresi lampiran KTP/KK, dan efisiensi memori runtime PHP 8.4 FrankenPHP.
