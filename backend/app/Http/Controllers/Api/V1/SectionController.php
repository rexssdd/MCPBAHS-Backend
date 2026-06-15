<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\SectionFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Section\StoreSectionRequest;
use App\Http\Requests\Section\UpdateSectionRequest;
use App\Http\Resources\SectionResource;
use App\Models\Learner;
use App\Models\Personnel;
use App\Models\Section;
use App\Services\Sections\SectionService;
use Illuminate\Http\Request;

class SectionController extends Controller
{

    public function __construct(protected SectionService $service)
    {
        //
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, SectionFilter $filter)
    {
        $sections = $filter->apply(
            Section::with('adviser'),
            $request->all()
        )->paginate();

        return SectionResource::collection($sections);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSectionRequest $request)
    {
        $section = $this->service->create(
            $request->validated()
        );

        return response()->json([
            'message' => 'Section created successfully.',
            'data' => new SectionResource(
                $section->load('adviser')
            ),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Section $section)
    {
        return new SectionResource(
            $section->load([
                'adviser',
                'learners',
                'classSchedules.teacher',
            ])
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSectionRequest $request, Section $section)
    {
        $section = $this->service->update(
            $section,
            $request->validated()
        );

        return response()->json([
            'message' => 'Section updated successfully.',
            'data' => new SectionResource(
                $section->load('adviser')
            ),
        ]);
    }

    public function destroy(Section $section): \Illuminate\Http\Response
    {
        $this->service->delete($section);

        return response()->noContent();
    }

    // archive for later
    public function archived(Request $request, SectionFilter $filter)
    {
        $sections = $filter->apply(
            Section::onlyTrashed()->with('adviser'),
            $request->all()
        )->paginate();

        return SectionResource::collection($sections);
    }

    public function restore(Section $section)
    {
        $this->service->restore($section);

        return response()->noContent();
    }

    public function forceDelete(Section $section)
    {
        $this->service->forceDelete($section);

        return response()->noContent();
    }

    private function attributes(Request $request, ?Section $existing = null): array
    {
        $grade = $request->input('grade_level', $request->input('gradeLevel', $existing?->grade_level ?? 'Grade 7'));

        return [
            'section_name' => $request->input('section_name', $request->input('sectionName', $existing?->section_name ?? 'Section')),
            'grade_level' => str_contains(strtolower((string) $grade), 'grade') ? $grade : "Grade {$grade}",
            'school_year' => $request->input('school_year', $request->input('schoolYear', $existing?->school_year ?? $this->schoolYear())),
            'academic_track' => $request->input('academic_track', $existing?->academic_track),
            'academic_strand' => $request->input('academic_strand', $existing?->academic_strand),
            'adviser_id' => $this->findAdviserId($request->input('adviser')) ?? $existing?->adviser_id,
        ];
    }

    private function payload(Section $section): array
    {
        return [
            'id' => $section->uuid,
            'uuid' => $section->uuid,
            'gradeLevel' => (string) ($this->gradeNumber($section->grade_level) ?? $section->grade_level),
            'sectionName' => $section->section_name,
            'adviser' => $section->adviser?->full_name,
            'students' => Learner::query()->where('section_assignment_id', $section->id)->count(),
            'section_name' => $section->section_name,
            'grade_level' => $section->grade_level,
            'school_year' => $section->school_year,
        ];
    }

    private function findAdviserId(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        // FIX: was ->get()->first(fn...) which loaded every personnel record into
        // memory to match a full name in PHP. Use SQL CONCAT instead — same fix
        // already applied to AppCompatController::findPersonnelByName().
        return Personnel::query()
            ->whereRaw("TRIM(CONCAT(first_name, ' ', last_name)) = ?", [trim($name)])
            ->orWhereRaw("TRIM(CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name)) = ?", [trim($name)])
            ->value('id');
    }

    private function gradeNumber(?string $grade): ?int
    {
        preg_match('/\d+/', (string) $grade, $matches);

        return $matches ? (int) $matches[0] : null;
    }

    private function schoolYear(): string
    {
        $year = (int) now()->year;

        return "{$year}-" . ($year + 1);
    }
}