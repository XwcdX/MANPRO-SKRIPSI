<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Lecturer;
use App\Models\Division;
use App\Services\CrudService;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\LecturersImport;
use App\Exports\LecturersExport;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

new #[Layout('components.layouts.lecturer')] 
class extends Component {
    use WithFileUploads, WithPagination;

    public $divisions = [];
    public bool $showModal = false;
    public bool $showImportModal = false;
    public bool $showDeleteModal = false;
    public string $search = '';
    public string $filterDivision = '';
    public string $filterRole = '';
    public ?string $deletingId = null;

    public ?Lecturer $editing = null;

    public string $name = '';
    public string $email = '';
    public int $title = 0;
    public ?string $primary_division_id = null;
    public array $selected_divisions = [];
    public bool $is_active = true;
    public string $password = '';
    public string $password_confirmation = '';

    public $upload;

    protected CrudService $crudService;

    public function boot(CrudService $crudService): void
    {
        $this->crudService = $crudService->setModel(new Lecturer());
    }

    public function mount(): void
    {
        $this->divisions = Division::orderBy('name')->get();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDivision(): void
    {
        $this->resetPage();
    }

    public function updatingFilterRole(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterDivision', 'filterRole']);
        $this->resetPage();
    }

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:100'],
            'title' => 'required|integer|in:0,1,2,3',
            'primary_division_id' => 'nullable|uuid|exists:divisions,id',
            'selected_divisions' => 'nullable|array',
            'selected_divisions.*' => 'uuid|exists:divisions,id',
            'is_active' => 'required|boolean',
            'password' => ['confirmed'],
        ];

        if ($this->editing && $this->editing->exists) {
            $rules['email'][] = Rule::unique('lecturers', 'email')->ignore($this->editing->id);
            $rules['password'][] = 'nullable';
        } else {
            $rules['email'][] = Rule::unique('lecturers', 'email');
            $rules['password'][] = 'required';
            $rules['password'][] = Password::defaults();
        }

        return $rules;
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

        if ($this->filterDivision) {
            $query->whereHas('divisions', function($q) {
                $q->where('divisions.id', $this->filterDivision);
            });
        }

        if ($this->filterRole) {
            $query->whereHas('roles', function($q) {
                $q->where('name', $this->filterRole);
            });
        }

