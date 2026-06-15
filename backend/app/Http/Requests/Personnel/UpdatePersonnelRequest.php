<?php

namespace App\Http\Requests\Personnel;

use App\Enums\Personnel\EmploymentStatus;
use App\Enums\Personnel\PersonnelPosition;
use App\Enums\Sex;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdatePersonnelRequest extends FormRequest
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
        $personnel = $this->route('personnel');

        return [
            'first_name' => ['sometimes'],
            'middle_name' => ['nullable'],
            'last_name' => ['sometimes'],

            'email' => ['sometimes', 'email', Rule::unique('personnels')->ignore($personnel->id)],
            'phone_number' => ['sometimes', Rule::unique('personnels')->ignore($personnel->id)],

            'date_of_birth' => ['sometimes', 'date'],

            'sex' => ['sometimes', new Enum(Sex::class)],
            'position' => ['sometimes', new Enum(PersonnelPosition::class)],
            'employment_status' => ['sometimes', new Enum(EmploymentStatus::class)],
        ];
    }
}
