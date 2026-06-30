<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Learners\EnrollmentStatus;
use App\Enums\Learners\LearnerType;
use App\Enums\Personnel\EmploymentStatus;
use App\Enums\Personnel\PersonnelPosition;
use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Learner;
use App\Models\Notification;
use App\Models\Personnel;
use App\Enums\Reports\ReportStatus;
use App\Models\Announcement;
use App\Models\Report;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AppCompatController extends Controller
{
    private const SECTION_CAPACITY = 40;

    public function health(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'ok' => true,
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
    public function schoolDashboard(): array
    {
        return [
            'stats' => [
                'students' => number_format(Learner::count()),
                'teachers' => number_format(
                    Personnel::query()->where('position', 'like', '%TEACHER%')->count()
                ),
                'sections' => number_format(Section::count()),
            ],
            'announcements' => [
                'count' => 0,
                'latest' => 'Updated today',
            ],
            'enrollment' => [
                'isOpen' => true,
                'label' => 'Open now',
            ],
            'schedule' => [
                'label' => ClassSchedule::exists() ? 'Updated' : 'No schedules yet',
            ],
            'reports' => [
                'label' => Report::exists() ? 'Reports available' : 'No reports yet',
            ],
        ];
    }

    public function profile(Request $request): array
    {
        return $this->profilePayload($request->user());
    }

    public function updateProfile(Request $request): array
    {
        // FIX: was saving any raw request input with no validation at all.
        // Added uniqueness check on email and length limits.
        $user = $request->user();

        $request->validate([
            'firstName'     => ['sometimes', 'string', 'max:100'],
            'lastName'      => ['sometimes', 'string', 'max:100'],
            'email'         => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'contactNumber' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $firstName = $request->input('firstName');
        $lastName  = $request->input('lastName');
        if ($firstName || $lastName) {
            $user->name = trim(($firstName ?: '') . ' ' . ($lastName ?: '')) ?: $user->name;
        }

        if ($request->filled('email')) {
            $user->email = $request->input('email');
        }

        $user->save();

        if ($user->personnel) {
            $user->personnel->forceFill(array_filter([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $request->input('email'),
                'phone_number' => $request->input('contactNumber'),
            ], fn ($value) => $value !== null))->save();
        }

        return $this->profilePayload($user->fresh('personnel'));
    }

    public function changePassword(Request $request): array
    {
        $request->validate([
            // FIX: was 'nullable' — an empty currentPassword silently bypassed
            // verification and allowed any authenticated user to take over an
            // account without knowing the existing password. Now 'required'.
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:8', 'different:currentPassword'],
        ]);

        $user = $request->user();

        // Users who accepted an invitation but have not yet set a password have
        // password = null. There is nothing to verify against — skip Hash::check.
        // This is safe because the user is already authenticated via Sanctum.
        // All users who have previously logged in with a password must pass the check.
        if ($user->password !== null && ! Hash::check($request->input('currentPassword'), $user->password)) {
            abort(422, 'Current password is incorrect.');
        }

        $user->forceFill([
            'password' => Hash::make($request->input('newPassword')),
        ])->save();

        return ['message' => 'Password changed successfully.'];
    }

    /**
     * GET /notifications
     * GET /teacher/notifications
     * GET /registrar/notifications
     *
     * Returns a merged, newest-first list of:
     *   1. All active Announcements           → type: "announcement"
     *   2. Approved / Rejected report verdicts → type: "report"
     *
     * Every item has the full {id, type, read, message, time, group, detail}
     * shape the frontend normalizers expect. The old stub returned {msg, type, time}
     * which caused normalizeApiNotification() to emit "No message" for every item
     * (it reads .message, not .msg) and normalizeNotifItem() to drop every item
     * (it also checks .message which was null).
     *
     * For the teacher role only that teacher's own report verdicts are included.
     */
    public function notifications(): array
    {
        /** @var \App\Models\User $user */
        $user  = auth()->user();
        $items = [];

        // ── 1. Announcements ─────────────────────────────────────────────────
        Announcement::query()
            ->latest()
            ->limit(50)
            ->get()
            ->each(function (Announcement $ann) use (&$items) {
                $dateStr = $ann->scheduled_at ?? $ann->created_at;

                $items[] = [
                    'id'        => 'ann-' . ($ann->uuid ?? $ann->id),
                    'type'      => 'announcement',
                    'read'      => false,
                    'message'   => mb_strimwidth($ann->message ?? $ann->title ?? 'Announcement', 0, 120, '…'),
                    'time'      => $dateStr?->toIso8601String() ?? now()->toIso8601String(),
                    'group'     => $this->notifGroup($dateStr),
                    '_sortDate' => $dateStr?->toIso8601String(),
                    'detail'    => [
                        'type'        => 'announcement',
                        'title'       => $ann->title ?? mb_strimwidth($ann->message ?? 'Announcement', 0, 80, '…'),
                        'urgency'     => $ann->urgency?->value ?? $ann->urgency ?? '—',
                        'audience'    => $ann->target_audience?->value ?? $ann->target_audience ?? 'All',
                        'scheduledOn' => $dateStr?->format('m/d/y') ?? '—',
                        'updatedOn'   => $ann->updated_at?->format('m/d/y') ?? '—',
                        'status'      => $ann->status?->value ?? $ann->status ?? 'Pending',
                        'comments'    => $ann->message ?? '—',
                    ],
                ];
            });

        // ── 2. Report verdicts (Approved / Rejected) ─────────────────────────
        $reportsQuery = Report::query()
            ->whereIn('status', [ReportStatus::Approved->value, ReportStatus::Rejected->value])
            ->with(['submitter', 'reviewer'])
            ->latest('reviewed_at')
            ->limit(50);

        // Teachers see only their own reports.
        if ($user?->hasRole('teacher')) {
            $reportsQuery->where('submitted_by', $user->id);
        }

        $reportsQuery->get()->each(function (Report $rpt) use (&$items) {
            $isApproved = $rpt->status === ReportStatus::Approved;
            $fileName   = $rpt->original_filename ?? ('SF' . ltrim($rpt->form_type?->value ?? '?', 'sf') . '.pdf');
            $dateStr    = $rpt->reviewed_at ?? $rpt->updated_at;
            $status     = $isApproved ? 'Approved' : 'Disapproved';

            $items[] = [
                'id'        => 'rpt-' . $rpt->uuid,
                'type'      => 'report',
                'read'      => false,
                'message'   => $isApproved
                    ? "Report {$fileName} has been approved"
                    : "Report {$fileName} has been disapproved",
                'time'      => $dateStr?->toIso8601String() ?? now()->toIso8601String(),
                'group'     => $this->notifGroup($dateStr),
                '_sortDate' => $dateStr?->toIso8601String(),
                'detail'    => [
                    'type'        => 'report',
                    'title'       => $isApproved ? 'Report Approved' : 'Report Disapproved',
                    'fileName'    => $fileName,
                    'submittedBy' => $rpt->submitter?->name ?? $rpt->submitter?->email ?? '—',
                    'submittedOn' => $rpt->created_at?->format('m/d/y') ?? '—',
                    'evaluatedOn' => $dateStr?->format('m/d/y') ?? '—',
                    'gradeLevel'  => '—',
                    'section'     => '—',
                    'status'      => $status,
                    'comments'    => $rpt->remarks ?? 'No comments provided.',
                ],
            ];
        });

        // ── 3. DB Notification records (targeted per-user alerts) ────────────
        // ReportService::approve() and reject() write rows to the notifications
        // table so that the teacher who submitted a report gets a direct alert.
        // These were previously created but never surfaced — included here now.
        if ($user) {
            Notification::query()
                ->where('user_id', $user->id)
                ->latest()
                ->limit(50)
                ->get()
                ->each(function (Notification $notif) use (&$items) {
                    $dateStr = $notif->created_at;

                    $items[] = [
                        'id'        => 'db-' . $notif->id,
                        'type'      => $notif->type ?? 'report',
                        'read'      => (bool) $notif->is_read,
                        'message'   => $notif->message,
                        'time'      => $dateStr?->toIso8601String() ?? now()->toIso8601String(),
                        'group'     => $this->notifGroup($dateStr),
                        '_sortDate' => $dateStr?->toIso8601String(),
                        'detail'    => [
                            'type'     => $notif->type ?? 'report',
                            'title'    => $notif->title ?? $notif->message,
                            'fileName' => '—',
                            'submittedOn' => $dateStr?->format('m/d/y') ?? '—',
                            'evaluatedOn' => $dateStr?->format('m/d/y') ?? '—',
                            'status'   => str_contains($notif->type ?? '', 'rejected')
                                ? 'Disapproved'
                                : 'Approved',
                            'comments' => $notif->message,
                        ],
                    ];
                });
        }

        // ── Sort: newest first ────────────────────────────────────────────────
        usort($items, fn ($a, $b) =>
            strcmp($b['_sortDate'] ?? '', $a['_sortDate'] ?? '')
        );

        return $items;
    }

    public function notificationsUnreadCount(): array
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $reportCount = Report::query()
            ->whereIn('status', [ReportStatus::Approved->value, ReportStatus::Rejected->value])
            ->count();

        $annCount = Announcement::query()->count();

        // Also count unread DB notification records for the current user.
        $dbUnread = $user
            ? Notification::query()->where('user_id', $user->id)->whereRaw('is_read = false')->count()
            : 0;

        return ['count' => $reportCount + $annCount + $dbUnread];
    }

    /** Map a Carbon date (or null) to Today / Yesterday / Earlier. */
    private function notifGroup($date): string
    {
        if (! $date) return 'Earlier';
        try {
            $d    = is_string($date) ? \Carbon\Carbon::parse($date) : $date;
            $diff = (int) $d->startOfDay()->diffInDays(now()->startOfDay(), false);
            if ($diff === 0) return 'Today';
            if ($diff === 1) return 'Yesterday';
            return 'Earlier';
        } catch (\Throwable) {
            return 'Earlier';
        }
    }

    /**
     * Mark a single notification as read.
     * Handles both DB-backed records (prefixed "db-N") and virtual ids
     * ("ann-*", "rpt-*") — virtual ones are stateless so a no-op 204 is correct.
     */
    public function markNotificationRead(string $id): \Illuminate\Http\Response
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($user && str_starts_with($id, 'db-')) {
            $numericId = (int) substr($id, 3);
            Notification::query()
                ->where('id', $numericId)
                ->where('user_id', $user->id)
                ->update(['is_read' => true]);
        }

        return response()->noContent();
    }

    /**
     * Mark all DB notification records for the current user as read.
     * Virtual items (announcements, report status rows) are stateless.
     */
    public function markAllNotificationsRead(): \Illuminate\Http\Response
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($user) {
            Notification::query()
                ->where('user_id', $user->id)
                ->whereRaw('is_read = false')
                ->update(['is_read' => true]);
        }

        return response()->noContent();
    }

    /**
     * Delete a single DB notification.
     * Scoped to the authenticated user so users cannot delete each other's records.
     */
    public function deleteNotification(string $id): \Illuminate\Http\Response
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($user && str_starts_with($id, 'db-')) {
            $numericId = (int) substr($id, 3);
            Notification::query()
                ->where('id', $numericId)
                ->where('user_id', $user->id)
                ->delete();
        }

        return response()->noContent();
    }

    /**
     * Delete all DB notification records for the current user.
     */
    public function clearNotifications(): \Illuminate\Http\Response
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($user) {
            Notification::query()->where('user_id', $user->id)->delete();
        }

        return response()->noContent();
    }

    public function facultyIndex(Request $request): array
    {
        // FIX: was ->get() with no limit — loaded all personnel into memory.
        // Now paginated. Frontend should consume the { data, total } envelope.
        $page  = max((int) $request->input('page', 1), 1);
        $limit = min(max((int) $request->input('limit', 20), 1), 100);

        $query = Personnel::query()->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $data  = $query->forPage($page, $limit)
            ->get()
            ->map(fn (Personnel $personnel) => $this->facultyPayload($personnel))
            ->values()
            ->all();

        return compact('data', 'total');
    }

    public function facultyShow(Personnel $personnel): array
    {
        return $this->facultyPayload($personnel);
    }

    public function facultyStore(Request $request): array
    {
        abort_unless(
            $request->user()?->hasAnyRole(['admin', 'principal']),
            403,
            'Only admins and principals may create faculty records.'
        );

        $request->validate([
            'first_name'  => ['required', 'string', 'max:100'],
            'last_name'   => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', 'max:255', 'unique:personnels,email'],
            'contact'     => ['nullable', 'string', 'max:20'],
            'role'        => ['nullable', 'string', 'max:100'],
            'status'      => ['nullable', 'string', 'max:50'],
            'dob'         => ['nullable', 'date'],
            'department'  => ['nullable', 'string', 'max:100'],
        ]);

        $payload = $this->personnelPayload($request);
        $personnel = Personnel::create($payload);

        return $this->facultyPayload($personnel);
    }

    public function facultyUpdate(Request $request, Personnel $personnel): array
    {
        abort_unless(
            $request->user()?->hasAnyRole(['admin', 'principal']),
            403,
            'Only admins and principals may update faculty records.'
        );

        $request->validate([
            'first_name'  => ['sometimes', 'string', 'max:100'],
            'last_name'   => ['sometimes', 'string', 'max:100'],
            'email'       => ['sometimes', 'email', 'max:255', 'unique:personnels,email,' . $personnel->id],
            'contact'     => ['nullable', 'string', 'max:20'],
            'role'        => ['nullable', 'string', 'max:100'],
            'status'      => ['nullable', 'string', 'max:50'],
            'dob'         => ['nullable', 'date'],
            'department'  => ['nullable', 'string', 'max:100'],
        ]);

        $personnel->forceFill($this->personnelPayload($request, $personnel))->save();

        return $this->facultyPayload($personnel->fresh());
    }

    public function facultyArchive(Personnel $personnel): \Illuminate\Http\Response
    {
        $personnel->delete();

        return response()->noContent();
    }

    public function facultyDestroy(Personnel $personnel): \Illuminate\Http\Response
    {
        $personnel->delete();

        return response()->noContent();
    }

    /**
     * POST /faculty/{personnel}/photo
     * Frontend-facing alias for PersonnelController::uploadPhoto(), so the
     * admin "Faculty and Staff" form (which talks to /faculty, not
     * /personnels) can upload a profile photo for the public homepage
     * faculty directory.
     */
    public function facultyUploadPhoto(Request $request, Personnel $personnel): array
    {
        abort_unless(
            $request->user()?->hasAnyRole(['admin', 'principal']),
            403,
            'Only admins and principals may update faculty photos.'
        );

        $request->validate([
            'photo' => ['required', 'image', 'max:4096'],
        ]);

        $disk = \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'));

        if ($personnel->photo_path && $disk->exists($personnel->photo_path)) {
            $disk->delete($personnel->photo_path);
        }

        $personnel->update([
            'photo_path' => $request->file('photo')->store('personnel-photos', config('filesystems.default')),
        ]);

        return $this->facultyPayload($personnel->refresh());
    }

    /**
     * GET /faculty/{personnel:uuid}/rpms?schoolYear=2024-2025&quarter=Q3
     * Returns the RPMS report for a faculty member. Returns empty structure if none exists yet.
     */
    public function facultyRpms(Request $request, Personnel $personnel): array
    {
        $schoolYear = $request->input('schoolYear', date('Y') . '-' . (date('Y') + 1));
        $quarter    = $request->input('quarter', 'Q1');

        // Stub: return a well-structured empty report so the UI renders without crashing.
        // Replace with actual DB lookup once the rpms table is migrated.
        return [
            'data' => [
                'facultyId'       => $personnel->uuid,
                'name'            => $personnel->full_name ?? ($personnel->first_name . ' ' . $personnel->last_name),
                'schoolYear'      => $schoolYear,
                'quarter'         => $quarter,
                'ratings'         => [],
                'finalRating'     => null,
                'adjectivalRating'=> null,
                'remarks'         => null,
                'generatedAt'     => null,
            ],
            'message' => 'RPMS report retrieved. No data yet for this period.',
        ];
    }

    /**
     * POST /faculty/{personnel:uuid}/rpms/generate
     * Body: { schoolYear: string, quarter: string }
     * Generates (or regenerates) the RPMS report for a faculty member.
     */
    public function facultyRpmsGenerate(Request $request, Personnel $personnel): array
    {
        $schoolYear = $request->input('schoolYear', date('Y') . '-' . (date('Y') + 1));
        $quarter    = $request->input('quarter', 'Q1');

        // Stub: return a generated report structure.
        // Replace with actual aggregation logic once the rpms table is migrated.
        return [
            'data' => [
                'facultyId'        => $personnel->uuid,
                'name'             => $personnel->full_name ?? ($personnel->first_name . ' ' . $personnel->last_name),
                'schoolYear'       => $schoolYear,
                'quarter'          => $quarter,
                'ratings'          => [],
                'finalRating'      => 0,
                'adjectivalRating' => 'Not yet rated',
                'remarks'          => 'Auto-generated stub. Implement aggregation logic.',
                'generatedAt'      => now()->toIso8601String(),
            ],
            'message' => 'RPMS report generated successfully.',
        ];
    }

    public function enrolleesIndex(Request $request): array
    {
        $query = Learner::query()->latest();

        if ($request->filled('status')) {
            $query->where('enrollment_status', $this->statusValue($request->input('status')));
        }

        if ($request->filled('gradeLevel')) {
            $query->where('grade_to_enroll', 'like', '%' . $request->input('gradeLevel') . '%');
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('lrn', 'like', "%{$search}%");
            });
        }

        // FIX: was ->get() — loaded every row into memory. Now paginated.
        $page  = max((int) $request->input('page', 1), 1);
        $limit = min(max((int) $request->input('limit', 20), 1), 100);

        $total = $query->count();
        $data  = $query->forPage($page, $limit)
            ->get()
            ->map(fn (Learner $learner) => $this->enrolleePayload($learner))
            ->values()
            ->all();

        return compact('data', 'total');
    }

    public function enrolleesShow(Learner $learner): array
    {
        return $this->enrolleePayload($learner);
    }

    public function enrolleesStore(Request $request): array
    {
        abort_unless(
            $request->user()?->hasAnyRole(['admin', 'registrar']),
            403,
            'Only admins and registrars may create enrollee records.'
        );

        // Accept both camelCase (frontend) and snake_case field names.
        // Merge camelCase aliases into the request before validation so that
        // 'first_name' is present whichever format the client sends.
        $request->mergeIfMissing([
            'first_name' => $request->input('firstName'),
            'last_name'  => $request->input('lastName'),
            'birth_date' => $request->input('birthDate', $request->input('dob')),
        ]);

        $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'sex' => ['nullable', 'string', 'in:Male,Female'],  // remove 'male','female'
            'lrn'        => ['nullable', 'string', 'max:20'],
        ]);

        $learner = Learner::create($this->learnerPayload($request));

        return $this->enrolleePayload($learner);
    }

    public function enrolleesUpdate(Request $request, Learner $learner): array
    {
        abort_unless(
            $request->user()?->hasAnyRole(['admin', 'registrar']),
            403,
            'Only admins and registrars may update enrollee records.'
        );

        // Accept both camelCase (frontend) and snake_case field names.
        $request->mergeIfMissing([
            'first_name' => $request->input('firstName'),
            'last_name'  => $request->input('lastName'),
            'birth_date' => $request->input('birthDate', $request->input('dob')),
        ]);

        $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name'  => ['sometimes', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'sex'        => ['nullable', 'string', 'in:Male,Female,male,female'],
            'lrn'        => ['nullable', 'string', 'max:20'],
        ]);

        $learner->forceFill($this->learnerPayload($request, $learner))->save();

        return $this->enrolleePayload($learner->fresh());
    }

    public function enrolleesApprove(Learner $learner): array
    {
        $learner->forceFill([
            'enrollment_status' => EnrollmentStatus::Enrolled->value,
            'approved_at' => now(),
        ])->save();

        return $this->enrolleePayload($learner->fresh());
    }

    public function enrolleesReject(Request $request, Learner $learner): array
    {
        $learner->forceFill([
            'enrollment_status' => EnrollmentStatus::Rejected->value,
            'remarks' => $request->input('reason', $request->input('remarks')),
            'reviewed_at' => now(),
        ])->save();

        return $this->enrolleePayload($learner->fresh());
    }

