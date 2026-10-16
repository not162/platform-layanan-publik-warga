<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\User;
use App\Services\CitizenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class Sprint1CitizenFamilyCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_with_scope_can_list_and_create_family_cards(): void
    {
        $admin = User::factory()->admin(['citizen.read', 'citizen.manage'])->create();

        $createResponse = $this->actingAs($admin)->postJson('/api/v1/admin/family-cards', [
            'no_kk' => '3201011234560001',
            'address' => 'Jl. Mawar No. 12 RT 01 RW 02',
            'rt' => '01',
            'rw' => '02',
            'village' => 'Sukamaju',
        ]);

        $createResponse->assertStatus(Response::HTTP_CREATED)
            ->assertJsonPath('data.address', 'Jl. Mawar No. 12 RT 01 RW 02')
            ->assertJsonPath('data.rt', '01')
            ->assertJsonPath('data.version', 1);

        $this->assertDatabaseHas('family_cards', [
            'no_kk_hash' => hash('sha256', '3201011234560001'),
            'address' => 'Jl. Mawar No. 12 RT 01 RW 02',
        ]);

        // Duplicate No KK rejection
        $duplicateResponse = $this->actingAs($admin)->postJson('/api/v1/admin/family-cards', [
            'no_kk' => '3201011234560001',
            'address' => 'Jl. Mawar No. 14',
        ]);
        $duplicateResponse->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('no_kk');

        // Listing family cards
        $listResponse = $this->actingAs($admin)->getJson('/api/v1/admin/family-cards');
        $listResponse->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_warga_cannot_access_admin_family_cards_endpoints(): void
    {
        $warga = User::factory()->warga()->create();
        $familyCard = FamilyCard::factory()->create();

        $this->actingAs($warga)->getJson('/api/v1/admin/family-cards')
            ->assertStatus(Response::HTTP_FORBIDDEN);

        $this->actingAs($warga)->postJson('/api/v1/admin/family-cards', [
            'no_kk' => '3201011234560002',
            'address' => 'Jl. Melati No. 5',
        ])->assertStatus(Response::HTTP_FORBIDDEN);

        $this->actingAs($warga)->getJson("/api/v1/admin/family-cards/{$familyCard->id}")
            ->assertStatus(Response::HTTP_FORBIDDEN);

        $this->actingAs($warga)->patchJson("/api/v1/admin/family-cards/{$familyCard->id}", [
            'address' => 'Jl. Melati No. 99',
        ])->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_family_card_optimistic_locking_prevents_concurrent_overwrite(): void
    {
        $admin = User::factory()->admin(['citizen.manage', 'citizen.read'])->create();
        $card = FamilyCard::factory()->create([
            'address' => 'Alamat Awal',
            'version' => 1,
        ]);

        // Success update matching version
        $updateResponse = $this->actingAs($admin)->patchJson("/api/v1/admin/family-cards/{$card->id}", [
            'address' => 'Alamat Direvisi',
            'version' => 1,
        ]);
        $updateResponse->assertOk()
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.address', 'Alamat Direvisi');

        // Stale update with old version (1) returns 409 Conflict
        $staleResponse = $this->actingAs($admin)->patchJson("/api/v1/admin/family-cards/{$card->id}", [
            'address' => 'Alamat Stale',
            'version' => 1,
        ]);
        $staleResponse->assertStatus(Response::HTTP_CONFLICT);
    }

    public function test_citizen_service_blocks_deleting_citizen_with_history(): void
    {
        $service = app(CitizenService::class);
        $familyCard = FamilyCard::factory()->create();
        $user = User::factory()->warga()->create();

        $citizen = Citizen::factory()->create([
            'family_card_id' => $familyCard->id,
            'user_id' => $user->id,
        ]);

        $letterType = LetterType::create([
            'kode_surat' => 'SKP-S1',
            'nama_surat' => 'Surat Pengantar Sprint 1',
            'template_key' => 'pengantar_umum',
            'format_penomoran' => 'SKP/{seq}/{month}/{year}',
            'is_active' => true,
        ]);

        // Citizen has a letter
        Letter::create([
            'citizen_id' => $citizen->id,
            'jenis_surat_id' => $letterType->id,
            'type' => 'Surat Pengantar',
            'status' => 'submitted',
            'ticket_number' => 'TRK-S1-TEST',
            'keperluan' => 'Pengurusan KTP',
        ]);

        // Attempt deletion via service should throw 422 HTTP exception
        try {
            $service->delete($citizen);
            $this->fail('Expected 422 exception when deleting citizen with active letter.');
        } catch (HttpException $e) {
            $this->assertEquals(422, $e->getStatusCode());
            $this->assertStringContainsString('Tidak dapat menghapus data warga', $e->getMessage());
        }

        // The citizen must still exist in database
        $this->assertDatabaseHas('citizens', [
            'id' => $citizen->id,
        ]);
    }

    public function test_citizen_service_allows_deleting_citizen_without_history(): void
    {
        $service = app(CitizenService::class);
        $familyCard = FamilyCard::factory()->create();

        $citizen = Citizen::factory()->create([
            'family_card_id' => $familyCard->id,
        ]);

        $citizenId = $citizen->id;
        $service->delete($citizen);

        $this->assertDatabaseMissing('citizens', [
            'id' => $citizenId,
        ]);
    }

    public function test_citizen_user_single_source_of_truth_relationship(): void
    {
        $user = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create([
            'user_id' => $user->id,
        ]);

        $freshUser = User::query()->find($user->id);
        $this->assertNotNull($freshUser?->citizen);
        $this->assertEquals($citizen->id, $freshUser->citizen->id);

        $freshCitizen = Citizen::query()->find($citizen->id);
        $this->assertNotNull($freshCitizen?->user);
        $this->assertEquals($user->id, $freshCitizen->user->id);
    }
}
