# Role-Based Access Control (RBAC) & Permission Matrix

Dokumen ini mendefinisikan arsitektur otorisasi berbasis peran (*Role-Based Access Control*) dan izin eksplisit (*Permission-Based Authorization*) pada **Platform Layanan Publik Warga RT/RW**.

---

## 1. Prinsip Utama Otorisasi

1. **Principle of Least Privilege (Hak Akses Minimum):**
   Setiap peran hanya diberikan akses yang secara fungsional diperlukan untuk menjalankan tugasnya. Tidak ada peran yang diberikan hak akses global berlebihan selain `SUPERADMIN`.
2. **Standardisasi Izin Singular (*Singular Resource Names*):**
   Format perizinan menggunakan nama entitas tunggal diikuti aksi (`resource.action`), contoh: `citizen.read`, `letter.verify`, `finance.manage`.
3. **Superadmin Bypass:**
   Pengguna dengan peran `SUPERADMIN` otomatis melewati pengecekan izin (*bypass*) untuk keperluan audit, pemeliharaan darurat, dan administrasi sistem.
4. **Pembatasan Khusus Ketua RT:**
   `KETUA_RT` memiliki wewenang persetujuan akhir (*approval*) surat pengantar dan pemantauan lingkungan, namun **tidak diberikan hak kelola kas keuangan** (milik Bendahara) ataupun **verifikasi berkas awal** (milik Sekretaris).
5. **Kepemilikan Data Warga (*Ownership Boundary*):**
   Peran `WARGA` hanya diizinkan membaca, memperbarui draf, dan mengunduh surat milik dirinya sendiri (`citizen_id === user.citizen.id`). Warga tidak memiliki izin administratif global.

---

## 2. Matriks Hak Akses Peran × Fitur (Role Access Control Matrix)

| Fitur / Modul | WARGA | ADMIN (Staff) | SEKRETARIS | KETUA_RT | BENDAHARA | PETUGAS_KEAMANAN | SUPERADMIN |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| **Profil Akun Sendiri** | CRUD Sendiri | Read | Read | Read | Read | Read | Full Access |
| **Data Warga (Kependudukan)** | Milik Sendiri | CRUD (Izin) | CRUD | Read | No | Limited Read | Full Access |
| **Pengajuan Surat Warga** | Create / Read Own | Manage (Izin) | Verify / Reject | Approve / Reject | No | No | Full Access |
| **Katalog & Template Surat** | Read Active | Read | Manage | Read | No | No | Full Access |
| **Unduh Dokumen Surat Sah** | Milik Sendiri | Yes | Yes | Yes | No | No | Full Access |
| **Aduan & Keluhan Warga** | Create / Read Own | Manage (Izin) | Manage | Monitor / Resolve | No | Security Scope | Full Access |
| **Laporan Keamanan RT** | Create / Track Own | Manage (Izin) | Read | Monitor | No | Manage / Resolve | Full Access |
| **Pengumuman Lingkungan** | Read | Manage (Izin) | Manage | Publish / Read | Read | Read | Full Access |
| **Agenda & Kegiatan Warga** | Read | Manage (Izin) | Manage | Read | Read | Read | Full Access |
| **Kas & Transparansi Keuangan** | Read Published | Read (Izin) | Read | Read | **Manage (Full)** | Read | Full Access |
| **Jadwal Ronda Malam** | Read | Manage (Izin) | Manage | Read | No | Manage | Full Access |
| **Kontak Darurat RT** | Read | Manage (Izin) | Manage | Read | Read | Manage | Full Access |
| **Log Audit Trail Keamanan** | No | Limited (Izin) | No | Read | No | No | Full Access |
| **Manajemen Role & Permission** | No | No | No | No | No | No | **Full Access** |

---

## 3. Daftar Hak Akses Standar (*Permission Scopes*)

```text
users.manage               - Mengelola akun pengguna portal
roles.manage               - Mengubah assignment peran
permissions.manage         - Menetapkan hak akses granular ke staf

citizen.read               - Membaca data kependudukan warga
citizen.manage             - Membuat, memperbarui, atau menghapus data warga

letter.read                - Membaca daftar dan detail permohonan surat warga
letter.create              - Membuat draf permohonan surat baru
letter.verify              - Memeriksa kelengkapan berkas dan memverifikasi surat
letter.approve             - Menyetujui dan menerbitkan nomor surat resmi
letter.reject              - Menolak permohonan surat dengan alasan wajib
letter.complete            - Menyelesaikan dokumen surat dan siap unduh
letter.template.manage     - Mengelola master jenis dan template formulir surat

complaint.read             - Membaca aduan/aspirasi warga
complaint.manage           - Merespons dan memperbarui status penanganan aduan

security.read              - Memantau laporan keamanan dan ketertiban lingkungan
security.manage            - Menugaskan petugas dan menyelesaikan resolusi keamanan

announcement.manage        - Membuat dan menerbitkan pengumuman warga
event.manage               - Mengatur agenda kegiatan warga RT

finance.read               - Membaca mutasi kas dan ringkasan keuangan
finance.manage             - Mencatat penerimaan/pengeluaran dan pembatalan kas
finance.report             - Mengunduh rekapitulasi kas RT, iuran bulanan warga, dan snapshot backup store

emergency.manage           - Mengelola nomor kontak darurat penting
round_schedule.manage      - Mengatur jadwal ronda malam warga

audit.read                 - Memeriksa jejak audit keamanan sistem
settings.manage            - Mengatur konfigurasi umum portal
```

---

## 4. Evaluasi Hak Akses pada Kode (`User::hasPermission`)

Evaluasi izin dieksekusi secara berlapis dengan efisiensi memori tinggi:
1. Pengecekan peran `SUPERADMIN` (otomatis lolos).
2. Pengecekan tabel pivot `admin_permissions` untuk izin khusus yang diberikan secara personal kepada staf `ADMIN`.
3. Pengecekan matriks tugas baku peran struktural (`KETUA_RT`, `SEKRETARIS`, `BENDAHARA`, `PETUGAS_KEAMANAN`).
4. Penolakan akses (`403 Forbidden`) jika tidak ada kecocokan izin.

---

## 5. Kebijakan Keamanan Unggah Berkas & Akses Kamera

1. **Akses Kamera & Unggah Gambar Bukti Aduan:**
   - Fitur upload foto / tangkapan kamera pada form pengaduan lingkungan dikunci secara ketat dan **hanya diizinkan untuk akun warga terdaftar (`isWarga()` / `WARGA`)** dan `SUPERADMIN`.
   - Permintaan unggah berkas dari pengguna anonim / tamu (*guest*) secara otomatis ditolak dengan status **`403 Forbidden`** untuk mencegah *spamming*, gambar berbahaya, atau manipulasi laporan visual tanpa identitas yang dapat dipertanggungjawabkan.
2. **Subfolder Privat & Hashing Bukti:**
   - Semua lampiran aduan disimpan di subfolder privat `storage/app/private/complaints/` dan dokumen laporan keuangan di `storage/app/private/financial-reports/` dengan sanitasi nama berkas dan proteksi direct access traversal.

