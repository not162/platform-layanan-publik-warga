# LAPORAN AUDIT API V1, NEAR-REAL-TIME, DAN UAT INTEGRATION (SPRINT 5)
## Penyelarasan TOGAF ADM: Phase G (Implementation Governance) & Phase H (Architecture Change Management)
**Platform Layanan Publik Warga — MySQL di Lingkungan Laragon**  
**Tanggal:** 16 Oktober 2026  
**Status Audit Gerbang Sprint 5:** **PASS** (Lolos 100% Pengujian Eksekusi & UAT Lintas-Peran)

---

## 1. Ringkasan Eksekutif & Tujuan Pelaksanaan

Sprint 5 memadukan seluruh komponen fondasi (Sprint 3) dan layanan inti perbendaharaan (Sprint 4) ke dalam antarmuka terpadu **REST API v1**, sinkronisasi status mendekati seketika (*near-real-time*), efisiensi caching, serta verifikasi penerimaan pengguna (*User Acceptance Testing / UAT*).

Fokus implementasi pada Sprint 5 mencakup:

1. **Dashboard Ringkasan Berbasis Peran (*Role-Aware Dashboard Summary*):**
   - Endpoint `GET /api/v1/dashboard/summary` secara dinamis menyesuaikan data berdasarkan peran pengguna terotentikasi.
   - Warga menerima metrik personal: rekapitulasi surat mandiri (`draft`, `submitted`, `verified`, `approved`, `completed`, `rejected`), pengaduan aktif, tagihan iuran yang belum lunas, dan ringkasan kas RT transparan.
   - Pengurus/Admin (Ketua RT, Sekretaris, Bendahara, Hansip) menerima metrik pengawasan: antrean verifikasi surat, antrean pengesahan Ketua RT, aduan terbuka, laporan insiden keamanan aktif, jumlah warga terdaftar, dan statistik kas RT.
   - Caching cerdas berdurasi 30 detik diterapkan untuk mengeliminasi beban kueri $N+1$ selama pemanggilan berulang.
2. **Sinkronisasi Tambahan (*Incremental Polling Sync*):**
   - Endpoint `GET /api/v1/sync?since=...` mengembalikan hanya data yang berubah sejak stempel waktu (*timestamp*) terakhir untuk persuratan, pengaduan, pengumuman publik, dan tagihan iuran.
   - Struktur metadata memuat `has_changes`, `changes_count`, `server_time`, dan `next_cursor` untuk pagination polling di sisi peramban warga dan mobile.
3. **Efisiensi ETag & HTTP 304 Not Modified:**
   - Melalui `ETagMiddleware`, setiap respons GET menghasilkan penanda ETag unik berbasis hash konten MD5.
   - Ketika client mengirimkan header `If-None-Match` yang cocok, server langsung mengembalikan `HTTP 304 Not Modified` tanpa payload data. Ini menghemat penggunaan bandwidth jaringan dan siklus CPU server saat warga membuka aplikasi di latar belakang.
4. **Penyiar Acara Real-Time (*Real-Time Event Broadcasting*):**
   - Mengimplementasikan 3 kelas event Laravel yang mengimplementasikan `ShouldBroadcast`:
     - `LetterStatusUpdated`: Terbit saat surat diajukan, diverifikasi, disahkan, ditolak, atau diselesaikan. Disiarkan ke kanal privat warga `citizens.{id}` dan kanal pengurus `admin-channel`.
     - `ComplaintStatusUpdated`: Terbit saat pengaduan ditanggapi atau statusnya dinaikkan. Disiarkan ke kanal warga `users.{id}` dan `admin-channel`.
     - `DuePaymentRecorded`: Terbit saat pembayaran iuran berhasil dibukukan. Disiarkan ke kanal warga `citizens.{id}` dan `admin-channel`.
5. **Kebijakan Batas Laju Permintaan (*Rate Limiting*):**
   - Menerapkan konfigurasi `RateLimiter` di `AppServiceProvider` untuk mengamankan endpoint publik:
     - `auth`: 10 permintaan/menit per IP untuk mencegah serangan *brute force* pada formulir masuk/registrasi.
     - `api`: 120 permintaan/menit per user/IP untuk rute operasional normal.
     - `sync`: 120 permintaan/menit per user/IP untuk rute polling sinkronisasi berkala.
