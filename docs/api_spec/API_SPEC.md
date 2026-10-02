# Dokumentasi Spesifikasi API — Platform Layanan Publik Warga

**Versi API:** `v1`  
**Base URL:** `/api/v1`  
**Autentikasi:** Laravel Sanctum Bearer Token (`Authorization: Bearer <token>`)

---

## 1. Standar Format Respons (Envelope Pattern)

### A. Respons Berhasil (HTTP 200 / 201)
```json
{
  "data": { ... },
  "meta": {
    "request_id": "c1f7b8a1-2d4e-4f3a-9e11-887766554433",
    "api_version": "v1",
    "timestamp": "2026-10-01T23:45:00+07:00"
  },
  "message": "OK"
}
```

### B. Respons Error Validasi (HTTP 422)
```json
{
  "data": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "fields": {
      "purpose": [
        "Keperluan surat wajib diisi."
      ],
      "attachments.0": [
        "Lampiran harus berupa file berformat pdf, jpg, jpeg, atau png."
      ]
    }
  },
  "message": "Data tidak valid."
}
```

### C. Respons Konflik Versi / Optimistic Lock (HTTP 409)
```json
{
  "message": "Konflik data: Versi data surat telah berubah."
}
```

### D. Respons Hak Akses Ditolak (HTTP 403)
```json
{
  "message": "Akses ditolak: Membutuhkan izin 'letter.approve'."
}
```

---

## 2. Katalog Endpoint Publik (Tanpa Autentikasi)

### 2.1 Katalog Layanan & Master Surat
* **Endpoint:** `GET /api/v1/public/services` atau `GET /api/v1/letter-types`
* **Role:** Publik / Tamu
* **Permission:** None
* **Request:** None
* **HTTP Status:** `200 OK`
* **Response Contoh:**
```json
{
  "data": [
    {
      "id": 1,
      "kode_surat": "SK-UMUM",
      "nama_surat": "Surat Keterangan Umum",
      "template_key": "surat-keterangan",
      "syarat_dokumen": ["Scan KTP Pemohon", "Scan Kartu Keluarga (KK)"],
      "estimated_process_hours": 24,
      "is_active": true
    }
  ],
  "message": "Katalog layanan surat berhasil dimuat."
}
```

### 2.2 Skema Formulir Dinamis Surat (Dynamic Form Schema)
* **Endpoint:** `GET /api/v1/letter-types/{code}/form-schema`
* **Role:** Publik / Warga
* **Permission:** None
* **Parameter:** `code` (string: `SK-UMUM`, `SK-KEMATIAN`, `SK-PINDAH`, `SKTM`)
* **HTTP Status:** `200 OK` (atau `404 Not Found`)
* **Response Contoh:**
```json
{
  "data": {
    "kode_surat": "SKTM",
    "nama_surat": "Surat Keterangan Tidak Mampu (SKTM)",
    "template_key": "surat-keterangan-tidak-mampu",
    "template_version": 1,
    "form_schema": {
      "fields": [
        { "name": "purpose", "label": "Keperluan", "type": "text", "required": true },
        { "name": "dependents", "label": "Jumlah Tanggungan", "type": "number", "required": true }
      ]
    },
    "syarat_dokumen": ["Scan KTP", "Scan KK", "Foto Rumah", "Surat Pernyataan Penghasilan"],
    "estimated_process_hours": 24
  },
  "message": "Skema formulir surat berhasil dimuat."
}
```

### 2.3 Pelacakan Tiket Surat (Public Tracking)
* **Endpoint:** `GET /api/v1/public/track/{ticket}`
* **Role:** Publik
* **Permission:** None
* **Proteksi Privasi:** Tidak mengekspos NIK, KK, atau data pribadi pemohon.
* **HTTP Status:** `200 OK` (atau `404 Not Found`)
* **Response Contoh:**
```json
{
  "found": true,
  "data": {
    "ticket_number": "SRT-20261001-A12BC",
    "type": "Surat Keterangan Umum",
    "status": "verified",
    "created_at": "2026-10-01T10:30:00+07:00",
    "verified_at": "2026-10-01T11:20:00+07:00",
    "approved_at": null
  },
  "message": "Informasi pelacakan surat berhasil dimuat."
}
```

