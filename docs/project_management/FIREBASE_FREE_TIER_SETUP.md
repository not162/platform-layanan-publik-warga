# Panduan Arsitektur & Konfigurasi Firebase Versi Gratis (Spark Plan)

Dokumen ini menjelaskan strategi penerapan dan pengoperasian **Firebase Spark Plan (Free Tier / Tanpa Biaya)** pada Platform Layanan Publik Warga RT/RW.

---

## 1. Ikhtisar Kuota Firebase Spark Plan (Gratis 100%)

Firebase menyediakan paket **Spark (No Cost $0/bulan)** yang sangat memadai untuk kebutuhan operasional tingkat RT atau RW:

| Layanan Firebase | Kuota Gratis (Spark Plan) | Pemanfaatan dalam Proyek RT |
|---|---|---|
| **Cloud Messaging (FCM)** | **Tanpa Batas (Unlimited)** | Push notifikasi status surat, update pengaduan, pengumuman darurat ke perangkat warga |
| **Cloud Firestore** | 1 GB data tersimpan, 50.000 read/hari, 20.000 write/hari | Sinkronisasi real-time warta, status pengaduan publik, dan token FCM warga |
| **Cloud Storage** | 5 GB data tersimpan, 1 GB transfer download/hari, 20.000 upload/hari | Penyimpanan bukti foto aduan warga dan file lampiran surat pengantar |
| **Firebase Hosting** | 10 GB storage, 360 MB/hari data transfer | CDN global untuk file statis (CSS, JS, logo SVG, manifest PWA) |
| **Firebase Authentication** | 50.000 active users/bulan | Opsional SSO Google atau integrasi token |

---

## 2. Arsitektur Hybrid: Laravel Enterprise + Firebase Spark

Untuk menghemat biaya server dan menjaga reliabilitas, sistem menerapkan pola **Hybrid Offloading**:

```
[ Browser / PWA Warga ]
    │
    ├── (1) HTTP Request (Auth, Form Data, Audit Log, Kas Anti-Fraud) ──> [ Laravel Backend API ]
    │                                                                           │
    │                                                                           └──> [ MySQL Database ]
    │
    ├── (2) Static Assets & PWA Service Worker ─────────────────────────> [ Firebase Hosting (CDN) ]
    │
    ├── (3) Push Notification Real-Time ─────────────────────────────────> [ Firebase Cloud Messaging (FCM) ]
    │                                                                           ▲
    │                                                                           │ (Kirim notifikasi via HTTP v1)
    │                                                                     [ Laravel Scheduler / Queue ]
    │
    └── (4) Upload Bukti Foto / Dokumen Ringan (Opsional) ───────────────> [ Firebase Storage (5GB Free) ]
```

### Keuntungan Pola Hybrid:
1. **Beban Server Minimal:** Aset statis dan notifikasi push ditangani oleh infrastruktur Google tanpa membebani PHP-FPM atau MySQL.
2. **Kerahasiaan Data Tetap Terjaga:** Data sensitif warga (NIK, Nomor KK, rincian buku kas RT) tetap berada di database internal Laravel, bukan di cloud publik.
3. **Biaya Operasional Nol Rupiah:** Seluruh batasan Spark Plan berada jauh di atas rata-rata konsumsi harian 1 RT (biasanya 50-200 KK).

---

## 3. Konfigurasi Environment (`.env`)

Tambahkan variabel berikut ke dalam berkas `.env` aplikasi Anda:

```dotenv
# Firebase Spark Plan Configuration
FIREBASE_PROJECT_ID=layanan-publik-warga
FIREBASE_API_KEY=AIzaSy...
FIREBASE_AUTH_DOMAIN=layanan-publik-warga.firebaseapp.com
FIREBASE_DATABASE_URL=https://layanan-publik-warga-default-rtdb.firebaseio.com
FIREBASE_STORAGE_BUCKET=layanan-publik-warga.appspot.com
FIREBASE_MESSAGING_SENDER_ID=123456789012
FIREBASE_APP_ID=1:123456789012:web:abcdef123456
FIREBASE_MEASUREMENT_ID=G-XXXXXXXXXX
FIREBASE_VAPID_KEY=BNxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json
```

---

## 4. Keamanan Data (Security Rules)

### 4.1 Cloud Firestore (`firestore.rules`)
Aturan keamanan Firestore membatasi akses baca dan tulis sesuai peran pengguna:
- **Pengumuman publik & agenda:** Dapat dibaca bebas oleh publik jika `is_published == true`.
- **Surat warga:** Hanya dapat dibaca oleh pemilik UID atau pengurus RT (`SUPERADMIN`, `ADMIN_RT`, `SEKRETARIS`).
- **Token FCM:** Warga dapat mendaftarkan token perangkat mereka secara aman.

### 4.2 Cloud Storage (`storage.rules`)
- **Foto Aduan Warga:** Dibatasi maksimal 5 MB dengan ekstensi tipe MIME gambar (`image/*`).
- **Lampiran Berkas:** Dibatasi maksimal 5 MB dengan format PDF atau gambar.
- **Laporan Keuangan RT:** Hanya dapat diakses oleh peran `BENDAHARA`, `SEKRETARIS`, atau `SUPERADMIN`.

---

## 5. Implementasi Web Push Notification (FCM)

### 5.1 Service Worker (`public/firebase-messaging-sw.js`)
Service worker menangani event `onBackgroundMessage` saat aplikasi tertutup atau berjalan di latar belakang:
- Menampilkan pesan notifikasi resmi RT.
- Membuka halaman permohonan surat atau dashboard saat warga menekan banner notifikasi.

### 5.2 Pendaftaran Token di Sisi Klien (`public/js/firebase-init.js`)
Script klien meminta izin notifikasi melalui browser Notification API dan mengirimkan token yang dihasilkan ke endpoint backend Laravel:
```javascript
// Contoh pemanggilan di frontend
LayananPublikFirebase.init(firebaseConfig, vapidKey).then(() => {
    LayananPublikFirebase.requestPermission(vapidKey).then((token) => {
        console.log('FCM Token terdaftar:', token);
    });
});
```

---

## 6. Tips Menjaga Kuota Spark Plan Tetap Gratis

1. **Kompresi Gambar di Frontend:** Foto keluhan yang diunggah warga dikompresi sebelum dikirim ke Firebase Storage untuk menghemat kuota 5 GB.
2. **Cache-Control Efektif:** Header pada `firebase.json` menetapkan cache 1 tahun (`max-age=31536000, immutable`) untuk aset statis agar tidak memicu read berulang.
3. **Agregasi Read:** Gunakan snapshot dokumen lokal di indexedDB atau Cache Storage browser untuk mencegah over-reading kuota 50.000 read/hari.
