<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\LecturerTopic;
use App\Models\Division;

new #[Layout('components.layouts.lecturer')] 
class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterDivision = '';
    public string $filterStatus = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDivision(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = LecturerTopic::with(['lecturer.divisions', 'period']);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('topic', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhereHas('lecturer', fn($q2) => $q2->where('name', 'like', '%' . $this->search . '%'));
            });
        }

        if ($this->filterDivision) {
            $query->whereHas('lecturer.divisions', fn($q) => $q->where('divisions.id', $this->filterDivision));
        }

        if ($this->filterStatus) {
            $query->where('is_available', $this->filterStatus === 'available');
        }

        return [
            'topics' => $query->orderBy('created_at', 'desc')->paginate(20),
            'divisions' => Division::orderBy('name')->get(),
        ];
    }
};

?>

<div>
    <section class="w-full">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-xl mb-10 p-6 sm:p-8">
            <div class="mb-6">
                <h1 class="text-3xl text-black dark:text-white font-bold">All Topics</h1>
                <p class="text-zinc-600 dark:text-zinc-400 mt-1">View and manage all thesis topics from all lecturers.</p>
            </div>

            <div class="mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by title, description, or lecturer..."
                    class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">

                <select wire:model.live="filterDivision"
                    class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Divisions</option>
                    @foreach ($divisions as $division)
                        <option value="{{ $division->id }}">{{ $division->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterStatus"
                    class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
                </select>
            </div>

            <hr class="border-t border-zinc-200 dark:border-zinc-700 mb-6">

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Topic
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Lecturer
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Division
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Created
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($topics as $topic)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ $topic->topic }}</div>
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ Str::limit($topic->description, 100) }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $topic->lecturer->name }}
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    @if($topic->lecturer->divisions->isNotEmpty())
                                        {{ $topic->lecturer->divisions->pluck('name')->join(', ') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $topic->is_available ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                        {{ $topic->is_available ? 'Available' : 'Unavailable' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $topic->created_at->format('d M Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    No topics found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $topics->links() }}
            </div>
        </div>
    </section>
</div>
