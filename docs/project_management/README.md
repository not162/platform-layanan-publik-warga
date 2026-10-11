# Dokumentasi Tata Kelola & Keamanan Sistem (`docs/project_management/`)

Subfolder ini memuat arsitektur otorisasi berbasis peran (Role-Based Access Control / RBAC), matriks wewenang operasional pengurus, audit log, serta pedoman keamanan sistem untuk **PRISMA RT 04 Kemayoran**.

---

## Berkas Dokumentasi dalam Subfolder Ini

1. **[RBAC.md](RBAC.md)**:
   - Fondasi otorisasi sistem Laravel berbasis **Policies**, **Gates**, dan middleware otentikasi.
   - Definisi 3 tingkatan peran utama:
     - **Superadmin**: Kontrol sistem penuh, audit trail, backup, dan manajemen hak administrator.
     - **Admin / Pengurus RT**: Operasional harian terbagi menjadi Ketua RT, Sekretaris, Bendahara, dan Petugas Keamanan.
     - **Warga**: Pelayanan mandiri, permohonan surat, pelaporan aduan, dan cek transparansi kas.
2. **[RBAC_MATRIX.md](RBAC_MATRIX.md)**:
   - Tabel matriks granular hak akses per modul sistem (Baca, Buat, Edit, Verifikasi, Sahkan, Hapus, Unduh).
3. **[MICROSERVICES_ENTERPRISE_ARCHITECTURE.md](MICROSERVICES_ENTERPRISE_ARCHITECTURE.md)**:
   - Dokumentasi pertimbangan arsitektur jangka panjang dan batasan domain modular *monolith-first*.
4. **[FIREBASE_FREE_TIER_SETUP.md](FIREBASE_FREE_TIER_SETUP.md)**:
   - Evaluasi integrasi layanan cloud Firebase (FCM & Storage), status dependensi, dan rekomendasi Web Push mandiri tanpa ketergantungan pihak ketiga.

---

## Matriks Ringkas Hak Akses Peran

| Modul / Tindakan | Warga | Petugas Keamanan | Bendahara | Sekretaris | Ketua RT | Superadmin |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| **Ajukan Surat Pengantar** |  | - | - | - | - | - |
| **Verifikasi Lampiran Surat** | - | - | - |  | - |  |
| **Persetujuan / Pengesahan Surat** | - | - | - | - |  |  |
| **Pencatatan Transaksi Kas** | - | - |  | - | - |  |
| **Penerbitan Rekap Kas Publik** | - | - |  | - |  |  |
| **Proses Aduan Fasilitas** | - | - | - |  |  |  |
| **Penanganan Laporan Keamanan**| - |  | - | - |  |  |
| **Kelola Akun & Hak Akses** | - | - | - | - | - |  |
| **Audit Logs & Backup Sistem** | - | - | - | - | - |  |
