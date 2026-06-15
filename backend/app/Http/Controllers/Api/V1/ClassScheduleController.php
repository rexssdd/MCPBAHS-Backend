<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\ClassScheduleFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClassSchedule\StoreClassScheduleRequest;
use App\Http\Requests\ClassSchedule\UpdateClassScheduleRequest;
use App\Http\Resources\ClassScheduleResource;
use App\Models\ClassSchedule;
use App\Services\ClassSchedules\ClassScheduleService;
use Illuminate\Http\Request;

class ClassScheduleController extends Controller
{
    public function __construct(protected ClassScheduleService $service)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, ClassScheduleFilter $filter)
    {
        $schedules = $filter->apply(
            ClassSchedule::with(['teacher', 'section']),
            $request->all()
        )->paginate();

        return ClassScheduleResource::collection($schedules);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClassScheduleRequest $request)
    {
        $schedule = $this->service->create($request->validated());

        return new ClassScheduleResource(
            $schedule->load(['teacher', 'section'])
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(ClassSchedule $classSchedule)
    {
        return new ClassScheduleResource(
            $classSchedule->load(['teacher', 'section'])
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClassScheduleRequest $request, ClassSchedule $classSchedule)
    {
        $schedule = $this->service->update(
            $classSchedule,
            $request->validated()
        );

        return new ClassScheduleResource(
            $schedule->load(['teacher', 'section'])
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ClassSchedule $classSchedule)
    {
        $this->service->delete($classSchedule);

        return response()->noContent();
    }
}