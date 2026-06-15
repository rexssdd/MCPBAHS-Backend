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
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Stream a report file download from whichever disk is configured
     * (local in dev, S3/R2 in production).
     *
     * Uses Storage::download() which works for both local and cloud disks —
     * no hard-coded paths, no BinaryFileResponse (which only works with
     * local filesystem paths and would break on S3).
     */
    public function download(Report $report): StreamedResponse
    {
        /** @var User $user */
        $user = Auth::user();

        abort_unless($user->can('download', $report), 403);
        abort_unless($report->file_path, 404, 'File path missing.');
        abort_unless(Storage::exists($report->file_path), 404, 'File not found.');

        return Storage::download(
            $report->file_path,
            $report->original_filename ?? basename($report->file_path)
        );
    }
}
