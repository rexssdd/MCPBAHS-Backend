<?php

namespace App\Http\Requests\Announcements;

use App\Enums\Announcements\AnnouncementStatus;
use App\Enums\Announcements\AnnouncementUrgency;
use App\Enums\Announcements\DisseminationMode;
use App\Enums\Announcements\TargetAudience;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnnouncementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * FIX: editing announcements is now Principal-only, matching
     * StoreAnnouncementRequest. Admin can still view/delete via the
     * role:admin|principal route group in routes/api.php, but cannot
     * modify an announcement's content or publish state.
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
        $announcement = $this->route('announcement');

        // CNS-03 fix: $announcement->status is cast to an AnnouncementStatus enum
        // by the model. The original code compared the enum instance against an
        // array of ->value strings, which always evaluated to false (never locked).
        // Compare enum to enum cases instead.
        $locked = in_array($announcement->status, [
            AnnouncementStatus::Posted,
            AnnouncementStatus::Processing,
            AnnouncementStatus::Cancelled,
        ], true);

        if ($locked) {
            return [
                'message' => ['prohibited']
            ];
        }

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'message' => ['sometimes', 'string'],

            'urgency' => ['sometimes', Rule::in(AnnouncementUrgency::values())],

            'target_audience' => ['sometimes', Rule::in(TargetAudience::values())],

            'dissemination_modes' => ['sometimes', 'array'],
            'dissemination_modes.*' => [Rule::in(DisseminationMode::values())],

            'scheduled_at' => ['sometimes', 'nullable', 'date'],

            // CNS-FE-03 fix: publish_mode was not in the allowed rules so Laravel
            // stripped it from validated() — AnnouncementService::update() never
            // received it and could never trigger DispatchAnnouncementAction.
            'publish_mode' => ['sometimes', Rule::in(['draft', 'now', 'schedule'])],
        ];
    }
}