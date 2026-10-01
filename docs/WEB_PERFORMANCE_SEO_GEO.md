# Dokumentasi Optimasi Web Performa, Core Web Vitals, SEO & GEO

Dokumen ini menguraikan arsitektur optimasi performa halaman web, pencegahan Cumulative Layout Shift (CLS), perbaikan Largest Contentful Paint (LCP), adaptasi dimensi perangkat, serta implementasi metadata SEO dan Geolocation (GEO) pada Platform Layanan Publik Warga.

---

## 1. Core Web Vitals & Optimasi Performa

Platform menerapkan strategi Core Web Vitals modern untuk memastikan pengalaman pengguna yang cepat, mulus, dan efisien pada perangkat mobile maupun desktop:

### 1.1 Largest Contentful Paint (LCP) & Priority Hints
- **Preload Kritis:** Logo utama dan font dimuat menggunakan `<link rel="preload">` dengan atribut `fetchpriority="high"`.
- **Preconnect DNS:** Menggunakan `<link rel="preconnect" href="https://fonts.bunny.net" crossorigin>` dan `<link rel="dns-prefetch">` untuk mengeliminasi latensi DNS handshake saat memuat tipografi.
- **Decoding Asinkron:** Seluruh aset gambar dilengkapi dengan atribut `decoding="async"` agar thread utama browser tidak terblokir selama proses render.

### 1.2 Cumulative Layout Shift (CLS) & Layout Shift Frequency (LFS)
- **Dimensi Eksplisit Gambar:** Komponen `<x-brand-logo>` menyertakan atribut `width` dan `height` tetap (misal: 180x40 untuk logo horizontal dan 40x40 untuk simbol). Hal ini memesan ruang render di DOM sebelum gambar selesai diunduh sehingga tidak terjadi lonjakan tata letak (layout shift).
- **Stabilitas Track Carousel:** Wadah carousel (`.carousel-track-outer`) memiliki `min-height: 220px` dengan properti CSS `will-change: transform` dan akselerasi hardware `transform: translate3d(...)` yang dieksekusi oleh GPU compositor, bukan CPU repaint.
- **Font Display Swap:** Menggunakan parameter `&display=swap` pada web font untuk memastikan teks langsung terbaca tanpa flash of invisible text (FOIT).

---

## 2. Adaptasi Dimensi & Responsivitas Perangkat

Aplikasi dirancang menggunakan prinsip **Mobile-First Responsive Layout**:

| Rentang Resolusi | Penyesuaian Komponen Antarmuka |
|---|---|
| **Ponsel Kecil (< 480px)** | Grid 1 kolom vertikal, carousel swipe sentuh, navbar desktop dialihkan penuh ke off-canvas drawer |
| **Tablet (481px - 860px)** | Grid 2 kolom, ticket tracker fleksibel, navigasi sekunder tertutup di tombol hamburger |
| **Desktop (> 861px)** | 3 menu navigasi prioritas langsung tampil di topbar, grid 3 kolom statistik kas, kontrol carousel prev/next aktif |
| **Layar Lebar (> 1200px)** | Batas kontainer maksimal 1180px dengan margin horizontal terpusat untuk ergonomi visual |

---

## 3. Struktur Navigasi Prioritas & Hamburger Drawer

Sesuai kebutuhan pengguna:
- **3 Navigasi Utama di Desktop Topbar:**
  1. `Warta & Agenda` (menuju carousel warta & agenda lingkungan).
  2. `Kas RT` (menuju statistik saldo kas transparan).
  3. `Lapor Pengaduan` (tombol pemicu modal pelaporan masalah).
- **Aksi Cepat:** Tombol `Masuk Portal` atau `Dashboard Warga` (tergantung status otentikasi).
- **Hamburger Drawer (Off-Canvas):**
  - Mengelompokkan menu sekunder: Agenda Kegiatan Warga, Jadwal Ronda & Kamling, Kontak Darurat 24 Jam, Struktur Pengurus RT, dan Swagger OpenAPI Spec.
  - Dilengkapi backdrop blur, animasi transisi CSS cubic-bezier, penutupan via tombol ESC keyboard, dan penutupan saat area luar diklik.

---

## 4. Metadata SEO (Search Engine Optimization)

Halaman utama dilengkapi metadata standar mesin pencari untuk indeksasi akurat:

- **Judul & Deskripsi Unik:** Disusun informatif dan spesifik lingkup RT/RW.
- **Tag Robot Komprehensif:** `index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1`.
- **URL Kanonikal:** Mencegah isu konten duplikat melalui `<link rel="canonical" href="...">`.
- **OpenGraph & Twitter Card:**
  - `og:type="website"`
  - `og:title`, `og:description`, `og:image` (gambar logo vektor tajam), `og:locale="id_ID"`.
  - `twitter:card="summary_large_image"`.

---

## 5. Metadata GEO (Geolocation & Wilayah Layanan)

Untuk mempermudah mesin pencari dan direktori lokal mengenali wilayah kerja RT:

```html
<!-- Geolocation Metadata -->
<meta name="geo.region" content="ID-JK">
<meta name="geo.placename" content="Jakarta">
<meta name="geo.position" content="-6.2088;106.8456">
<meta name="ICBM" content="-6.2088, 106.8456">
```

---

## 6. Schema.org JSON-LD (Structured Data)

Format data terstruktur berbasis `application/ld+json` disematkan untuk pengenalan entitas organisasi pemerintahan tingkat rukun tetangga dan kemampuan pencarian langsung:

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "GovernmentOrganization",
      "@id": "https://rt01rw05.warga.id/#organization",
      "name": "Rukun Tetangga 01 / Rukun Warga 05",
      "alternateName": "Portal Layanan Publik Warga RT 01",
      "url": "https://rt01rw05.warga.id",
      "logo": "https://rt01rw05.warga.id/images/logo-horizontal.svg",
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "Jakarta",
        "addressRegion": "DKI Jakarta",
        "addressCountry": "ID"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": -6.2088,
        "longitude": 106.8456
      }
    },
    {
      "@type": "WebSite",
      "@id": "https://rt01rw05.warga.id/#website",
      "url": "https://rt01rw05.warga.id",
      "name": "Platform Layanan Publik Warga",
      "inLanguage": "id-ID",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "https://rt01rw05.warga.id/api/v1/public/track/{ticket_number}",
        "query-input": "required name=ticket_number"
      }
    }
  ]
}
```
*(Catatan teknis: Pada berkas template Blade, simbol `@` di-escape menjadi `@@` untuk mencegah benturan dengan parser direktif Blade).*
