<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Services\DivisionService;

new #[Layout('components.layouts.lecturer')] class extends Component {
    public $divisions;
    public $showModal = false;
    public $showDeleteModal = false;
    public $editingId = null;
    public $deletingId = null;
    public $name = '';
    public $description = '';

    protected DivisionService $divisionService;

    public function boot(DivisionService $divisionService): void
    {
        $this->divisionService = $divisionService;
    }

    public function mount(): void
    {
        $this->loadDivisions();
    }

    public function loadDivisions(): void
    {
        $this->divisions = $this->divisionService->getAllDivisions();
    }

    public function openModal($id = null): void
    {
        if ($id) {
            $division = $this->divisionService->findDivision($id);
            $this->editingId = $id;
            $this->name = $division->name;
            $this->description = $division->description ?? '';
        } else {
            $this->reset(['editingId', 'name', 'description']);
        }
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $data = [
            'name' => $this->name,
            'description' => $this->description,
        ];

        if ($this->editingId) {
            $this->divisionService->updateDivision($this->editingId, $data);
            session()->flash('success', 'Division updated successfully.');
        } else {
            $this->divisionService->createDivision($data);
            session()->flash('success', 'Division created successfully.');
        }

        $this->closeModal();
        $this->loadDivisions();
    }

    public function confirmDelete($id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (!$this->divisionService->deleteDivision($this->deletingId)) {
            session()->flash('error', 'Cannot delete division with assigned lecturers.');
        } else {
            session()->flash('success', 'Division deleted successfully.');
        }
        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->loadDivisions();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['editingId', 'name', 'description']);
        $this->resetErrorBag();
    }
}; ?>

<div>
    <section class="w-full">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-xl mb-10 p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-3xl text-black dark:text-white font-bold">Manage Divisions</h1>
                    <p class="text-zinc-600 dark:text-zinc-400 mt-1">Add, edit, or delete academic divisions.</p>
                </div>
                <flux:button wire:click="openModal" variant="primary" class="cursor-pointer">
                    Add Division
                </flux:button>
            </div>

            <hr class="border-t border-zinc-200 dark:border-zinc-700 mb-6">

            @include('partials.session-messages')

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Lecturers</th>
                            <th class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($divisions as $division)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-white">{{ $division->name }}</td>
                                <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $division->description ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">{{ $division->lecturers()->count() }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end items-center gap-2">
                                        <flux:button wire:click="openModal('{{ $division->id }}')" variant="ghost" size="sm" class="cursor-pointer">
                                            Edit
                                        </flux:button>
                                        <flux:button wire:click="confirmDelete('{{ $division->id }}')" 
                                            variant="danger" size="sm" class="cursor-pointer">
                                            Delete
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    No divisions found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    @if($showModal)
        <flux:modal name="division-modal" wire:model="showModal" class="max-w-2xl w-full">
            <form wire:submit.prevent="save" class="space-y-6">
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Division' : 'Add New Division' }}
                </flux:heading>
                
                <div class="space-y-4">
                    <flux:input wire:model="name" label="Name" required class="w-full" />
                    <flux:textarea wire:model="description" label="Description" rows="3" class="w-full" />
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="$set('showModal', false)" class="cursor-pointer">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary" class="cursor-pointer">
                        {{ $editingId ? 'Update' : 'Create' }}
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    @if($showDeleteModal)
        <flux:modal name="delete-modal" wire:model="showDeleteModal" class="max-w-md">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Confirm Deletion</flux:heading>
                </div>
                
                <p class="text-zinc-600 dark:text-zinc-400">
                    Are you sure you want to delete this division? This action cannot be undone.
                </p>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="$set('showDeleteModal', false)" class="cursor-pointer">
                        Cancel
                    </flux:button>
                    <flux:button wire:click="delete" variant="danger" class="cursor-pointer">
                        Delete
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>