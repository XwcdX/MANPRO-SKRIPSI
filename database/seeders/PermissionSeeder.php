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
            'administrate',
            'offer-topics',
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
            'set-availability',
        ]);

        $seniorSupervisorRole->syncPermissions([
            'view-students',
            'edit-student-status',
            'set-availability',
            'offer-topics',
        ]);

        $headDivisionRole->syncPermissions([
            'view-students',
            'edit-student-status',
            'approve-thesis-title',
            'set-availability',
            'offer-topics',
        ]);

        $headThesisRole->syncPermissions([
            'view-students',
            'edit-student-status',
            'approve-thesis-title',
            'set-availability',
            'offer-topics',
            'administrate',
        ]);

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