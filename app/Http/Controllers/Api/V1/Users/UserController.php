<?php

namespace App\Http\Controllers\Api\V1\Users;

use App\Filters\UserFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{

    public function __construct(protected UserService $service)
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, UserFilter $filter)
    {
        $users = $filter->apply(
            User::query(),
            $request->all()
        )
        ->latest()
        ->paginate(10);

        return UserResource::collection($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $user = $this->service->create($request->validated());

        return response()->json([
            'message' => 'User created successfully',
            'data' => new UserResource($user->fresh()),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return new UserResource($user->load('personnel'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->service->update($user, $request->validated());
        return new UserResource($user->load('personnel'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $this->service->delete($user);
        return response()->noContent();
    }
}