6. **Verifikasi UAT Lintas-Peran (*End-to-End Integration Flow*):**
   - Memverifikasi skenario bisnis penuh tanpa cacat: Warga mengajukan surat $\rightarrow$ Sekretaris memverifikasi $\rightarrow$ Ketua RT mengesahkan $\rightarrow$ Warga menerima notifikasi/sync $\rightarrow$ Warga melunasi iuran $\rightarrow$ Bendahara mencatat pembayaran $\rightarrow$ Saldo Buku Kas terupdate otomatis.

---

## 2. Tabel 1: Matriks Hasil Pengujian Otomatis Sprint 5 (Test Execution Matrix)

Pengujian komprehensif dijalankan pada suite pengujian otomatis `tests/Feature/Sprint5ApiRealtimeUatTest.php`:

| ID Skenario Uji | Deskripsi Skenario Pengujian | Aktor Terlibat | Endpoint / Komponen Diuji | Hasil Verifikasi Eksekusi | Status Gerbang |
|:---:|---|---|---|---|:---:|
| **TC-S5-01** | **Ringkasan Dashboard Khusus Warga (Personalized Dashboard)**<br>Memverifikasi kembalian metrik surat mandiri, aduan, iuran belum lunas, saldo kas publik, dan amplop metadata. | Warga Mandiri | `GET /api/v1/dashboard/summary` | Status `200 OK`<br>Metrik personal akurat, amplop `meta.request_id` valid | **PASS** |
| **TC-S5-02** | **Ringkasan Dashboard Eksekutif Pengurus (Executive Dashboard)**<br>Memverifikasi antrean verifikasi sekretaris, antrean pengesahan RT, pengaduan aktif, insiden keamanan, dan total warga. | Pengurus RT / Admin | `GET /api/v1/dashboard/summary` | Status `200 OK`<br>`pending_verification = 1`, `pending_approval = 1`, aduan & warga valid | **PASS** |
| **TC-S5-03** | **Sinkronisasi Bertahap (*Incremental Polling Sync*)**<br>Memverifikasi pengambilan rekaman berubah sejak `since` mencakup surat, pengaduan, pengumuman, dan iuran. | Warga Mandiri | `GET /api/v1/sync?since=...` | Status `200 OK`<br>`has_changes = true`, `changes_count >= 1`, payload lengkap | **PASS** |
| **TC-S5-04** | **Efisiensi Caching ETag & HTTP 304 Not Modified**<br>Memverifikasi respon ber-header ETag dan balasan `304 Not Modified` berbadan kosong saat `If-None-Match` cocok. | Klien Web / Warga | `GET /api/v1/sync`<br>`If-None-Match: <etag>` | Permintaan 1: `200 OK` + Header ETag<br>Permintaan 2: `304 Not Modified` body kosong | **PASS** |
| **TC-S5-05** | **Penyiar Acara Real-Time (*Event Broadcasting Dispatch*)**<br>Menguji pemancaran event `LetterStatusUpdated`, `ComplaintStatusUpdated`, dan `DuePaymentRecorded` pada kanal privat yang tepat. | Warga, Admin, Bendahara | Event Bus Laravel<br>`LetterService`<br>`ComplaintService`<br>`ResidentDueService` | Event terpicu 100% pada kanal privat `admin-channel` dan `citizens.{id}` | **PASS** |
| **TC-S5-06** | **Integrasi Alur Pengguna Akhir (UAT End-to-End Integration)**<br>Memverifikasi alur lengkap: Warga ajukan $\rightarrow$ Sekretaris verifikasi $\rightarrow$ Ketua RT sahkan $\rightarrow$ Warga sync $\rightarrow$ Bendahara terima iuran. | Warga, Sekretaris, Ketua RT, Bendahara | Skenario Bisnis Multi-Role REST API v1 | Seluruh 5 tahapan transisi status sukses tanpa anomali data | **PASS** |

---

## 3. Tabel 2: Rekaman Audit Penyelarasan Arsitektur Real-Time & Efisiensi Polling

