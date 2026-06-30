<?php

use App\Http\Controllers\Api\V1\AnnouncementController;
use App\Http\Controllers\Api\V1\AppCompatController;
use App\Http\Controllers\Api\V1\ClassScheduleController;
use App\Http\Controllers\Api\V1\LearnerController;
use App\Http\Controllers\Api\V1\PersonnelController;
use App\Http\Controllers\Api\V1\PrincipalDashboardController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SectionController;
use App\Http\Controllers\Api\V1\Users\UserController;
use App\Http\Controllers\Api\V1\Users\UserInvitationController;
use App\Http\Controllers\Api\V1\Users\UserPasswordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware('signed')->prefix('v1')->group(function () {
    // Invitation routes
    Route::get('invitations/{user:uuid}/accept', [UserInvitationController::class, 'accept'])->name('user.invitation.accept');
    Route::post('invitations/{user:uuid}/complete', [UserInvitationController::class, 'complete'])->name('user.invitation.complete');
});

Route::get('/school/dashboard', [AppCompatController::class, 'schoolDashboard']);

// Public landing-page data — intentionally unauthenticated, and intentionally
// narrower than their auth:sanctum counterparts (facultyIndex/announcements
// resource route): no contact info, no draft/scheduled/internal-audience
// announcements. See AppCompatController::facultyPublic / announcementsPublic.
Route::prefix('v1')->controller(AppCompatController::class)->group(function () {
    Route::get('public/faculty', 'facultyPublic');
    Route::get('public/announcements', 'announcementsPublic');
});

Route::prefix('v1')->controller(AppCompatController::class)->group(function () {
    Route::get('health', 'health');

    // Public enrollment form endpoints — GET/POST/PUT are intentionally unauthenticated
    // so parents/guardians can submit and retrieve their own forms without an account.
    Route::post('enrollment/{type}', 'publicEnrollmentStore')
        ->whereIn('type', ['grade7', 'recurring', 'transferee']);
    Route::get('enrollment/{type}/{key}', 'publicEnrollmentShow')
        ->whereIn('type', ['grade7', 'recurring', 'transferee']);
    Route::put('enrollment/{type}/{key}', 'publicEnrollmentUpdate')
        ->whereIn('type', ['grade7', 'recurring', 'transferee']);
    // FIX: DELETE was unauthenticated — any anonymous request could delete a
    // learner record by UUID. Moved to the auth:sanctum group below.
});

