# Architecture and Flow Diagrams

> Rujukan Pemodelan Lengkap 5 Unsur Sistem: **[SYSTEM_MODELING_5_ELEMENTS.md](SYSTEM_MODELING_5_ELEMENTS.md)**  
> Dokumentasi ini memetakan diagram alur dan arsitektur dengan pemisahan peran: **Superadmin & Admin di sisi kiri**, **Sistem PRISMA di tengah**, dan **User & Warga di sisi kanan**.

---

## 1. Sequence Diagram: Optimistic Concurrency Control

Diagram urutan penanganan proteksi tabrakan data kependudukan saat diedit bersamaan:

```mermaid
sequenceDiagram
    autonumber
    actor Admin1 as [Kiri] Admin A (Sekretaris)
    actor Admin2 as [Kiri] Admin B (Staf RT)
    participant API as [Tengah] API Controller
    participant Svc as [Tengah] CitizenService
    participant DB as [Tengah] MySQL Database

    Admin1->>API: GET /api/v1/admin/citizens/1
    API->>DB: SELECT * FROM citizens WHERE id = 1
    DB-->>API: Data Warga (version = 1)
    API-->>Admin1: Tampilkan Form Edit (version: 1)

    Admin2->>API: GET /api/v1/admin/citizens/1
    API->>DB: SELECT * FROM citizens WHERE id = 1
    DB-->>API: Data Warga (version = 1)
    API-->>Admin2: Tampilkan Form Edit (version: 1)

    Admin1->>API: PATCH /api/v1/admin/citizens/1 (version: 1)
    API->>Svc: updateWithOptimisticLock(id=1, data, expectedVersion=1)
    Svc->>DB: SELECT version FROM citizens WHERE id = 1 FOR UPDATE
    DB-->>Svc: version = 1 (Cocok)
    Svc->>DB: UPDATE citizens SET ..., version = 2 WHERE id = 1
    DB-->>Svc: OK
    Svc-->>API: Success
    API-->>Admin1: HTTP 200 OK (Tersimpan)

    Admin2->>API: PATCH /api/v1/admin/citizens/1 (version: 1)
    API->>Svc: updateWithOptimisticLock(id=1, data, expectedVersion=1)
    Svc->>DB: SELECT version FROM citizens WHERE id = 1 FOR UPDATE
    DB-->>Svc: version = 2 (Konflik!)
    Svc-->>API: Throw ConcurrencyConflictException
    API-->>Admin2: HTTP 409 Conflict ("Data telah diubah oleh pengurus lain")
```

---

## 2. Activity Diagram: Alur Birokrasi Surat Pengantar Digital 2-Tier

```mermaid
stateDiagram-v2
    direction LR

    state "SISI KIRI: PENGURUS RT" as Kiri {
        SekReview: Sekretaris Periksa Berkas & Syarat
        SekApproval: Verifikasi Disetujui
        KRTApproval: Ketua RT Sahkan & TTD Digital
    }

    state "TENGAH: SISTEM PRISMA RT 04" as Tengah {
        DrafSurat: Simpan Formulir & Dokumen
        StatusVerified: Update Status = VERIFIED
        TerbitQR: Terbitkan No Surat, SHA-256 Hash & QR Token
    }

    state "SISI KANAN: WARGA LINGKUNGAN" as Kanan {
        InputPermohonan: Warga Pilih Surat & Unggah Syarat
        KirimSurat: Submit Permohonan (Status: SUBMITTED)
        UnduhPDF: Unduh Surat PDF Berstempel QR
    }

    Kanan --> Tengah: 1. Input & Unggah Syarat
    Tengah --> Kiri: 2. Notifikasi Berkas Masuk ke Sekretaris
    Kiri --> Kiri: 3. Sekretaris Verifikasi Keabsahan Berkas
    Kiri --> Tengah: 4. Setujui Berkas (Status: VERIFIED)
    Tengah --> Kiri: 5. Antrean Tanda Tangan ke Ketua RT
    Kiri --> Tengah: 6. Ketua RT Pengesahan / Tanda Tangan
    Tengah --> Tengah: 7. Hitung Hash Dokumen & Buat QR Code
    Tengah --> Kanan: 8. Dokumen Siap Diunduh Warga (Status: COMPLETED)
```

> **Untuk rincian langkah demi langkah dalam Format Kotak Tabel Berpetak**, lihat:  
> [docs/design/SYSTEM_MODELING_5_ELEMENTS.md (Bagian 4: Activity Diagram)](SYSTEM_MODELING_5_ELEMENTS.md#unsur-4-activity-diagram-format-kotak-tabel-step-by-step)
