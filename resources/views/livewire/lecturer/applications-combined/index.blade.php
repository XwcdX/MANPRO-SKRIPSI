<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\TopicApplication;
use App\Services\TopicApplicationService;
use App\Services\SupervisionApplicationService;
use App\Services\PeriodService;

new #[Layout('components.layouts.lecturer')] class extends Component {
    use WithPagination;

    public string $activeTab = 'supervision';
    public string $search = '';
    public string $filterStatus = '';
    public string $filterPeriod = '';
    
    // Topic application
    public ?TopicApplication $viewingTopic = null;
    public bool $showTopicModal = false;
    public bool $showTopicAcceptModal = false;
    public bool $showTopicDeclineModal = false;
    public bool $showTopicReleaseModal = false;
    public bool $showTopicReopenModal = false;
    public string $topic_lecturer_notes = '';
    
    // Supervision application
    public bool $showSupervisionAcceptModal = false;
    public bool $showSupervisionDeclineModal = false;
    public ?string $selectedSupervisionId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterPeriod(): void
    {
        $this->resetPage();
    }

    public function updatedActiveTab(): void
    {
        $this->reset('search', 'filterStatus', 'filterPeriod');
        $this->resetPage();
    }

    public function with(): array
    {
        if ($this->activeTab === 'topics') {
            $query = TopicApplication::with(['student', 'topic', 'period'])
                ->where('lecturer_id', auth()->id());

            if ($this->filterStatus) {
                $query->where('status', $this->filterStatus);
            }

            if ($this->filterPeriod) {
                $query->where('period_id', $this->filterPeriod);
            }

            $topicApplications = $query->latest()->paginate(15);
            $supervisionApplications = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        } else {
            $supervisionApplications = app(SupervisionApplicationService::class)
                ->getApplicationsForLecturer(auth()->id(), $this->filterStatus ?: null, $this->search, $this->filterPeriod ?: null)
                ->paginate(15);
            $topicApplications = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        }

        $topicCountQuery = TopicApplication::where('lecturer_id', auth()->id());
        if ($this->filterStatus) {
            $topicCountQuery->where('status', $this->filterStatus);
        }
        if ($this->filterPeriod) {
            $topicCountQuery->where('period_id', $this->filterPeriod);
        }
        $topicCount = $topicCountQuery->count();
        
        $supervisionCount = app(SupervisionApplicationService::class)
            ->getApplicationsForLecturer(auth()->id(), $this->filterStatus ?: null, $this->search, $this->filterPeriod ?: null)
            ->count();

        $displayPeriodId = $this->filterPeriod ?: app(PeriodService::class)->getActivePeriod()?->id;
        $displayPeriod = $displayPeriodId ? \App\Models\Period::find($displayPeriodId) : null;

        return [
            'topicApplications' => $topicApplications,
            'supervisionApplications' => $supervisionApplications,
            'topicCount' => $topicCount,
            'supervisionCount' => $supervisionCount,
            'currentQuota' => $displayPeriodId ? auth()->user()->getAvailableCapacityForPeriod($displayPeriodId) : 0,
            'displayPeriod' => $displayPeriod,
            'periods' => \App\Models\Period::notArchived()->orderBy('start_date', 'desc')->get(),
        ];
    }

    // Topic Application Methods
    public function viewTopicApplication(TopicApplication $application): void
    {
        if ($application->lecturer_id !== auth()->id()) {
            abort(403);
        }

        $this->viewingTopic = $application;
        $this->topic_lecturer_notes = $application->lecturer_notes ?? '';
        $this->showTopicModal = true;
    }

    public function confirmTopicAccept(): void
    {
        $this->showTopicAcceptModal = true;
    }

    public function acceptTopicApplication(): void
    {
        if (!$this->viewingTopic || $this->viewingTopic->lecturer_id !== auth()->id()) {
            abort(403);
        }

        $service = app(TopicApplicationService::class);
        $result = $service->acceptApplication($this->viewingTopic->id, auth()->id(), $this->topic_lecturer_notes);

        session()->flash($result['success'] ? 'success' : 'error', $result['message']);
        
        if ($result['success']) {
            $this->showTopicModal = false;
            $this->showTopicAcceptModal = false;
            $this->reset('viewingTopic', 'topic_lecturer_notes');
        }
    }

    public function confirmTopicDecline(): void
    {
        $this->showTopicDeclineModal = true;
    }

    public function declineTopicApplication(): void
    {
        if (!$this->viewingTopic || $this->viewingTopic->lecturer_id !== auth()->id()) {
            abort(403);
        }

        $service = app(TopicApplicationService::class);
        $result = $service->declineApplication($this->viewingTopic->id, auth()->id(), $this->topic_lecturer_notes);

        session()->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->showTopicModal = false;
        $this->showTopicDeclineModal = false;
        $this->reset('viewingTopic', 'topic_lecturer_notes');
    }

    public function confirmTopicRelease(): void
    {
        $this->showTopicReleaseModal = true;
    }

    public function releaseTopicStudent(): void
    {
        if (!$this->viewingTopic || $this->viewingTopic->lecturer_id !== auth()->id()) {
            abort(403);
        }

        $service = app(TopicApplicationService::class);
        $result = $service->releaseStudent($this->viewingTopic->id, auth()->id());

        session()->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->showTopicModal = false;
        $this->showTopicReleaseModal = false;
        $this->reset('viewingTopic', 'topic_lecturer_notes');
    }

    public function confirmTopicReopen(): void
    {
        $this->showTopicReopenModal = true;
    }

    public function reopenTopicApplication(): void
    {
        if (!$this->viewingTopic || $this->viewingTopic->lecturer_id !== auth()->id()) {
            abort(403);
        }

        $service = app(TopicApplicationService::class);
        $result = $service->reopenApplication($this->viewingTopic->id, auth()->id());

        session()->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->showTopicModal = false;
        $this->showTopicReopenModal = false;
        $this->reset('viewingTopic', 'topic_lecturer_notes');
    }

    // Supervision Application Methods
    public function confirmSupervisionAccept($applicationId): void
    {
        $this->selectedSupervisionId = $applicationId;
        $this->showSupervisionAcceptModal = true;
    }

    public function acceptSupervisionApplication(SupervisionApplicationService $service): void
    {
        if ($this->selectedSupervisionId) {
            $service->acceptApplication($this->selectedSupervisionId, auth()->id());
            session()->flash('success', 'Application accepted successfully.');
            $this->showSupervisionAcceptModal = false;
            $this->selectedSupervisionId = null;
            $this->resetPage();
        }
    }

    public function confirmSupervisionDecline($applicationId): void
    {
        $this->selectedSupervisionId = $applicationId;
        $this->showSupervisionDeclineModal = true;
    }

    public function declineSupervisionApplication(SupervisionApplicationService $service): void
    {
        if ($this->selectedSupervisionId) {
            $service->declineApplication($this->selectedSupervisionId);
            session()->flash('success', 'Application declined.');
            $this->showSupervisionDeclineModal = false;
            $this->selectedSupervisionId = null;
            $this->resetPage();
        }
    }
};

