<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserRepository
{
    public function __construct()
    {
    }

    /**
     * find by id.
     */
    public function get(): Builder
    {
        $columns = [
            'users.id',
            'users.email',
            'users.type',
            'users.updated_at',
            'pub_lists.id as pub_id',
        ];

        return User::select($columns)
            ->leftJoin('pubs', 'pubs.id', '=', 'users.pub_id')
            ->leftJoin('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
            ->leftJoin('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->selectRaw('users.name AS user_name')
            ->selectRaw('pub_lists.name AS vendors')
            ->selectRaw('roles.name AS role_name');
    }

    public function show(): array
    {
        return User::pluck('name', 'id')->toArray();
    }
}
