<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'kategori' => $this->kategori,
            'title' => $this->title,
            'description' => $this->description,
            'lokasi' => $this->lokasi,
            'status' => $this->status,
            'priority' => $this->priority,
            'admin_response' => $this->admin_response,
            'is_anonymous' => (bool) $this->is_anonymous,
            'attachment_path' => $this->attachment_path,
            'version' => $this->version,
            'user' => $this->is_anonymous ? null : new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
