<?php

namespace App\Http\Requests\ClassSchedule;

use App\Enums\WeekDay;
use App\Models\Personnel;
use App\Models\Section;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassScheduleRequest extends FormRequest
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
            'room_no' => ['nullable', 'string', 'max:100'],

            'subject' => ['required', 'string', 'max:255'],

            'school_year' => ['required', 'string', 'max:20'],

            'semester' => ['nullable', Rule::in(['1st', '2nd'])],

            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['required', Rule::in(WeekDay::values())],

            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],

            'section_id'   => ['nullable', 'exists:sections,id'],
            'teacher_id'   => ['nullable', 'exists:personnels,id'],

            // UUID aliases sent by the frontend — resolved in prepareForValidation
            'section_uuid' => ['nullable', 'string'],
            'teacher_uuid' => ['nullable', 'string'],
        ];
    }

    /**
     * Resolve frontend-friendly aliases before validation runs.
     *
     * The frontend sends:
     *   - room        → mapped to room_no
     *   - section_uuid → resolved to section_id
     *   - teacher_uuid → resolved to teacher_id
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        // room alias
        if ($this->has('room') && ! $this->has('room_no')) {
            $merge['room_no'] = $this->input('room');
        }

        // Resolve section UUID → integer ID
        if ($this->filled('section_uuid') && ! $this->filled('section_id')) {
            $section = Section::where('uuid', $this->input('section_uuid'))->first();
            if ($section) {
                $merge['section_id'] = $section->id;
            }
        }

        // Resolve teacher UUID → integer ID
        if ($this->filled('teacher_uuid') && ! $this->filled('teacher_id')) {
            $personnel = Personnel::where('uuid', $this->input('teacher_uuid'))->first();
            if ($personnel) {
                $merge['teacher_id'] = $personnel->id;
            }
        }

        if ($merge) {
            $this->merge($merge);
        }
    }
}