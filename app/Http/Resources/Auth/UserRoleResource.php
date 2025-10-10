<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Role */
class UserRoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->created_at,
        ];
    }
}
