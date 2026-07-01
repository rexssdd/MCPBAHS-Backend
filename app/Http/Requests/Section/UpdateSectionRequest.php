<?php

namespace App\Http\Requests\Section;

use App\Enums\Sections\GradeLevel;
use App\Enums\Sections\AcademicStrand;
use App\Enums\Sections\AcademicTrack;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'section_name' => [
                'sometimes',
                'string',
                'max:100',
            ],

            'grade_level' => [
                'sometimes',
                Rule::in(GradeLevel::values()),
            ],

            'school_year' => [
                'sometimes',
                'string',
                'max:20',
            ],

            'capacity' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

            'academic_track' => [
                'sometimes',
                'nullable',
                Rule::in(AcademicTrack::values()),
            ],

            'academic_strand' => [
                'sometimes',
                'nullable',
                Rule::in(AcademicStrand::values()),
            ],

            'adviser_id' => [
                'sometimes',
                'nullable',
                'exists:personnels,id',
            ],
        ];
    }
}