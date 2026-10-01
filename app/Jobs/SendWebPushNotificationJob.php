<?php

namespace App\Jobs;

use App\Models\PushSubscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendWebPushNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $title,
        public string $body,
        public ?string $targetUrl = null,
        public ?int $userId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $query = PushSubscription::query();

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        $subscriptions = $query->get();

        Log::info('Dispatching WebPush notification', [
            'title' => $this->title,
            'body' => $this->body,
            'targetUrl' => $this->targetUrl,
            'recipients_count' => $subscriptions->count(),
        ]);

        // In production, minishlink/web-push or similar driver sends payloads to each endpoint.
        // We log and iterate through valid subscriptions.
        foreach ($subscriptions as $subscription) {
            // Simulated delivery log
            Log::debug('Push delivered to endpoint', [
                'endpoint_hash' => $subscription->endpoint_hash,
            ]);
        }
    }
}
