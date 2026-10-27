# Pemodelan 5 Unsur dengan Metode Waterfall: PRISMA RT 04

> Dokumen induk pemodelan perangkat lunak. Menempatkan lima unsur pemodelan pada fase-fase Waterfall proyek ini dan menelusurinya sampai ke kode Laravel dan pengujian. Rencana pelaksanaan ada di [SPRINT_PLAN.md](../plan_project/SPRINT_PLAN.md), kebutuhan di [SRS.md](../business/SRS.md).

## 1. Metode dan lima unsur

Proyek memakai **Waterfall terkendali**: fase berjalan berurutan dan satu fase baru dimulai setelah *gate* fase sebelumnya selesai. Istilah "sprint" pada rencana proyek hanyalah *timebox* pelaksanaan, bukan perubahan metode (lihat catatan di `SPRINT_PLAN.md`).

Lima unsur pemodelan yang dipakai:

| No | Unsur | Menjawab pertanyaan | Dokumen rinci |
|---|---|---|---|
| 1 | Entity Relationship Diagram (ERD) | Data apa yang dikelola dan bagaimana hubungannya? | [ERD.md](ERD.md) |
| 2 | Logical Record Structure (LRS) | Bagaimana data itu disusun menjadi tabel, kunci, dan relasi? | [LRS.md](LRS.md) |
| 3 | Use Case Diagram | Siapa memakai sistem dan untuk keperluan apa? | [DIAGRAMS.md, bagian 3](DIAGRAMS.md) |
| 4 | Activity Diagram | Bagaimana proses bisnis berjalan, dan siapa mengerjakan tiap langkah? | [DIAGRAMS.md, bagian 4](DIAGRAMS.md) |
| 5 | UML (Class dan Sequence) | Bagaimana perangkat lunak disusun dan objek saling berinteraksi? | [DIAGRAMS.md, bagian 5](DIAGRAMS.md) |

## 2. Peta unsur ke fase Waterfall

| Fase Waterfall | Sprint (timebox) | Unsur yang dihasilkan | Gate keluar fase |
|---|---|---|---|
| 1. Analisis kebutuhan | Sprint 0 dan 1 | **Unsur 3 Use Case** beserta SRS (FR-01 sampai FR-08, NFR-01 sampai NFR-04) | Lingkup dan kebijakan cabang disetujui (Sprint 0), lalu *requirements sign-off* (Sprint 1) |
| 2. Perancangan | Sprint 2 | **Unsur 4 Activity** (proses), **Unsur 1 ERD** (data konseptual), **Unsur 2 LRS** (data logis), **Unsur 5 UML** (Class dan Sequence) | *Design consistency review*: UML, database, API, dan UI memakai istilah dan status yang sama |
| 3. Implementasi | Sprint 3 dan 4 | Kode Laravel yang menerjemahkan seluruh unsur (bagian 5) | Kriteria penerimaan Sprint 3 dan 4 terpenuhi (catatan kas terbit tidak bisa diubah diam-diam, aksi kritis tercatat di audit log) |
| 4. Pengujian | Sprint 5 | Tes yang menelusuri use case, activity, dan sequence (bagian 6); UAT | Tidak ada cacat kritis dan UAT disetujui (`0.9.0-rc`) |
| 5. Penyebaran dan pemeliharaan | Sprint 6 dan seterusnya | Runbook rilis; diagram diperbarui setiap perubahan | Rilis `1.0.0`; perubahan lanjutan lewat *change request* |

Setiap fase menghasilkan artefak yang menjadi masukan fase berikutnya, dan arah penelusuran juga berlaku sebaliknya: sebuah tes harus bisa ditunjuk ke use case atau activity asalnya.

```
Analisis ──► Use Case (3) ──► Perancangan ──► Activity (4) ─┐
                              │                ERD (1) ──► LRS (2) ──► migrasi database/
                              │                UML (5) ──────────────► app/Models, Services, Controllers
                              ▼
                         Implementasi ──► Pengujian ──► Penyebaran dan pemeliharaan
```

---

## 3. Fase 1: Analisis kebutuhan (Unsur 3)

Use case dipecah per modul agar terbaca, memakai notasi UML (aktor di luar batas sistem, `«include»`, `«extend»`, pewarisan aktor). Posisi tiap garis dan elips sudah dioptimalkan agar garis tidak menembus elips lain dan tidak saling bersilangan.

| Modul | Diagram | Kebutuhan (SRS) |
|---|---|---|
| Akun dan registrasi warga | ![](diagrams/usecase-1-akun-registrasi.svg) | FR-01, FR-02, NFR-01 |
| Surat pengantar | ![](diagrams/usecase-2-surat-pengantar.svg) | FR-03, FR-04, NFR-03 |
| Keuangan dan iuran | ![](diagrams/usecase-3-keuangan-rt.svg) | FR-06, FR-08 |
| Pengaduan dan keamanan | ![](diagrams/usecase-4-pengaduan-keamanan.svg) | FR-05, FR-07 |
| Informasi RT dan administrasi | ![](diagrams/usecase-5-informasi-administrasi.svg) | FR-02, FR-07, FR-08 |

