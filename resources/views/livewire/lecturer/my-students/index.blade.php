<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Services\StudentService;
use App\Services\ProposalService;
use Illuminate\Support\Facades\Storage;

new #[Layout('components.layouts.lecturer')] class extends Component {
    public $activeTab = 'supervisor1';
    public $selectedPeriod = null;
    public $activePeriods = [];
    public $supervisor1Students = [];
    public $supervisor2Students = [];
    public $showPdfModal = false;
    public $pdfUrl = '';
    public $showDetailModal = false;
    public $selectedStudent = null;
    public $showAcceptModal = false;
    public $showDeclineModal = false;
    public $selectedProposal = null;
    public $comment = '';
    public $selectedDivision = null;
    public $divisions = [];

    protected StudentService $studentService;
    protected ProposalService $proposalService;

    public function boot(StudentService $studentService, ProposalService $proposalService): void
    {
        $this->studentService = $studentService;
        $this->proposalService = $proposalService;
        $this->divisions = \App\Models\Division::orderBy('name')->get()->toArray();
    }

    public function mount(): void
    {
        $this->loadActivePeriods();
        $this->loadStudents();
    }

    public function loadActivePeriods(): void
    {
        $this->activePeriods = $this->studentService->getActivePeriods();
        if (!empty($this->activePeriods) && !$this->selectedPeriod) {
            $this->selectedPeriod = $this->activePeriods[0]['id'];
        }
    }

    public function loadStudents(): void
    {
        $lecturerId = auth()->id();
        
        $this->supervisor1Students = $this->studentService->getSupervisor1Students($lecturerId, $this->selectedPeriod);
        $this->supervisor2Students = $this->studentService->getSupervisor2Students($lecturerId, $this->selectedPeriod);
    }

    public function setActiveTab($tab): void
    {
        $this->activeTab = $tab;
    }

    public function updatedSelectedPeriod(): void
    {
        $this->loadStudents();
    }

    public function viewPdf($filePath): void
    {
        $this->pdfUrl = url(Storage::url($filePath));
        $this->showPdfModal = true;
    }

    public function closePdfModal(): void
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
    }

    public function viewDetails($studentData): void
    {
        $this->selectedStudent = $studentData;
        $this->showDetailModal = true;
    }

    public function confirmAccept($id, $type = 'proposal', $studentDivisionId = null): void
    {
        $this->selectedProposal = ['id' => $id, 'type' => $type];
        $this->selectedDivision = $studentDivisionId;
        $this->comment = '';
        $this->showAcceptModal = true;
    }

    public function confirmDecline($id, $type = 'proposal'): void
    {
        $this->selectedProposal = ['id' => $id, 'type' => $type];
        $this->comment = '';
        $this->showDeclineModal = true;
    }

    public function acceptProposal(): void
    {
        if ($this->selectedProposal['type'] === 'thesis') {
            $this->proposalService->acceptThesis($this->selectedProposal['id'], $this->comment);
            session()->flash('success', 'Thesis accepted successfully.');
        } else {
            $this->validate(['selectedDivision' => 'required|uuid|exists:divisions,id']);
            $this->proposalService->acceptProposal($this->selectedProposal['id'], $this->selectedDivision, $this->comment);
            session()->flash('success', 'Proposal accepted and assigned to division.');
        }
        $this->showAcceptModal = false;
        $this->showDetailModal = false;
        $this->loadStudents();
    }

    public function declineProposal(): void
    {
        $this->validate(['comment' => 'required|string|min:10']);
        if ($this->selectedProposal['type'] === 'thesis') {
            $this->proposalService->declineThesis($this->selectedProposal['id'], $this->comment);
            session()->flash('success', 'Thesis declined. Student will be notified.');
        } else {
            $this->proposalService->declineProposal($this->selectedProposal['id'], $this->comment);
            session()->flash('success', 'Proposal declined. Student will be notified.');
        }
        $this->showDeclineModal = false;
        $this->showDetailModal = false;
        $this->loadStudents();
    }
}; ?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">My Students</h1>
        
        <!-- Period Filter -->
        @if(count($activePeriods) > 0)
            <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Period:</label>
                <select wire:model.live="selectedPeriod" 
                    class="rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm">
                    @foreach($activePeriods as $period)
                        <option value="{{ $period['id'] }}">{{ $period['name'] }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="setActiveTab('supervisor1')"
                class="py-2 px-1 border-b-2 font-medium text-sm transition-colors
                {{ $activeTab === 'supervisor1' 
                    ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                    : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                Supervisor 1 Students ({{ count($supervisor1Students) }})
            </button>
            <button wire:click="setActiveTab('supervisor2')"
                class="py-2 px-1 border-b-2 font-medium text-sm transition-colors
                {{ $activeTab === 'supervisor2' 
                    ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                    : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                Supervisor 2 Students ({{ count($supervisor2Students) }})
            </button>
        </nav>
    </div>



    <!-- Students List -->
    <div class="space-y-4">
        @php
            $students = $activeTab === 'supervisor1' ? $supervisor1Students : $supervisor2Students;
        @endphp

        @if(count($students) === 0)
            <div class="text-center py-12">
                <flux:icon.user-group class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">No students found</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    You don't have any students as {{ $activeTab === 'supervisor1' ? 'Supervisor 1' : 'Supervisor 2' }} yet.
                </p>
            </div>
        @else
            @foreach($students as $student)
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                        <span class="text-sm font-medium text-blue-600 dark:text-blue-400">
                                            {{ strtoupper(substr($student['name'], 0, 2)) }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $student['name'] }}
                                    </h3>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $student['email'] }} • {{ $student['student_number'] }}
                                    </p>
                                </div>
                            </div>

                            <!-- Latest Proposal/Thesis Status -->
                            @php
                                $statusLabels = [0 => 'Pending', 1 => 'Revision', 2 => 'Approved by Supervisor', 3 => 'Approved by Head'];
                                $statusColors = [0 => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200', 1 => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200', 2 => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200', 3 => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'];
                            @endphp
                            
                            @if(isset($student['latest_thesis']) && $student['latest_thesis'])
                                @php $thesis = $student['latest_thesis']; @endphp
                                <div class="mt-4 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Latest Thesis</h4>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ \Carbon\Carbon::parse($thesis['created_at'])->diffForHumans() }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$thesis['status']] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $statusLabels[$thesis['status']] ?? 'Unknown' }}
                                        </span>
                                        <flux:button wire:click="viewDetails({{ json_encode($student) }})" size="sm" variant="ghost" class="cursor-pointer">View Details</flux:button>
                                    </div>
                                </div>
                            @elseif(isset($student['latest_proposal']) && $student['latest_proposal'])
                                @php $proposal = $student['latest_proposal']; @endphp
                                <div class="mt-4 p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Latest Proposal</h4>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ \Carbon\Carbon::parse($proposal['created_at'])->diffForHumans() }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$proposal['status']] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $statusLabels[$proposal['status']] ?? 'Unknown' }}
                                        </span>
                                        <flux:button wire:click="viewDetails({{ json_encode($student) }})" size="sm" variant="ghost" class="cursor-pointer">View Details</flux:button>
                                    </div>
                                </div>
                            @else
                                <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                    <p class="text-sm text-yellow-700 dark:text-yellow-300">No submission yet.</p>
                                </div>
                            @endif
                        </div>

                        <!-- Role Badge -->
                        <div class="flex-shrink-0 ml-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                {{ $activeTab === 'supervisor1' 
                                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' 
                                    : 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200' }}">
                                {{ $activeTab === 'supervisor1' ? 'Supervisor 1' : 'Supervisor 2' }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    @if($showDetailModal && $selectedStudent)
        <flux:modal wire:model="showDetailModal" class="max-w-6xl">
            <flux:heading size="lg">{{ $selectedStudent['name'] }} - Submission History</flux:heading>
            
            @include('partials.session-messages')

            @php
                $statusLabels = [0 => 'Pending', 1 => 'Revision', 2 => 'Approved by Supervisor', 3 => 'Approved by Head'];
                $statusColors = [0 => 'bg-yellow-100 text-yellow-800', 1 => 'bg-orange-100 text-orange-800', 2 => 'bg-blue-100 text-blue-800', 3 => 'bg-green-100 text-green-800'];
            @endphp

            @if(!empty($selectedStudent['history_theses']))
                <div class="mt-4">
                    <h3 class="text-md font-semibold text-purple-700 dark:text-purple-400 mb-2">Thesis Submissions</h3>
                    <div class="space-y-3">
                        @foreach($selectedStudent['history_theses'] as $thesis)
                            <div class="border border-purple-300 dark:border-purple-600 rounded-lg p-4 bg-purple-50 dark:bg-purple-900/10">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$thesis['status']] }}">{{ $statusLabels[$thesis['status']] }}</span>
                                    <span class="text-xs text-zinc-500">{{ \Carbon\Carbon::parse($thesis['created_at'])->format('d M Y H:i') }}</span>
                                </div>
                                @if($thesis['description'])
                                    <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2"><strong>Description:</strong> {{ $thesis['description'] }}</p>
                                @endif
                                @if($thesis['comment'])
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-2"><strong>Comment:</strong> {{ $thesis['comment'] }}</p>
                                @endif
                                <div class="flex gap-2 mt-3">
                                    @if($thesis['file_path'])
                                        <flux:button wire:click="viewPdf('{{ $thesis['file_path'] }}')" size="sm" variant="ghost" class="cursor-pointer">View PDF</flux:button>
                                    @endif
                                    @if($thesis['status'] == 0 && $activeTab === 'supervisor1')
                                        <flux:button wire:click="confirmAccept('{{ $thesis['id'] }}', 'thesis')" size="sm" variant="primary" class="cursor-pointer">Accept</flux:button>
                                        <flux:button wire:click="confirmDecline('{{ $thesis['id'] }}', 'thesis')" size="sm" variant="danger" class="cursor-pointer">Decline</flux:button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(!empty($selectedStudent['history_proposals']))
                <div class="mt-4">
                    <h3 class="text-md font-semibold text-blue-700 dark:text-blue-400 mb-2">Proposal Submissions</h3>
                    <div class="space-y-3">
                        @foreach($selectedStudent['history_proposals'] as $proposal)
                            <div class="border border-zinc-300 dark:border-zinc-600 rounded-lg p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$proposal['status']] }}">{{ $statusLabels[$proposal['status']] }}</span>
                                    <span class="text-xs text-zinc-500">{{ \Carbon\Carbon::parse($proposal['created_at'])->format('d M Y H:i') }}</span>
                                </div>
                                @if($proposal['description'])
                                    <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2"><strong>Description:</strong> {{ $proposal['description'] }}</p>
                                @endif
                                @if($proposal['comment'])
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-2"><strong>Comment:</strong> {{ $proposal['comment'] }}</p>
                                @endif
                                <div class="flex gap-2 mt-3">
                                    @if($proposal['file_path'])
                                        <flux:button wire:click="viewPdf('{{ $proposal['file_path'] }}')" size="sm" variant="ghost" class="cursor-pointer">View PDF</flux:button>
                                    @endif
                                    @if($proposal['status'] == 0 && $activeTab === 'supervisor1')
                                        <flux:button wire:click="confirmAccept('{{ $proposal['id'] }}', 'proposal', '{{ $selectedStudent['division_id'] ?? null }}')" size="sm" variant="primary" class="cursor-pointer">Accept</flux:button>
                                        <flux:button wire:click="confirmDecline('{{ $proposal['id'] }}', 'proposal')" size="sm" variant="danger" class="cursor-pointer">Decline</flux:button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex gap-2 mt-6">
                <flux:spacer />
                <flux:button wire:click="$set('showDetailModal', false)" variant="ghost" class="cursor-pointer">Close</flux:button>
            </div>
        </flux:modal>
    @endif

    @if($showAcceptModal)
        <flux:modal wire:model="showAcceptModal" class="max-w-2xl">
            <flux:heading size="lg">Accept {{ $selectedProposal['type'] === 'thesis' ? 'Thesis' : 'Proposal' }}</flux:heading>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-2">{{ $selectedProposal['type'] === 'thesis' ? 'Accept this thesis submission?' : 'Select division and accept this proposal.' }}</p>
            
            @if($selectedProposal['type'] === 'proposal')
                <flux:select wire:model="selectedDivision" label="Assign to Division" required class="mt-4">
                    <option value="">Select Division</option>
                    @foreach($divisions as $division)
                        <option value="{{ $division['id'] }}">{{ $division['name'] }}</option>
                    @endforeach
                </flux:select>
                @error('selectedDivision') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            @endif
            
            <flux:textarea wire:model="comment" label="Comment (Optional)" placeholder="Add a comment..." rows="4" class="mt-4" />
            <div class="flex gap-2 mt-6">
                <flux:spacer />
                <flux:button wire:click="$set('showAcceptModal', false)" variant="ghost" class="cursor-pointer">Cancel</flux:button>
                <flux:button wire:click="acceptProposal" variant="primary" class="cursor-pointer">Accept</flux:button>
            </div>
        </flux:modal>
    @endif

    @if($showDeclineModal)
        <flux:modal wire:model="showDeclineModal" class="max-w-2xl">
            <flux:heading size="lg">Decline Proposal</flux:heading>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-2">Please provide a reason for declining this proposal.</p>
            <flux:textarea wire:model="comment" label="Reason (Required)" placeholder="Explain why you're declining..." rows="4" class="mt-4" required />
            @error('comment') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            <div class="flex gap-2 mt-6">
                <flux:spacer />
                <flux:button wire:click="$set('showDeclineModal', false)" variant="ghost" class="cursor-pointer">Cancel</flux:button>
                <flux:button wire:click="declineProposal" variant="danger" class="cursor-pointer">Decline</flux:button>
            </div>
        </flux:modal>
    @endif

    @if($showPdfModal)
        <flux:modal wire:model="showPdfModal" class="max-w-6xl">
            <div class="flex justify-between items-center mb-4">
                <flux:heading size="lg">Document Viewer</flux:heading>
                <a href="{{ $pdfUrl }}" download class="text-sm text-blue-600 hover:underline">Download</a>
            </div>
            <iframe src="{{ $pdfUrl }}" class="w-full h-[600px] border-0 rounded"></iframe>
        </flux:modal>
    @endif
</div>