// AFTER
    public function enrolleesArchive(Learner $learner): \Illuminate\Http\Response
    {
        $learner->delete();           // soft delete — intentional, record is recoverable
        return response()->noContent();
    }

    public function enrolleesDestroy(Learner $learner): \Illuminate\Http\Response
    {
        $learner->forceDelete();      // permanent delete — removes the row entirely
        return response()->noContent();
    }
    public function enrolleesBulkArchive(Request $request): array
    {
        $ids = $request->input('ids', []);
        // FIX: was ->get()->each(delete) — loaded all records before deleting.
        // whereIn()->delete() does this in a single query.
        $count = Learner::query()->whereIn('uuid', $ids)->delete();

        return ['success' => true, 'archived' => $count];
    }

    public function enrolleesBulkApprove(Request $request): array
    {
        abort_unless(
            $request->user()?->hasAnyRole(['admin', 'registrar']),
            403,
            'Only admins and registrars may bulk-approve enrollees.'
        );

        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['string']]);

        $ids = $request->input('ids', []);
        Learner::query()
            ->whereIn('uuid', $ids)
            ->update([
                'enrollment_status' => EnrollmentStatus::Enrolled->value,
                'approved_at' => now(),
            ]);

        return ['success' => true, 'updated' => count($ids)];
    }

    public function enrolleesBulkReject(Request $request): array
    {
        abort_unless(
            $request->user()?->hasAnyRole(['admin', 'registrar']),
            403,
            'Only admins and registrars may bulk-reject enrollees.'
        );

        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['string']]);

        $ids = $request->input('ids', []);
        Learner::query()
            ->whereIn('uuid', $ids)
            ->update([
                'enrollment_status' => EnrollmentStatus::Rejected->value,
                'reviewed_at' => now(),
            ]);

        return ['success' => true, 'updated' => count($ids)];
    }

    public function publicEnrollmentStore(Request $request, string $type): array
    {
        $request->merge($this->publicEnrollmentData($request, $type));
        $learner = Learner::create($this->learnerPayload($request));

        return $this->enrolleePayload($learner);
    }

    public function publicEnrollmentShow(string $type, string $key): array
    {
        $learner = Learner::query()
            ->where('lrn', $key)
            ->orWhere('uuid', $key)
            ->firstOrFail();

        return $this->enrolleePayload($learner);
    }

    public function publicEnrollmentUpdate(Request $request, string $type, string $key): array
    {
        $learner = Learner::query()
            ->where('lrn', $key)
            ->orWhere('uuid', $key)
            ->firstOrFail();

        $request->merge($this->publicEnrollmentData($request, $type));
        $learner->forceFill($this->learnerPayload($request, $learner))->save();

        return $this->enrolleePayload($learner->fresh());
    }

    public function publicEnrollmentDestroy(string $type, string $key): \Illuminate\Http\Response
    {
        Learner::query()
            ->where('lrn', $key)
            ->orWhere('uuid', $key)
            ->firstOrFail()
            ->delete();

        return response()->noContent();
    }

    public function schedulesIndex(): array
    {
        return ClassSchedule::query()
            ->with(['section', 'teacher'])
            ->latest()
            ->get()
            ->map(fn (ClassSchedule $schedule) => $this->schedulePayload($schedule))
            ->values()
            ->all();
    }

    public function schedulesStore(Request $request): array
    {
        abort_unless(
            $request->user()?->hasAnyRole(['admin', 'principal']),
            403,
            'Only admins and principals may create schedules.'
        );

        $request->validate([
            'subject'     => ['required', 'string', 'max:255'],
            'school_year' => ['nullable', 'string', 'max:20'],
            'timeslot'    => ['nullable', 'string', 'max:100'],
            'section'     => ['nullable', 'string', 'max:100'],
            'adviser'     => ['nullable', 'string', 'max:200'],
        ]);

        $schedule = ClassSchedule::create($this->classSchedulePayload($request));

        return $this->schedulePayload($schedule->fresh(['section', 'teacher']));
    }

    public function schedulesUpdate(Request $request, ClassSchedule $classSchedule): array
    {
        abort_unless(
            $request->user()?->hasAnyRole(['admin', 'principal']),
            403,
            'Only admins and principals may update schedules.'
        );

        $request->validate([
            'subject'     => ['sometimes', 'string', 'max:255'],
            'school_year' => ['nullable', 'string', 'max:20'],
            'timeslot'    => ['nullable', 'string', 'max:100'],
            'section'     => ['nullable', 'string', 'max:100'],
            'adviser'     => ['nullable', 'string', 'max:200'],
        ]);

        $classSchedule->forceFill($this->classSchedulePayload($request, $classSchedule))->save();

        return $this->schedulePayload($classSchedule->fresh(['section', 'teacher']));
    }

    public function schedulesArchive(ClassSchedule $classSchedule): \Illuminate\Http\Response
    {
        $classSchedule->delete();

        return response()->noContent();
    }

    public function schedulesDestroy(ClassSchedule $classSchedule): \Illuminate\Http\Response
    {
        $classSchedule->delete();

        return response()->noContent();
    }

    public function archiveSection(Section $section): \Illuminate\Http\Response
    {
        $section->delete();

        return response()->noContent();
    }

    public function teacherDashboard(Request $request)
    {
        $user = $request->user();
        $personnel = $user ? Personnel::query()->where('user_id', $user->id)->first() : null;

        // Schedule — real ClassSchedule rows for this teacher, ordered by start time.
        $schedulesQuery = ClassSchedule::query()->with('section');
        if ($personnel) {
            $schedulesQuery->where('teacher_id', $personnel->id);
        }
        $schedules = $schedulesQuery->orderBy('start_time')->get();

        $todayAbbrev = now()->format('D'); // "Mon", "Tue", ...
        $todayClasses = $schedules->filter(function (ClassSchedule $s) use ($todayAbbrev) {
            $days = $s->days ?? '';
            return $days === '' || str_contains($days, $todayAbbrev);
        })->count();

        $schedulePayload = $schedules->values()->map(function (ClassSchedule $s, int $i) {
            $start = $s->start_time ? Carbon::parse($s->start_time)->format('g:i A') : '';
            $end   = $s->end_time   ? Carbon::parse($s->end_time)->format('g:i A')   : '';
            return [
                'period'  => ($i + 1) . $this->ordinalSuffix($i + 1) . ' Period',
                'time'    => trim("{$start} – {$end}", ' –'),
                'subject' => $s->subject,
                'section' => $s->section?->section_name ?? '—',
                'room'    => $s->room_no ?? '—',
            ];
        })->values()->all();

        // Sections / students this teacher actually teaches, via their schedules.
        $sectionIds = $schedules->pluck('section.id')->filter()->unique()->values();
        $totalSections = $sectionIds->count();
        $totalStudents = $totalSections > 0
            ? Learner::query()->whereIn('section_assignment_id', $sectionIds)->count()
            : 0;

        // Upcoming announcements double as calendar events (same source the
        // Principal dashboard uses for its events() endpoint).
        $calendarEvents = Announcement::query()
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', Carbon::today())
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get()
            ->map(fn (Announcement $a) => [
                'date'  => Carbon::parse($a->scheduled_at)->format('M j'),
                'type'  => $this->teacherAnnouncementType($a->urgency),
                'label' => $a->title ?? mb_strimwidth($a->message ?? 'Event', 0, 80, '…'),
            ])
            ->values()
            ->all();

        // Reports this teacher has submitted that are still awaiting a decision.
        $pendingReports = $user
            ? Report::query()
                ->where('submitted_by', $user->id)
                ->where('status', ReportStatus::ForAdminApproval->value)
                ->count()
            : 0;

        // Attendance, grades, low performers, and subject performance have no
        // backing tables in this schema yet — returned as real empty states
        // rather than fabricated numbers, so the UI shows "no data" instead of
        // silently displaying stale mock figures.
        return response()->json([
            'stats' => [
                'presentToday'  => 0,
                'totalStudents' => $totalStudents,
                'pendingGrades' => 0,
                'lowPerformers' => 0,
                'upcomingEvents' => count($calendarEvents),
                'classAverage'  => null,
            ],
            'attendance'         => [],
            'grades'             => [],
            'lowPerformers'      => [],
            'schedule'           => $schedulePayload,
            'calendarEvents'     => $calendarEvents,
            'notifications'      => [],
            'subjectPerformance' => [],
            'recentActivities'   => [],
            // Kept for any older consumers still reading the flat shape.
            'totalSections'  => $totalSections,
            'todayClasses'   => $todayClasses,
            'pendingReports' => $pendingReports,
        ]);
    }

    private function ordinalSuffix(int $n): string
    {
        if (in_array($n % 100, [11, 12, 13], true)) return 'th';
        return match ($n % 10) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th',
        };
    }

    private function teacherAnnouncementType(mixed $urgency): string
    {
        $value = is_object($urgency) && property_exists($urgency, 'value') ? $urgency->value : $urgency;
        return match ($value) {
            'high' => 'alert',
            'medium' => 'warn',
            default => 'info',
        };
    }
    public function teacherSections(Request $request): array
    {
        // FIX: load learner counts in one query, then map — avoids N+1 in sectionPayload.
        $sections = Section::query()->with('adviser')->withCount('learners')->get();

        return $sections
            ->map(fn (Section $section) => $this->sectionPayload($section))
            ->values()
            ->all();
    }

    public function teacherSchedules(Request $request): array
    {
        return $this->schedulesIndex();
    }

    public function reportsBulkDelete(Request $request): array
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return ['deleted' => 0, 'message' => 'No IDs provided.'];
        }

        // FIX: was get()+foreach — Report has no SoftDeletes so direct delete is safe.
        $count = Report::whereIn('uuid', $ids)->delete();

        return ['deleted' => $count, 'message' => "{$count} report(s) deleted."];
    }

    public function usersBulkDelete(Request $request): array
    {
        abort_unless(
            $request->user()?->hasRole('admin'),
            403,
            'Only admins may bulk-delete user accounts.'
        );

        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['string']]);

        $ids = $request->input('ids', []);
        $count = User::query()->whereIn('uuid', $ids)->delete();

        return ['success' => true, 'deleted' => $count];
    }

    public function archiveUser(User $user): \Illuminate\Http\Response
    {
        abort_unless(
            request()->user()?->hasRole('admin'),
            403,
            'Only admins may deactivate user accounts.'
        );

        $user->forceFill(['account_status' => 'deactivated'])->save();

        return response()->noContent();
    }

    public function resetPassword(Request $request, User $user): array
    {
        abort_unless(
            $request->user()?->hasRole('admin'),
            403,
            'Only admins may reset user passwords.'
        );

        $request->validate([
            'password'    => ['nullable', 'string', 'min:8'],
            'newPassword' => ['nullable', 'string', 'min:8'],
        ]);

        $provided = $request->input('password', $request->input('newPassword'));

        // FIX: when no password is supplied a random one is generated. Previously
        // it was silently discarded, leaving the admin with no way to tell the
        // user what their new credentials are. Now we return it in the response.
        $plainPassword = $provided ?: Str::random(12);
        $wasGenerated  = ! $provided;

        $user->forceFill(['password' => Hash::make($plainPassword)])->save();

        $response = ['message' => 'Password reset successfully.'];
        if ($wasGenerated) {
            $response['generatedPassword'] = $plainPassword;
            $response['notice'] = 'A temporary password was generated. Share it with the user and ask them to change it immediately.';
        }

        return $response;
    }

    public function userActivityLogs(User $user): array
    {
        return [
            'logs' => [],
            'total' => 0,
            'page' => 1,
            'totalPages' => 1,
        ];
    }

    public function logActivity(Request $request): array
    {
        // FIX: was returning $request->all() — reflected arbitrary input back to caller.
        // Activity logging is not yet persisted; acknowledge receipt only.
        return ['success' => true];
    }

    public function registrarDashboardStats(): array
    {
        $enrolled = Learner::query()->where('enrollment_status', 'enrolled')->count();
        $capacity = max(Section::count() * self::SECTION_CAPACITY, $enrolled);

        return [
            'enrolledToday' => Learner::query()
                ->whereDate('approved_at', Carbon::today())
                ->count(),
            'totalEnrolled' => $enrolled,
            'totalCapacity' => $capacity,
            'pendingReview' => Learner::query()->where('enrollment_status', 'pending')->count(),
            'missingDocs' => 0,
            'transferees' => Learner::query()->where('learner_type', LearnerType::Transferee->value)->count(),
        ];
    }

    public function registrarEnrollmentByGrade(): array
    {
        // FIX: was Learner::query()->get()->each(...) — loaded every learner into
        // memory and grouped in PHP. Replaced with a single SQL GROUP BY query.
        $counts = Learner::query()
            ->selectRaw('grade_to_enroll, enrollment_status, sex, learner_type, COUNT(*) as cnt')
            ->groupBy('grade_to_enroll', 'enrollment_status', 'sex', 'learner_type')
            ->get();

        $rows = [];
        foreach ($counts as $row) {
            $grade = $this->gradeNumber($row->grade_to_enroll) ?? 0;
            $rows[$grade] ??= [
                'grade'     => $grade,
                'enrolled'  => 0,
                'capacity'  => self::SECTION_CAPACITY,
                'male'      => 0,
                'female'    => 0,
                'new'       => 0,
                'returning' => 0,
            ];

            $cnt = (int) $row->cnt;

            if ($this->enumValue($row->enrollment_status) === EnrollmentStatus::Enrolled->value) {
                $rows[$grade]['enrolled'] += $cnt;
            }

            $sex = $this->enumValue($row->sex);
            if ($sex === 'male') {
                $rows[$grade]['male'] += $cnt;
            } elseif ($sex === 'female') {
                $rows[$grade]['female'] += $cnt;
            }

            if ($this->enumValue($row->learner_type) === LearnerType::UpcomingGrade7->value) {
                $rows[$grade]['new'] += $cnt;
            } else {
                $rows[$grade]['returning'] += $cnt;
            }
        }

        ksort($rows);

        return array_values($rows);
    }

    public function registrarApplicationStats(): array
    {
        return [
            'total' => Learner::count(),
            'approved' => Learner::query()->where('enrollment_status', 'enrolled')->count(),
            'pending' => Learner::query()->where('enrollment_status', 'pending')->count(),
            'incomplete' => Learner::query()->where('enrollment_status', 'partially enrolled')->count(),
        ];
    }

    public function registrarPending(Request $request): array
    {
        $page = max((int) $request->input('page', 1), 1);
        $limit = max((int) $request->input('limit', 10), 1);
        $query = Learner::query()->whereIn('enrollment_status', [
            EnrollmentStatus::Pending->value,
            EnrollmentStatus::PartiallyEnrolled->value,
        ]);
        $total = $query->count();
        $data = $query->forPage($page, $limit)
            ->get()
            ->map(fn (Learner $learner) => $this->enrolleePayload($learner))
            ->values()
            ->all();

        return compact('data', 'total');
    }

    public function registrarRecentlyProcessed(Request $request): array
    {
        $limit = (int) $request->input('limit', 6);

        return Learner::query()
            ->whereIn('enrollment_status', [
                EnrollmentStatus::Enrolled->value,
                EnrollmentStatus::Rejected->value,
            ])
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn (Learner $learner) => $this->enrolleePayload($learner))
            ->values()
            ->all();
    }

    public function registrarProcess(Request $request, Learner $learner): array
    {
        $action = strtolower((string) $request->input('action'));

        return str_contains($action, 'disapproved') || str_contains($action, 'reject')
            ? $this->enrolleesReject($request, $learner)
            : $this->enrolleesApprove($learner);
    }

    public function documentTracker(): array
    {
        // FIX: was returning hardcoded 100 % completion figures that had no
        // relation to reality. The learners table has no document-submission
        // columns, so we cannot report real figures yet. Returning a clearly
        // labelled "not yet tracked" response is better than false data.
        // TODO: add document_submissions table and replace this stub.
        $total = Learner::count();
        return [
            ['name' => 'Birth Certificate', 'submitted' => 0, 'pending' => $total, 'tracked' => false],
            ['name' => 'Report Card',       'submitted' => 0, 'pending' => $total, 'tracked' => false],
            ['name' => 'Good Moral',        'submitted' => 0, 'pending' => $total, 'tracked' => false],
        ];
    }

    public function missingDocuments(): array
    {
        return [];
    }

    public function documentStats(): array
    {
        // FIX: was hardcoded to 100% — same issue as documentTracker.
        // Returns honest zeros until the document_submissions table is built.
        $total = Learner::count();
        return [
            'completionRate' => 0,
            'fullyComplete'  => 0,
            'withMissing'    => 0,
            'notSubmitted'   => $total,
        ];
    }

    public function sendDocumentReminders(): array
    {
        return ['sent' => 0];
    }

    public function sectionCapacity(): array
    {
        // FIX: was running Learner::query()->...->count() inside a map() — one
        // extra query per section. Now uses withCount() so it's a single JOIN query.
        return Section::query()
            ->with('adviser')
            ->withCount('learners')
            ->get()
            ->map(fn (Section $section) => [
                'section' => $section->section_name,
                'enrolled' => $section->learners_count,
                'cap' => self::SECTION_CAPACITY,
                'adviser' => $section->adviser?->full_name,
            ])
            ->values()
            ->all();
    }

    public function enrollmentBreakdown(): array
    {
        // FIX: was 6 separate Learner::query()->count() calls. One query with
        // selectRaw + groupBy replaces all of them.
        $byType = Learner::query()
            ->selectRaw('learner_type, COUNT(*) as cnt')
            ->groupBy('learner_type')
            ->pluck('cnt', 'learner_type');

        $bySex = Learner::query()
            ->selectRaw('sex, COUNT(*) as cnt')
            ->groupBy('sex')
            ->pluck('cnt', 'sex');

        $oldStudentVal = LearnerType::OldStudent->value;
        $grade7Val     = LearnerType::UpcomingGrade7->value;
        $transfereeVal = LearnerType::Transferee->value;

        return [
            'new'        => (int) ($byType[$grade7Val]     ?? 0),
            'returning'  => (int) ($byType[$oldStudentVal] ?? 0),
            'transferees'=> (int) ($byType[$transfereeVal] ?? 0),
            'reEnrollees'=> (int) ($byType[$oldStudentVal] ?? 0),
            'male'       => (int) ($bySex['male']          ?? 0),
            'female'     => (int) ($bySex['female']        ?? 0),
        ];
    }

    public function registrarTransferees(): array
    {
        return Learner::query()
            ->where('learner_type', LearnerType::Transferee->value)
            ->latest()
            ->get()
            ->map(fn (Learner $learner) => [
                'name' => $this->learnerName($learner),
                'from' => $learner->previous_school_name,
                'grade' => $this->gradeNumber($learner->grade_to_enroll),
                'status' => $this->statusLabel($learner->enrollment_status),
            ])
            ->values()
            ->all();
    }

    public function calendarEvents(): array
    {
        return [];
    }

    public function complianceChecklist(): array
    {
        return [
            ['label' => 'Enrollment records encoded', 'done' => true, 'note' => 'Synced from learners table'],
            ['label' => 'DepEd forms submitted', 'done' => Report::query()->where('status', 'approved')->exists(), 'note' => 'Track pending reports'],
        ];
    }

    public function dashboardAi(Request $request, string $tool): array
    {
        return match ($tool) {
            'enrollment-insights' => [
                'summary' => 'Enrollment data has been received by the backend.',
                'alerts' => [],
            ],
            'priority-recommendations' => [
                'recommendation' => 'Process pending applications by submission date first.',
                'priorityIds' => [],
            ],
            'document-reminder' => [
                'text' => 'Please submit any missing enrollment documents to the registrar office as soon as possible.',
            ],
            'compliance-action-plan' => [
                'text' => 'Review pending checklist items, assign owners, and confirm completion before the deadline.',
            ],
            'daily-briefing' => [
                'text' => 'Dashboard data is synced. Review pending applications and reports for today.',
            ],
            default => ['text' => 'Request received.'],
        };
    }

    private function profilePayload(?User $user): array
    {
        $personnel = $user?->personnel;
        [$firstName, $lastName] = $this->splitName($user?->name ?? '');

        return [
            'firstName' => $personnel?->first_name ?? $firstName,
            'lastName' => $personnel?->last_name ?? $lastName,
            'email' => $user?->email,
            'role' => $this->roleForUser($user),
            'employeeId' => (string) ($personnel?->personnel_id_number ?? $user?->uuid),
            'school' => 'M.C.P.B.A.H.S',
            'department' => $personnel ? $this->humanize($this->enumValue($personnel->position)) : 'Administration',
            'contactNumber' => $personnel?->phone_number,
            'subjects' => '',
            'gradeLevel' => '',
            'isAdviser' => false,
            'advisorySection' => null,
            'lastPasswordChange' => null,
        ];
    }

    private function facultyPayload(Personnel $personnel): array
    {
        return [
            'id' => $personnel->uuid,
            'firstName' => $personnel->first_name,
            'middleName' => $personnel->middle_name,
            'lastName' => $personnel->last_name,
            'role' => $this->isTeachingPosition($personnel->position) ? 'Teacher' : 'Non-Teaching',
            'department' => $this->humanize($this->enumValue($personnel->position)),
            'city' => $personnel->city,
            'postalCode' => $personnel->postal_code,
            'country' => $personnel->country,
            'contact' => $personnel->phone_number,
            'email' => $personnel->email,
            'dob' => optional($personnel->date_of_birth)->format('m-d-Y'),
            'status' => $this->statusHuman($personnel->employment_status),
            'teachingLoad' => [],
            'advisory' => null,
            'photoUrl' => $personnel->photo_url,
        ];
    }

    private function sectionPayload(Section $section): array
    {
        // FIX: use learners_count from withCount() eager load (set by teacherSections
        // and sectionCapacity) to avoid a per-section COUNT query. Falls back to a
        // direct count only when the relation count was not pre-loaded.
        $studentCount = isset($section->learners_count)
            ? $section->learners_count
            : Learner::query()->where('section_assignment_id', $section->id)->count();

        return [
            'id' => $section->uuid,
            'gradeLevel' => (string) ($this->gradeNumber($section->grade_level) ?? $section->grade_level),
            'sectionName' => $section->section_name,
            'adviser' => $section->adviser?->full_name,
            'students' => $studentCount,
        ];
    }

    private function enrolleePayload(Learner $learner): array
    {
        return [
            'id'               => $learner->uuid,
            'learnerId'        => $learner->lrn ?? '',
            'lrn'              => $learner->lrn ?? '',
            'firstName'        => $learner->first_name ?? '',
            'middleName'       => $learner->middle_name ?? '',
            'lastName'         => $learner->last_name ?? '',
            'name'             => $this->learnerName($learner),
            'gradeLevel'       => 'Grade ' . ($this->gradeNumber($learner->grade_to_enroll) ?? $learner->grade_to_enroll),
            'grade'            => $this->gradeNumber($learner->grade_to_enroll) ?? 0,
            'email'            => '',
            'phone'            => $learner->contact_number ?? '',
            'contactNumber'    => $learner->contact_number ?? '',
            'dob'              => optional($learner->birth_date)->format('Y-m-d') ?? '',
            'country'          => $learner->country ?? '',
            'city'             => $learner->municipality ?? '',
            'postalCode'       => $learner->zip_code ?? '',
            'oldSchoolName'    => $learner->previous_school_name ?? '',
            'oldSchoolAddress' => $learner->previous_school_address ?? '',
            'oldSchoolType'    => 'Public',
            'oldSchoolId'      => '',
            'status'           => $this->statusLabel($learner->enrollment_status),
        ];
    }

    private function schedulePayload(ClassSchedule $schedule): array
    {
        $days = $schedule->days ?: 'Mon-Fri';
        $start = $schedule->start_time ? Carbon::parse($schedule->start_time)->format('g:i a') : '';
        $end = $schedule->end_time ? Carbon::parse($schedule->end_time)->format('g:i a') : '';

        return [
            'id' => $schedule->uuid,
            'subject' => $schedule->subject,
            'gradeLevel' => (string) ($this->gradeNumber($schedule->section?->grade_level) ?? ''),
            'section' => $schedule->section?->section_name,
            'adviser' => $schedule->teacher?->full_name,
            'timeslot' => trim("{$days} at {$start} - {$end}"),
        ];
    }

    private function personnelPayload(Request $request, ?Personnel $existing = null): array
    {
        $role = $request->input('role', 'Teacher');
        $status = $request->input('status', $request->input('employment_status', 'Active'));

        return [
            'personnel_id_number' => $request->input('personnel_id_number', $request->input('id', $existing?->personnel_id_number ?? $this->nextPersonnelNumber())),
            'first_name' => $request->input('first_name', $request->input('firstName', $existing?->first_name)),
            'middle_name' => $request->input('middle_name', $request->input('middleName', $existing?->middle_name)),
            'last_name' => $request->input('last_name', $request->input('lastName', $existing?->last_name)),
            'email' => $request->input('email', $existing?->email ?? 'personnel-' . Str::uuid() . '@example.test'),
            'phone_number' => $request->input('phone_number', $request->input('contact', $existing?->phone_number ?? '09' . random_int(100000000, 999999999))),
            'date_of_birth' => $this->dateValue($request->input('date_of_birth', $request->input('dob', $existing?->date_of_birth ?? '2000-01-01'))),
            'sex' => $request->input('sex', $existing ? $this->enumValue($existing->sex) : 'male'),
            'country' => $request->input('country', $existing?->country ?? 'Philippines'),
            'region' => $request->input('region', $existing?->region ?? 'Region XI'),
            'province' => $request->input('province', $existing?->province ?? 'Davao del Sur'),
            'brgy_street_address' => $request->input('brgy_street_address', $request->input('street', $existing?->brgy_street_address ?? 'N/A')),
            'city' => $request->input('city', $existing?->city ?? 'Davao City'),
            'postal_code' => $request->input('postal_code', $request->input('postalCode', $existing?->postal_code ?? '8000')),
            'teaching_load' => (int) $request->input('teaching_load', $existing?->teaching_load ?? 0),
            'position' => $request->input('position', $this->positionValue($role, $request->input('department'))),
            'employment_status' => $this->employmentStatusValue($status),
        ];
    }

    private function learnerPayload(Request $request, ?Learner $existing = null): array
    {
        $grade = $request->input('grade_to_enroll', $request->input('gradeToEnroll', $request->input('gradeLevel', $existing?->grade_to_enroll ?? 'Grade 7')));
        $learnerType = $request->input('learner_type', $request->input('learnerType', $existing ? $this->enumValue($existing->learner_type) : LearnerType::UpcomingGrade7->value));

        return [
            'school_year' => $request->input('school_year', $request->input('schoolYear', $existing?->school_year ?? $this->schoolYear())),
            'grade_to_enroll' => str_contains(strtolower((string) $grade), 'grade') ? $grade : "Grade {$grade}",
            'learner_type' => $learnerType,
            'enrollment_status' => $this->statusValue($request->input('status', $existing ? $this->enumValue($existing->enrollment_status) : EnrollmentStatus::Pending->value)),
            'remarks' => $request->input('remarks', $existing?->remarks),
            'has_lrn' => (bool) $request->input('has_lrn', $request->input('hasLrn', $request->filled('lrn') || $existing?->has_lrn)),
            'lrn' => $request->input('lrn', $request->input('learnerId', $existing?->lrn)),
            'last_name' => $request->input('last_name', $request->input('lastName', $existing?->last_name ?? 'Pending')),
            'first_name' => $request->input('first_name', $request->input('firstName', $existing?->first_name ?? 'Learner')),
            'middle_name' => $request->input('middle_name', $request->input('middleName', $existing?->middle_name)),
            'name_extension' => $request->input('name_extension', $request->input('nameExt', $existing?->name_extension)),
            'birth_date' => $this->dateValue($request->input('birth_date', $request->input('birthDate', $request->input('dob', $existing?->birth_date ?? '2010-01-01')))),
            'sex' => ucfirst(strtolower($request->input('sex', $existing ? $this->enumValue($existing->sex) : 'Male'))),
            'age' => (int) $request->input('age', $existing?->age ?? 12),
            'mother_tongue' => $request->input('mother_tongue', $request->input('motherTongue', $existing?->mother_tongue)),
            'religion' => $request->input('religion', $existing?->religion),
            'place_of_birth' => $request->input('place_of_birth', $request->input('placeOfBirth', $existing?->place_of_birth ?? 'N/A')),
            'is_ip' => $this->yesNo($request->input('is_ip', $request->input('isIP', $existing?->is_ip))),
            'ip_specification' => $request->input('ip_specification', $request->input('ipSpecify', $existing?->ip_specification)),
            'is_4ps' => $this->yesNo($request->input('is_4ps', $request->input('is4Ps', $existing?->is_4ps))),
            'household_id_number' => $request->input('household_id_number', $request->input('householdId', $existing?->household_id_number)),
            'is_pwd' => $this->yesNo($request->input('is_pwd', $request->input('isPWD', $existing?->is_pwd))),
            'pwd_specification' => $request->input('pwd_specification', $request->input('pwdSpecify', $existing?->pwd_specification)),
            'house_no_street' => $request->input('house_no_street', $request->input('houseNo', $existing?->house_no_street ?? 'N/A')),
            'street_name' => $request->input('street_name', $request->input('streetName', $existing?->street_name ?? 'N/A')),
            'barangay' => $request->input('barangay', $existing?->barangay ?? 'N/A'),
            'municipality' => $request->input('municipality', $request->input('city', $existing?->municipality ?? 'N/A')),
            'province' => $request->input('province', $existing?->province ?? 'N/A'),
            'country' => $request->input('country', $existing?->country ?? 'Philippines'),
            'zip_code' => $request->input('zip_code', $request->input('zipCode', $existing?->zip_code ?? '0000')),
            'father_last_name' => $request->input('father_last_name', $request->input('fatherLast', $existing?->father_last_name)),
            'father_first_name' => $request->input('father_first_name', $request->input('fatherFirst', $existing?->father_first_name)),
            'father_middle_name' => $request->input('father_middle_name', $request->input('fatherMiddle', $existing?->father_middle_name)),
            'father_name_extension' => $request->input('father_name_extension', $request->input('fatherExt', $existing?->father_name_extension)),
            'mother_last_name' => $request->input('mother_last_name', $request->input('motherLast', $existing?->mother_last_name)),
            'mother_first_name' => $request->input('mother_first_name', $request->input('motherFirst', $existing?->mother_first_name)),
            'mother_middle_name' => $request->input('mother_middle_name', $request->input('motherMiddle', $existing?->mother_middle_name)),
            'mother_name_extension' => $request->input('mother_name_extension', $request->input('motherExt', $existing?->mother_name_extension)),
            'contact_number' => $request->input('contact_number', $request->input('contactNumber', $request->input('phone', $existing?->contact_number ?? 'N/A'))),
            'last_grade_completed' => $request->input('last_grade_completed', $request->input('lastGradeCompleted', $existing?->last_grade_completed ?? 'Grade 6')),
            'previous_school_name' => $request->input('previous_school_name', $request->input('previousSchool', $request->input('oldSchoolName', $existing?->previous_school_name))),
            'previous_school_address' => $request->input('previous_school_address', $request->input('previousSchoolAddress', $request->input('oldSchoolAddress', $existing?->previous_school_address))),
            'date_transferred' => $this->nullableDate($request->input('date_transferred', $existing?->date_transferred)),
            'shs_academic_track' => $request->input('shs_academic_track', $existing?->shs_academic_track),
            'shs_strand' => $request->input('shs_strand', $existing?->shs_strand),
            'academic_track' => $request->input('academic_track', $existing?->academic_track),
            'academic_strand' => $request->input('academic_strand', $existing?->academic_strand),
            'image_usage_consent' => (bool) $request->input('image_usage_consent', $request->input('consentImages', $existing?->image_usage_consent ?? true)),
            'data_privacy_consent' => (bool) $request->input('data_privacy_consent', $request->input('consentData', $existing?->data_privacy_consent ?? true)),
            'consented_at' => $existing?->consented_at ?? now(),
        ];
    }

    private function classSchedulePayload(Request $request, ?ClassSchedule $existing = null): array
    {
        [$days, $start, $end] = $this->parseTimeslot($request->input('timeslot'));
        $section = $this->findSection($request->input('section'), $request->input('gradeLevel'));
        $teacher = $this->findPersonnelByName($request->input('adviser'));

        return [
            'room_no' => $request->input('room_no', $request->input('room', $existing?->room_no)),
            'subject' => $request->input('subject', $existing?->subject ?? 'Subject'),
            'school_year' => $request->input('school_year', $request->input('schoolYear', $existing?->school_year ?? $this->schoolYear())),
            'semester' => $request->input('semester', $existing?->semester),
            'days' => $request->input('days', $days ?: $existing?->days ?: 'Mon-Fri'),
            'start_time' => $request->input('start_time', $start ?: $existing?->start_time ?: '08:00:00'),
            'end_time' => $request->input('end_time', $end ?: $existing?->end_time ?: '09:00:00'),
            'section_id' => $section?->id ?? $existing?->section_id,
            'teacher_id' => $teacher?->id ?? $existing?->teacher_id,
        ];
    }

    private function publicEnrollmentData(Request $request, string $type): array
    {
        $raw = $request->input('data');
        $data = is_string($raw) ? json_decode($raw, true) : [];
        $data = is_array($data) ? $data : [];

        $data['learnerType'] = match ($type) {
            'recurring' => LearnerType::OldStudent->value,
            'transferee' => LearnerType::Transferee->value,
            default => LearnerType::UpcomingGrade7->value,
        };

        return $data;
    }

    private function findSection(?string $name, ?string $grade): ?Section
    {
        if (! $name && ! $grade) {
            return null;
        }

        return Section::query()
            ->when($name, fn ($query) => $query->where('section_name', $name))
            ->when($grade, fn ($query) => $query->where('grade_level', 'like', '%' . $grade . '%'))
            ->first();
    }

    private function findPersonnelByName(?string $name): ?Personnel
    {
        if (! $name) {
            return null;
        }

        // FIX: was Personnel::query()->get()->first(fn...) — loaded every personnel
        // record into memory to find one by full_name in PHP. Now uses SQL CONCAT.
        return Personnel::query()
            ->whereRaw("TRIM(CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name)) = ?", [trim($name)])
            ->orWhereRaw("TRIM(CONCAT(first_name, ' ', last_name)) = ?", [trim($name)])
            ->first();
    }

    private function parseTimeslot(?string $timeslot): array
    {
        if (! $timeslot || ! str_contains($timeslot, ' at ')) {
            return [null, null, null];
        }

        [$days, $timeRange] = explode(' at ', $timeslot, 2);
        [$start, $end] = array_pad(explode(' - ', $timeRange, 2), 2, null);

        return [
            $days,
            $start ? Carbon::parse($start)->format('H:i:s') : null,
            $end ? Carbon::parse($end)->format('H:i:s') : null,
        ];
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $first = array_shift($parts) ?: '';

        return [$first, implode(' ', $parts)];
    }

    private function roleForUser(?User $user): string
    {
        if (! empty($user?->role)) {
            return ucfirst((string) $user->role);
        }

        $text = strtolower(($user?->name ?? '') . ' ' . ($user?->email ?? ''));

        foreach (['principal', 'registrar', 'teacher', 'guidance', 'admin'] as $role) {
            if (str_contains($text, $role)) {
                return ucfirst($role);
            }
        }

        return 'Admin';
    }

    private function roleValueForUser(?User $user): string
    {
        return strtolower($this->roleForUser($user));
    }

    private function positionValue(string $role, ?string $department): string
    {
        if (strtolower($role) !== 'teacher') {
            return PersonnelPosition::AdministrativeAssistantI->value;
        }

        return PersonnelPosition::TeacherI->value;
    }

    private function employmentStatusValue(string $status): string
    {
        return match (strtolower(str_replace(' ', '_', $status))) {
            'on_leave' => EmploymentStatus::OnLeave->value,
            'resigned' => EmploymentStatus::Resigned->value,
            'retired' => EmploymentStatus::Retired->value,
            default => EmploymentStatus::Active->value,
        };
    }

    private function statusValue(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'enrolled', 'approved', 'active' => EnrollmentStatus::Enrolled->value,
            'rejected', 'cancelled', 'disapproved' => EnrollmentStatus::Rejected->value,
            'partial', 'partially enrolled', 'incomplete' => EnrollmentStatus::PartiallyEnrolled->value,
            default => EnrollmentStatus::Pending->value,
        };
    }

    private function statusLabel(mixed $status): string
    {
        return match ($this->enumValue($status)) {
            EnrollmentStatus::Enrolled->value => 'Enrolled',
            EnrollmentStatus::Rejected->value => 'Rejected',
            EnrollmentStatus::PartiallyEnrolled->value => 'Incomplete',
            default => 'Pending',
        };
    }

    private function statusHuman(mixed $status): string
    {
        return match ($this->enumValue($status)) {
            EmploymentStatus::OnLeave->value => 'On Leave',
            EmploymentStatus::Resigned->value => 'Resigned',
            EmploymentStatus::Retired->value => 'Retired',
            default => 'Active',
        };
    }

    private function isTeachingPosition(mixed $position): bool
    {
        return str_contains($this->enumValue($position), 'TEACHER');
    }

    private function gradeNumber(?string $grade): ?int
    {
        preg_match('/\d+/', (string) $grade, $matches);

        return $matches ? (int) $matches[0] : null;
    }

    private function learnerName(Learner $learner): string
    {
        return trim("{$learner->first_name} {$learner->middle_name} {$learner->last_name}");
    }

    private function dateValue(mixed $value): string
    {
        return Carbon::parse($value ?: '2000-01-01')->format('Y-m-d');
    }

    private function nullableDate(mixed $value): ?string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    private function yesNo(mixed $value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'y'], true);
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof \BackedEnum ? (string) $value->value : (string) $value;
    }

    private function humanize(?string $value): string
    {
        return ucwords(strtolower(str_replace(['_', '-'], ' ', (string) $value)));
    }

    private function schoolYear(): string
    {
        $year = (int) now()->year;

        return "{$year}-" . ($year + 1);
    }

    private function nextPersonnelNumber(): int
    {
        return (int) Personnel::withTrashed()->max('personnel_id_number') + 1 ?: random_int(100000, 999999);
    }
}