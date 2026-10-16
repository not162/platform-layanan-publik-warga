<?php

namespace App\Events;

use App\Models\Letter;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LetterStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Letter $letter,
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

        if ($this->letter->citizen_id) {
            $channels[] = new PrivateChannel("citizens.{$this->letter->citizen_id}");
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->letter->id,
            'ticket_number' => $this->letter->ticket_number,
            'status' => $this->status,
            'type' => $this->letter->type,
            'citizen_id' => $this->letter->citizen_id,
            'letter_number' => $this->letter->letter_number,
            'version' => $this->letter->version,
            'updated_at' => $this->letter->updated_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'letter.status.updated';
    }
}
