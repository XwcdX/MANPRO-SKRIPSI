<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\PresentationVenue;
use App\Services\VenueService;

new #[Layout('components.layouts.lecturer')] 
class extends Component {
    use WithPagination;

    protected VenueService $venueService;

    public function boot(VenueService $venueService): void
    {
        $this->venueService = $venueService;
    }

    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public string $search = '';
    public ?string $deletingId = null;

    public ?PresentationVenue $editing = null;
    public string $name = '';
    public string $location = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = PresentationVenue::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('location', 'like', '%' . $this->search . '%');
            });
        }

        return [
            'venues' => $query->orderBy('name')->paginate(15),
        ];
    }

    public function create(): void
    {
        $this->resetInput();
        $this->editing = new PresentationVenue();
        $this->showModal = true;
    }

    public function edit(PresentationVenue $venue): void
    {
        $this->resetInput();
        $this->editing = $venue;
        $this->name = $venue->name;
        $this->location = $venue->location;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
        ]);

        $data = [
            'name' => $this->name,
            'location' => $this->location,
        ];

        if ($this->editing && $this->editing->exists) {
            $this->venueService->updateVenue($this->editing, $data);
        } else {
            $this->venueService->createVenue($data);
        }

        session()->flash('success', 'Venue saved successfully.');
        $this->showModal = false;
        $this->resetPage();
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteVenue(): void
    {
        if ($this->deletingId) {
            $this->venueService->deleteVenue($this->deletingId);
            session()->flash('success', 'Venue deleted successfully.');
        }
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    private function resetInput(): void
    {
        $this->resetErrorBag();
        $this->reset('name', 'location');
    }
};

?>

<div>
    <section class="w-full">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-xl mb-10 p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-3xl text-black dark:text-white font-bold">
                        Manage Venues
                    </h1>
                    <p class="text-zinc-600 dark:text-zinc-400 mt-1">Manage presentation venues and locations.</p>
                </div>
                <flux:button wire:click="create" variant="primary" class="cursor-pointer">
                    Add Venue
                </flux:button>
            </div>

            <div class="mb-6">
                <div class="w-full sm:w-1/3">
                    <input wire:model.live.debounce.300ms="search" type="text"
                        placeholder="Search by name or location..."
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
                                Name
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Location
                            </th>
                            <th class="px-6 py-3 relative">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($venues as $venue)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $venue->name }}
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $venue->location }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end items-center gap-2">
                                        <flux:button wire:click="edit('{{ $venue->id }}')" variant="ghost" size="sm" class="cursor-pointer">
                                            Edit
                                        </flux:button>
                                        <flux:button wire:click="confirmDelete('{{ $venue->id }}')" variant="danger" size="sm" class="cursor-pointer">
                                            Delete
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    No venues found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $venues->links() }}
            </div>
        </div>
    </section>

    @if ($showModal)
        <flux:modal name="venue-modal" wire:model="showModal" class="max-w-2xl">
            <form wire:submit.prevent="save" class="space-y-6">
                <flux:heading size="lg">
                    {{ $editing && $editing->exists ? 'Edit Venue' : 'Add New Venue' }}
                </flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input wire:model="name" label="Venue Name" required />
                    <flux:input wire:model="location" label="Location" required />
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

    @if($showDeleteModal)
        <flux:modal name="delete-modal" wire:model="showDeleteModal" class="max-w-md">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Confirm Deletion</flux:heading>
                </div>
                
                <p class="text-zinc-600 dark:text-zinc-400">
                    Are you sure you want to delete this venue? This action cannot be undone.
                </p>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="$set('showDeleteModal', false)" class="cursor-pointer">
                        Cancel
                    </flux:button>
                    <flux:button wire:click="deleteVenue" variant="danger" class="cursor-pointer">
                        Delete
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