// CNS-05 fix: GET /announcements was a public unauthenticated route, exposing all
// school announcements (including urgency, target audience, and scheduled content)
// to anyone without a login. Removed from the public group — the authenticated
// apiResource route below handles GET /announcements for logged-in users.

Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::get('/user', fn (Request $request) =>  $request->user());

    Route::prefix('v1')->group(function () {

        // api resource routes contains index, store, show, update, destroy methods for each resource
        // unless with soft deletes, then it will also contain archive, restore, (force delete?) methods

        // Principal dashboard routes used by the React dashboard service.
        // Keep report/dashboard special routes above apiResource('reports') so
        // static paths such as reports/deped-forms are not captured as IDs.
        Route::controller(PrincipalDashboardController::class)->group(function () {
            Route::get('dashboard/stats', 'stats');
            Route::get('dashboard/grade-data', 'gradeData');
            Route::get('dashboard/enrollment-table', 'enrollmentTable');
            Route::get('dashboard/application-status', 'applicationStatus');
            Route::get('dashboard/attendance', 'attendance');
            Route::get('dashboard/at-risk', 'atRisk');
            Route::get('dashboard/strands', 'strands');
            Route::get('dashboard/transferees', 'transferees');
            Route::get('dashboard/executive-summary', 'executiveSummary');
            Route::get('dashboard/school-health', 'schoolHealth');
            Route::get('dashboard/sip-progress', 'sipProgress');
            Route::get('teachers', 'teachers');
            Route::get('finance/fee-collection', 'feeCollection');
            Route::get('events', 'events');
            // ROUTE-FIX: GET /notifications was handled here, returning the old
            // {msg, type, time} shape from PrincipalDashboardController::notifications().
            // That registration shadowed AppCompatController::notifications() (registered
            // later in the same prefix group), which returns the correct full
            // {id, type, read, message, time, group, detail} shape the frontend expects.
            // Moved to the AppCompatController block below so it reaches the right handler.
            Route::get('enrollment/recent-activity', 'recentActivity');
            Route::get('reports/deped-forms', 'depedReports');
            Route::get('reports/quarterly-summary', 'quarterlySummary');
            Route::get('reports/staff-performance', 'staffPerformance');
        });

        Route::controller(AppCompatController::class)->group(function () {
            // Current-user profile endpoints shared by Admin/Principal/Registrar plus teacher aliases.
            Route::get('profile', 'profile');
            Route::put('profile', 'updateProfile');
            Route::post('profile/change-password', 'changePassword');
            Route::get('teacher/profile', 'profile');
            Route::put('teacher/profile', 'updateProfile');
            Route::post('teacher/profile/change-password', 'changePassword');

            // FIX: enrollment DELETE moved here from public group — requires authentication.
            Route::delete('enrollment/{type}/{key}', 'publicEnrollmentDestroy')
                ->whereIn('type', ['grade7', 'recurring', 'transferee']);

            // Notification actions.
            // GET /notifications is now owned here — PrincipalDashboardController used
            // to shadow this route with its old {msg,type,time} shape.
            Route::get('notifications', 'notifications');
            Route::get('notifications/unread-count', 'notificationsUnreadCount');
            Route::patch('notifications/read-all', 'markAllNotificationsRead');
            Route::post('notifications/mark-all-read', 'markAllNotificationsRead');
            Route::patch('notifications/{id}/read', 'markNotificationRead');
            Route::delete('notifications/{id}', 'deleteNotification');
            Route::delete('notifications', 'clearNotifications');
            Route::get('teacher/notifications', 'notifications');
            Route::patch('teacher/notifications/{id}/read', 'markNotificationRead');
            Route::post('teacher/notifications/mark-all-read', 'markAllNotificationsRead');
            Route::delete('teacher/notifications/{id}', 'deleteNotification');
            Route::delete('teacher/notifications', 'clearNotifications');

            // Frontend aliases for existing domain resources.
            Route::get('faculty', 'facultyIndex');
            Route::post('faculty', 'facultyStore');
            Route::get('faculty/{personnel:uuid}', 'facultyShow');
            Route::put('faculty/{personnel:uuid}', 'facultyUpdate');
            Route::patch('faculty/{personnel:uuid}/archive', 'facultyArchive');
            Route::delete('faculty/{personnel:uuid}', 'facultyDestroy');
            // RPMS (Results-Based Performance Management System) endpoints
            Route::get('faculty/{personnel:uuid}/rpms', 'facultyRpms');
            Route::post('faculty/{personnel:uuid}/rpms/generate', 'facultyRpmsGenerate');


            Route::get('enrollees', 'enrolleesIndex');
            Route::post('enrollees', 'enrolleesStore');
            Route::get('enrollees/{learner:uuid}', 'enrolleesShow');
            Route::put('enrollees/{learner:uuid}', 'enrolleesUpdate');
            Route::patch('enrollees/{learner:uuid}/approve', 'enrolleesApprove');
            Route::patch('enrollees/{learner:uuid}/reject', 'enrolleesReject');
            Route::patch('enrollees/{learner:uuid}/archive', 'enrolleesArchive');
            Route::delete('enrollees/{learner:uuid}', 'enrolleesDestroy');
            Route::post('enrollees/bulk-approve', 'enrolleesBulkApprove');
            Route::post('enrollees/bulk-reject', 'enrolleesBulkReject');
            Route::post('enrollees/bulk-archive', 'enrolleesBulkArchive');

            Route::get('registrar/enrollees', 'enrolleesIndex');
            Route::post('registrar/enrollees', 'enrolleesStore');
            Route::get('registrar/enrollees/{learner:uuid}', 'enrolleesShow');
            Route::put('registrar/enrollees/{learner:uuid}', 'enrolleesUpdate');
            Route::patch('registrar/enrollees/{learner:uuid}/approve', 'enrolleesApprove');
            Route::patch('registrar/enrollees/{learner:uuid}/reject', 'enrolleesReject');
            Route::patch('registrar/enrollees/{learner:uuid}/archive', 'enrolleesArchive');
            Route::post('registrar/enrollees/bulk-archive', 'enrolleesBulkArchive');

            Route::get('schedules', 'schedulesIndex');
            Route::post('schedules', 'schedulesStore');
            Route::put('schedules/{classSchedule:uuid}', 'schedulesUpdate');
            Route::patch('schedules/{classSchedule:uuid}/archive', 'schedulesArchive');
            Route::delete('schedules/{classSchedule:uuid}', 'schedulesDestroy');
            Route::patch('sections/{section:uuid}/archive', 'archiveSection');
            Route::get('teacher/sections', 'teacherSections');
            Route::get('teacher/schedules', 'teacherSchedules');

            // Registrar dashboard compatibility.
            Route::get('registrar/dashboard/stats', 'registrarDashboardStats');
            Route::get('registrar/enrollment/by-grade', 'registrarEnrollmentByGrade');
            Route::get('registrar/enrollment/application-stats', 'registrarApplicationStats');
            Route::get('registrar/enrollment/pending', 'registrarPending');
            Route::get('registrar/enrollment/recently-processed', 'registrarRecentlyProcessed');
            Route::patch('registrar/enrollment/{learner:uuid}/process', 'registrarProcess');
            Route::get('registrar/documents/tracker', 'documentTracker');
            Route::get('registrar/documents/missing', 'missingDocuments');
            Route::get('registrar/documents/stats', 'documentStats');
            Route::post('registrar/documents/send-reminders', 'sendDocumentReminders');
            Route::get('registrar/records/section-capacity', 'sectionCapacity');
            Route::get('registrar/records/enrollment-breakdown', 'enrollmentBreakdown');
            Route::get('registrar/records/transferees', 'registrarTransferees');
            Route::get('registrar/schedule/events', 'calendarEvents');
            Route::get('registrar/schedule/compliance', 'complianceChecklist');
            Route::get('registrar/notifications', 'notifications');

            // User-management extras and lightweight audit log endpoint.
            Route::post('users/bulk-delete', 'usersBulkDelete');
            Route::patch('users/{user:uuid}/archive', 'archiveUser');
            Route::post('users/{user:uuid}/reset-password', 'resetPassword');
            Route::get('users/{user:uuid}/activity-logs', 'userActivityLogs');
            Route::post('activity-logs', 'logActivity');

            // Teacher dashboard assistant endpoints.
            Route::post('teacher/dashboard/{tool}', 'dashboardAi')
                ->whereIn('tool', [
                    'enrollment-insights',
                    'priority-recommendations',
                    'document-reminder',
                    'compliance-action-plan',
                    'daily-briefing',
                ]);
        });

        // User routes — admin only
        Route::middleware('role:admin')->group(function () {
            Route::apiResource('users', UserController::class)->whereUuid('user');
            Route::post('users/{user:uuid}/request-password-otp', [UserPasswordController::class, 'requestPasswordOtp']);
            Route::post('users/{user:uuid}/update-password', [UserPasswordController::class, 'updatePassword']);
        });

        // Personnel routes — admin and principal
        // FIX: static routes BEFORE apiResource() — wildcard {personnel} would
        // otherwise capture /personnels/archived before reaching this handler.
        Route::middleware('role:admin|principal')->group(function () {
            Route::controller(PersonnelController::class)->group(function () {
                Route::get('personnels/archived', 'archived');
                Route::patch('personnels/{personnel:uuid}/restore', 'restore')->withTrashed();
                Route::delete('personnels/{personnel:uuid}/force', 'forceDelete')->withTrashed();
                Route::post('personnels/{personnel:uuid}/assign-user', 'assignUser');
                Route::apiResource('personnels', PersonnelController::class)->whereUuid('personnel');
            });
        });


        // Class Schedule routes
        Route::controller(ClassScheduleController::class)->group(function () {
            Route::apiResource('class-schedules', ClassScheduleController::class)->whereUuid('class_schedule');
        });


        // Section routes
        // FIX: static routes BEFORE apiResource().
        Route::controller(SectionController::class)->group(function () {
            Route::get('sections/archived', 'archived');
            Route::patch('sections/{section:uuid}/restore', 'restore')->withTrashed();
            Route::delete('sections/{section:uuid}/force', 'forceDelete')->withTrashed();
            Route::apiResource('sections', SectionController::class)->whereUuid('section');
        });


        // Announcement routes — admin and principal
        // FIX: static routes BEFORE apiResource().
        Route::middleware('role:admin|principal')->group(function () {
            Route::controller(AnnouncementController::class)->group(function () {
                Route::get('announcements/archived', 'archived');
                Route::patch('announcements/{announcement:uuid}/restore', 'restore')->withTrashed();
                Route::delete('announcements/{announcement:uuid}/force', 'forceDelete')->withTrashed();
                // CNS-BULK fix: bulk delete must be declared before apiResource() so the
                // static path /announcements/bulk is not captured by the {announcement} wildcard.
                Route::delete('announcements/bulk', 'bulkDestroy');
                Route::apiResource('announcements', AnnouncementController::class)->whereUuid('announcement');
            });
        });

        // Report routes — principal can approve/reject; admin and registrar can manage
        // FIX: static paths (bulk-delete, mine) BEFORE apiResource so they are not
        // swallowed by the {report} UUID wildcard registered by apiResource().
        Route::post('reports/bulk-delete', [AppCompatController::class, 'reportsBulkDelete']);
        Route::controller(ReportController::class)->group(function () {
            // GET /reports/mine — teacher sees only their own submissions.
            // Must be declared before apiResource() to prevent the {report}
            // wildcard capturing "mine" as a UUID and returning a 404.
            // RDCS-BE-02 fix: restrict to teacher role so other roles cannot call
            // a semantically meaningless endpoint for them.
            Route::middleware('role:teacher')->get('reports/mine', 'mine');
            // Static paths must be declared before apiResource to avoid UUID capture
            Route::middleware('role:admin|principal')->get('reports/archived', 'archived');
            Route::apiResource('reports', ReportController::class)->whereUuid('report');
            Route::middleware('role:admin|principal')->group(function () {
                Route::patch('reports/{report:uuid}/approve', 'approve');
                Route::patch('reports/{report:uuid}/reject', 'reject');
                Route::patch('reports/{report:uuid}/archive', 'archive');
                Route::patch('reports/{report:uuid}/unarchive', 'unarchive');
            });
            // RDCS-BE-01 fix: download route was missing — every frontend download
            // request returned 404. Accessible to any authenticated user so that
            // teachers can download their own reports and admins/principals can
            // download any report for review.
            Route::get('reports/{report:uuid}/download', 'download');
        });


        // Learner routes
        Route::controller(LearnerController::class)->group(function () {

            Route::apiResource('learners', LearnerController::class)->whereUuid('learner');
        });
    });
});

require __DIR__ . '/auth.php';