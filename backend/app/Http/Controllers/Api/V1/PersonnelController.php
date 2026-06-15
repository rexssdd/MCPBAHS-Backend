<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\PersonnelFilter;
use App\Http\Controllers\Controller;
// use App\Http\Requests\Personnel\AssignPersonnelUserRequest;
use App\Http\Requests\Personnel\StorePersonnelRequest;
use App\Http\Requests\Personnel\UpdatePersonnelRequest;
use App\Http\Resources\PersonnelResource;
use App\Models\Personnel;
use App\Services\PersonnelService;
use Illuminate\Http\Request;

class PersonnelController extends Controller
{
    public function __construct(protected PersonnelService $service)
    {
        //
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PersonnelFilter $filter)
    {
        $personnels = $filter->apply(
            Personnel::query(),
            $request->all()
        )->paginate(10);

        return PersonnelResource::collection($personnels);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePersonnelRequest $request)
    {
        $personnel = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Personnel created successfully.',
            'data' => new PersonnelResource($personnel),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Personnel $personnel)
    {
        return new PersonnelResource($personnel);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePersonnelRequest $request, Personnel $personnel)
    {
        $personnel = $this->service->update($personnel, $request->validated());

        return response()->json([
            'message' => 'Personnel updated successfully.',
            'data' => new PersonnelResource($personnel),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Personnel $personnel)
    {
        $this->service->delete($personnel);
        return response()->noContent();
    }

    // archived personnel
    public function archived(Request $request, PersonnelFilter $filter)
    {
        $personnels = $filter->apply(
            Personnel::onlyTrashed(),
            $request->all()
        )->paginate();

        return PersonnelResource::collection($personnels);
    }

    public function restore(Personnel $personnel)
    {
        $this->service->restore($personnel);

        return response()->noContent();
    }

    public function forceDelete(Personnel $personnel)
    {
        $this->service->forceDelete($personnel);

        return response()->noContent();
    }

    // will be used later, for now it will be resolved automatically by the system
    // public function assignUser(
    //     AssignPersonnelUserRequest $request,
    //     Personnel $personnel
    // ) {
    //     return new PersonnelResource(
    //         $this->service->assignUser(
    //             $personnel,
    //             $request->user_uuid
    //         )
    //     );
    // }
}
