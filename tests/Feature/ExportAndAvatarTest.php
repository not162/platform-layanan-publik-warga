<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\FinanceTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportAndAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_csv_export_has_bom_headers_filters_and_blocks_formula_injection(): void
    {
        $bendahara = User::factory()->bendahara()->create();
        FinanceTransaction::query()->create([
            'transaction_number' => 'TXN-1', 'type' => 'income', 'source' => 'other', 'category' => '=HYPERLINK("http://x")', 'amount' => 1000, 'amount_idr' => 1000,
            'transaction_date' => '2026-10-01', 'status' => 'published', 'created_by' => $bendahara->id, 'version' => 1,
        ]);
        FinanceTransaction::query()->create([
            'transaction_number' => 'TXN-2', 'type' => 'expense', 'source' => 'other', 'category' => 'Kebersihan', 'amount' => 500, 'amount_idr' => 500,
            'transaction_date' => '2026-10-02', 'status' => 'draft', 'created_by' => $bendahara->id, 'version' => 1,
        ]);

        $csv = $this->actingAs($bendahara)->get('/api/v1/manage/finance/export?status=published')->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('transaction_number', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString('TXN-2', $csv);
        $this->assertStringNotContainsString('can_edit', $csv);
        $this->assertDatabaseHas('audit_logs', ['action' => 'finance.exported']);
    }

    public function test_export_respects_role_permissions_and_masks_sensitive_columns(): void
    {
        $warga = User::factory()->warga()->create();
        $sekretaris = User::factory()->sekretaris()->create();
        Citizen::factory()->create(['nik' => '3171041203900001', 'nik_hash' => hash('sha256', '3171041203900001'), 'full_name' => 'Budi Santoso']);

        $this->actingAs($warga)->get('/api/v1/manage/finance/export')->assertForbidden();
        $this->actingAs($warga)->get('/api/v1/manage/users/export')->assertForbidden();
        $this->actingAs($warga)->get('/api/v1/manage/audit-logs/export')->assertForbidden();

        $csv = $this->actingAs($sekretaris)->get('/api/v1/manage/citizens/export')->assertOk()->streamedContent();
        $this->assertStringContainsString('Budi Santoso', $csv);
        $this->assertStringContainsString('3171********0001', $csv);
        $this->assertStringNotContainsString('3171041203900001', $csv);
    }

    public function test_profile_photo_upload_replace_serve_and_remove(): void
    {
        Storage::fake('local');
        $user = User::factory()->warga()->create();

        $this->actingAs($user)->postJson('/api/v1/me/photo', ['photo' => UploadedFile::fake()->create('a.pdf', 10)])->assertStatus(422);

        $res = $this->actingAs($user)->postJson('/api/v1/me/photo', ['photo' => UploadedFile::fake()->image('a.jpg', 200, 200)])->assertOk();
        $first = $user->refresh()->avatar_url;
        Storage::disk('local')->assertExists($first);
        $this->assertStringContainsString('/api/v1/files/avatar/'.$user->id, $res->json('data.avatar'));

        $this->actingAs($user)->postJson('/api/v1/me/photo', ['photo' => UploadedFile::fake()->image('b.png', 200, 200)])->assertOk();
        Storage::disk('local')->assertMissing($first);

        $this->actingAs(User::factory()->sekretaris()->create())->get("/api/v1/files/avatar/{$user->id}")->assertOk();
        $this->actingAs($user)->get('/dashboard')->assertSee("/api/v1/files/avatar/{$user->id}", false);

        $this->actingAs($user)->deleteJson('/api/v1/me/photo')->assertOk();
        $this->assertNull($user->refresh()->avatar_url);
    }
}
