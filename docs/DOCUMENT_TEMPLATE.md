# Standar Template Dokumen Resmi (Document Template Architecture)

Dokumen ini mendokumentasikan spesifikasi, struktur komponen, mekanisme rendering server-side, serta perlindungan integritas file dokumen resmi pada portal warga.

---

## 1. Daftar Berkas Template Dokumen

Seluruh berkas template dokumen diletakkan pada:
`resources/views/documents/templates/`

| Berkas Template | Jenis Layanan | Kode Surat Terkait | Deskripsi Fungsional |
| :--- | :---: | :---: | :--- |
| **`surat-keterangan.blade.php`** | Surat Keterangan Umum | `SK-UMUM` | Menerangkan status kependudukan dan kelakuan baik warga untuk berbagai keperluan umum. |
| **`surat-kematian.blade.php`** | Surat Keterangan Kematian | `SK-KEMATIAN` | Menerangkan kewafatan warga dengan data almarhum, waktu, tempat, dan data pelapor/keluarga. |
| **`surat-pindah.blade.php`** | Surat Pengantar Pindah | `SK-PINDAH` | Pengantar permohonan SKPWNI kepindahan domisili warga antar kelurahan/kota. |
| **`surat-keterangan-tidak-mampu.blade.php`** | Surat Keterangan Tidak Mampu | `SKTM` | Keterangan kondisi ekonomi pra-sejahtera untuk keperluan beasiswa atau bantuan sosial. |
| **`laporan-keamanan.blade.php`** | Berita Acara Laporan Keamanan | Domain `security_reports` | Dokumen internal berita acara insiden keamanan, ketertiban, dan resolusi petugas ronda. |

---

## 2. Struktur Wajib Setiap Template

Setiap template dokumen memuat 13 komponen standar legalitas:
1. **Header / Kop Resmi RT-RW:** Nama RT 01 / RW 05, Kelurahan, Kecamatan, dan kontak sekretariat.
2. **Nomor Surat Resmi:** Sesuai format penomoran atomik dari `document_sequences`.
3. **Nama Judul Dokumen:** Bergaris bawah dan huruf kapital formal.
4. **Data Kependudukan Pemohon:** Nama, NIK terdekripsi legal, Tempat/Tgl Lahir, Jenis Kelamin, Agama, Pekerjaan, Alamat.
5. **Isi Keterangan Pokok:** Narasi pokok yang menerangkan status hukum atau kondisi warga.
6. **Tujuan / Keperluan Permohonan:** Keperluan permohonan yang diajukan oleh pemohon.
7. **Keterangan / Rincian Pendukung:** Keterangan tambahan hasil inputan formulir dinamis.
8. **Tempat dan Tanggal Pengesahan:** Kota dan tanggal diterbitkan (Bahasa Indonesia baku).
9. **Nama Pejabat Penandatangan:** Nama Ketua RT 01 yang menyetujui.
10. **Jabatan Penandatangan:** Jabatan resmi dalam kepengurusan lingkungan.
11. **Placeholder Tanda Tangan & QR Code:** Tanda tangan digital dengan stempel verifikasi token.
12. **Token Verifikasi Publik:** Token alfanumerik 40-karakter unik untuk pelacakan keaslian.
13. **Footer Legalitas & URL Verifikasi:** Tautan langsung publik untuk memverifikasi dokumen tanpa login.

---

## 3. Server-Side Data Binding & Sanitasi (Anti-XSS)

* **Tanpa HTML Mentah dari Klien:** Template **tidak pernah menerima atau merender HTML mentah** yang dikirim dari klien. Semua data disanitasi secara ketat oleh engine Blade (`{{ ... }}`).
* **Placeholder Server-Side:**
  ```blade
  {{ $letter_number }}
  {{ $citizen['full_name'] }}
  {{ $citizen['nik'] }}
  {{ $citizen['address'] }}
  {{ $purpose }}
  {{ $issued_at }}
  {{ $officer['name'] }}
  {{ $verification_url }}
  ```

---

## 4. Penyimpanan Privat (*Private Storage*) & Integritas Dokumen

1. **Direktori Privat:** Dokumen tidak disimpan pada folder `public/`. Dokumen disimpan pada:
   `storage/app/private/generated-letters/letter_{ticket}_{token}.html`
2. **Kalkulasi Hash SHA-256:** Setelah dokumen dirender di server, sistem menghitung ringkasan SHA-256 dari seluruh isi HTML dokumen:
   `$documentHash = hash('sha256', $renderedHtml);`
   Hash ini disimpan pada kolom `document_hash` di database. Setiap perubahan fisik pada file di luar sistem akan langsung terdeteksi.
3. **Akses Unduh Berizin (*Authorized Download*):**
   Unduh dokumen hanya dapat diakses melalui controller:
   `GET /api/v1/letters/{id}/download`
   yang memvalidasi kepemilikan pemohon (`citizen_id`) atau otorisasi staf (`letter.read` / `SUPERADMIN`).
