<?php

namespace App\Http\Requests\Reports;

use App\Enums\Reports\ReportType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    /**
     * Only teachers and registrars may submit reports.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['teacher', 'registrar', 'admin']) ?? false;
    }

    /**
     * Accepted file types:
     *   pdf   — standard DepEd document format
     *   xlsx  — Excel (OpenXML)
     *   xls   — Excel legacy
     *   csv   — spreadsheet / data export
     *   docx  — Word (OpenXML)
     *   doc   — Word legacy
     *
     * FIX: xlsx/xls/csv were listed in the frontend dropzone hint and the
     * frontend `validateFileList` helper but NOT accepted by this backend
     * validator, causing an "invalid file type" 422 for any spreadsheet upload.
     * Both sides now agree on the allowed set.
     */
    public function rules(): array
    {
        return [
            'form_type' => [
                'required',
                'string',
                Rule::in(ReportType::values()),
            ],

            'school_year' => [
                'required',
                'string',
                'max:20',
            ],

            'file' => [
                'required',
                'file',
                'mimes:pdf,xlsx,xls,csv,docx,doc',
                'max:10240', // 10 MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'The uploaded file must be a PDF, Excel (xlsx/xls), CSV, or Word document (docx/doc).',
        ];
    }
}