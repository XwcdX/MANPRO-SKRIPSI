<?php

namespace App\Services;

use App\Models\Lecturer;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionService
{
    protected string $guardName = 'lecturer';

    public function getRoles(): Collection
    {
        return Role::where('guard_name', $this->guardName)->get();
    }

    public function findRole(int $roleId): Role
    {
        return Role::findById($roleId, $this->guardName);
    }

    public function createRole(array $data): Role
    {
        return Role::create([
            'name' => $data['name'],
            'guard_name' => $this->guardName
        ]);
    }

    public function updateRole(Role $role, array $data): Role
    {
        $role->update(['name' => $data['name']]);
        return $role;
    }

    public function deleteRole(Role $role): bool
    {
        return $role->delete();
    }

    public function renameRole(Role $role, string $newName): bool
    {
        return $role->update(['name' => $newName]);
    }

    public function getAllPermissions(): Collection
    {
        return Permission::where('guard_name', $this->guardName)->get();
    }

    public function syncPermissionsForRole(Role $role, array $permissionNames): Role
    {
        return $role->syncPermissions($permissionNames);
    }

    public function getLecturers(): Collection
    {
        return Lecturer::all();
    }

    public function findLecturer(int $lecturerId): Lecturer
    {
        return Lecturer::findOrFail($lecturerId);
    }

    public function syncRolesForLecturer(Lecturer $lecturer, array $roleNames): Lecturer
    {
        return $lecturer->syncRoles($roleNames);
    }

    public function getRoleNamesForLecturer(Lecturer $lecturer): \Illuminate\Support\Collection
    {
        return $lecturer->getRoleNames();
    }
}