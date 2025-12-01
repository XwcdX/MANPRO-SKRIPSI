<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Services\StudentService;
use App\Services\ProposalService;
use Illuminate\Support\Facades\Storage;

new #[Layout('components.layouts.lecturer')] class extends Component {
    public $selectedPeriod = null;
    public $activePeriods = [];
    public $divisionStudents = [];
    public $showPdfModal = false;
    public $pdfUrl = '';
    public $showDetailModal = false;
    public $selectedStudent = null;
    public $showAcceptModal = false;
    public $showDeclineModal = false;
    public $selectedProposal = null;
    public $comment = '';

    protected StudentService $studentService;
    protected ProposalService $proposalService;

    public function boot(StudentService $studentService, ProposalService $proposalService): void
    {
        $this->studentService = $studentService;
        $this->proposalService = $proposalService;
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
        $lecturer = auth()->user();
        
        $this->divisionStudents = \App\Models\Student::where('division_id', $lecturer->primary_division_id)
            ->when($this->selectedPeriod, function($query) {
                $query->whereHas('periods', function($q) {
                    $q->where('periods.id', $this->selectedPeriod);
                });
            })
            ->with(['latestProposal', 'history_proposals' => function($query) {
                $query->orderBy('created_at', 'desc');
            }, 'latestThesis', 'history_theses' => function($query) {
                $query->orderBy('created_at', 'desc');
            }])
            ->get()
            ->map(function($student) {
                $data = $student->toArray();
                $data['student_number'] = explode('@', $student->email)[0];
                return $data;
            })
            ->toArray();
    }

    public function updatedSelectedPeriod(): void
    {
        $this->loadStudents();
    }

    public function viewPdf($filePath): void
    {
        $this->pdfUrl = Storage::url($filePath);
        $this->showPdfModal = true;
    }

    public function viewDetails($studentData): void
    {
        $this->selectedStudent = $studentData;
        $this->showDetailModal = true;
    }

    public function confirmAccept($id, $type = 'proposal'): void
    {
        $this->selectedProposal = ['id' => $id, 'type' => $type];
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
            $thesis = \App\Models\HistoryThesis::findOrFail($this->selectedProposal['id']);
            $thesis->update(['status' => 3, 'comment' => $this->comment]);
            session()->flash('success', 'Thesis approved by division head.');
        } else {
            $proposal = \App\Models\HistoryProposal::findOrFail($this->selectedProposal['id']);
            $proposal->update(['status' => 3, 'comment' => $this->comment]);
            session()->flash('success', 'Proposal approved by division head.');
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
        } else {
            $this->proposalService->declineProposal($this->selectedProposal['id'], $this->comment);
        }
        session()->flash('success', 'Submission declined.');
        $this->showDeclineModal = false;
        $this->showDetailModal = false;
        $this->loadStudents();
    }
}; ?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">Division Students</h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Review submissions from students in your division</p>
        </div>
        
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

    @include('partials.session-messages')

    <div class="space-y-4">
        @if(count($divisionStudents) === 0)
            <div class="text-center py-12 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <flux:icon.user-group class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">No students found</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">No students assigned to your division yet.</p>
            </div>
        @else
            @foreach($divisionStudents as $student)
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                            <span class="text-sm font-medium text-blue-600 dark:text-blue-400">{{ strtoupper(substr($student['name'], 0, 2)) }}</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">{{ $student['name'] }}</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $student['email'] }} • {{ $student['student_number'] }}</p>
                        </div>
                    </div>

                    @php
                        $statusLabels = [0 => 'Pending', 1 => 'Revision', 2 => 'Approved by Supervisor', 3 => 'Approved by Head'];
                        $statusColors = [0 => 'bg-yellow-100 text-yellow-800', 1 => 'bg-orange-100 text-orange-800', 2 => 'bg-blue-100 text-blue-800', 3 => 'bg-green-100 text-green-800'];
                    @endphp
                    
                    @if(isset($student['latest_thesis']) && $student['latest_thesis'])
                        @php $thesis = $student['latest_thesis']; @endphp
                        <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Latest Thesis</h4>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$thesis['status']] }} mt-1 inline-block">{{ $statusLabels[$thesis['status']] }}</span>
                                </div>
                                <flux:button wire:click="viewDetails({{ json_encode($student) }})" size="sm" variant="ghost" class="cursor-pointer">View Details</flux:button>
                            </div>
                        </div>
                    @elseif(isset($student['latest_proposal']) && $student['latest_proposal'])
                        @php $proposal = $student['latest_proposal']; @endphp
                        <div class="p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Latest Proposal</h4>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$proposal['status']] }} mt-1 inline-block">{{ $statusLabels[$proposal['status']] }}</span>
                                </div>
                                <flux:button wire:click="viewDetails({{ json_encode($student) }})" size="sm" variant="ghost" class="cursor-pointer">View Details</flux:button>
                            </div>
                        </div>
                    @else
                        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">No submission yet.</p>
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    @if($showDetailModal && $selectedStudent)
        <flux:modal wire:model="showDetailModal" class="max-w-4xl">
            <flux:heading size="lg">{{ $selectedStudent['name'] }} - Submission History</flux:heading>
            
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
                                    @if($thesis['status'] == 2)
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
                                    @if($proposal['status'] == 2)
                                        <flux:button wire:click="confirmAccept('{{ $proposal['id'] }}', 'proposal')" size="sm" variant="primary" class="cursor-pointer">Accept</flux:button>
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
        <flux:modal wire:model="showAcceptModal" class="max-w-md">
            <flux:heading size="lg">Accept {{ $selectedProposal['type'] === 'thesis' ? 'Thesis' : 'Proposal' }}</flux:heading>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-2">Approve this as division head?</p>
            <flux:input wire:model="comment" label="Comment (Optional)" placeholder="Add a comment..." class="mt-4" />
            <div class="flex gap-2 mt-6">
                <flux:spacer />
                <flux:button wire:click="$set('showAcceptModal', false)" variant="ghost" class="cursor-pointer">Cancel</flux:button>
                <flux:button wire:click="acceptProposal" variant="primary" class="cursor-pointer">Accept</flux:button>
            </div>
        </flux:modal>
    @endif

    @if($showDeclineModal)
        <flux:modal wire:model="showDeclineModal" class="max-w-md">
            <flux:heading size="lg">Decline {{ $selectedProposal['type'] === 'thesis' ? 'Thesis' : 'Proposal' }}</flux:heading>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-2">Provide reason for declining.</p>
            <flux:textarea wire:model="comment" label="Reason (Required)" placeholder="Explain why..." rows="4" class="mt-4" required />
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
