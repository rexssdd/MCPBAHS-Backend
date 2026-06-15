<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnouncementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * CNS-01 fix: the original stub returned only ['uuid']. All announcement
     * fields are now included so API consumers receive a complete response.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
{
    return [
        'uuid' => $this->uuid,

        'title' => $this->title,
        'message' => $this->message,

        'urgency' => $this->urgency?->value,
        'status' => $this->status?->value,

        // CNS-07 fix: field was incorrectly named 'mode_of_dissemination' and called
        // ->value on it as if it were a single enum, but the model stores a JSON
        // array cast as 'dissemination_modes'. This always returned null before.
        'dissemination_modes' => $this->dissemination_modes,
        'target_audience' => $this->target_audience?->value,


        'scheduled_at' => $this->scheduled_at,
        'posted_at' => $this->posted_at,

        'created_by' => $this->whenLoaded('creator', function () {
            return [
                'uuid' => $this->creator?->uuid,
                'name' => $this->creator?->name,
            ];
        }),

        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
    ];
}
}