Aktor dan wewenangnya dijabarkan pada [DIAGRAMS.md bagian 3](DIAGRAMS.md); hak akses dihitung dari `User::hasPermission()` dan `app/Enums/UserRole.php` (7 peran dalam 3 tingkat).

## 4. Fase 2: Perancangan (Unsur 4, 1, 2, 5)

### 4.1. Perancangan proses: Activity Diagram (Unsur 4)

Tujuh diagram swimlane: registrasi, login, lupa kata sandi, surat 2-tier, pembayaran iuran, buku kas dan laporan triwulan, pengaduan. Detail dan gambarnya di [DIAGRAMS.md bagian 4](DIAGRAMS.md).

### 4.2. Perancangan data: ERD lalu LRS (Unsur 1 dan 2)

ERD menggambarkan entitas dan kardinalitas (6 modul, 27 tabel). LRS menurunkannya menjadi struktur tabel logis dengan PK, FK, dan aturan `ON DELETE`. Keduanya dibangkitkan dari skema hasil migrasi.

| Modul | ERD | LRS |
|---|---|---|
| Akun dan akses | [ERD](diagrams/erd-1-akun-akses.svg) | [LRS](diagrams/lrs-1-akun-akses.svg) |
| Kependudukan | [ERD](diagrams/erd-2-kependudukan.svg) | [LRS](diagrams/lrs-2-kependudukan.svg) |
| Surat pengantar | [ERD](diagrams/erd-3-surat.svg) | [LRS](diagrams/lrs-3-surat.svg) |
| Keuangan dan iuran | [ERD](diagrams/erd-4-keuangan.svg) | [LRS](diagrams/lrs-4-keuangan.svg) |
| Pengaduan dan keamanan | [ERD](diagrams/erd-5-pengaduan-keamanan.svg) | [LRS](diagrams/lrs-5-pengaduan-keamanan.svg) |
| Informasi RT dan audit | [ERD](diagrams/erd-6-informasi-audit.svg) | [LRS](diagrams/lrs-6-informasi-audit.svg) |

LRS berdiri sebagai dokumen sendiri: [LRS.md](LRS.md) (diagram, matriks FK, daftar tabel teks, dan catatan normalisasi).

### 4.3. Perancangan perangkat lunak: UML (Unsur 5)

- **Class diagram** (7 diagram): domain akun, surat, keuangan, pengaduan, lapisan aplikasi, dan hierarki kelas.
- **Sequence diagram** (6 diagram): registrasi, login, lupa kata sandi, pengesahan surat dengan *optimistic locking*, pembayaran iuran, dan pembaruan dashboard.

---

## 5. Fase 3: Implementasi (penerjemahan ke Laravel)

| Artefak desain | Hasil di kode Laravel | Lokasi |
|---|---|---|
| ERD dan LRS | Skrip migrasi (39 berkas), *factory* dan *seeder* untuk data uji | `database/migrations/`, `database/factories/`, `database/seeders/` |
| Class: entitas | Model Eloquent dengan relasi (`HasMany`, `BelongsTo`) dan *cast* (`encrypted`, `hashed`) | `app/Models/` (24 model) |
| Class: peran dan izin | Enum peran dan izin, `User::hasPermission()` | `app/Enums/UserRole.php`, `app/Enums/Permission.php` |
| Class dan Activity: aturan bisnis | Service dengan transaksi, kunci baris, dan *optimistic locking* | `app/Services/` (`LetterService`, `FinanceService`, `ResidentDueService`, `ComplaintService`, dst.) |
| Sequence: titik masuk | Controller API v1 dan rute | `app/Http/Controllers/`, `routes/api.php`, `routes/web.php` |
| Sequence: peristiwa | Event siaran `LetterStatusUpdated`, `DuePaymentRecorded`, `ComplaintStatusUpdated`, `CitizenRegistered` | `app/Events/` |
| Use case: akses publik dan PWA | Tampilan Blade, aset PWA, *service worker* | `resources/views/`, `public/js/`, `public/sw.js` |
| Audit (FR-08) | `AuditService` dan tabel `audit_logs` | `app/Services/AuditService.php` |

Aturan implementasi yang diwariskan dari desain: setiap perubahan status memakai `DB::transaction` dengan `lockForUpdate` dan memeriksa kolom `version` (NFR-03); NIK dan No. KK disimpan terenkripsi dengan hash terpisah (NFR-01); transaksi kas yang terbit tidak dihapus, hanya di-*reverse* (FR-06).

## 6. Fase 4: Pengujian (penelusuran dari desain)

Hasil terakhir `php artisan test`: **242 tes lulus** (tes fitur dan unit).

