<?php

namespace App\Http\Requests\Reports;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ApproveReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * RDCS-04 fix: duplicated here as a defence-in-depth measure so that
     * authorization is enforced even if the route middleware is misconfigured.
     */
    public function authorize(): bool
{
    return $this->user()?->hasAnyRole([
        'admin',
        'principal',
    ]) ?? false;
}

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}