### 2.4 Verifikasi Dokumen Sah (Public Document Verification)
* **Endpoint:** `GET /api/v1/public/letter/verify/{token}`
* **Role:** Publik
* **Permission:** None
* **Proteksi Privasi:** Hanya mengonfirmasi nomor surat, jenis surat, status keabsahan, dan penerbit.
* **HTTP Status:** `200 OK`
* **Response Contoh:**
```json
{
  "valid": true,
  "data": {
    "letter_number": "SK/0001/RT01/10/2026",
    "letter_type": "Surat Keterangan Umum",
    "status": "approved",
    "recipient_name": "Ahmad Dahlan",
    "approved_at": "2026-10-01T12:00:00+07:00",
    "issuer": "Pengurus RT 01"
  },
  "message": "Dokumen sah dan terverifikasi secara resmi."
}
```

---

## 3. Katalog Endpoint Warga (Authenticated Warga)

### 3.1 Profil Pengguna
* **Endpoint:** `GET /api/v1/me`
* **Role:** WARGA, PENGURUS
* **HTTP Status:** `200 OK`
* **Keterangan:** NIK dikembalikan dalam bentuk ter-masking (`3171********0001`).

### 3.2 Pengajuan Permohonan Surat
* **Endpoint:** `POST /api/v1/letters`
* **Content-Type:** `multipart/form-data`
* **Role:** WARGA
* **Permission:** `letter.create` (otomatis pada warga aktif berprofil kependudukan)
* **Payload Request:**
  * `letter_type_code`: string (misal: `SK-UMUM`)
  * `purpose`: string (maks. 500 karakter)
  * `data_tambahan`: array/json (data dinamis sesuai jenis surat)
  * `attachments[]`: file (maks. 5 file, tipe `pdf, jpg, jpeg, png`, maks. 5MB/file)
* **HTTP Status:** `201 Created`
* **Response Contoh:**
```json
{
  "data": {
    "id": 14,
    "type": "Surat Keterangan Umum",
    "status": "draft",
    "ticket_number": "SRT-20261001-K91PQ",
    "version": 1,
    "created_at": "2026-10-01T23:50:00+07:00"
  }
}
```

### 3.3 Aksi Ajukan Surat (Draft ➔ Submitted)
* **Endpoint:** `POST /api/v1/letters/{id}/submit`
* **Role:** WARGA (Pemilik surat)
* **Request:** `{ "version": 1 }`
* **HTTP Status:** `200 OK` (atau `409 Conflict` jika versi tidak sesuai)

### 3.4 Payload Ekspor Dokumen untuk Front-End (Client-Side Offloading)
* **Endpoint:** `GET /api/v1/letters/{id}/export-payload`
* **Role:** WARGA (Pemilik surat) atau PENGURUS yang berwenang
* **Kebutuhan Keamanan:** Surat wajib berstatus `approved` atau `completed`. Jika draft/submitted, sistem menolak dengan `409 Conflict`.
* **Keunggulan Enterprise:** Menyediakan template HTML ter-render beserta SHA-256 digital fingerprint untuk diproses langsung di Frontend (browser warga). Menghilangkan kompilasi file di server sehingga mencegah kehabisan memori (*OOM*) dan *API route bottleneck*.
* **HTTP Status:** `200 OK`
* **Response Contoh:**
```json
{
  "data": {
    "id": 1,
    "ticket_number": "SRT-20261001-A12BC",
    "letter_number": "SK/0001/RT01/10/2026",
    "letter_type": "Surat Keterangan Umum",
    "template_key": "surat-keterangan",
    "status": "approved",
    "citizen": {
      "name": "Budi Santoso",
      "nik_masked": "3171************"
    },
    "issued_at": "01 Oktober 2026",
    "verification_token": "a8f5c3b9e1d2f4a6b8c0e2d4f6a8b0c2e4d6f8a0",
    "verification_url": "http://127.0.0.1:8000/api/v1/public/letter/verify/a8f5c3b9e1d2f4a6b8c0e2d4f6a8b0c2e4d6f8a0",
    "document_hash": "b2f6b4e073c6833959dfdc9f000b21a8d0f1b2c3d4e5f60718293a4b5c6d7e8f",
    "rendered_html": "<!DOCTYPE html><html>...</html>",
    "export_capabilities": {
      "formats": ["pdf", "word"],
      "client_side_processing": true,
      "cacheable_offline": true
    },
    "security_check": {
      "hash_algorithm": "SHA-256",
      "hash_match": true,
      "anti_tamper_verified": true,
      "authorized_citizen_id": 1
    }
  },
  "meta": {
    "architecture": "Enterprise Microservices / Client-Side Offloading",
    "benefits": "Zero server CPU load, eliminates route bottlenecks and API timeout",
    "timestamp": "2026-10-01T23:55:00+07:00"
  },
  "message": "Payload dokumen berhasil disiapkan untuk pemrosesan dan penyimpanan di sisi klien (Front-End)."
}
```

