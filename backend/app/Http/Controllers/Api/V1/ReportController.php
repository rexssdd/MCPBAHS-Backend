<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ApproveReportRequest;
use App\Http\Requests\Reports\RejectReportRequest;
use App\Http\Requests\Reports\StoreReportRequest;
use App\Http\Requests\Reports\UpdateReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Models\User;
use App\Services\ReportService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $service
    ) {
    }

    public function index()
    {
        return ReportResource::collection(
            $this->service->index()
        );
    }

    public function store(StoreReportRequest $request)
    {
        /** @var User $user */
        $user = Auth::user();

        return response()->json(
            new ReportResource(
                $this->service->store(
                    $request->validated(),
                    $request->file('file'),
                    $user
                )
            ),
            201
        );
    }

    public function show(Report $report)
    {
        return new ReportResource(
            $this->service->show(
                $report->load('submitter', 'reviewer')
            )
        );
    }

    public function update(UpdateReportRequest $request, Report $report)
    {
        return new ReportResource(
            $this->service->update(
                $report,
                $request->validated(),
                $request->file('file')
            )
        );
    }

    public function mine()
    {
        /** @var User $user */
        $user = Auth::user();

        return ReportResource::collection(
            Report::with('submitter', 'reviewer')
                ->where('submitted_by', $user->id)
                ->latest()
                ->paginate()
        );
    }

    public function destroy(Report $report)
    {
        $this->service->delete($report);

        return response()->noContent();
    }

    public function approve(
        ApproveReportRequest $request,
        Report $report
    ) {
        /** @var User $user */
        $user = Auth::user();

        try {
            return new ReportResource(
                $this->service->approve(
                    $report,
                    $user,
                    $request->validated('remarks')
                )
            );
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function reject(
        RejectReportRequest $request,
        Report $report
    ) {
        /** @var User $user */
        $user = Auth::user();

        try {
            return new ReportResource(
                $this->service->reject(
                    $report,
                    $user,
                    $request->validated('remarks')
                )
            );
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function download(Report $report): BinaryFileResponse
    {
        /** @var User $user */
        $user = Auth::user();

        abort_unless($user->can('download', $report), 403);

        abort_unless($report->file_path, 404, 'File path missing.');

        // FIX: Use Storage::disk('local') instead of a hardcoded storage_path('app/...')
        // prefix. In Laravel 11 the 'local' disk root is storage/app/private (not
        // storage/app), so the old prefix resolved to a path that never exists on disk,
        // causing every download to return 404. Using the Storage facade always resolves
        // relative to the configured disk root regardless of Laravel version.
        abort_unless(
            Storage::disk('local')->exists($report->file_path),
            404,
            'File not found on server.'
        );

        $fullPath = Storage::disk('local')->path($report->file_path);

        return response()->download(
            $fullPath,
            $report->original_filename ?? basename($fullPath)
        );
    }
}
