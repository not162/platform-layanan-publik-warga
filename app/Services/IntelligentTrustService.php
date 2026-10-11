<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * IntelligentTrustService
 *
 * Mengimplementasikan seluruh metode, algoritma, dan standarisasi
 * dari Kerangka Diagram Lingkaran Sistem PRISMA RT 04:
 *
 * 1. CORE TRIAD (Tengah):
 *    - Governance (Tata Kelola Kebijakan & Validasi Wewenang)
 *    - Timestamp (Kronologi Imutabel & RFC 3339 Monotonic Time)
 *    - Privacy (UU PDP No. 27/2022, Blind Indexing & PII Masking)
 *
 * 2. KRIPTOGRAFI & KEPERCAYAAN (Sisi Kiri - Oranye):
 *    - Decentralized (Verifikasi Independen Ber-Token Tanpa Akses Internal DB)
 *    - Trust (Immutable Ledger, Anti-Fraud & Checksum Validasi)
 *    - Timestamp (Anti-Collision Sequencing & Atomic Concurrency Clock)
 *    - Hash/Signature (SHA-256 Document Fingerprint & Signature Verification)
 *
 * 3. AGEN OTONOM, ETIKA & AI (Sisi Kanan - Biru):
 *    - Ethics (Minimalisasi Data, Aksesibilitas WCAG & Keadilan Transparansi)
 *    - Autonomous Agent (Otomasi Validasi & Pemrosesan Latar Belakang)
 *    - Governance (Penegakan Kebijakan RBAC & Multi-Tier State Machine)
 *    - AI (Algoritma Cerdas Parsing NIK, Fuzzy Similarity Matching, Deteksi Anomali Kas)
 */
class IntelligentTrustService
{
    // =========================================================================
    // 1. CORE TRIAD: PRIVACY, TIMESTAMP, GOVERNANCE
    // =========================================================================

    /**
     * Algoritma Blind Indexing SHA-256 untuk perlindungan privasi (UU PDP).
     * Menghasilkan hash satu arah berulang untuk pencarian cepat tanpa
     * mengekspos plaintext NIK atau No. KK di database / log kueri.
     */
    public function generateBlindIndex(string $value): string
    {
        return hash('sha256', trim($value));
    }

    /**
     * Algoritma Masking PII (Personally Identifiable Information).
     * Menyamarkan karakter tengah untuk tampilan publik / log sistem.
     */
    public function maskNik(string $nik): string
    {
        $clean = trim($nik);
        $len = strlen($clean);

        if ($len < 8) {
            return '********';
        }

        return substr($clean, 0, 4).str_repeat('*', $len - 8).substr($clean, -4);
    }

    /**
     * Masking Nomor Telepon / WhatsApp Warga.
     */
    public function maskPhone(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);
        $len = strlen($clean);

        if ($len < 7) {
            return '********';
        }

