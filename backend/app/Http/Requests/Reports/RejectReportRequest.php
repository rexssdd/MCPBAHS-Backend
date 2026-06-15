<?php

namespace App\Http\Requests\Reports;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RejectReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            // Allow nullable remarks so the frontend can disapprove
            // without being forced to enter a comment.
            // The UI encourages a comment but does not enforce it at the HTTP layer.
            'remarks' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}