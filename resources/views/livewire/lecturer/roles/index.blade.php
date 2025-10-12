<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use App\Services\RolePermissionService;

new #[Layout('components.layouts.lecturer')] class extends Component {
    public $roles;
    public $allPermissions;
    public ?Role $selectedRole = null;
    public array $rolePermissions = [];
    public string $newRoleName = '';

    protected RolePermissionService $rolePermissionService;

    public function boot(RolePermissionService $rolePermissionService): void
    {
        $this->rolePermissionService = $rolePermissionService;
    }

    public function mount(): void
    {
        $this->roles = $this->rolePermissionService->getRoles();
        $this->allPermissions = $this->rolePermissionService->getAllPermissions();
    }

    public function selectRole(int $roleId): void
    {
        $this->selectedRole = $this->rolePermissionService->findRole($roleId);
        $this->rolePermissions = $this->selectedRole->permissions()->pluck('name')->toArray();
    }

    public function savePermissions(): void
    {
        if ($this->selectedRole) {
            $this->rolePermissionService->syncPermissionsForRole($this->selectedRole, $this->rolePermissions);
            session()->flash('success', 'Permissions for ' . $this->selectedRole->name . ' updated successfully.');
        }
    }

    public function createNewRole(): void
    {
        $this->validate(['newRoleName' => 'required|string|min:3|unique:roles,name']);
        $this->rolePermissionService->createRole(['name' => $this->newRoleName]);
        
        $this->newRoleName = '';
        $this->mount();
        $this->dispatch('close-modal');
        session()->flash('success', 'New role created successfully.');
    }
}; ?>

<div class="h-full">
    <section class="w-full h-full">
        <div class="lg:grid lg:grid-cols-3 w-full lg:h-[calc(100vh-4rem)] rounded-lg shadow-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            {{-- Left Panel: List of Roles --}}
            <div class="lg:col-span-1 border-r border-zinc-200 dark:border-zinc-700 p-4 lg:h-full overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h1 class="text-2xl text-black dark:text-white font-bold">Roles</h1>
                    {{-- A wire:click event would open a modal to create a new role --}}
                    <button wire:click="createNewRole" type="button"
                        class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 text-sm">
                        New Role
                    </button>
                </div>
                <hr class="border-zinc-200 dark:border-zinc-700 mb-4">

                <div class="flex flex-col gap-2">
                    @foreach ($roles as $role)
                        {{-- When a role is clicked, the $selectedRole in the component is updated --}}
                        <button wire:click="selectRole({{ $role->id }})"
                            class="w-full text-start rounded-lg p-3 cursor-pointer transition-all duration-200
                                {{ optional($selectedRole)->id == $role->id 
                                    ? 'bg-blue-100 dark:bg-blue-900 font-semibold text-blue-900 dark:text-blue-100' 
                                    : 'bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-900 dark:text-zinc-100' }}">
                            {{ $role->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Right Panel: Edit Selected Role --}}
            <div class="lg:col-span-2 p-4 h-full overflow-y-auto">
                @if ($selectedRole)
                    <div>
                        <div class="flex justify-between items-center mb-5">
                            <h1 class="text-2xl text-black dark:text-white font-bold">
                                Edit Role: {{ $selectedRole->name }}
                            </h1>
                            <button wire:click="savePermissions" type="button"
                                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm">
                                Save Changes
                            </button>
                        </div>

                        @if (session('success'))
                            <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 dark:bg-green-900 dark:border-green-700 dark:text-green-200 rounded-lg text-sm">
                                {{ session('success') }}
                            </div>
                        @endif

                        <hr class="border-zinc-200 dark:border-zinc-700 mb-5">

                        <div class="space-y-4">
                            <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Permissions</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach ($allPermissions as $permission)
                                    <label
                                        class="flex items-center p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 border border-zinc-200 dark:border-zinc-700 cursor-pointer transition-colors">
                                        <input type="checkbox" wire:model.live="rolePermissions"
                                            value="{{ $permission->name }}"
                                            class="h-4 w-4 rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $permission->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex items-center justify-center h-full">
                        <p class="text-xl text-zinc-400 dark:text-zinc-500">Select a role to view its permissions.</p>
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>