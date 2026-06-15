<?php

namespace App\Http\Requests\Reports;

use App\Enums\Reports\ReportType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Restricted to the same roles that can submit reports. Without this,
     * any authenticated user (students, parents) could PATCH any report record.
     * Mirrors StoreReportRequest for consistency — both gates should always
     * allow the same roles.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['teacher', 'registrar', 'admin']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'form_type' => [
                'sometimes',
                'string',
                Rule::in(ReportType::values()),
            ],

            'school_year' => [
                'sometimes',
                'string',
                'max:20',
            ],

            'remarks' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],

            'file' => [
                'sometimes',
                'file',
                'mimes:pdf,xlsx,xls,csv,docx,doc',
                'max:10240',
            ],
        ];
    }
}