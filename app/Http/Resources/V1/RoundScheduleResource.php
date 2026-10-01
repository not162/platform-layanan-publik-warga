<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoundScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day_of_week' => $this->day_of_week,
            'shift_name' => $this->shift_name,
            'officer_names' => $this->officer_names,
            'pos_location' => $this->pos_location,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
        ];
    }
}