### 3.5 Unduh Dokumen Surat Sah (Multi-Format)
* **Endpoint:** `GET /api/v1/letters/{id}/download`
* **Query Parameters:**
  * `format=word` (atau `format=docx`): Mengunduh berkas Microsoft Word (`.doc`) dengan format Office Open XML.
  * `format=pdf`: Mengembalikan tampilan cetak vektor siap print A4 dengan header `X-Document-Printable: true`.
  * `format=html` (default): Mengunduh berkas HTML arsip resmi.
* **Role:** WARGA (Pemilik surat) atau PENGURUS yang berwenang
* **HTTP Status:** `200 OK`
* **Headers Word:** `Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document`, `Content-Disposition: attachment; filename="Surat_SK_0001_RT01_10_2026.doc"`

### 3.5 Pembuatan Laporan Keamanan Warga
* **Endpoint:** `POST /api/v1/security-reports`
* **Role:** WARGA
* **Payload Request:**
```json
{
  "category": "kehilangan",
  "severity": "medium",
  "title": "Kehilangan Sepeda",
  "description": "Sepeda lipat merah hilang di depan pagar semalam.",
  "location": "Jl. Mawar No. 10",
  "incident_at": "2026-10-01 20:00:00",
  "is_anonymous": false
}
```
* **HTTP Status:** `201 Created`

---

## 4. Katalog Endpoint Administratif Pengurus (Admin / Staff)

### 4.1 Verifikasi Surat oleh Sekretaris
* **Endpoint:** `POST /api/v1/admin/letters/{id}/verify` (juga mendukung `PATCH`)
* **Role:** SEKRETARIS, SUPERADMIN
* **Permission:** `letter.verify`
* **Request:** `{ "version": 2, "notes": "KTP dan KK telah diverifikasi dan valid." }`
* **HTTP Status:** `200 OK` (atau `403 Forbidden`, `409 Conflict`)

### 4.2 Persetujuan Surat oleh Ketua RT
* **Endpoint:** `POST /api/v1/admin/letters/{id}/approve` (juga mendukung `PATCH`)
* **Role:** KETUA_RT, SUPERADMIN
* **Permission:** `letter.approve`
* **Request:** `{ "version": 3, "letter_number": null }` *(Nomor digenerate otomatis jika null)*
* **Efek Samping:** Menghasilkan dokumen resmi, hash SHA-256, dan token verifikasi publik.
* **HTTP Status:** `200 OK`

### 4.3 Penyelesaian Surat (Approved ➔ Completed)
* **Endpoint:** `POST /api/v1/admin/letters/{id}/complete`
* **Role:** KETUA_RT, SUPERADMIN
* **Permission:** `letter.complete` (atau `letter.approve`)
* **Request:** `{ "version": 4 }`
* **HTTP Status:** `200 OK`

### 4.4 Penolakan Surat
* **Endpoint:** `POST /api/v1/admin/letters/{id}/reject` (juga mendukung `PATCH`)
* **Role:** SEKRETARIS, KETUA_RT, SUPERADMIN
* **Permission:** `letter.reject` / `letter.verify` / `letter.approve`
* **Request:** `{ "version": 2, "rejection_reason": "Lampiran KTP buram dan tidak terbaca." }`
* **HTTP Status:** `200 OK` (atau `422 Unprocessable Content` jika alasan kosong)

### 4.5 Pengelolaan Laporan Keamanan
* **Endpoint:** `PATCH /api/v1/admin/security-reports/{id}`
* **Role:** PETUGAS_KEAMANAN, SUPERADMIN
* **Permission:** `security.manage`
* **Request:**
```json
{
  "version": 1,
  "assigned_to": 4,
  "status": "resolved",
  "resolution": "Petugas telah berkoordinasi dan masalah diselesaikan secara damai."
}
```
* **HTTP Status:** `200 OK`

### 4.6 Unduh Dokumen Berita Acara Laporan Keamanan
* **Endpoint:** `GET /api/v1/admin/security-reports/{id}/document`
* **Role:** PETUGAS_KEAMANAN, SUPERADMIN
* **Permission:** `security.read`
* **HTTP Status:** `200 OK`