?>

<div>
    <section class="w-full">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-xl mb-10 p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-3xl text-black dark:text-white font-bold">Applications</h1>
                    <p class="text-zinc-600 dark:text-zinc-400 mt-1">Review and manage student applications.</p>
                    @if ($displayPeriod)
                        <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <span class="text-sm text-blue-900 dark:text-blue-200">
                                Available Quota for {{ $displayPeriod->name }}: <strong>{{ $currentQuota }}</strong> students
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-zinc-200 dark:border-zinc-700 mb-6">
                <nav class="-mb-px flex space-x-8">
                    <button wire:click="$set('activeTab', 'supervision')" 
                        class="py-2 px-1 border-b-2 font-medium text-sm transition-colors cursor-pointer
                        {{ $activeTab === 'supervision' 
                            ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                            : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                        Supervisor Applications ({{ $supervisionCount }})
                    </button>
                    <button wire:click="$set('activeTab', 'topics')" 
                        class="py-2 px-1 border-b-2 font-medium text-sm transition-colors cursor-pointer
                        {{ $activeTab === 'topics' 
                            ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                            : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                        Topic Applications ({{ $topicCount }})
                    </button>
                </nav>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <select wire:model.live="filterStatus"
                    class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="accepted">Accepted</option>
                    <option value="declined">Declined</option>
                    @if($activeTab === 'topics')
                        <option value="quota_full">Quota Full</option>
                    @endif
                </select>
                <select wire:model.live="filterPeriod"
                    class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Periods</option>
                    @foreach($periods as $period)
                        <option value="{{ $period->id }}">{{ $period->name }}</option>
                    @endforeach
                </select>
                @if($activeTab === 'supervision')
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by student name or email..."
                        class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                @endif
            </div>

            <hr class="border-t border-zinc-200 dark:border-zinc-700 mb-6">

            @include('partials.session-messages')

            <!-- Supervision Applications Tab -->
            @if($activeTab === 'supervision')
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Student</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Period</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Thesis Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Applied</th>
                                @if ($filterStatus === 'pending')
                                    <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($supervisionApplications as $application)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $application->student->name }}</div>
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $application->student->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">{{ $application->period->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                            {{ $application->proposed_role == 0 ? 'Supervisor 1' : 'Supervisor 2' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                        @if ($application->student->thesis_title)
                                            <span class="line-clamp-2">{{ $application->student->thesis_title }}</span>
                                        @else
                                            <span class="italic text-zinc-400 dark:text-zinc-500">Not submitted yet</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">{{ $application->created_at->diffForHumans() }}</td>
                                    @if ($filterStatus === 'pending')
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end items-center gap-2">
                                                @php
                                                    $appQuota = auth()->user()->getAvailableCapacityForPeriod($application->period_id);
                                                @endphp
                                                <flux:button wire:click="confirmSupervisionAccept('{{ $application->id }}')" variant="primary" size="sm" class="cursor-pointer" :disabled="$appQuota <= 0">Accept</flux:button>
                                                <flux:button wire:click="confirmSupervisionDecline('{{ $application->id }}')" variant="danger" size="sm" class="cursor-pointer">Decline</flux:button>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $filterStatus === 'pending' ? '6' : '5' }}" class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                        No {{ $filterStatus ?: '' }} applications found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-6">{{ $supervisionApplications->links() }}</div>
            @endif

            <!-- Topic Applications Tab -->
            @if($activeTab === 'topics')
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
                            @forelse ($topicApplications as $application)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-white">
                                        <div class="font-medium">{{ $application->student->name }}</div>
                                        <div class="text-xs text-zinc-500">{{ $application->student->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                        <div>{{ Str::limit($application->topic->topic, 50) }}</div>
                                        <div class="text-xs text-zinc-400">Capacity: {{ $application->topic->student_quota }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">{{ $application->period->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($application->status === 'pending')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Pending</span>
                                        @elseif($application->status === 'accepted')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Accepted</span>
                                        @elseif($application->status === 'quota_full')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">Quota Full</span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Declined</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">{{ $application->created_at->diffForHumans() }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <flux:button wire:click="viewTopicApplication('{{ $application->id }}')" variant="ghost" size="sm" class="cursor-pointer">View</flux:button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">No applications found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-6">{{ $topicApplications->links() }}</div>
            @endif
        </div>
    </section>

    <!-- Topic Application Modal -->
    @if($showTopicModal && $viewingTopic)
        <flux:modal name="topic-modal" wire:model="showTopicModal" class="max-w-2xl w-full">
            <div class="space-y-6">
                <flux:heading size="lg">Application Details</flux:heading>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Student</label>
                        <p class="text-zinc-900 dark:text-white">{{ $viewingTopic->student->name }}</p>
                        <p class="text-sm text-zinc-500">{{ $viewingTopic->student->email }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Topic</label>
                        <p class="text-zinc-900 dark:text-white">{{ $viewingTopic->topic->topic }}</p>
                        @if($viewingTopic->topic->description)
                            <p class="text-sm text-zinc-500 mt-1">{{ $viewingTopic->topic->description }}</p>
                        @endif
                        <p class="text-sm text-amber-600 dark:text-amber-400 mt-1">
                            ⚠️ This topic requires {{ $viewingTopic->topic->student_quota }} student(s). Accepting will reduce your quota by {{ $viewingTopic->topic->student_quota }}.
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Period</label>
                        <p class="text-zinc-900 dark:text-white">{{ $viewingTopic->period->name }}</p>
                        @php
                            $capacity = auth()->user()->getAvailableCapacityForPeriod($viewingTopic->period_id);
                        @endphp
                        <p class="text-sm {{ $capacity >= $viewingTopic->topic->student_quota ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            Your available capacity: {{ $capacity }} student(s)
                        </p>
                    </div>
                    @if($viewingTopic->student_notes)
                        <div>
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Student Notes</label>
                            <p class="text-zinc-900 dark:text-white whitespace-pre-wrap">{{ $viewingTopic->student_notes }}</p>
                        </div>
                    @endif
                    <div>
                        <flux:label>Your Notes (Optional)</flux:label>
                        <textarea wire:model="topic_lecturer_notes" rows="3"
                            class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :disabled="$viewingTopic->status !== 'pending'"></textarea>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Status</label>
                        <p class="text-zinc-900 dark:text-white capitalize">{{ $viewingTopic->status }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    @if($viewingTopic->status === 'pending')
                        @php
                            $capacity = auth()->user()->getAvailableCapacityForPeriod($viewingTopic->period_id);
                        @endphp
                        <flux:button wire:click="confirmTopicDecline" variant="danger" class="cursor-pointer">Decline</flux:button>
                        <flux:button wire:click="confirmTopicAccept" variant="primary" class="cursor-pointer" :disabled="$capacity < $viewingTopic->topic->student_quota">Accept & Assign as Supervisor</flux:button>
                    @elseif($viewingTopic->status === 'accepted')
                        <flux:button wire:click="confirmTopicRelease" variant="danger" class="cursor-pointer">Release Student</flux:button>
                        <flux:button wire:click="$set('showTopicModal', false)" variant="ghost" class="cursor-pointer">Close</flux:button>
                    @elseif($viewingTopic->status === 'declined')
                        <flux:button wire:click="confirmTopicReopen" variant="primary" class="cursor-pointer">Reopen Application</flux:button>
                        <flux:button wire:click="$set('showTopicModal', false)" variant="ghost" class="cursor-pointer">Close</flux:button>
                    @else
                        <flux:button wire:click="$set('showTopicModal', false)" variant="ghost" class="cursor-pointer">Close</flux:button>
                    @endif
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- Topic Accept Modal -->
    @if($showTopicAcceptModal)
        <flux:modal name="topic-accept-modal" wire:model="showTopicAcceptModal" class="max-w-md">
            <div class="space-y-6">
                <flux:heading size="lg">Confirm Acceptance</flux:heading>
                <p class="text-zinc-600 dark:text-zinc-400">
                    Are you sure you want to accept this application? The student will be assigned as your supervisee and this action cannot be undone.
                </p>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="$set('showTopicAcceptModal', false)" class="cursor-pointer">Cancel</flux:button>
                    <flux:button wire:click="acceptTopicApplication" variant="primary" class="cursor-pointer">Confirm Accept</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- Topic Decline Modal -->
    @if($showTopicDeclineModal)
        <flux:modal name="topic-decline-modal" wire:model="showTopicDeclineModal" class="max-w-md">
            <div class="space-y-6">
                <flux:heading size="lg">Confirm Decline</flux:heading>
                <p class="text-zinc-600 dark:text-zinc-400">Are you sure you want to decline this application?</p>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="$set('showTopicDeclineModal', false)" class="cursor-pointer">Cancel</flux:button>
                    <flux:button wire:click="declineTopicApplication" variant="danger" class="cursor-pointer">Confirm Decline</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- Supervision Accept Modal -->
    @if($showSupervisionAcceptModal)
        <flux:modal name="supervision-accept-modal" wire:model="showSupervisionAcceptModal" class="max-w-md">
            <div class="space-y-6">
                <flux:heading size="lg">Confirm Acceptance</flux:heading>
                <p class="text-zinc-600 dark:text-zinc-400">
                    Are you sure you want to accept this student? This action cannot be undone and will update the student's status.
                </p>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="$set('showSupervisionAcceptModal', false)" class="cursor-pointer">Cancel</flux:button>
                    <flux:button wire:click="acceptSupervisionApplication" variant="primary" class="cursor-pointer">Confirm Accept</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- Supervision Decline Modal -->
    @if($showSupervisionDeclineModal)
        <flux:modal name="supervision-decline-modal" wire:model="showSupervisionDeclineModal" class="max-w-md">
            <div class="space-y-6">
                <flux:heading size="lg">Confirm Decline</flux:heading>
                <p class="text-zinc-600 dark:text-zinc-400">Are you sure you want to decline this application?</p>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="$set('showSupervisionDeclineModal', false)" class="cursor-pointer">Cancel</flux:button>
                    <flux:button wire:click="declineSupervisionApplication" variant="danger" class="cursor-pointer">Confirm Decline</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- Topic Release Modal -->
    @if($showTopicReleaseModal)
        <flux:modal name="topic-release-modal" wire:model="showTopicReleaseModal" class="max-w-md">
            <div class="space-y-6">
                <flux:heading size="lg">Confirm Release</flux:heading>
                <p class="text-zinc-600 dark:text-zinc-400">
                    Are you sure you want to release this student? This will:
                </p>
                <ul class="list-disc list-inside text-sm text-zinc-600 dark:text-zinc-400 space-y-1">
                    <li>Remove the student from your supervision</li>
                    <li>Reset student's thesis title and description</li>
                    <li>Restore topic quota (+1)</li>
                    <li>Restore your lecturer quota (+1)</li>
                    <li>Reopen pending applications that were marked as quota_full</li>
                </ul>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="$set('showTopicReleaseModal', false)" class="cursor-pointer">Cancel</flux:button>
                    <flux:button wire:click="releaseTopicStudent" variant="danger" class="cursor-pointer">Confirm Release</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- Topic Reopen Modal -->
    @if($showTopicReopenModal)
        <flux:modal name="topic-reopen-modal" wire:model="showTopicReopenModal" class="max-w-md">
            <div class="space-y-6">
                <flux:heading size="lg">Confirm Reopen</flux:heading>
                <p class="text-zinc-600 dark:text-zinc-400">
                    Are you sure you want to reopen this application? The status will change to:
                </p>
                <ul class="list-disc list-inside text-sm text-zinc-600 dark:text-zinc-400 space-y-1">
                    <li><strong>Pending</strong> - if you have sufficient capacity</li>
                    <li><strong>Quota Full</strong> - if you don't have enough capacity</li>
                </ul>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="$set('showTopicReopenModal', false)" class="cursor-pointer">Cancel</flux:button>
                    <flux:button wire:click="reopenTopicApplication" variant="primary" class="cursor-pointer">Confirm Reopen</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