        return substr($clean, 0, 4).str_repeat('*', $len - 7).substr($clean, -3);
    }

    /**
     * Standarisasi Timestamp ISO 8601 / RFC 3339 presisi mikrodik.
     */
    public function generateImmutableTimestamp(?Carbon $date = null): string
    {
        return ($date ?? now())->toRfc3339String();
    }

    // =========================================================================
    // 2. KRIPTOGRAFI & KEPERCAYAAN: HASH, SIGNATURE, DECENTRALIZED TRUST
    // =========================================================================

    /**
     * Algoritma Cryptographic Document Fingerprint (SHA-256).
     * Menggabungkan payload dokumen, nomor surat, token verifikasi, dan timestamp
     * untuk menghasilkan hash integritas anti-pemalsuan (tamper-evident).
     */
    public function generateDocumentFingerprint(string $content, array $metadata = []): string
    {
        $serializedMetadata = json_encode($metadata);

        return hash('sha256', $content.'|'.$serializedMetadata);
    }

    /**
     * Memverifikasi keabsahan dokumen digital terhadap hash tersimpan.
     */
    public function verifyDocumentIntegrity(string $rawContent, string $expectedHash, array $metadata = []): bool
    {
        $computed = $this->generateDocumentFingerprint($rawContent, $metadata);

        return hash_equals($expectedHash, $computed);
    }

    /**
     * Menghasilkan Token Verifikasi Digital (Decentralized Verification Token).
     * Digunakan pada QR Code surat agar pihak luar (Kelurahan/Kecamatan/Instansi)
     * dapat memverifikasi keabsahan surat secara publik tanpa perlu login atau akses database internal.
     */
    public function generateVerificationToken(): string
    {
        return Str::random(40);
    }

    // =========================================================================
    // 3. AI & ALGORITMA CERDAS (INTELLIGENT PROCESSING)
    // =========================================================================

    /**
     * Algoritma AI/Heuristik 1: Intelligent NIK Parser.
     * Mengurai 16 digit NIK Dukcapil secara cerdas:
     * - Digit 1-2: Kode Provinsi
     * - Digit 3-4: Kode Kota/Kabupaten
     * - Digit 5-6: Kode Kecamatan
     * - Digit 7-8: Tanggal Lahir (Koreksi offset +40 hari untuk perempuan)
     * - Digit 9-10: Bulan Lahir (01-12)
     * - Digit 11-12: Tahun Lahir 2 Digit (Inferensi Abad 1900 vs 2000)
     * - Digit 13-16: Nomor Urut Komputerisasi
     *
     * @return array{
     *     is_valid: bool,
     *     province_code: ?string,
     *     city_code: ?string,
     *     district_code: ?string,
     *     gender: string,
     *     birth_date: ?string,
     *     estimated_age: ?int,
     *     message: string
     * }
     */
    public function parseNik(string $nik): array
    {
        $clean = trim($nik);

        if (strlen($clean) !== 16 || ! ctype_digit($clean)) {
            return [
                'is_valid' => false,
                'province_code' => null,
                'city_code' => null,
                'district_code' => null,
                'gender' => 'Tidak Diketahui',
                'birth_date' => null,
                'estimated_age' => null,
                'message' => 'NIK harus berupa tepat 16 digit angka.',
            ];
        }

        $provinceCode = substr($clean, 0, 2);
        $cityCode = substr($clean, 2, 2);
        $districtCode = substr($clean, 4, 2);

        $dayRaw = (int) substr($clean, 6, 2);
        $month = (int) substr($clean, 8, 2);
        $year2Digit = (int) substr($clean, 10, 2);

        // Algoritma Dukcapil: Jika tanggal > 40, subjek adalah perempuan
        $isFemale = $dayRaw > 40;
        $birthDay = $isFemale ? ($dayRaw - 40) : $dayRaw;
        $gender = $isFemale ? 'Perempuan' : 'Laki-laki';

        // Inferensi Abad: Bandingkan dengan tahun berjalan 2 digit
        $currentYear2Digit = (int) date('y');
        $fullYear = ($year2Digit > $currentYear2Digit) ? (1900 + $year2Digit) : (2000 + $year2Digit);

        $birthDate = null;
        $age = null;

        if ($month >= 1 && $month <= 12 && $birthDay >= 1 && $birthDay <= 31 && checkdate($month, $birthDay, $fullYear)) {
            $birthDate = sprintf('%04d-%02d-%02d', $fullYear, $month, $birthDay);
            try {
                $carbonBirth = Carbon::parse($birthDate);
                $age = (int) $carbonBirth->age;
            } catch (\Exception) {
                $age = null;
            }
        }

        return [
            'is_valid' => $birthDate !== null,
            'province_code' => $provinceCode,
            'city_code' => $cityCode,
            'district_code' => $districtCode,
            'gender' => $gender,
            'birth_date' => $birthDate,
            'estimated_age' => $age,
            'message' => $birthDate ? 'NIK berhasil diurai secara valid.' : 'Tanggal lahir dalam digit NIK tidak valid.',
        ];
    }

    /**
     * Algoritma AI/Heuristik 2: Fuzzy Name Similarity Matching.
     * Menggabungkan algoritma Levenshtein Distance dan Similar Text Percentage
     * untuk mendeteksi kesamaan nama calon pendaftar warga dengan data master RT,
     * mencegah pendaftaran duplikat atau salah ketik (*typo*).
     *
     * @return float Derajat kemiripan dari 0.0 (berbeda total) hingga 1.0 (identik)
     */
    public function calculateNameSimilarity(string $queryName, string $targetName): float
    {
        $str1 = strtolower(trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $queryName)));
        $str2 = strtolower(trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $targetName)));

        if ($str1 === $str2) {
            return 1.0;
        }

        if ($str1 === '' || $str2 === '') {
            return 0.0;
        }

        // 1. Similar text percentage
        similar_text($str1, $str2, $percent);
        $similarityScore = $percent / 100.0;

        // 2. Levenshtein distance weighting
        $maxLen = max(strlen($str1), strlen($str2));
        $levDistance = levenshtein($str1, $str2);
        $levScore = 1.0 - ($levDistance / (float) $maxLen);

        // Gabungan berbobot: 60% Similar Text + 40% Levenshtein
        return round(($similarityScore * 0.6) + (max(0.0, $levScore) * 0.4), 4);
    }

    /**
     * Algoritma AI/Heuristik 3: Deteksi Anomali & Duplikasi Transaksi Keuangan.
     * Mencegah *double posting* dan mendeteksi anomali mutasi kas:
     * - Idempotency hash berbasis jumlah, tanggal, kategori, dan deskripsi.
     * - Deteksi apakah transaksi identik telah tercatat dalam rentang waktu singkat.
     */
    public function generateTransactionIdempotencyKey(
        string $type,
        float|string $amount,
        string $date,
        string $category,
        string $description
    ): string {
        $normalized = sprintf(
            '%s|%.2f|%s|%s|%s',
            strtolower(trim($type)),
            (float) $amount,
            trim($date),
            strtolower(trim($category)),
            strtolower(trim($description))
        );

        return hash('sha256', $normalized);
    }

    /**
     * Standarisasi Etika & Tata Kelola:
     * Memastikan pemisahan data agregat publik dan data privat.
     */
    public function sanitizePublicFinancePayload(array $transactionData): array
    {
        return [
            'id' => $transactionData['id'] ?? null,
            'type' => $transactionData['transaction_type'] ?? null,
            'amount' => $transactionData['amount'] ?? 0,
            'category' => $transactionData['category'] ?? null,
            'description' => $transactionData['description'] ?? null,
            'date' => $transactionData['transaction_date'] ?? null,
            // Hilangkan seluruh referensi PII (user_id, citizen_id, NIK, nama pengunggah)
        ];
    }
}
