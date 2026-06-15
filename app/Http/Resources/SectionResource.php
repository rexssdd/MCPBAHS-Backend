<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
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

            'section_name' => $this->section_name,

            'grade_level' => $this->grade_level,

            'school_year' => $this->school_year,

            'academic_track' => $this->academic_track,

            'academic_strand' => $this->academic_strand,

            'adviser' => $this->whenLoaded('adviser', function () {
                return [
                    'uuid' => $this->adviser->uuid,
                    'full_name' => $this->adviser->full_name,
                ];
            }),

            // only need a specific set of fields from the learners, so we will not use the LearnerResource
            // 'learners' => LearnerResource::collection(
            //     $this->whenLoaded('learners')
            // ),

            'class_schedules' => ClassScheduleResource::collection(
                $this->whenLoaded('classSchedules')
            ),
        ];
    }
}
