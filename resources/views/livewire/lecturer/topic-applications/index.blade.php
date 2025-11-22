<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\TopicApplication;
use App\Services\TopicApplicationService;

new #[Layout('components.layouts.lecturer')] class extends Component {
    use WithPagination;

    public string $filterStatus = '';
    public ?TopicApplication $viewing = null;
    public bool $showModal = false;
    public bool $showAcceptModal = false;
    public bool $showDeclineModal = false;
    public string $lecturer_notes = '';

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = TopicApplication::with(['student', 'topic', 'period'])
            ->where('lecturer_id', auth()->id());

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        return [
            'applications' => $query->latest()->paginate(15),
        ];
    }

    public function viewApplication(TopicApplication $application): void
    {
        if ($application->lecturer_id !== auth()->id()) {
            abort(403);
        }

        $this->viewing = $application;
        $this->lecturer_notes = $application->lecturer_notes ?? '';
        $this->showModal = true;
    }

    public function confirmAccept(): void
    {
        $this->showAcceptModal = true;
    }

    public function acceptApplication(): void
    {
        if (!$this->viewing || $this->viewing->lecturer_id !== auth()->id()) {
            abort(403);
        }

        $service = app(TopicApplicationService::class);
        $result = $service->acceptApplication($this->viewing->id, auth()->id(), $this->lecturer_notes);

        session()->flash($result['success'] ? 'success' : 'error', $result['message']);
        
        if ($result['success']) {
            $this->showModal = false;
            $this->showAcceptModal = false;
            $this->reset('viewing', 'lecturer_notes');
        }
    }

    public function confirmDecline(): void
    {
        $this->showDeclineModal = true;
    }

    public function declineApplication(): void
    {
        if (!$this->viewing || $this->viewing->lecturer_id !== auth()->id()) {
            abort(403);
        }

        $service = app(TopicApplicationService::class);
        $result = $service->declineApplication($this->viewing->id, auth()->id(), $this->lecturer_notes);

        session()->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->showModal = false;
        $this->showDeclineModal = false;
        $this->reset('viewing', 'lecturer_notes');
    }
};

?>

<div>
    <section class="w-full">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-xl mb-10 p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-3xl text-black dark:text-white font-bold">Topic Applications</h1>
                    <p class="text-zinc-600 dark:text-zinc-400 mt-1">Review and manage student applications for your topics.</p>
                </div>
            </div>

            <div class="mb-6">
                <select wire:model.live="filterStatus"
                    class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="accepted">Accepted</option>
                    <option value="declined">Declined</option>
                </select>
            </div>

            <hr class="border-t border-zinc-200 dark:border-zinc-700 mb-6">

            @include('partials.session-messages')

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Topic</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Applied</th>
                            <th class="px-6 py-3 relative"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($applications as $application)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-white">
                                    <div class="font-medium">{{ $application->student->name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $application->student->email }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    <div>{{ Str::limit($application->topic->topic, 50) }}</div>
                                    <div class="text-xs text-zinc-400">Capacity: {{ $application->topic->student_quota }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $application->period->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($application->status === 'pending')
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Pending</span>
                                    @elseif($application->status === 'accepted')
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Accepted</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Declined</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $application->created_at->diffForHumans() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <flux:button wire:click="viewApplication('{{ $application->id }}')" variant="ghost" size="sm" class="cursor-pointer">
                                        View
                                    </flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    No applications found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $applications->links() }}
            </div>
        </div>
    </section>

    @if($showModal && $viewing)
        <flux:modal name="application-modal" wire:model="showModal" class="max-w-2xl w-full">
            <div class="space-y-6">
                <flux:heading size="lg">Application Details</flux:heading>

                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Student</label>
                        <p class="text-zinc-900 dark:text-white">{{ $viewing->student->name }}</p>
                        <p class="text-sm text-zinc-500">{{ $viewing->student->email }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Topic</label>
                        <p class="text-zinc-900 dark:text-white">{{ $viewing->topic->topic }}</p>
                        @if($viewing->topic->description)
                            <p class="text-sm text-zinc-500 mt-1">{{ $viewing->topic->description }}</p>
                        @endif
                        <p class="text-sm text-amber-600 dark:text-amber-400 mt-1">
                            ⚠️ This topic requires {{ $viewing->topic->student_quota }} student(s). 
                            Accepting will reduce your quota by {{ $viewing->topic->student_quota }}.
                        </p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Period</label>
                        <p class="text-zinc-900 dark:text-white">{{ $viewing->period->name }}</p>
                        @php
                            $capacity = auth()->user()->getAvailableCapacityForPeriod($viewing->period_id);
                        @endphp
                        <p class="text-sm {{ $capacity >= $viewing->topic->student_quota ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            Your available capacity: {{ $capacity }} student(s)
                        </p>
                    </div>

                    @if($viewing->student_notes)
                        <div>
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Student Notes</label>
                            <p class="text-zinc-900 dark:text-white whitespace-pre-wrap">{{ $viewing->student_notes }}</p>
                        </div>
                    @endif

                    <div>
                        <flux:label>Your Notes (Optional)</flux:label>
                        <textarea wire:model="lecturer_notes" rows="3"
                            class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :disabled="$viewing->status !== 'pending'"></textarea>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Status</label>
                        <p class="text-zinc-900 dark:text-white capitalize">{{ $viewing->status }}</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    @if($viewing->status === 'pending')
                        <flux:button wire:click="confirmDecline" variant="danger" class="cursor-pointer">
                            Decline
                        </flux:button>
                        <flux:button wire:click="confirmAccept" variant="primary" class="cursor-pointer">
                            Accept & Assign as Supervisor
                        </flux:button>
                    @else
                        <flux:button wire:click="$set('showModal', false)" variant="ghost" class="cursor-pointer">
                            Close
                        </flux:button>
                    @endif
                </div>
            </div>
        </flux:modal>
    @endif

    @if($showAcceptModal)
        <flux:modal name="accept-modal" wire:model="showAcceptModal" class="max-w-md">
            <div class="space-y-6">
                <flux:heading size="lg">Confirm Acceptance</flux:heading>
                
                <p class="text-zinc-600 dark:text-zinc-400">
                    Are you sure you want to accept this application? The student will be assigned as your supervisee and this action cannot be undone.
                </p>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="$set('showAcceptModal', false)" class="cursor-pointer">
                        Cancel
                    </flux:button>
                    <flux:button wire:click="acceptApplication" variant="primary" class="cursor-pointer">
                        Confirm Accept
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    @if($showDeclineModal)
        <flux:modal name="decline-modal" wire:model="showDeclineModal" class="max-w-md">
            <div class="space-y-6">
                <flux:heading size="lg">Confirm Decline</flux:heading>
                
                <p class="text-zinc-600 dark:text-zinc-400">
                    Are you sure you want to decline this application?
                </p>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="$set('showDeclineModal', false)" class="cursor-pointer">
                        Cancel
                    </flux:button>
                    <flux:button wire:click="declineApplication" variant="danger" class="cursor-pointer">
                        Confirm Decline
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
