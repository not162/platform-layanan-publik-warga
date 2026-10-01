<?php

namespace Tests\Feature;

use App\Jobs\SendWebPushNotificationJob;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\FinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_register_push_subscription(): void
    {
        $payload = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/guest-endpoint-123',
            'public_key' => 'BEl62iUYgUivxIkv69yViEuiBIa-Ib9-SkvSoP119Nsn',
            'auth_token' => '5xZb7BwXWlKz3X',
            'content_encoding' => 'aes128gcm',
        ];

        $response = $this->postJson('/api/v1/push/subscribe', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.endpoint_hash', hash('sha256', $payload['endpoint']));

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint_hash' => hash('sha256', $payload['endpoint']),
            'user_id' => null,
        ]);
    }

    public function test_authenticated_citizen_can_register_push_subscription(): void
    {
        $user = User::factory()->warga()->create();

        $payload = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/warga-endpoint-456',
            'public_key' => 'BP81vXzQ',
            'auth_token' => 'auth-token-warga',
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/push/subscribe', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint_hash' => hash('sha256', $payload['endpoint']),
            'user_id' => $user->id,
        ]);
    }

    public function test_duplicate_endpoint_updates_existing_record(): void
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/unique-endpoint';

        $this->postJson('/api/v1/push/subscribe', [
            'endpoint' => $endpoint,
            'public_key' => 'key-1',
            'auth_token' => 'auth-1',
        ])->assertStatus(201);

        $this->postJson('/api/v1/push/subscribe', [
            'endpoint' => $endpoint,
            'public_key' => 'key-updated',
            'auth_token' => 'auth-updated',
        ])->assertStatus(201);

        $this->assertEquals(1, PushSubscription::query()->where('endpoint_hash', hash('sha256', $endpoint))->count());
        $this->assertEquals('key-updated', PushSubscription::query()->where('endpoint_hash', hash('sha256', $endpoint))->value('public_key'));
    }

    public function test_web_push_notification_job_executes_successfully(): void
    {
        Queue::fake();

        SendWebPushNotificationJob::dispatch('Surat Disetujui', 'Surat pengantar Anda telah disetujui ketua RT.', '/dashboard', 1);

        Queue::assertPushed(SendWebPushNotificationJob::class, function ($job) {
            return $job->title === 'Surat Disetujui' && $job->userId === 1;
        });
    }

    public function test_finance_cache_invalidation_upon_new_published_transaction(): void
    {
        Cache::flush();

        /** @var FinanceService $service */
        $service = app(FinanceService::class);

        // Pre-warm cache
        $initialSummary = $service->getPublicSummary();
        $this->assertEquals(0, $initialSummary['net_balance']);

        // Create & publish transaction
        $admin = User::factory()->admin(['finance.manage'])->create();
        $draft = $service->create([
            'type' => 'income',
            'category' => 'Iuran Warga',
            'amount' => 1000000,
            'transaction_date' => now()->toDateString(),
        ], $admin);

        $service->publish($draft, 1);

        // Query again, fresh cached summary must reflect 1,000,000
        $updatedSummary = $service->getPublicSummary();
        $this->assertEquals(1000000, $updatedSummary['net_balance']);
    }
}
