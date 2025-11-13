<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Services\StudentService;
use Illuminate\Support\Facades\Storage;

new #[Layout('components.layouts.lecturer')] class extends Component {
    public $activeTab = 'supervisor1';
    public $selectedPeriod = null;
    public $activePeriods = [];
    public $supervisor1Students = [];
    public $supervisor2Students = [];
    public $showPdfModal = false;
    public $pdfUrl = '';

    protected StudentService $studentService;

    public function boot(StudentService $studentService): void
    {
        $this->studentService = $studentService;
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
        $this->pdfUrl = Storage::url($filePath);
        $this->showPdfModal = true;
    }

    public function closePdfModal(): void
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
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

                            <!-- Latest Proposal Status -->
                            @if(isset($student['latest_proposal']) && $student['latest_proposal'])
                                <div class="mt-4 p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            Latest Proposal Update
                                        </h4>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ \Carbon\Carbon::parse($student['latest_proposal']['created_at'])->diffForHumans() }}
                                        </span>
                                    </div>
                                    @if($student['latest_proposal']['description'])
                                        <p class="text-sm text-zinc-600 dark:text-zinc-300 mb-2">
                                            <strong>Description:</strong> {{ $student['latest_proposal']['description'] }}
                                        </p>
                                    @endif
                                    <div class="flex items-center justify-between">
                                        @php
                                            $statusLabels = [
                                                0 => 'Pending',
                                                1 => 'Revision',
                                                2 => 'Approved by Supervisor',
                                                3 => 'Approved by Head'
                                            ];
                                            $statusColors = [
                                                0 => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                                1 => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                                2 => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                                3 => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$student['latest_proposal']['status']] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $statusLabels[$student['latest_proposal']['status']] ?? 'Unknown' }}
                                        </span>
                                        @if($student['latest_proposal']['file_path'])
                                            <button wire:click="viewPdf('{{ $student['latest_proposal']['file_path'] }}')"
                                                class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                                View Document
                                            </button>
                                        @endif
                                    </div>
                                    @if($student['latest_proposal']['comment'])
                                        <div class="mt-2 p-2 bg-zinc-100 dark:bg-zinc-600 rounded text-sm">
                                            <strong>Comment:</strong> {{ $student['latest_proposal']['comment'] }}
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                    <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                        No proposal submitted yet.
                                    </p>
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

    <!-- PDF Modal -->
    @if($showPdfModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            x-data x-show="true" x-transition>
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl w-full max-w-6xl h-5/6 flex flex-col"
                @click.away="$wire.closePdfModal()">
                <div class="flex justify-between items-center p-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">Document Viewer</h3>
                    <div class="flex space-x-2">
                        <a href="{{ $pdfUrl }}" download 
                           class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                            Download
                        </a>
                        <button wire:click="closePdfModal" 
                                class="px-3 py-1 bg-zinc-500 text-white rounded hover:bg-zinc-600 text-sm">
                            Close
                        </button>
                    </div>
                </div>
                <div class="flex-1 p-4">
                    <iframe src="{{ $pdfUrl }}" 
                            class="w-full h-full border-0 rounded"
                            frameborder="0">
                    </iframe>
                </div>
            </div>
        </div>
    @endif
</div>