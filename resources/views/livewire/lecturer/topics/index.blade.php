<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\LecturerTopic;
use App\Services\TopicService;

new #[Layout('components.layouts.lecturer')] class extends Component {
    use WithPagination;

    public bool $showModal = false;
    public string $search = '';
    public ?string $filterPeriod = '';
    public ?LecturerTopic $editing = null;

    public string $topic = '';
    public string $description = '';
    public int $student_quota = 1;
    public ?string $period_id = null;
    public bool $is_available = true;

    public function mount(): void
    {
        if (!auth()->user()->can('offer-topics')) {
            abort(403, 'You do not have permission to manage topics.');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterPeriod(): void
    {
        $this->resetPage();
    }

    protected function rules()
    {
        return [
            'topic' => 'required|string|max:255',
            'description' => 'nullable|string',
            'student_quota' => 'required|integer|min:1|max:10',
            'period_id' => 'required|uuid|exists:periods,id',
            'is_available' => 'required|boolean',
        ];
    }

    public function with(): array
    {
        $service = app(TopicService::class);
        $lecturer = auth()->user();
        $selectedPeriod = $this->filterPeriod ? app(\App\Services\PeriodService::class)->findPeriod($this->filterPeriod) : null;

        return [
            'topics' => $service->getTopicsForLecturer($lecturer->id, $this->search, $this->filterPeriod)
                ->paginate(15),
            'periods' => $service->getActivePeriods(),
            'lecturer' => $lecturer,
            'selectedPeriod' => $selectedPeriod,
            'periodQuota' => $selectedPeriod ? $selectedPeriod->getLecturerQuota($lecturer) : null,
            'periodCapacity' => $selectedPeriod ? $lecturer->getAvailableCapacityForPeriod($selectedPeriod->id) : null,
        ];
    }

    public function create(): void
    {
        $this->resetInput();
        $this->editing = new LecturerTopic();
        $this->showModal = true;
    }

    public function edit(LecturerTopic $topic): void
    {
        if ($topic->lecturer_id !== auth()->id()) {
            abort(403);
        }

        $this->resetInput();
        $this->editing = $topic;
        $this->topic = $topic->topic;
        $this->description = $topic->description ?? '';
        $this->student_quota = $topic->student_quota;
        $this->period_id = $topic->period_id;
        $this->is_available = $topic->is_available;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();
        $service = app(TopicService::class);

        $data = [
            'lecturer_id' => auth()->id(),
            'topic' => $this->topic,
            'description' => $this->description,
            'student_quota' => $this->student_quota,
            'period_id' => $this->period_id,
            'is_available' => $this->is_available,
        ];

        if ($this->editing && $this->editing->exists) {
            $service->updateTopic($this->editing, $data);
        } else {
            $service->createTopic($data);
        }

        session()->flash('success', 'Topic saved successfully.');
        $this->showModal = false;
        $this->resetPage();
    }

    public function deleteTopic(LecturerTopic $topic): void
    {
        if ($topic->lecturer_id !== auth()->id()) {
            abort(403);
        }

        app(TopicService::class)->deleteTopic($topic);
        session()->flash('success', 'Topic deleted successfully.');
    }



    private function resetInput(): void
    {
        $this->resetErrorBag();
        $this->reset('topic', 'description', 'student_quota', 'period_id', 'is_available');
        $this->student_quota = 1;
        $this->is_available = true;
    }
};

?>

<div>
    <section class="w-full">
        <div
            class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-xl mb-10 p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-3xl text-black dark:text-white font-bold">My Topics</h1>
                    <p class="text-zinc-600 dark:text-zinc-400 mt-1">Manage thesis topics you offer to students.</p>
                </div>
                <flux:button wire:click="create" variant="primary" class="cursor-pointer">
                    Add Topic
                </flux:button>
            </div>

            <div class="mb-6">
                <div class="flex gap-4">
                    <div class="w-full sm:w-1/3">
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search topics..."
                            class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="w-full sm:w-1/4">
                        <select wire:model.live="filterPeriod"
                            class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Periods</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}">{{ $period->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @if($selectedPeriod)
                    <div class="mt-3 text-sm">
                        <span class="text-zinc-600 dark:text-zinc-400">Period Quota: </span>
                        <span class="font-semibold text-zinc-900 dark:text-white">{{ $periodQuota }} students</span>
                        <span class="mx-2 text-zinc-400">|</span>
                        <span class="text-zinc-600 dark:text-zinc-400">Available: </span>
                        <span class="font-semibold {{ $periodCapacity > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $periodCapacity }} slots</span>
                    </div>
                @endif
            </div>

            <hr class="border-t border-zinc-200 dark:border-zinc-700 mb-6">

            @include('partials.session-messages')

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Topic</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Period</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Quota</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 relative"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($topics as $topicItem)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-6 py-4 text-sm text-zinc-900 dark:text-white">
                                    <div class="font-medium">{{ $topicItem->topic }}</div>
                                    @if ($topicItem->description)
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                            {{ Str::limit($topicItem->description, 100) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $topicItem->period->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $topicItem->student_quota }} student(s)
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $periodCapacity = $lecturer->isAtCapacityForPeriod($topicItem->period_id);
                                    @endphp
                                    @if ($periodCapacity)
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Period
                                            Full</span>
                                    @elseif($topicItem->is_available)
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Available</span>
                                    @else
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">Unavailable</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end items-center gap-2">
                                        <flux:button wire:click="edit('{{ $topicItem->id }}')" variant="ghost"
                                            size="sm" class="cursor-pointer">
                                            Edit
                                        </flux:button>
                                        <flux:button wire:click="deleteTopic('{{ $topicItem->id }}')"
                                            wire:confirm="Are you sure you want to delete this topic?" variant="danger"
                                            size="sm" class="cursor-pointer">
                                            Delete
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5"
                                    class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    No topics found. Click "Add Topic" to create one.
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

    @if ($showModal)
        <flux:modal name="topic-modal" wire:model="showModal" class="!w-[800px] !max-w-[90vw]">
            <form wire:submit.prevent="save" class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        {{ $editing && $editing->exists ? 'Edit Topic' : 'Add New Topic' }}
                    </flux:heading>
                </div>

                <div class="space-y-4">
                    <flux:input wire:model="topic" label="Topic Title" required />

                    <div>
                        <flux:label>Description</flux:label>
                        <textarea wire:model="description" rows="4"
                            class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        @error('description')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <flux:select wire:model="period_id" label="Period" required>
                        <option value="">Select a Period</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}">{{ $period->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="student_quota" type="number" label="Student Quota" min="1"
                        max="10" required />

                    <flux:checkbox wire:model="is_available" label="Available for students" />
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
</div>
