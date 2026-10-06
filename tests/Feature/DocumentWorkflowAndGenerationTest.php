<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\User;
use Database\Seeders\LetterSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentWorkflowAndGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LetterSeeder::class);
        Storage::fake('local');
    }

    public function test_letter_explicit_workflow_state_transitions(): void
    {
        $wargaUser = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $wargaUser->id]);

        $sekretaris = User::factory()->sekretaris()->create();
        $ketuaRt = User::factory()->ketuaRt()->create();

        // 1. Initial State: Draft
        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'Surat Keterangan Umum',
            'ticket_number' => 'SRT-FLOW-01',
            'status' => 'draft',
            'version' => 1,
        ]);

        // Attempt illegal transition: draft -> approved MUST FAIL
        $illegalApprove = $this->actingAs($ketuaRt)->postJson("/api/v1/admin/letters/{$letter->id}/approve", [
            'version' => 1,
        ]);
        $illegalApprove->assertStatus(409);

        // 2. Draft -> Submitted (Action endpoint)
        $submitResponse = $this->actingAs($wargaUser)->postJson("/api/v1/letters/{$letter->id}/submit", [
            'version' => 1,
        ]);
        $submitResponse->assertStatus(200);
        $this->assertDatabaseHas('letters', ['id' => $letter->id, 'status' => 'submitted', 'version' => 2]);

        // Attempt illegal transition: submitted -> approved MUST FAIL (cannot skip verified)
        $illegalSkip = $this->actingAs($ketuaRt)->postJson("/api/v1/admin/letters/{$letter->id}/approve", [
            'version' => 2,
        ]);
        $illegalSkip->assertStatus(409);

        // 3. Submitted -> Verified (Sekretaris)
        $verifyResponse = $this->actingAs($sekretaris)->postJson("/api/v1/admin/letters/{$letter->id}/verify", [
            'version' => 2,
            'notes' => 'Persyaratan KTP dan KK lengkap',
        ]);
        $verifyResponse->assertStatus(200);
        $this->assertDatabaseHas('letters', ['id' => $letter->id, 'status' => 'verified', 'version' => 3]);

        // 4. Verified -> Approved (Ketua RT)
        $approveResponse = $this->actingAs($ketuaRt)->postJson("/api/v1/admin/letters/{$letter->id}/approve", [
            'version' => 3,
        ]);
        $approveResponse->assertStatus(200);
        $this->assertDatabaseHas('letters', [
            'id' => $letter->id,
            'status' => 'approved',
            'version' => 4,
        ]);

        $letter->refresh();
        $this->assertNotNull($letter->letter_number);
        $this->assertNotNull($letter->verification_token);
        $this->assertNotNull($letter->document_hash);

        // 5. Approved -> Completed
        $completeResponse = $this->actingAs($ketuaRt)->postJson("/api/v1/admin/letters/{$letter->id}/complete", [
            'version' => 4,
        ]);
        $completeResponse->assertStatus(200);
        $this->assertDatabaseHas('letters', ['id' => $letter->id, 'status' => 'completed', 'version' => 5]);

        // Attempt illegal transition: completed -> verified MUST FAIL
        $illegalRevert = $this->actingAs($sekretaris)->postJson("/api/v1/admin/letters/{$letter->id}/verify", [
            'version' => 5,
        ]);
        $illegalRevert->assertStatus(409);
    }

    public function test_optimistic_locking_prevents_concurrent_state_overwrite(): void
    {
        $citizen = Citizen::factory()->create();
        $sekretaris = User::factory()->sekretaris()->create();

        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'Surat Keterangan Umum',
            'ticket_number' => 'SRT-LOCK-01',
            'status' => 'submitted',
            'version' => 1,
        ]);

        // First verification with version 1 -> succeeds, version becomes 2
        $res1 = $this->actingAs($sekretaris)->postJson("/api/v1/admin/letters/{$letter->id}/verify", [
            'version' => 1,
        ]);
        $res1->assertStatus(200);

        // Second verification with stale version 1 -> returns 409 Conflict
        $res2 = $this->actingAs($sekretaris)->postJson("/api/v1/admin/letters/{$letter->id}/verify", [
            'version' => 1,
        ]);
        $res2->assertStatus(409);
    }

    public function test_multipart_file_upload_and_private_attachment_security(): void
    {
        $wargaUser = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $wargaUser->id]);

        $otherWarga = User::factory()->warga()->create();
        Citizen::factory()->create(['user_id' => $otherWarga->id]);

        $file = UploadedFile::fake()->create('ktp_pemohon.pdf', 500, 'application/pdf');

        $response = $this->actingAs($wargaUser)->post('/api/v1/letters', [
            'letter_type_code' => 'SK-UMUM',
            'purpose' => 'Pengurusan beasiswa kuliah',
            'attachments' => [$file],
        ]);

        $response->assertStatus(201);
        $letterId = $response->json('data.id');

        $this->assertDatabaseHas('letters', [
            'id' => $letterId,
            'citizen_id' => $citizen->id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('letter_attachments', [
            'letter_id' => $letterId,
            'judul_lampiran' => 'ktp_pemohon.pdf',
        ]);

        // Unauthorized warga cannot preview or download
        $unauthorizedDownload = $this->actingAs($otherWarga)->getJson("/api/v1/letters/{$letterId}/download");
        $unauthorizedDownload->assertStatus(403);

        // Owner can preview document
        $ownerPreview = $this->actingAs($wargaUser)->get("/api/v1/letters/{$letterId}/preview");
        $ownerPreview->assertStatus(200);

        // Owner can download document
        $ownerDownload = $this->actingAs($wargaUser)->get("/api/v1/letters/{$letterId}/download");
        $ownerDownload->assertStatus(200);
    }

    public function test_dynamic_letter_types_and_form_schemas(): void
    {
        $response = $this->getJson('/api/v1/letter-types');
        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data');

        // Form schema for SK-UMUM
        $schemaResponse = $this->getJson('/api/v1/letter-types/SK-UMUM/form-schema');
        $schemaResponse->assertStatus(200);
        $schemaResponse->assertJsonPath('data.kode_surat', 'SK-UMUM');
        $schemaResponse->assertJsonPath('data.template_key', 'surat-keterangan');
        $this->assertNotEmpty($schemaResponse->json('data.form_schema.fields'));
    }

    public function test_public_verification_returns_non_pii_official_data(): void
    {
        $citizen = Citizen::factory()->create([
            'full_name' => 'Budi Santoso',
            'nik' => '3171010000000001',
        ]);

        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'Surat Keterangan Umum',
            'status' => 'approved',
            'ticket_number' => 'SRT-PUB-01',
            'letter_number' => 'SK/0005/RT01/10/2026',
            'verification_token' => 'verification-public-token-abc',
            'approved_at' => now(),
            'version' => 2,
        ]);

        $response = $this->getJson('/api/v1/public/letter/verify/verification-public-token-abc');
        $response->assertStatus(200);
        $response->assertJsonPath('valid', true);
        $response->assertJsonPath('data.letter_number', 'SK/0005/RT01/10/2026');

        // Verify NIK is NOT in response
        $this->assertArrayNotHasKey('nik', $response->json('data'));
        $this->assertArrayNotHasKey('nik_hash', $response->json('data'));
    }

    public function test_completed_letter_cannot_revert_to_draft(): void
    {
        $wargaUser = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $wargaUser->id]);
        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'Surat Keterangan Umum',
            'status' => 'completed',
            'ticket_number' => 'SRT-CMPL-01',
            'version' => 5,
        ]);

        $response = $this->actingAs($wargaUser)->patchJson("/api/v1/letters/{$letter->id}/status", [
            'status' => 'draft',
            'version' => 5,
        ]);
        $response->assertStatus(409);
    }

    public function test_invalid_mime_and_oversized_file_are_rejected(): void
    {
        $warga = User::factory()->warga()->create();
        Citizen::factory()->create(['user_id' => $warga->id]);

        // Executable / PHP file rejected
        $badFile = UploadedFile::fake()->create('malicious.exe', 100, 'application/x-msdownload');
        $response = $this->actingAs($warga)->postJson('/api/v1/letters', [
            'type' => 'domicile',
            'attachments' => [$badFile],
        ]);
        $response->assertStatus(422);

        // Oversized file (> 5MB) rejected
        $hugeFile = UploadedFile::fake()->create('huge.pdf', 6000, 'application/pdf');
        $oversizedResponse = $this->actingAs($warga)->postJson('/api/v1/letters', [
            'type' => 'domicile',
            'attachments' => [$hugeFile],
        ]);
        $oversizedResponse->assertStatus(422);
    }

    public function test_document_sequence_numbering_increments_concurrency_safely(): void
    {
        $ketuaRt = User::factory()->ketuaRt()->create();
        $letterType = LetterType::query()->where('kode_surat', 'SK-UMUM')->first();

        $letters = [];
        for ($i = 1; $i <= 3; $i++) {
            $citizen = Citizen::factory()->create();
            $letters[] = Letter::create([
                'citizen_id' => $citizen->id,
                'jenis_surat_id' => $letterType->id,
                'type' => $letterType->nama_surat,
                'status' => 'verified',
                'ticket_number' => "SRT-SEQ-0{$i}",
                'version' => 2,
            ]);
        }

        $generatedNumbers = [];
        foreach ($letters as $letter) {
            $res = $this->actingAs($ketuaRt)->postJson("/api/v1/admin/letters/{$letter->id}/approve", [
                'version' => 2,
            ]);
            $res->assertStatus(200);
            $letter->refresh();
            $generatedNumbers[] = $letter->letter_number;
        }

        // Must be unique and consecutive
        $this->assertCount(3, array_unique($generatedNumbers));
        $this->assertStringContainsString('0001', $generatedNumbers[0]);
        $this->assertStringContainsString('0002', $generatedNumbers[1]);
        $this->assertStringContainsString('0003', $generatedNumbers[2]);
    }

    public function test_warga_cannot_access_export_payload_of_unapproved_letter(): void
    {
        $wargaUser = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $wargaUser->id]);
        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'Surat Keterangan Umum',
            'ticket_number' => 'SRT-EXP-DRAFT',
            'status' => 'draft',
            'version' => 1,
        ]);

        $response = $this->actingAs($wargaUser)->getJson("/api/v1/letters/{$letter->id}/export-payload");
        $response->assertStatus(409);
    }

    public function test_warga_cannot_access_export_payload_of_another_citizen(): void
    {
        $warga1 = User::factory()->warga()->create();
        $citizen1 = Citizen::factory()->create(['user_id' => $warga1->id]);

        $warga2 = User::factory()->warga()->create();
        $citizen2 = Citizen::factory()->create(['user_id' => $warga2->id]);

        $letter = Letter::create([
            'citizen_id' => $citizen1->id,
            'type' => 'Surat Keterangan Umum',
            'ticket_number' => 'SRT-EXP-OTHER',
            'status' => 'approved',
            'version' => 3,
        ]);

        $response = $this->actingAs($warga2)->getJson("/api/v1/letters/{$letter->id}/export-payload");
        $response->assertStatus(403);
    }

    public function test_warga_can_access_export_payload_for_client_side_export(): void
    {
        $wargaUser = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $wargaUser->id]);
        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'Surat Keterangan Umum',
            'ticket_number' => 'SRT-EXP-OK',
            'letter_number' => 'SK/0001/RT01/10/2026',
            'status' => 'approved',
            'version' => 3,
        ]);

        $response = $this->actingAs($wargaUser)->getJson("/api/v1/letters/{$letter->id}/export-payload");
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'approved');
        $response->assertJsonPath('data.export_capabilities.client_side_processing', true);
        $response->assertJsonPath('data.security_check.hash_match', true);
        $response->assertJsonPath('data.security_check.anti_tamper_verified', true);
        $this->assertNotEmpty($response->json('data.rendered_html'));
        $this->assertNotEmpty($response->json('data.document_hash'));
    }

    public function test_letter_download_supports_word_and_pdf_formats_via_get_and_post(): void
    {
        $wargaUser = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $wargaUser->id]);
        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'Surat Keterangan Umum',
            'ticket_number' => 'SRT-FMT-01',
            'letter_number' => 'SK/0001/RT01/10/2026',
            'status' => 'approved',
            'version' => 3,
        ]);

        // 1. Word format (.docx) via GET
        $wordGetResponse = $this->actingAs($wargaUser)->get("/api/v1/letters/{$letter->id}/download?format=docx");
        $wordGetResponse->assertStatus(200);
        $wordGetResponse->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertStringContainsString('filename="Surat_SK_0001_RT01_10_2026.docx"', (string) $wordGetResponse->headers->get('Content-Disposition'));
        // Genuine Zip/DOCX PK header
        $this->assertStringStartsWith('PK', (string) $wordGetResponse->getContent());

        // 2. Word format (.docx) via POST
        $wordPostResponse = $this->actingAs($wargaUser)->postJson("/api/v1/letters/{$letter->id}/download", [
            'format' => 'docx',
        ]);
        $wordPostResponse->assertStatus(200);
        $wordPostResponse->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertStringContainsString('filename="Surat_SK_0001_RT01_10_2026.docx"', (string) $wordPostResponse->headers->get('Content-Disposition'));

        // 3. Genuine PDF format (.pdf) via GET
        $pdfGetResponse = $this->actingAs($wargaUser)->get("/api/v1/letters/{$letter->id}/download?format=pdf");
        $pdfGetResponse->assertStatus(200);
        $pdfGetResponse->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('filename="Surat_SK_0001_RT01_10_2026.pdf"', (string) $pdfGetResponse->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-1.4', (string) $pdfGetResponse->getContent());

        // 4. Genuine PDF format (.pdf) via POST
        $pdfPostResponse = $this->actingAs($wargaUser)->postJson("/api/v1/letters/{$letter->id}/download", [
            'format' => 'pdf',
        ]);
        $pdfPostResponse->assertStatus(200);
        $pdfPostResponse->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('filename="Surat_SK_0001_RT01_10_2026.pdf"', (string) $pdfPostResponse->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-1.4', (string) $pdfPostResponse->getContent());
    }

    public function test_unapproved_letter_download_returns_clear_failure_reason(): void
    {
        $wargaUser = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $wargaUser->id]);
        $draftLetter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'Surat Keterangan Usaha',
            'ticket_number' => 'SRT-DRAFT-99',
            'status' => 'draft',
            'version' => 1,
        ]);

        $response = $this->actingAs($wargaUser)->getJson("/api/v1/letters/{$draftLetter->id}/download?format=pdf");
        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('status', 'draft');
        $this->assertStringContainsString('DRAF', $response->json('reason'));

        // Also test POST method
        $postResponse = $this->actingAs($wargaUser)->postJson("/api/v1/letters/{$draftLetter->id}/download", [
            'format' => 'pdf',
        ]);
        $postResponse->assertStatus(422);
        $postResponse->assertJsonPath('success', false);
        $this->assertNotEmpty($postResponse->json('reason'));
    }

    public function test_citizen_user_relation_has_unique_constraint(): void
    {
        $user = User::factory()->create();
        $citizen1 = Citizen::factory()->create(['user_id' => $user->id]);

        $this->expectException(QueryException::class);
        Citizen::factory()->create(['user_id' => $user->id]);
    }
}
