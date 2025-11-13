<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Lecturer;
use App\Models\Division;
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.lecturer')] 
class extends Component {
    use WithPagination;

    public $divisions = [];
    public bool $showModal = false;
    public string $search = '';

    public ?Lecturer $editing = null;
    public array $selected_divisions = [];
    public ?string $primary_division_id = null;

    public function mount(): void
    {
        $this->divisions = Division::orderBy('name')->get();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = Lecturer::with(['divisions', 'primaryDivision', 'roles']);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        return [
            'lecturers' => $query->paginate(15),
        ];
    }

    public function edit(Lecturer $lecturer): void
    {
        $this->editing = $lecturer;
        $this->selected_divisions = $lecturer->divisions->pluck('id')->toArray();
        $this->primary_division_id = $lecturer->primary_division_id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'selected_divisions' => 'nullable|array',
            'selected_divisions.*' => 'uuid|exists:divisions,id',
            'primary_division_id' => 'nullable|uuid|exists:divisions,id',
        ]);

        if ($this->primary_division_id && !in_array($this->primary_division_id, $this->selected_divisions)) {
            session()->flash('error', 'Primary division must be one of the selected divisions.');
            return;
        }

        $this->editing->divisions()->sync($this->selected_divisions);
        $this->editing->update(['primary_division_id' => $this->primary_division_id]);

        session()->flash('success', 'Divisions assigned successfully.');
        $this->showModal = false;
        $this->resetPage();
    }
};

?>

<div>
    <section class="w-full">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-xl mb-10 p-6 sm:p-8">
            <div class="mb-6">
                <h1 class="text-3xl text-black dark:text-white font-bold">
                    Assign Divisions
                </h1>
                <p class="text-zinc-600 dark:text-zinc-400 mt-1">Manage lecturer division assignments and specializations.</p>
            </div>

            <div class="mb-6">
                <div class="w-full sm:w-1/3">
                    <input wire:model.live.debounce.300ms="search" type="text"
                        placeholder="Search by name or email..."
                        class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <hr class="border-t border-zinc-200 dark:border-zinc-700 mb-6">

            @include('partials.session-messages')

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Lecturer
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Role
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Divisions
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Head of Division
                            </th>
                            <th class="px-6 py-3 relative">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($lecturers as $lecturer)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-6 py-4 text-sm">
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ $lecturer->name }}</div>
                                    <div class="text-zinc-500 dark:text-zinc-400">{{ $lecturer->email }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse($lecturer->roles as $role)
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ $role->name }}
                                            </span>
                                        @empty
                                            <span class="text-zinc-400 italic">No roles</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse($lecturer->divisions as $division)
                                            <span class="px-2 py-0.5 rounded text-xs bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">
                                                {{ $division->name }}
                                            </span>
                                        @empty
                                            <span class="text-zinc-400 italic">No divisions</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if($lecturer->primaryDivision)
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                            ⭐ {{ $lecturer->primaryDivision->name }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <flux:button wire:click="edit('{{ $lecturer->id }}')" variant="ghost" size="sm" class="cursor-pointer">
                                        Assign
                                    </flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    No lecturers found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $lecturers->links() }}
            </div>
        </div>
    </section>

    @if ($showModal)
        <flux:modal name="assign-modal" wire:model="showModal" class="max-w-md">
            <form wire:submit.prevent="save" class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        Assign Divisions - {{ $editing->name }}
                    </flux:heading>
                </div>

                <div class="space-y-4">
                    <div>
                        <flux:label>Divisions (Specializations)</flux:label>
                        <div class="mt-2 space-y-2 max-h-48 overflow-y-auto border border-zinc-300 dark:border-zinc-600 rounded-lg p-3">
                            @foreach ($divisions as $division)
                                <label class="flex items-center gap-2 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 p-2 rounded">
                                    <input type="checkbox" wire:model="selected_divisions" value="{{ $division->id }}" class="rounded cursor-pointer">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $division->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('selected_divisions')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <flux:select wire:model="primary_division_id" label="Head of Division (Primary)">
                        <option value="">None - Not a division head</option>
                        @foreach ($divisions as $division)
                            <option value="{{ $division->id }}">{{ $division->name }}</option>
                        @endforeach
                    </flux:select>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Only select if this lecturer is the head of a division</p>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="$set('showModal', false)" class="cursor-pointer">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary" class="cursor-pointer">
                        Save
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>
