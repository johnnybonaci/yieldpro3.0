<?php

namespace App\Http\Controllers\Api\Roles;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use App\Http\Resources\Auth\RoleResource;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Leads\SearchRequest;
use Illuminate\Contracts\Support\Responsable;
use App\Http\Resources\Auth\PermissionResource;

class RoleController extends Controller
{
    public function index(): Responsable
    {
        return RoleResource::collection(Role::query()->with('permissions')->get());
    }

    public function indexApi(SearchRequest $request): Responsable
    {
        $search = $request->search();

        $roles = Role::query()->when($search, function (Builder $query, string $search): Builder {
            return $query->where(
                DB::raw('LOWER(roles.name)'),
                'like',
                '%' . strtolower($search) . '%'
            );
        })->with('permissions')->paginate(
            perPage: $request->perPage(),
            page: $request->page()
        );

        return RoleResource::collection($roles);
    }

    public function show(int $id): RoleResource
    {
        return RoleResource::make(Role::query()->with('permissions')->findOrFail($id));
    }

    public function store(Request $request): Responsable
    {
        $request->validate([
            'name' => 'required|min:3|max:20|alpha_dash|unique:roles,name',
            'permissions' => 'present|array',
            'permissions.*' => 'string',
        ]);

        $permissions = $request->collect('permissions');

        $newRole = Role::create(['guard_name' => 'web', 'name' => $request->input('name')]);

        $newRole->givePermissionTo($permissions);

        return RoleResource::make($newRole->load('permissions'));
    }

    public function update(int $id, Request $request): Responsable
    {
        $request->validate([
            'name' => 'required|min:3|max:20|alpha_dash|unique:roles,name,' . $id,
            'permissions' => 'present|array',
            'permissions.*' => 'string',
        ]);

        $role = Role::findOrFail($id);

        $role->update([
            'name' => $request->input('name'),
        ]);

        $role->syncPermissions($request->collect('permissions'));

        return RoleResource::make($role->load('permissions'));
    }

    public function permissions(): mixed
    {
        return PermissionResource::collection(Permission::all());
    }
}