        return [
            'lecturers' => $query->paginate(15),
            'roles' => DB::table('roles')->where('guard_name', 'lecturer')->pluck('name'),
        ];
    }

    public function create(): void
    {
        $this->resetInput();
        $this->editing = new Lecturer();
        $this->name = '';
        $this->email = '';
        $this->title = 0;
        $this->primary_division_id = null;
        $this->selected_divisions = [];
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit(Lecturer $lecturer): void
    {
        $this->resetInput();
        $this->editing = $lecturer;
        $this->name = $lecturer->name;
        $this->email = $lecturer->email;
        $this->title = $lecturer->title;
        $this->primary_division_id = $lecturer->primary_division_id;
        $this->selected_divisions = $lecturer->divisions->pluck('id')->toArray();
        $this->is_active = $lecturer->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        if (!$this->editing) {
            $this->editing = new Lecturer();
        }

        $this->editing->name = $this->name;
        $this->editing->email = $this->email;
        $this->editing->title = $this->title;
        $this->editing->primary_division_id = $this->primary_division_id ?: null;
        $this->editing->is_active = $this->is_active;

        if (!empty($this->password)) {
            $this->editing->password = Hash::make($this->password);
        }

        $this->editing->save();
        
        // Sync divisions
        $this->editing->divisions()->sync($this->selected_divisions);

        session()->flash('success', 'Lecturer saved successfully.');
        $this->showModal = false;
        $this->resetPage();
    }

    public function importLecturers()
    {
        $this->validate(['upload' => 'required|file|mimes:xlsx,xls']);

        try {
            Excel::import(new LecturersImport(), $this->upload->getRealPath());
            session()->flash('success', 'Lecturers data imported successfully.');
            $this->showImportModal = false;
            $this->reset('upload');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];
            foreach ($failures as $failure) {
                $errorMessages[] = 'Row ' . $failure->row() . ': ' . implode(', ', $failure->errors());
            }
            session()->flash('error', 'Import failed: ' . implode(' | ', $errorMessages));
        } catch (\Exception $e) {
            session()->flash('error', 'Import failed: ' . $e->getMessage());
        }

        $this->resetPage();
    }

    public function exportLecturers()
    {
        return Excel::download(new LecturersExport(), 'lecturers-' . now()->timestamp . '.xlsx');
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteLecturer(): void
    {
        $lecturer = Lecturer::find($this->deletingId);
        if ($lecturer) {
            $lecturer->delete();
            session()->flash('success', 'Lecturer deleted successfully.');
        }
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    private function resetInput(): void
    {
        $this->resetErrorBag();
        $this->reset('password', 'password_confirmation', 'upload', 'name', 'email', 'title', 'primary_division_id', 'selected_divisions', 'is_active');
    }
};

?>

<div>
    <section class="w-full">
        <div
            class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-xl mb-10 p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-3xl text-black dark:text-white font-bold">
                        Manage Lecturers
                    </h1>
                    <p class="text-zinc-600 dark:text-zinc-400 mt-1">Add, edit, import, or export lecturer data.</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <flux:button wire:click="create" variant="primary" class="cursor-pointer">
                        Add Lecturer
                    </flux:button>
                    <flux:button wire:click="$set('showImportModal', true)" variant="outline" class="cursor-pointer">
                        Import
                    </flux:button>
                    <flux:button wire:click="exportLecturers" variant="outline" class="cursor-pointer">
                        Export
                    </flux:button>
                </div>
            </div>

            <div class="mb-6 grid grid-cols-1 sm:grid-cols-4 gap-4">
                <input wire:model.live.debounce.300ms="search" type="text"
                    placeholder="Search by name or email..."
                    class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                
                <select wire:model.live="filterDivision"
                    class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Divisions</option>
                    @foreach ($divisions as $division)
                        <option value="{{ $division->id }}">{{ $division->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterRole"
                    class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Roles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}">{{ $role }}</option>
                    @endforeach
                </select>

                <button wire:click="clearFilters"
                    class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white hover:bg-zinc-50 dark:hover:bg-zinc-700 cursor-pointer">
                    Clear Filters
                </button>
            </div>

            <hr class="border-t border-zinc-200 dark:border-zinc-700 mb-6">

            @include('partials.session-messages')

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Name
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Email
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Role / Division
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($lecturers as $lecturer)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $lecturer->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $lecturer->email }}
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    <div class="flex flex-wrap gap-1.5 items-center">
                                        @forelse($lecturer->roles as $role)
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ $role->name }}
                                            </span>
                                        @empty
                                            <span class="text-zinc-400 italic">No roles assigned</span>
                                        @endforelse
                                        @if ($lecturer->divisions->isNotEmpty())
                                            <span class="text-zinc-400 dark:text-zinc-500 mx-1">•</span>
                                            @foreach($lecturer->divisions as $division)
                                                <span class="px-2 py-0.5 rounded text-xs {{ $lecturer->primary_division_id === $division->id ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300' }}">
                                                    @if($lecturer->primary_division_id === $division->id)⭐ @endif{{ $division->name }}
                                                </span>
                                            @endforeach
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($lecturer->is_active)
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>
                                    @else
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end items-center gap-2">
                                        <flux:button wire:click="edit('{{ $lecturer->id }}')" variant="ghost"
                                            size="sm" class="cursor-pointer">
                                            Edit
                                        </flux:button>
                                        <flux:button wire:click="confirmDelete('{{ $lecturer->id }}')"
                                            variant="danger" size="sm" class="cursor-pointer">
                                            Delete
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5"
                                    class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    No lecturers found matching your search.
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
        <flux:modal name="lecturer-modal" wire:model="showModal" class="max-w-md">
            <form wire:submit.prevent="save" class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        {{ $editing && $editing->exists ? 'Edit Lecturer' : 'Add New Lecturer' }}
                    </flux:heading>
                </div>

                <div class="space-y-4">
                    <flux:input wire:model="name" label="Name" required />

                    <flux:input wire:model="email" type="email" label="Email" required />

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

                    <flux:select wire:model="primary_division_id" label="Primary Division (Head of)">
                        <option value="">None - Not a division head</option>
                        @foreach ($divisions as $division)
                            <option value="{{ $division->id }}">{{ $division->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="password" type="password" label="Password"
                        :placeholder="$editing && $editing->exists ? 'Leave blank to keep current' : ''"
                        :required="!$editing || !$editing->exists" />

                    <flux:input wire:model="password_confirmation" type="password" label="Confirm Password" />

                    <flux:checkbox wire:model="is_active" label="Active" />
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="$set('showModal', false)"
                        class="cursor-pointer">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary" class="cursor-pointer">
                        Save
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    @if ($showImportModal)
        <flux:modal name="import-modal" wire:model="showImportModal" class="max-w-md">
            <form wire:submit.prevent="importLecturers" class="space-y-6">
                <div>
                    <flux:heading size="lg">Import Lecturers</flux:heading>
                </div>

                <div class="space-y-4">
                    <div>
                        <flux:label>Excel File (.xlsx, .xls)</flux:label>
                        <input type="file" wire:model="upload" accept=".xlsx,.xls"
                            class="mt-2 block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900 dark:file:text-blue-200 file:cursor-pointer cursor-pointer">
                        <div wire:loading wire:target="upload" class="text-sm text-zinc-500 mt-1">Uploading...</div>
                        @error('upload')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div
                        class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg text-sm text-blue-900 dark:text-blue-200">
                        <p>Your file must have columns: <strong>name</strong> and <strong>email</strong>.</p>
                        <p class="mt-1">Optionally, include a <strong>password</strong> column for new lecturers.</p>
                        <p class="mt-1">If an email already exists, the name will be updated. If not, a new lecturer
                            will be created.</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="$set('showImportModal', false)"
                        class="cursor-pointer">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary" class="cursor-pointer" :disabled="!$upload">
                        Import Data
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
                    Are you sure you want to delete this lecturer? This action cannot be undone.
                </p>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="$set('showDeleteModal', false)" class="cursor-pointer">
                        Cancel
                    </flux:button>
                    <flux:button wire:click="deleteLecturer" variant="danger" class="cursor-pointer">
                        Delete
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