| Use case dan activity | Sequence | Rute utama | Tes yang menelusuri |
|---|---|---|---|
| Registrasi, login, lupa kata sandi | Registrasi, login, lupa kata sandi | `POST /api/v1/auth/check-citizen`, `/register`, `/login`, `/auth/forgot-password`, `/auth/reset-password` | `AuthSelfRegistrationTest`, `DuesAttachmentsAndRecoveryTest`, `CitizenModuleTest`, `RoleAccessTest` |
| Surat pengantar 2-tier | Pengesahan surat (OCC) | `POST /api/v1/letters`, `/letters/{id}/submit`, `/admin/letters/{id}/verify`, `/approve`, `/complete`, `/reject`; publik: `/public/track/{ticket}`, `/public/letter/verify/{token}` | `LetterModuleTest`, `DocumentWorkflowAndGenerationTest`, `Sprint2LetterComplaintSecurityTest`, `OfficialLetterheadTest` |
| Iuran dan buku kas | Pembayaran iuran | `POST /api/v1/admin/finance/dues/generate`, `/payments`, `/transactions/{id}/publish`, `/reverse`, `/reports/quarterly/generate`; `GET /api/v1/me/dues` | `FinanceModuleTest`, `FinanceLedgerSprint2Test`, `ResidentDuePersonalLedgerSprint3Test`, `Sprint3FinanceDueLedgerTest`, `PurchaseAndQuarterlyReportSprint4Test`, `FinanceReportBackupTest` |
| Pengaduan dan keamanan | (lihat class 5) | `POST /api/v1/complaints`, `PATCH /api/v1/admin/complaints/{id}/status`, `POST /api/v1/security-reports`, `PATCH /api/v1/admin/security-reports/{id}` | `ComplaintModuleTest`, `ComplaintCameraPermissionTest`, `SecurityReportModuleTest` |
| Informasi RT dan administrasi | Pembaruan dashboard | `GET /api/v1/portal`, `/public/*`, `/dashboard/summary`, `/notifications`, `/manage/{resource}` | `PublicContentModuleTest`, `ManageApiCrudTest`, `NotificationModuleTest`, `Sprint5ApiRealtimeUatTest`, `Sprint4BroadcastingNotificationTest`, `PwaModuleTest` |
| Integritas data (ERD dan LRS) | n/a | n/a | `Sprint1CitizenFamilyCardTest`, `Sprint3AccountCitizenFamilyCardTest`, `ArchitectureAndRbacNormalizationTest`, `DatabaseSeederTest` |
| Hak akses (RBAC) | n/a | semua rute `permission:*` | `RoleAndAuthorizationTest`, `CitizenServiceRoleMatrixTest`, `Sprint1BusinessArchitectureAndNavbarTest` |

Pengujian penerimaan (UAT) skenario SRS bagian 6 dijalankan pada Sprint 5 (`Sprint5ApiRealtimeUatTest`, `Sprint5DocumentationAndIntegrityTest`).

## 7. Fase 5: Penyebaran dan pemeliharaan

- **Penyebaran**: prosedur rilis, uji kesehatan, dan pemulihan bencana ada di [PRODUCTION_RUNBOOK_AND_ROLLBACK.md](../plan_project/PRODUCTION_RUNBOOK_AND_ROLLBACK.md); tes pendukung `Sprint6ProductionReleaseTest`.
- **Pemeliharaan**: perubahan setelah rilis 1.0.0 dikelola sebagai *change request*. Contohnya perombakan antarmuka menjadi PWA (digabung 2026-10-26), yang mengubah lapisan tampilan dan route web tanpa mengubah aturan bisnis di `app/Services/`. Setelah penggabungan itu seluruh diagram dibangkitkan ulang dari kode dan skema.

Aturan pemeliharaan diagram:

| Jika berubah | Perbarui |
|---|---|
| Migrasi (tabel, kolom, FK) | ERD, LRS, class diagram model |
| Aturan alur di `app/Services/` | Activity dan sequence terkait |
| Izin peran di `User::hasPermission()` | Use case dan tabel aktor |
| Rute baru di `routes/api.php` | Use case, tabel penelusuran bagian 6 |

## 8. Kualitas diagram: tidak ada garis yang bertabrakan

Seluruh diagram bergaris siku-siku (ERD, LRS, class, activity) diaudit otomatis pada saat dibangkitkan: tidak ada garis yang menembus kotak, dan tidak ada dua garis yang berimpit. Garis yang memang harus melintas diberi busur kecil (*line hop*) agar jelas tidak terhubung. Pada use case, urutan elips dan posisi aktor dicari otomatis supaya garis tidak menembus elips lain dan tidak bersilangan.

| Jenis diagram | Garis menembus kotak | Garis berimpit | Persilangan tersisa |
|---|---|---|---|
| ERD (6) | 0 | 0 | 1, diberi busur |
| LRS (6) | 0 | 0 | 1, diberi busur |
| Class (7) | 0 | 0 | 0 |
| Activity (7) | 0 | 0 | 0 |
| Use case (5) | 0 | n/a | 0 (satu garis menyentuh tepi elips) |
| Sequence (6) | n/a | n/a | n/a (hanya panah horizontal antar-lifeline) |

## 9. Berkas diagram

Semua gambar ada di [`diagrams/`](diagrams/): `usecase-*`, `activity-*`, `erd-*`, `lrs-*`, `class-*`, `sequence-*` (format SVG).
