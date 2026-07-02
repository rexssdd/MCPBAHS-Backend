<?php

namespace App\Http\Requests\Announcements;

use App\Enums\Announcements\AnnouncementUrgency;
use App\Enums\Announcements\AnnouncementCategory;
use App\Enums\Announcements\DisseminationMode;
use App\Enums\Announcements\TargetAudience;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * FIX: creating announcements is now Principal-only. Admin retains
     * access to view/delete announcements (see routes/api.php's
     * role:admin|principal group), but must not be able to author or edit
     * them — that decision belongs to the Principal.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('principal') ?? false;
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],

            'urgency' => [
                'required',
                Rule::in(AnnouncementUrgency::values())
            ],
            'category' => [
            'required',
            Rule::in(AnnouncementCategory::values())
             ],

            'target_audience' => [
                'required',
                Rule::in(TargetAudience::values())
            ],

            'dissemination_modes' => [
                'required',
                'array',
                'min:1'
            ],

            'dissemination_modes.*' => [
                Rule::in(DisseminationMode::values())
            ],

            'publish_mode' => [
                'required',
                Rule::in(['draft', 'now', 'schedule'])
            ],


            // CNS-06 fix: scheduled_at was unconditionally nullable, so an
            // announcement could be saved with status=scheduled but no date —
            // the scheduler job would never fire. Now required when publish_mode
            // is 'schedule', and must be a future date.
            'scheduled_at' => [
                Rule::requiredIf(fn () => $this->input('publish_mode') === 'schedule'),
                'nullable',
                'date',
                'after:now',
            ],
        ];
    }
}