<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'event_date' => optional($this->event_date)->format('Y-m-d'),
            'category' => $this->category,
            'is_published' => $this->is_published,
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator?->name),
        ];
    }
}
