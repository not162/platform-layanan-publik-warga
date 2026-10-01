<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CitizenRegistered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('admin-channel'),
        ];
    }

    /**
     * Data untuk event
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'message' => "Warga baru ({$this->user->name}) telah mendaftar dan terverifikasi.",
            'time' => now()->toDateTimeString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'citizen.registered';
    }
}
