<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleUpdate extends Component
{
    public $formStep = 1;

    public $role_list;

    public $role;

    public $create_mode = false;

    #[Validate]
    public $new_role_name;

    // Permissions

    public $permission_name = [];

    public $permission_value = [];

    public $permissions_updated = [];

    public function mount()
    {
        // User role names
        $this->role_list = Role::all()->pluck('name')->toArray();
        $this->permission_name = Permission::all()->pluck('name')->toArray();
        $this->role = is_null($this->role) ? $this->role_list[0] : $this->role;
        $this->setPermissions();
    }

    public function render()
    {
        return view('backend.users.livewire.role-update-lw');
    }

    protected function rules()
    {
        return [
            'new_role_name' => 'required|min:3|max:20|alpha_dash|unique:roles,name',
        ];
    }

    public function createMode()
    {
        $this->create_mode = true;
        foreach ($this->permission_name as $id => $name) {
            $this->permission_value[$id] = 0;
        }
    }

    public function setPermissions()
    {
        $role_selected = Role::findByName($this->role, 'web');
        foreach ($this->permission_name as $id => $name) {
            $this->permission_value[$id] = $role_selected->permissions->contains('name', $name);
        }
    }

    public function update()
    {
        $role_selected = Role::findByName($this->role, 'web');
        foreach ($this->permission_value as $id => $value) {
            if ($value) {
                array_push($this->permissions_updated, $this->permission_name[$id]);
            }
        }
        $role_selected->syncPermissions($this->permissions_updated);
        $this->formStep = 2;
    }

    public function create()
    {
        $this->validate();
        $new_role = Role::create(['guard_name' => 'web', 'name' => $this->new_role_name]);
        foreach ($this->permission_value as $id => $value) {
            if ($value) {
                array_push($this->permissions_updated, $this->permission_name[$id]);
            }
        }
        $new_role->givePermissionTo($this->permissions_updated);
        $this->formStep = 2;
    }

    public function closeModal()
    {
        $this->formStep = 1;
        $this->permissions_updated = [];
    }
}
