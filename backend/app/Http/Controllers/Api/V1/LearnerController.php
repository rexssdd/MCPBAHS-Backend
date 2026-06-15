<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Learners\EnrollmentStatus;
use App\Enums\Learners\LearnerType;
use App\Http\Controllers\Controller;
use App\Models\Learner;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LearnerController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Learner::query()
                ->latest()
                ->get()
        ]);
    }
    public function store(Request $request)
    {
        $learner = Learner::create($this->payload($request));

        return response()->json(['data' => $learner], 201);
    }

    public function show(Learner $learner)
    {
        return ['data' => $learner];
    }

    public function update(Request $request, Learner $learner)
    {
        $learner->forceFill($this->payload($request, $learner))->save();

        return ['data' => $learner->fresh()];
    }

    public function destroy(Learner $learner)
    {
        $learner->delete();

        return response()->noContent();
    }

    private function payload(Request $request, ?Learner $existing = null): array
    {
        return [
            'school_year' => $request->input('school_year', $existing?->school_year ?? $this->schoolYear()),
            'grade_to_enroll' => $request->input('grade_to_enroll', $existing?->grade_to_enroll ?? 'Grade 7'),
            'learner_type' => $request->input('learner_type', $existing?->learner_type?->value ?? LearnerType::UpcomingGrade7->value),
            'enrollment_status' => $request->input('enrollment_status', $existing?->enrollment_status?->value ?? EnrollmentStatus::Pending->value),
            'remarks' => $request->input('remarks', $existing?->remarks),
            'has_lrn' => (bool) $request->input('has_lrn', $existing?->has_lrn ?? false),
            'lrn' => $request->input('lrn', $existing?->lrn),
            'last_name' => $request->input('last_name', $existing?->last_name ?? 'Pending'),
            'first_name' => $request->input('first_name', $existing?->first_name ?? 'Learner'),
            'middle_name' => $request->input('middle_name', $existing?->middle_name),
            'name_extension' => $request->input('name_extension', $existing?->name_extension),
            'birth_date' => $request->input('birth_date', $existing?->birth_date ?? '2010-01-01'),
            'sex' => $request->input('sex', $existing?->sex?->value ?? 'male'),
            'age' => (int) $request->input('age', $existing?->age ?? 12),
            'mother_tongue' => $request->input('mother_tongue', $existing?->mother_tongue),
            'religion' => $request->input('religion', $existing?->religion),
            'place_of_birth' => $request->input('place_of_birth', $existing?->place_of_birth ?? 'N/A'),
            'is_ip' => (bool) $request->input('is_ip', $existing?->is_ip ?? false),
            'ip_specification' => $request->input('ip_specification', $existing?->ip_specification),
            'is_4ps' => (bool) $request->input('is_4ps', $existing?->is_4ps ?? false),
            'household_id_number' => $request->input('household_id_number', $existing?->household_id_number),
            'is_pwd' => (bool) $request->input('is_pwd', $existing?->is_pwd ?? false),
            'pwd_specification' => $request->input('pwd_specification', $existing?->pwd_specification),
            'house_no_street' => $request->input('house_no_street', $existing?->house_no_street ?? 'N/A'),
            'street_name' => $request->input('street_name', $existing?->street_name ?? 'N/A'),
            'barangay' => $request->input('barangay', $existing?->barangay ?? 'N/A'),
            'municipality' => $request->input('municipality', $existing?->municipality ?? 'N/A'),
            'province' => $request->input('province', $existing?->province ?? 'N/A'),
            'country' => $request->input('country', $existing?->country ?? 'Philippines'),
            'zip_code' => $request->input('zip_code', $existing?->zip_code ?? '0000'),
            'father_last_name' => $request->input('father_last_name', $existing?->father_last_name),
            'father_first_name' => $request->input('father_first_name', $existing?->father_first_name),
            'father_middle_name' => $request->input('father_middle_name', $existing?->father_middle_name),
            'father_name_extension' => $request->input('father_name_extension', $existing?->father_name_extension),
            'mother_last_name' => $request->input('mother_last_name', $existing?->mother_last_name),
            'mother_first_name' => $request->input('mother_first_name', $existing?->mother_first_name),
            'mother_middle_name' => $request->input('mother_middle_name', $existing?->mother_middle_name),
            'mother_name_extension' => $request->input('mother_name_extension', $existing?->mother_name_extension),
            'contact_number' => $request->input('contact_number', $existing?->contact_number ?? 'N/A'),
            'last_grade_completed' => $request->input('last_grade_completed', $existing?->last_grade_completed ?? 'Grade 6'),
            'previous_school_name' => $request->input('previous_school_name', $existing?->previous_school_name),
            'previous_school_address' => $request->input('previous_school_address', $existing?->previous_school_address),
            'date_transferred' => $request->input('date_transferred', $existing?->date_transferred),
            'shs_academic_track' => $request->input('shs_academic_track', $existing?->shs_academic_track),
            'shs_strand' => $request->input('shs_strand', $existing?->shs_strand),
            'academic_track' => $request->input('academic_track', $existing?->academic_track),
            'academic_strand' => $request->input('academic_strand', $existing?->academic_strand),
            'image_usage_consent' => (bool) $request->input('image_usage_consent', $existing?->image_usage_consent ?? true),
            'data_privacy_consent' => (bool) $request->input('data_privacy_consent', $existing?->data_privacy_consent ?? true),
            'consented_at' => $request->input('consented_at', $existing?->consented_at ?? Carbon::now()),
        ];
    }

    private function schoolYear(): string
    {
        $year = (int) now()->year;

        return "{$year}-" . ($year + 1);
    }
}
