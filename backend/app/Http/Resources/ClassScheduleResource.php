<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,

            'room' => $this->room,
            'subject' => $this->subject,

            'school_year' => $this->school_year,
            'semester' => $this->semester,

            'days' => $this->days,

            'start_time' => $this->start_time,
            'end_time' => $this->end_time,

            'teacher' => $this->whenLoaded('teacher', function () {
                return [
                    'uuid' => $this->teacher->uuid,
                    'name' => $this->teacher->full_name,
                ];
            }),

            'section' => $this->whenLoaded('section', function () {
                return [
                    'uuid' => $this->section->uuid,
                    'name' => $this->section->name,
                ];
            }),
        ];
    }
}
