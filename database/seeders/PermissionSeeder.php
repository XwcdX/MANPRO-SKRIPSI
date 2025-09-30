<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for lecturer guard
        $permissions = [
            // Student management
            'view-students',
            'edit-student-status',
            'assign-supervisors',
            'view-student-details',
            
            // Thesis management
            'approve-thesis-title',
            'decline-thesis-title',
            'schedule-presentation',
            'evaluate-thesis',
            'add-revision-notes',
            
            // Schedule management
            'manage-schedules',
            'set-availability',
            'view-all-schedules',
            
            // Division management (for heads)
            'manage-division',
            'assign-examiners',
            'view-division-reports',
            
            // System administration
            'manage-lecturers',
            'view-system-reports',
            'manage-venues',
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => 'lecturer'
            ]);
        }

        // Create roles based on lecturer titles
        $supervisorRole = Role::create([
            'name' => 'supervisor',
            'guard_name' => 'lecturer'
        ]);
        
        $seniorSupervisorRole = Role::create([
            'name' => 'senior-supervisor',
            'guard_name' => 'lecturer'
        ]);
        
        $headDivisionRole = Role::create([
            'name' => 'head-division',
            'guard_name' => 'lecturer'
        ]);
        
        $headThesisRole = Role::create([
            'name' => 'head-thesis',
            'guard_name' => 'lecturer'
        ]);

        // Assign permissions to roles
        $supervisorRole->givePermissionTo([
            'view-students',
            'view-student-details',
            'add-revision-notes',
            'set-availability',
            'view-all-schedules',
        ]);

        $seniorSupervisorRole->givePermissionTo([
            'view-students',
            'edit-student-status',
            'view-student-details',
            'add-revision-notes',
            'set-availability',
            'view-all-schedules',
            'evaluate-thesis',
        ]);

        $headDivisionRole->givePermissionTo([
            'view-students',
            'edit-student-status',
            'assign-supervisors',
            'view-student-details',
            'approve-thesis-title',
            'decline-thesis-title',
            'schedule-presentation',
            'evaluate-thesis',
            'add-revision-notes',
            'manage-schedules',
            'set-availability',
            'view-all-schedules',
            'manage-division',
            'assign-examiners',
            'view-division-reports',
        ]);

        $headThesisRole->givePermissionTo(Permission::where('guard_name', 'lecturer')->pluck('name'));
    }
}