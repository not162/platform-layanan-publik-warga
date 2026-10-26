<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\FinanceTransaction;
use App\Models\User;
use Database\Seeders\LetterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OfficialLetterheadTest extends TestCase
{
    use RefreshDatabase;

    protected function approvedLetterId(User $warga): int
    {
        $sekretaris = User::factory()->sekretaris()->create();
        $ketua = User::factory()->ketuaRt()->create(['name' => 'Akun Login Ketua']);

        $id = $this->actingAs($warga)->postJson('/api/v1/manage/letters', ['jenis_surat_id' => 1, 'keperluan' => 'Izin usaha', 'submit' => true])->json('data.id');
        $this->actingAs($sekretaris)->postJson("/api/v1/manage/letters/{$id}/verify", ['version' => 2])->assertOk();
        $this->actingAs($ketua)->postJson("/api/v1/manage/letters/{$id}/approve", ['version' => 3])->assertOk();

        return $id;
    }

    public function test_official_letter_has_rt04_rw09_letterhead_contacts_and_signatories(): void
    {
        Storage::fake('local');
        $this->seed(LetterSeeder::class);
        $warga = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $warga->id]);
        $warga->update(['warga_id' => $citizen->id]);

        $id = $this->approvedLetterId($warga->refresh());

        $html = $this->actingAs($warga)->get("/api/v1/letters/{$id}/preview")->assertOk()->getContent();

        $this->assertStringContainsString('Rukun Tetangga 04 / Rukun Warga 09', $html);
        $this->assertStringContainsString('Kelurahan Kemayoran, Kecamatan Kemayoran', $html);
        $this->assertStringContainsString('+62 878-7200-4448', $html);
        $this->assertStringContainsString('swandrax@gmail.com', $html);
        $this->assertStringContainsString('Ketua RT 04 RW 09', $html);
        $this->assertStringContainsString('( Raden Erry Adu Sundaru )', $html);
        $this->assertStringContainsString('Sekretaris Melly', $html);
        $this->assertStringContainsString('/RT04/', $html);
        $this->assertStringNotContainsString('Akun Login Ketua', $html);
        $this->assertStringNotContainsString('RT 01', $html);
        $this->assertStringNotContainsString('Sejahtera', $html);
    }

    public function test_pdf_and_docx_exports_use_the_same_official_identity(): void
    {
        Storage::fake('local');
        $this->seed(LetterSeeder::class);
        $warga = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $warga->id]);
        $warga->update(['warga_id' => $citizen->id]);
        $id = $this->approvedLetterId($warga->refresh());

        $pdf = $this->actingAs($warga)->get("/api/v1/letters/{$id}/download?format=pdf")->assertOk()->getContent();
        $this->assertStringContainsString('RUKUN TETANGGA 04 / RUKUN WARGA 09', $pdf);
        $this->assertStringContainsString('Raden Erry Adu Sundaru', $pdf);
        $this->assertStringNotContainsString('RT 01', $pdf);

        $docx = $this->actingAs($warga)->get("/api/v1/letters/{$id}/download?format=docx")->assertOk()->getContent();
        $tmp = tempnam(sys_get_temp_dir(), 'dx');
        file_put_contents($tmp, $docx);
        $zip = new \ZipArchive;
        $zip->open($tmp);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        unlink($tmp);

        $this->assertStringContainsString('KELURAHAN KEMAYORAN', $xml);
        $this->assertStringContainsString('RUKUN TETANGGA 04 / RUKUN WARGA 09', $xml);
        $this->assertStringContainsString('Raden Erry Adu Sundaru', $xml);
        $this->assertStringContainsString('+62 878-7200-4448', $xml);
    }

    public function test_finance_report_is_signed_by_ketua_and_bendahara_of_rt04(): void
    {
        Storage::fake('local');
        $bendahara = User::factory()->bendahara()->create();
        Citizen::factory()->create();
        FinanceTransaction::query()->create([
            'created_by' => $bendahara->id, 'category' => 'Iuran', 'type' => 'income', 'amount' => 1000, 'amount_idr' => 1000,
            'description' => 'x', 'transaction_date' => now(), 'status' => 'published', 'version' => 1,
        ]);

        $monthly = $this->actingAs($bendahara)->get('/api/v1/admin/finance/reports/monthly?year='.now()->year.'&month='.now()->month.'&format=html')->assertOk()->getContent();
        $dues = $this->actingAs($bendahara)->get('/api/v1/admin/finance/reports/citizen-dues?year='.now()->year.'&month='.now()->month)->assertOk()->getContent();

        foreach ([$monthly, $dues] as $html) {
            $this->assertStringNotContainsString('RT 01', $html);
        }
        $this->assertStringContainsString('( Raden Erry Adu Sundaru )', $monthly);
        $this->assertStringContainsString('( Retno Fellyanti )', $monthly);
        $this->assertStringContainsString('Bendahara RT 04', $monthly);
        $this->assertStringContainsString('RT 04 / RW 09', $dues);
    }

    public function test_security_report_document_also_uses_official_letterhead(): void
    {
        $html = view('documents.templates.laporan-keamanan', ['kop_title' => 'x'])->render();

        $this->assertStringContainsString('Kelurahan Kemayoran', $html);
        $this->assertStringContainsString('Rukun Tetangga 04 / Rukun Warga 09', $html);
    }
}