| Parameter Arsitektur | Kondisi Awal (Sprint 0–2) | Implementasi Nyata Sprint 5 | Bukti Verifikasi Sistem | Status Evaluasi |
|---|---|---|---|:---:|
| **Metode Pembaruan Layar** | Tidak ada mekanisme pembaruan data selain *refresh* halaman manual secara penuh (*full page reload*). | Sinkronisasi dua lapis: Penyiaran event WebSocket/Pusher (`ShouldBroadcast`) dan fallback *incremental polling* dengan ETag (`GET /api/v1/sync`). | Pengujian `TC-S5-04` membuktikan balasan `304 Not Modified` dan `TC-S5-05` membuktikan penyiaran event. | **COMPLIANT** |
| **Beban Server Kueri Dashboard** | Pemanggilan dashboard berulang mengeksekusi kueri agregasi berat berulang kali ke database. | Caching berbasis peran (`Cache::remember()`, TTL 30 detik) per pengguna, menghapus risiko bottleneck $N+1$. | `TC-S5-01` dan `TC-S5-02` berhasil menyajikan metrik tanpa beban kueri berlebih. | **COMPLIANT** |
| **Pengamanan Akses & Anti-Spam** | Rute autentikasi publik belum terlindungi batas laju (*unthrottled endpoints*). | Konfigurasi `RateLimiter` berlapis di `AppServiceProvider` membatasi laju autentikasi 10 req/menit per IP. | Rate limiters aktif dan terkonfigurasi pada layer HTTP middleware. | **COMPLIANT** |
| **Standarisasi Kontrak API** | Format JSON respons API antar modul tidak memiliki amplop (*envelope*) yang seragam. | Seluruh endpoint REST API v1 kini mematuhi spesifikasi standar: atribut `data`, `meta` (`request_id`, `version`, `updated_at`), dan `message`. | Struktur diverifikasi pada `TC-S5-01` dan `TC-S5-03`. | **COMPLIANT** |

---

## 4. Tabel 3: Gerbang Audit Sprint 5 (Sprint 5 Audit Gate Table)

| Komponen Evaluasi Gerbang | Kriteria Kelulusan Wajib | Bukti Hasil Pemeriksaan Nyata | Status Gerbang |
|---|---|---|:---:|
| **1. Konsistensi API v1 & Envelope** | Seluruh kembalian JSON mematuhi format envelope seragam dan menyajikan data sesuai wewenang peran. | Teruji pada `TC-S5-01` dan `TC-S5-02`: struktur payload valid, metrik terisolasi per wewenang. | **PASS** |
| **2. Efisiensi Incremental Sync** | Klien dapat mengambil perubahan data parsial tanpa perlu mengunduh ulang seluruh dataset. | Teruji pada `TC-S5-03`: endpoint `/sync` menyaring rekaman berdasarkan `since` dan menyertakan `next_cursor`. | **PASS** |
| **3. Mekanisme ETag & HTTP 304** | Polling berulang saat data tidak berubah mengembalikan status `304 Not Modified` tanpa payload data. | Teruji pada `TC-S5-04`: header ETag valid dan permintaan `If-None-Match` dijawab dengan status 304 kosong. | **PASS** |
| **4. Penyiar Event Real-Time** | Setiap mutasi kritis (surat, pengaduan, pembayaran kas) memancarkan event penyiaran ke kanal aman. | Teruji pada `TC-S5-05`: event `LetterStatusUpdated`, `ComplaintStatusUpdated`, `DuePaymentRecorded` terbukti terpicu. | **PASS** |
| **5. Validasi UAT Multi-Peran** | Seluruh peran (Warga, Sekretaris, Ketua RT, Bendahara) dapat berinteraksi dalam satu alur bisnis tanpa kegagalan. | Teruji pada `TC-S5-06`: rantai pengajuan, verifikasi, pengesahan, dan pembayaran tuntas 100%. | **PASS** |
| **6. Kualitas Kode & Regresi Penuh** | Seluruh test suite lulus 100% dan lolos standar formatting Laravel Pint. | **201 tests, 955 assertions, 100% PASSED**; Laravel Pint: `passed`. | **PASS** |

---

### Keputusan Resmi Gerbang Sprint 5: **PASS**
*Seluruh kriteria kelulusan TOGAF ADM Phase G dan Phase H untuk integrasi REST API v1, Near-Real-Time, dan UAT telah terpenuhi 100%. Sistem berada dalam kondisi stabil, aman, dan siap dilanjutkan ke **Sprint 6 — Staging Release, Production and Maintenance Start (Final Phase H: Pre-release Checklist, Disaster Recovery Rehearsal, & Milestone Tag v1.0.0)**.*
