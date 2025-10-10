<?php

namespace App\Http\Resources\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'profile_photo_path' => $this->profile_photo_path,
            'type' => $this->type,
            'pub_id' => $this->pub_id,
            'current_team_id' => $this->current_team_id,
            'created_at' => $this->created_at,

            'roles' => UserRoleResource::collection($this->roles),
            'permissions' => PermissionResource::collection($this->getAllPermissions()),
        ];
    }
}
