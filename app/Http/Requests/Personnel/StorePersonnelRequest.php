<?php

namespace App\Http\Requests\Personnel;

use App\Enums\Personnel\EmploymentStatus;
use App\Enums\Personnel\PersonnelDepartment;
use App\Enums\Personnel\PersonnelPosition;
use App\Enums\Sex;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePersonnelRequest extends FormRequest
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
            'personnel_id_number' => ['required', 'integer', 'unique:personnels'],

            'first_name' => ['required'],
            'middle_name' => ['nullable'],
            'last_name' => ['required'],

            'email' => ['required', 'email', 'unique:personnels'],
            'phone_number' => ['required', 'unique:personnels'],

            'date_of_birth' => ['required', 'date'],

            'sex' => ['required', new Enum(Sex::class)],

            'country' => ['required'],
            'region' => ['required'],
            'province' => ['required'],
            'brgy_street_address' => ['required'],
            'city' => ['required'],
            'postal_code' => ['required'],

            'teaching_load' => ['required', 'integer'],

            'position' => ['required', new Enum(PersonnelPosition::class)],
            'department' => ['required', new Enum(PersonnelDepartment::class)],

            'employment_status' => ['sometimes', new Enum(EmploymentStatus::class)],
        ];
    }
}
