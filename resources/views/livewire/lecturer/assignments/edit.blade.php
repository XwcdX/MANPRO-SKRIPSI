<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Lecturer;
use App\Services\RolePermissionService;

new #[Layout('components.layouts.lecturer')] 
class extends Component {
    public ?Lecturer $lecturer = null;
    public $allRoles = [];
    public array $assignedRoles = [];
    
    protected RolePermissionService $rolePermissionService;

    public function boot(RolePermissionService $rolePermissionService): void
    {
        $this->rolePermissionService = $rolePermissionService;
    }

    public function mount(Lecturer $lecturer): void
    {
        $this->lecturer = $lecturer;
        $this->allRoles = $this->rolePermissionService->getRoles();
        $this->assignedRoles = $this->rolePermissionService->getRoleNamesForLecturer($this->lecturer)->toArray();
    }

    public function assignRoles(): void
    {
        if ($this->lecturer) {
            $this->rolePermissionService->syncRolesForLecturer($this->lecturer, $this->assignedRoles);
            session()->flash('success', 'Roles for ' . $this->lecturer->name . ' have been updated.');
        }
    }
}; 

?>

<div>
    <section class="w-full">
        <div class="w-full max-w-5xl mx-auto bg-white dark:bg-zinc-900 rounded-lg shadow-xl border border-zinc-200 dark:border-zinc-700 p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                @if ($lecturer)
                    <div>
                        <h1 class="text-2xl sm:text-3xl text-black dark:text-white font-bold">Assign Roles</h1>
                        <p class="text-zinc-600 dark:text-zinc-400 mt-1">{{ $lecturer->name }}</p>
                    </div>
                @endif
                <button wire:click="assignRoles" type="button"
                    class="bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-700 transition-colors whitespace-nowrap">
                    Save Assignments
                </button>
            </div>
            
            <p class="text-zinc-600 dark:text-zinc-400 mb-6 text-sm">
                Select the roles that this lecturer should have. Their permissions will be automatically updated based on the roles assigned.
            </p>
            
            <hr class="border-zinc-200 dark:border-zinc-700 mb-6">

            @if (session('success'))
                <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 dark:bg-green-900 dark:border-green-700 dark:text-green-200 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="space-y-4">
                <h2 class="text-xl font-semibold text-zinc-800 dark:text-zinc-200">Available Roles</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse ($allRoles as $role)
                        <label
                            class="flex items-center p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 border border-zinc-200 dark:border-zinc-700 cursor-pointer transition-colors">
                            <input type="checkbox" wire:model.live="assignedRoles" value="{{ $role->name }}"
                                class="h-5 w-5 rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500">
                            <span class="ml-3 text-sm sm:text-base text-zinc-800 dark:text-zinc-300 font-medium">{{ $role->name }}</span>
                        </label>
                    @empty
                        <p class="text-zinc-500 dark:text-zinc-500 col-span-full">
                            No roles have been created yet. Please create roles in the Role Management section first.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</div>