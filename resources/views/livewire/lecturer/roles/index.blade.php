<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use App\Services\RolePermissionService;

new #[Layout('components.layouts.lecturer')] 
class extends Component {
    public $roles;
    public $allPermissions;
    public ?Role $selectedRole = null;
    public array $rolePermissions = [];
    public string $newRoleName = '';
    public bool $showCreateModal = false;

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

    public function openCreateModal(): void
    {
        $this->newRoleName = '';
        $this->resetErrorBag();
        $this->showCreateModal = true;
    }

    public function createNewRole(): void
    {
        $this->validate(['newRoleName' => 'required|string|min:3|unique:roles,name']);
        $this->rolePermissionService->createRole(['name' => $this->newRoleName]);
        
        $this->newRoleName = '';
        $this->showCreateModal = false;
        $this->mount();
        session()->flash('success', 'New role created successfully.');
    }
}; ?>

<div class="h-full">
    <section class="w-full h-full">
        <div class="lg:grid lg:grid-cols-3 w-full lg:h-[calc(100vh-4rem)] rounded-lg shadow-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            <div class="lg:col-span-1 border-r border-zinc-200 dark:border-zinc-700 p-4 lg:h-full overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h1 class="text-2xl text-black dark:text-white font-bold">Roles</h1>
                    <button 
                        wire:click="openCreateModal" 
                        type="button"
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 cursor-pointer transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        wire:loading.attr="disabled"
                        wire:target="openCreateModal">
                        <span wire:loading.remove wire:target="openCreateModal">New Role</span>
                        <span wire:loading wire:target="openCreateModal">Loading...</span>
                    </button>
                </div>
                <hr class="border-zinc-200 dark:border-zinc-700 mb-4">

                <div class="flex flex-col gap-2">
                    @foreach ($roles as $role)
                        <button 
                            wire:click="selectRole({{ $role->id }})"
                            class="w-full text-start rounded-lg p-3 cursor-pointer transition-all duration-200
                                {{ optional($selectedRole)->id == $role->id 
                                    ? 'bg-blue-100 dark:bg-blue-900 font-semibold text-blue-900 dark:text-blue-100' 
                                    : 'bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-900 dark:text-zinc-100' }}"
                            wire:loading.attr="disabled"
                            wire:target="selectRole({{ $role->id }})">
                            <span wire:loading.remove wire:target="selectRole({{ $role->id }})">{{ $role->name }}</span>
                            <span wire:loading wire:target="selectRole({{ $role->id }})">Loading...</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="lg:col-span-2 p-4 h-full overflow-y-auto">
                @if ($selectedRole)
                    <div>
                        <div class="flex justify-between items-center mb-5">
                            <h1 class="text-2xl text-black dark:text-white font-bold">
                                Edit Role: {{ $selectedRole->name }}
                            </h1>
                            <button 
                                wire:click="savePermissions" 
                                type="button"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 cursor-pointer transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                wire:loading.attr="disabled"
                                wire:target="savePermissions">
                                <span wire:loading.remove wire:target="savePermissions">Save Changes</span>
                                <span wire:loading wire:target="savePermissions">Saving...</span>
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
                                        <input 
                                            type="checkbox" 
                                            wire:model.live="rolePermissions"
                                            value="{{ $permission->name }}"
                                            class="h-4 w-4 rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500 cursor-pointer">
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

    @if ($showCreateModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" wire:loading.class="opacity-50" wire:target="createNewRole">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-8 w-full max-w-md" @click.away="$wire.set('showCreateModal', false)">
                <h2 class="text-2xl font-bold text-zinc-800 dark:text-zinc-200 mb-6">Create New Role</h2>
                <form wire:submit.prevent="createNewRole">
                    <div class="space-y-4">
                        <div>
                            <label for="newRoleName" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Role Name</label>
                            <input 
                                type="text" 
                                wire:model="newRoleName" 
                                id="newRoleName" 
                                placeholder="Enter role name"
                                class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('newRoleName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-4">
                        <button 
                            type="button" 
                            wire:click="$set('showCreateModal', false)" 
                            class="px-4 py-2 bg-zinc-200 dark:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-md hover:bg-zinc-300 dark:hover:bg-zinc-500 cursor-pointer transition-colors">
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer transition-colors"
                            wire:loading.attr="disabled"
                            wire:target="createNewRole">
                            <span wire:loading.remove wire:target="createNewRole">Create Role</span>
                            <span wire:loading wire:target="createNewRole">Creating...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>