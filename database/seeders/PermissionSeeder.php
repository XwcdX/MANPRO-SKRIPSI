<?php

namespace Database\Seeders;

use App\Models\Lecturer;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guardName = 'lecturer';

        $permissions = [
            // Student management
            'view-students', 'edit-student-status', 'assign-supervisors', 'view-student-details',
            
            // Thesis management
            'approve-thesis-title', 'decline-thesis-title', 'schedule-presentation', 'evaluate-thesis', 'add-revision-notes',
            
            // Schedule management
            'manage-schedules', 'set-availability', 'view-all-schedules',
            
            // Division management (for heads)
            'manage-division', 'assign-examiners', 'view-division-reports',
            
            // System administration
            'manage-lecturers', 'view-system-reports', 'manage-venues',

            // RBAC - The most powerful permission
            'manage-roles',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => $guardName]);
        }
        $this->command->info('Lecturer permissions created or verified successfully.');

        $headThesisRole = Role::firstOrCreate(['name' => 'Head of Thesis Department', 'guard_name' => $guardName]);
        $headDivisionRole = Role::firstOrCreate(['name' => 'Head of Division', 'guard_name' => $guardName]);
        $seniorSupervisorRole = Role::firstOrCreate(['name' => 'Senior Supervisor', 'guard_name' => $guardName]);
        $supervisorRole = Role::firstOrCreate(['name' => 'Supervisor', 'guard_name' => $guardName]);

        $this->command->info('Core roles created or verified successfully.');

        $supervisorRole->syncPermissions([
            'view-students',
            'view-student-details',
            'add-revision-notes',
            'set-availability',
            'view-all-schedules',
        ]);

        $seniorSupervisorRole->syncPermissions([
            'view-students',
            'edit-student-status',
            'view-student-details',
            'add-revision-notes',
            'set-availability',
            'view-all-schedules',
            'evaluate-thesis',
        ]);

        $headDivisionRole->syncPermissions([
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

        $headThesisRole->givePermissionTo(Permission::where('guard_name', $guardName)->get());

        $this->command->info('Permissions have been assigned to roles.');

        $adminLecturer = Lecturer::firstOrCreate(
            ['email' => 'john@peter.petra.ac.id'],
            [
                'name' => 'Dr. John Peter',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        
        $adminLecturer->assignRole($headThesisRole);

        $this->command->info('Super Admin user "Dr. John Peter" has been created and assigned the "Head of Thesis Department" role.');
        $this->command->info('Admin credentials: john@peter.petra.ac.id / password');
    }
}