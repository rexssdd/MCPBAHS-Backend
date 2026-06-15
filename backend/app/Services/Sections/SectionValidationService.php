<?php

namespace App\Services\Sections;

use App\Enums\Sections\GradeLevel;
use App\Models\Section;

use Illuminate\Validation\ValidationException;

class SectionValidationService
{
    public function validate(
        array $data,
        ?Section $ignoreSection = null
    ): void {

        $gradeLevel = GradeLevel::from(
            $data['grade_level']
        );

        $track = $data['academic_track'] ?? null;
        $strand = $data['academic_strand'] ?? null;

        // only senior high school levels may have tracks or strands

        if (
            !$gradeLevel->isSeniorHigh()
            && ($track || $strand)
        ) {
            throw ValidationException::withMessages([
                'academic_track' =>
                    'Only Senior High School levels may have tracks or strands.',
            ]);
        }

        // strand requires track validation

        if ($strand && !$track) {
            throw ValidationException::withMessages([
                'academic_track' =>
                    'Academic track is required when strand is provided.',
            ]);
        }

        // adviser check validation

        if (isset($data['adviser_id'])) {

            $query = Section::query()
                ->where('school_year', $data['school_year'])
                ->where('adviser_id', $data['adviser_id']);

            if ($ignoreSection) {
                $query->where('id', '!=', $ignoreSection->id);
            }

            if ($query->exists()) {
                throw ValidationException::withMessages([
                    'adviser_id' =>
                        'This adviser is already assigned to another section for the same school year.',
                ]);
            }
        }
    }
}
