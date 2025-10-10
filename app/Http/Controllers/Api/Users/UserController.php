<?php

namespace App\Http\Controllers\Api\Users;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use App\Http\Resources\Auth\UserResource;
use Illuminate\Contracts\Pagination\Paginator;

class UserController extends Controller
{
    public function __construct(
        protected UserRepository $user_repository,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Paginator
    {
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);
        $rows = $this->user_repository->get();

        return $rows->filterFields()->sortsFields('id')->paginate($size, ['*'], 'page', $page);
    }

    public function authenticated(): UserResource
    {
        return UserResource::make(auth()->user()->load(['roles']));
    }

    public function getUserById($userId)
    {
        $authenticatedUser = auth('sanctum')->user();

        if (!$authenticatedUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($authenticatedUser->id != $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::with(['roles'])->find($userId);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return UserResource::make($user);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
    }
}
