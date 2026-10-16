<?php

namespace App\Events;

use App\Models\DuePayment;
use App\Models\ResidentDue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DuePaymentRecorded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public DuePayment $payment,
        public ResidentDue $due
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin-channel'),
        ];

        if ($this->due->citizen_id) {
            $channels[] = new PrivateChannel("citizens.{$this->due->citizen_id}");
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'payment_id' => $this->payment->id,
            'resident_due_id' => $this->due->id,
            'citizen_id' => $this->due->citizen_id,
            'amount_paid_idr' => $this->payment->amount_paid_idr,
            'status' => $this->due->status,
            'paid_at' => $this->payment->created_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'due.payment.recorded';
    }
}
