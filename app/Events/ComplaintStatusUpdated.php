<?php

namespace App\Events;

use App\Models\Complaint;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComplaintStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Complaint $complaint,
        public string $status
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin-channel'),
        ];

        if (! $this->complaint->is_anonymous && $this->complaint->user_id) {
            $channels[] = new PrivateChannel("users.{$this->complaint->user_id}");
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->complaint->id,
            'ticket_number' => $this->complaint->ticket_number,
            'status' => $this->status,
            'admin_response' => $this->complaint->admin_response,
            'version' => $this->complaint->version,
            'updated_at' => $this->complaint->updated_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'complaint.status.updated';
    }
}
