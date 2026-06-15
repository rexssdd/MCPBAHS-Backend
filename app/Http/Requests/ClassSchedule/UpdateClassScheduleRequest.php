<?php

namespace App\Http\Requests\ClassSchedule;

use App\Enums\WeekDay;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassScheduleRequest extends FormRequest
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
            'room' => ['sometimes', 'string'],

            'subject' => ['sometimes', 'string'],

            'school_year' => ['sometimes', 'string'],

            'semester' => ['sometimes', Rule::in(['1st', '2nd'])],

            'days' => ['sometimes', 'array'],
            'days.*' => ['sometimes', Rule::in(WeekDay::values())],

            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],

            'section_id' => ['sometimes', 'exists:sections,id'],
            'teacher_id' => ['sometimes', 'exists:personnels,id'],
        ];
    }
}
