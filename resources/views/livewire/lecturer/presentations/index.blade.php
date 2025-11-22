<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\ThesisPresentation;
use App\Models\Period;
use App\Models\Student;
use App\Models\PresentationVenue;
use App\Models\Lecturer;
use App\Services\PresentationService;
use App\Services\AvailabilityService;
use Carbon\Carbon;

new #[Layout('components.layouts.lecturer')] 
class extends Component {
    use WithPagination;

    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public string $search = '';
    public string $filterPeriod = '';
    public string $filterType = '';
    public ?string $deletingId = null;

    public ?ThesisPresentation $editing = null;
    public ?string $period_id = null;
    public ?string $student_id = null;
    public ?string $venue_id = null;
    public string $presentation_date = '';
    public string $start_time = '';
    public string $end_time = '';
    public string $presentation_type = 'proposal';
    public string $notes = '';
    public ?string $lead_examiner_id = null;
    public array $examiner_ids = [];

    public array $availableLecturers = [];

    protected PresentationService $presentationService;

    public function boot(PresentationService $presentationService): void
    {
        $this->presentationService = $presentationService;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterPeriod(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatedPeriodId(): void
    {
        $this->presentation_date = '';
        $this->loadAvailableLecturers();
    }

    public function updatedPresentationType(): void
    {
        $this->presentation_date = '';
    }

    public function updatedPresentationDate(): void
    {
        $this->loadAvailableLecturers();
    }

    public function updatedStartTime(): void
    {
        $this->loadAvailableLecturers();
    }

    public function updatedEndTime(): void
    {
        $this->loadAvailableLecturers();
    }

    public function loadAvailableLecturers(): void
    {
        if ($this->period_id && $this->presentation_date && $this->start_time && $this->end_time) {
            $excludeId = ($this->editing && $this->editing->exists) ? $this->editing->id : null;
            
            $lecturerIds = $this->presentationService->getAvailableLecturers(
                $this->period_id,
                $this->presentation_date,
                $this->start_time,
                $this->end_time,
                $excludeId
            );
            $this->availableLecturers = Lecturer::whereIn('id', $lecturerIds)
                ->orWhere('id', auth()->id())
                ->orderBy('name')
                ->get()
                ->toArray();
        } else {
            $this->availableLecturers = [];
        }
    }

    public function with(): array
    {
        $query = ThesisPresentation::with(['student', 'period', 'venue', 'examiners.lecturer', 'leadExaminer.lecturer']);

        if ($this->search) {
            $query->whereHas('student', function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterPeriod) {
            $query->where('period_id', $this->filterPeriod);
        }

        if ($this->filterType) {
            $query->where('presentation_type', $this->filterType);
        }

        $scheduledStudentIds = ThesisPresentation::when($this->editing && $this->editing->exists, function($q) {
            $q->where('id', '!=', $this->editing->id);
        })->pluck('student_id')->toArray();
        
        return [
            'presentations' => $query->orderBy('presentation_date', 'desc')->paginate(15),
            'periods' => Period::notArchived()
                ->where('start_date', '<=', Carbon::now())
                ->where('end_date', '>=', Carbon::now())
                ->orderBy('start_date', 'desc')
                ->get(),
            'students' => Student::where('status', 3)
                ->when(!empty($scheduledStudentIds), function($q) use ($scheduledStudentIds) {
                    $q->whereNotIn('id', $scheduledStudentIds);
                })
                ->orderBy('name')
                ->get(),
            'venues' => PresentationVenue::orderBy('name')->get(),
            'timeSlots' => $this->getTimeSlots(),
            'minDate' => $this->getMinDate(),
            'maxDate' => $this->getMaxDate(),
            'availableTypes' => $this->getAvailableTypes(),
        ];
    }

    public function create(): void
    {
        $this->resetInput();
        $this->editing = new ThesisPresentation();
        $this->showModal = true;
    }

    public function edit(ThesisPresentation $presentation): void
    {
        $this->editing = $presentation;
        $this->period_id = $presentation->period_id;
        $this->student_id = $presentation->student_id;
        $this->venue_id = $presentation->venue_id;
        $this->presentation_date = Carbon::parse($presentation->presentation_date)->format('Y-m-d');
        $this->start_time = $presentation->start_time;
        $this->end_time = $presentation->end_time;
        $this->presentation_type = $presentation->presentation_type;
        $this->notes = $presentation->notes ?? '';
        $this->lead_examiner_id = $presentation->leadExaminer?->lecturer_id;
        $this->examiner_ids = $presentation->examiners()->where('is_lead_examiner', false)->pluck('lecturer_id')->toArray();
        $this->loadAvailableLecturers();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'period_id' => 'required|uuid|exists:periods,id',
            'student_id' => 'required|uuid|exists:students,id',
            'venue_id' => 'required|uuid|exists:presentation_venues,id',
            'presentation_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'presentation_type' => 'required|in:proposal,thesis',
            'lead_examiner_id' => 'required|uuid|exists:lecturers,id',
            'examiner_ids' => 'nullable|array',
            'examiner_ids.*' => 'uuid|exists:lecturers,id',
        ]);

        $data = [
            'period_id' => $this->period_id,
            'student_id' => $this->student_id,
            'venue_id' => $this->venue_id,
            'presentation_date' => $this->presentation_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'presentation_type' => $this->presentation_type,
            'notes' => $this->notes,
            'lead_examiner_id' => $this->lead_examiner_id,
            'examiner_ids' => $this->examiner_ids,
        ];

        if ($this->editing && $this->editing->exists) {
            $this->presentationService->updatePresentation($this->editing, $data);
        } else {
            $this->presentationService->createPresentation($data);
        }

        session()->flash('success', 'Presentation scheduled successfully.');
        $this->showModal = false;
        $this->resetPage();
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function deletePresentation(): void
    {
        if ($this->deletingId) {
            $this->presentationService->deletePresentation($this->deletingId);
            session()->flash('success', 'Presentation deleted successfully.');
        }
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    private function resetInput(): void
    {
        $this->resetErrorBag();
        $this->reset('period_id', 'student_id', 'venue_id', 'presentation_date', 'start_time', 'end_time', 'presentation_type', 'notes', 'lead_examiner_id', 'examiner_ids', 'availableLecturers');
        $this->presentation_type = 'proposal';
    }

    private function getTimeSlots(): array
    {
        if (!$this->period_id) {
            return [];
        }

        $period = Period::find($this->period_id);
        if (!$period) {
            return [];
        }

        return app(AvailabilityService::class)->generateTimeSlots($period);
    }

    private function getMinDate(): ?string
    {
        if (!$this->period_id) {
            return null;
        }

        $period = Period::find($this->period_id);
        if (!$period) {
            return null;
        }

        if ($this->presentation_type === 'proposal') {
            return $period->proposal_hearing_start ? Carbon::parse($period->proposal_hearing_start)->format('Y-m-d') : null;
        } else {
            return $period->thesis_start ? Carbon::parse($period->thesis_start)->format('Y-m-d') : null;
        }
    }

    private function getMaxDate(): ?string
    {
        if (!$this->period_id) {
            return null;
        }

        $period = Period::find($this->period_id);
        if (!$period) {
            return null;
        }

        if ($this->presentation_type === 'proposal') {
            return $period->proposal_hearing_end ? Carbon::parse($period->proposal_hearing_end)->format('Y-m-d') : null;
        } else {
            return $period->thesis_end ? Carbon::parse($period->thesis_end)->format('Y-m-d') : null;
        }
    }

    private function getAvailableTypes(): array
    {
        if (!$this->period_id) {
            return [];
        }

        $period = Period::find($this->period_id);
        if (!$period) {
            return [];
        }

        $types = [];
        if ($period->proposal_hearing_start && $period->proposal_hearing_end) {
            $types[] = 'proposal';
        }
        if ($period->thesis_start && $period->thesis_end) {
            $types[] = 'thesis';
        }
        return $types;
    }
};

?>

<div>
    <section class="w-full">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-xl mb-10 p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-3xl text-black dark:text-white font-bold">Manage Presentations</h1>
                    <p class="text-zinc-600 dark:text-zinc-400 mt-1">Schedule and manage thesis presentations.</p>
                </div>
                <flux:button wire:click="create" variant="primary" class="cursor-pointer">
                    Schedule Presentation
                </flux:button>
            </div>

            <div class="mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <input wire:model.live.debounce.300ms="search" type="text"
                    placeholder="Search by student name..."
                    class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                
                <select wire:model.live="filterPeriod"
                    class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Periods</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}">{{ $period->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterType"
                    class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="proposal">Proposal</option>
                    <option value="thesis">Thesis</option>
                </select>
            </div>

            <hr class="border-t border-zinc-200 dark:border-zinc-700 mb-6">

            @include('partials.session-messages')

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Venue</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Examiners</th>
                            <th class="px-6 py-3 relative"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($presentations as $presentation)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-6 py-4 text-sm">
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ $presentation->student->name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $presentation->period->name }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    <div>{{ \Carbon\Carbon::parse($presentation->presentation_date)->format('d M Y') }}</div>
                                    <div class="text-xs">{{ substr($presentation->start_time, 0, 5) }} - {{ substr($presentation->end_time, 0, 5) }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    <div>{{ $presentation->venue->name }}</div>
                                    <div class="text-xs">{{ $presentation->venue->location }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium capitalize {{ $presentation->presentation_type === 'proposal' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' }}">
                                        {{ $presentation->presentation_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if($presentation->leadExaminer)
                                        <div class="text-zinc-900 dark:text-white font-medium">⭐ {{ $presentation->leadExaminer->lecturer->name }}</div>
                                    @endif
                                    @foreach($presentation->examiners()->where('is_lead_examiner', false)->get() as $examiner)
                                        <div class="text-zinc-500 dark:text-zinc-400 text-xs">{{ $examiner->lecturer->name }}</div>
                                    @endforeach
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end items-center gap-2">
                                        <flux:button wire:click="edit('{{ $presentation->id }}')" variant="ghost" size="sm" class="cursor-pointer">
                                            Edit
                                        </flux:button>
                                        <flux:button wire:click="confirmDelete('{{ $presentation->id }}')" variant="danger" size="sm" class="cursor-pointer">
                                            Delete
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    No presentations scheduled.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $presentations->links() }}
            </div>
        </div>
    </section>

    @if ($showModal)
        <flux:modal name="presentation-modal" wire:model="showModal" class="max-w-3xl">
            <form wire:submit.prevent="save" class="space-y-6">
                <flux:heading size="lg">
                    {{ $editing && $editing->exists ? 'Edit Presentation' : 'Schedule New Presentation' }}
                </flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model.live="period_id" label="Period" required>
                        <option value="">Select Period</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}">{{ $period->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="student_id" label="Student" required>
                        <option value="">Select Student</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}">{{ $student->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="venue_id" label="Venue" required>
                        <option value="">Select Venue</option>
                        @foreach ($venues as $venue)
                            <option value="{{ $venue->id }}">{{ $venue->name }} - {{ $venue->location }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="presentation_type" label="Type" required>
                        @foreach($availableTypes as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model.live="presentation_date" type="date" label="Presentation Date" required 
                        :min="$minDate" :max="$maxDate" />

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <flux:label>Start Time</flux:label>
                            <select wire:model.live="start_time" required
                                class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Time</option>
                                @foreach ($timeSlots as $slot)
                                    <option value="{{ $slot }}">{{ $slot }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <flux:label>End Time</flux:label>
                            <select wire:model.live="end_time" required
                                class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Time</option>
                                @foreach ($timeSlots as $slot)
                                    <option value="{{ $slot }}">{{ $slot }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <flux:select wire:model.live="lead_examiner_id" label="Lead Examiner (⭐)" required>
                            <option value="">Select Lead Examiner</option>
                            @foreach ($availableLecturers as $lecturer)
                                <option value="{{ $lecturer['id'] }}">{{ $lecturer['name'] }}</option>
                            @endforeach
                        </flux:select>
                        @if(empty($availableLecturers))
                            <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Select date and time to see available lecturers</p>
                        @endif
                    </div>

                    <div class="md:col-span-2">
                        <flux:label>Examiners</flux:label>
                        <div class="mt-2 space-y-2 max-h-48 overflow-y-auto border border-zinc-300 dark:border-zinc-600 rounded-lg p-3">
                            @forelse ($availableLecturers as $lecturer)
                                @if($lecturer['id'] !== $lead_examiner_id)
                                    <label class="flex items-center gap-2 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 p-2 rounded">
                                        <input type="checkbox" wire:model="examiner_ids" value="{{ $lecturer['id'] }}" class="rounded cursor-pointer">
                                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $lecturer['name'] }}</span>
                                    </label>
                                @endif
                            @empty
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">No available lecturers. Select date and time first.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <flux:label>Notes</flux:label>
                        <textarea wire:model="notes" rows="3"
                            class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
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
                <flux:heading size="lg">Confirm Deletion</flux:heading>
                
                <p class="text-zinc-600 dark:text-zinc-400">
                    Are you sure you want to delete this presentation? This action cannot be undone.
                </p>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="$set('showDeleteModal', false)" class="cursor-pointer">
                        Cancel
                    </flux:button>
                    <flux:button wire:click="deletePresentation" variant="danger" class="cursor-pointer">
                        Delete
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
