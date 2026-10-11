<?php

namespace Tests\Unit;

use App\Services\IntelligentTrustService;
use PHPUnit\Framework\TestCase;

class IntelligentTrustServiceTest extends TestCase
{
    protected IntelligentTrustService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new IntelligentTrustService;
    }

    public function test_blind_indexing_produces_consistent_sha256_hash(): void
    {
        $nik = '3171031605030002';
        $hash1 = $this->service->generateBlindIndex($nik);
        $hash2 = $this->service->generateBlindIndex($nik);

        $this->assertEquals(64, strlen($hash1));
        $this->assertEquals($hash1, $hash2);
        $this->assertEquals(hash('sha256', $nik), $hash1);
    }

    public function test_mask_nik_masks_middle_digits_for_privacy(): void
    {
        $nik = '3171031605030002';
        $masked = $this->service->maskNik($nik);

        $this->assertEquals('3171********0002', $masked);
    }

    public function test_mask_phone_masks_middle_digits_for_privacy(): void
    {
        $phone = '081234567890';
        $masked = $this->service->maskPhone($phone);

        $this->assertStringStartsWith('0812', $masked);
        $this->assertStringEndsWith('890', $masked);
        $this->assertStringContainsString('*', $masked);
    }

    public function test_document_fingerprint_and_integrity_verification(): void
    {
        $content = '<h1>Surat Keterangan Domisili Resmi</h1>';
        $metadata = ['letter_number' => 'SK/20261011/001', 'token' => 'abc123xyz'];

        $fingerprint = $this->service->generateDocumentFingerprint($content, $metadata);
        $this->assertEquals(64, strlen($fingerprint));

        // Intact content returns true
        $this->assertTrue($this->service->verifyDocumentIntegrity($content, $fingerprint, $metadata));

        // Tampered content returns false
        $tamperedContent = '<h1>Surat Keterangan Domisili PALSU</h1>';
        $this->assertFalse($this->service->verifyDocumentIntegrity($tamperedContent, $fingerprint, $metadata));
    }

    public function test_intelligent_nik_parser_extracts_male_birthdate_and_gender(): void
    {
        // 31 (DKI) 71 (Jakarta Pusat) 03 (Kemayoran) - 16 (Tgl 16 Laki-laki) 05 (Mei) 95 (1995) - 0001
        $nik = '3171031605950001';
        $parsed = $this->service->parseNik($nik);

        $this->assertTrue($parsed['is_valid']);
        $this->assertEquals('Laki-laki', $parsed['gender']);
        $this->assertEquals('1995-05-16', $parsed['birth_date']);
        $this->assertEquals('31', $parsed['province_code']);
        $this->assertEquals('71', $parsed['city_code']);
        $this->assertEquals('03', $parsed['district_code']);
        $this->assertNotNull($parsed['estimated_age']);
    }

    public function test_intelligent_nik_parser_extracts_female_birthdate_with_40_day_offset(): void
    {
        // 31 71 03 - 56 (Tgl 16 Perempuan: 16+40=56) 08 (Agustus) 01 (2001) - 0002
        $nik = '3171035608010002';
        $parsed = $this->service->parseNik($nik);

        $this->assertTrue($parsed['is_valid']);
        $this->assertEquals('Perempuan', $parsed['gender']);
        $this->assertEquals('2001-08-16', $parsed['birth_date']);
    }

    public function test_fuzzy_name_similarity_matching(): void
    {
        // Exact match
        $this->assertEquals(1.0, $this->service->calculateNameSimilarity('Swandaru Tirta', 'Swandaru Tirta'));

        // Minor spacing/case difference
        $score = $this->service->calculateNameSimilarity('Swandaru Tirta', 'Swan Daru Tirta');
        $this->assertGreaterThan(0.85, $score);

        // Completely different names
        $lowScore = $this->service->calculateNameSimilarity('Budi Santoso', 'Siti Aminah');
        $this->assertLessThan(0.35, $lowScore);
    }

    public function test_transaction_idempotency_key_is_deterministic(): void
    {
        $key1 = $this->service->generateTransactionIdempotencyKey('income', 500000.00, '2026-10-11', 'Iuran', 'Iuran Warga Oktober');
        $key2 = $this->service->generateTransactionIdempotencyKey('income', '500000', '2026-10-11', 'Iuran', 'Iuran Warga Oktober');

        $this->assertEquals($key1, $key2);
        $this->assertEquals(64, strlen($key1));
    }
}
