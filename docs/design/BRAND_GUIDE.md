# Panduan Identitas Brand (Brand Identity Guide)
## Layanan Publik Warga

> **Product Label Alternatif:** *WargaTerbuka* atau *Portal Warga* (digunakan jika dibutuhkan label antarmuka ringkas/mobile).

---

## 1. Filosofi Brand (Brand Philosophy)

Identitas visual dan pengalaman pengguna **Layanan Publik Warga** dibangun berdasarkan empat pilar utama:

1. **Pelayanan** (*Citizen-Centric Service*)  
   Warga datang dengan kebutuhan nyata. Sistem harus memberikan alur proses yang jelas, mudah dipahami, tidak berbelit, dan dapat dipantau dari awal hingga akhir.
2. **Kebersamaan** (*Community Partnership*)  
   Layanan dibangun untuk kemaslahatan warga dan kemudahan pengurus RT/RW, bukan dominasi satu pihak semata. Tercipta hubungan yang saling mendukung dan guyub.
3. **Transparansi Informasi** (*Open & Accountable Information*)  
   Status layanan, alur birokrasi, dan rekapitulasi data publik (seperti laporan kas atau pengumuman) dapat diakses dengan terang benderang sesuai tingkatan hak akses (*RBAC*).
4. **Kepercayaan & Privasi** (*Trust & Data Protection*)  
   Keterbukaan informasi publik tidak mengorbankan privasi. Data pribadi warga (NIK, nomor kontak, rincian keluarga) tetap terlindungi dengan proteksi ketat dan kepatuhan terhadap klasifikasi data.

---

## 2. Visi & Misi

### Visi
> **Menjadi portal layanan warga yang sederhana, dapat dilacak, transparan, dan aman untuk mendukung administrasi lingkungan yang lebih tertib.**

### Misi
- **Aksesibilitas:** Mempermudah warga mengakses layanan administrasi dan informasi lingkungan kapan saja dan di mana saja.
- **Kemudahan Pelacakan:** Membuat status pengajuan surat dan pengaduan warga dapat dipantau secara langsung (*real-time*).
- **Keseimbangan Transparansi & Privasi:** Menyediakan transparansi informasi publik tanpa membuka data pribadi yang sensitif.
- **Akuntabilitas Pengurus:** Membantu pengurus RT/RW bekerja dengan data terstruktur, pencatatan jejak audit (*audit trail*), dan pembagian peran yang terdefinisi rapi.
- **Keberlanjutan:** Menjaga sistem tetap ringan, mudah dirawat, dan dapat dikembangkan secara bertahap (*modular & maintainable*).

---

## 3. Konsep & Makna Logo

### Komposisi Simbol Utama
Simbol utama menggabungkan tiga elemen geometris esensial:
1. **Dua/Tiga Figur Warga (Lingkaran & Siluet Komunitas):** Melambangkan partisipasi warga dan kebersamaan rukun tetangga/rukun warga.
2. **Lembar Dokumen / Jendela Terbuka:** Melambangkan keterbukaan informasi publik, kejelasan arsip, dan transparansi alur administrasi.
3. **Checkmark / Perisai (Shield) Pelindung:** Melambangkan layanan yang terverifikasi, aman, terpercaya, dan perlindungan privasi data warga.

### Rangkuman Makna Simbolis
- **Bentuk Terbuka:** Transparansi informasi dan keterbukaan layanan.
- **Figur Komunitas:** Partisipasi aktif warga dalam lingkungan sosial.
- **Garis Terhubung & Panah Alur:** Alur birokrasi layanan yang terarah dan cepat selesai.
- **Perisai & Tanda Centang:** Keamanan data warga dan verifikasi resmi pengurus.

---

## 4. Panduan Warna & Tipografi

### Palet Warna (Civic Tech Color Palette)
| Nama Warna | Kode Hex | Penggunaan Utama |
|---|---|---|
| **Deep Civic Navy** | `#1B365D` | Warna primer: Header, teks utama, simbol perisai integritas |
| **Calm Teal** | `#3A9696` | Warna sekunder: Aksen tombol, status verifikasi, elemen dokumen |
| **Trust Slate** | `#4A5568` | Warna teks pendukung, border netral, versi monokrom |
| **Soft Background** | `#F8FAFC` | Latar belakang halaman antarmuka yang bersih dan nyaman di mata |
| **Accent Emerald** | `#10B981` | Indikator status sukses / disetujui / terverifikasi |

### Tipografi
- **Font Utama Antarmuka:** Inter / Plus Jakarta Sans / Roboto (Sans-serif modern, mudah dibaca di layar kecil).
- **Font Heading / Dokumen Resmi:** Sans-serif bersih dengan ketebalan *Semi-Bold* (*600*) untuk keterbacaan tinggi.

---

## 5. Batasan & Larangan Desain (Design Restrictions)

1. **DILARANG** menggunakan lambang Garuda Pancasila, logo resmi kementerian, lambang daerah pemerintah provinsi/kota, atau atribut yang menimbulkan kesan instansi pemerintah resmi tanpa otorisasi formal.
2. **DILARANG** menggunakan detail visual yang terlalu rumit sehingga tidak terbaca pada ukuran ikon kecil (favicon 16x16px / 32x32px).
3. **DILARANG** membuat kesan visual yang menyerupai institusi perbankan komersial, kepolisian, atau kampanye politik tertentu.
4. Simbol harus selalu memiliki kontras tinggi dan tetap dapat dibaca jelas saat dicetak dalam **mode hitam-putih (monokrom)**.

---

## 6. Aturan Penggunaan Antarmuka (UI Usage Guidelines)

- **Header / Top Navigation:** Menggunakan *horizontal lockup* (simbol + teks "Layanan Publik Warga").
- **Halaman Login & Landing Page:** Menampilkan simbol vertikal yang tegas disertai nama brand dan visi singkat.
- **Favicon & App Icon:** Menggunakan simbol inti yang disederhanakan tanpa teks agar tetap tajam pada resolusi kecil.
- **Kop Surat / Template Cetak Dokumen PDF:** Menggunakan versi monokrom hitam-putih (*Dark Slate/Black*) guna memastikan hasil cetak fisik bersih dan hemat tinta.
- **Privasi Data pada UI:** Data pribadi sensitif warga (NIK, no HP, alamat lengkap) tidak boleh ditempatkan berdampingan dengan elemen brand publik kecuali pada halaman yang telah terotentikasi (*authenticated session*).
