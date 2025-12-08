<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\ThesisPresentation;
use App\Models\Period;
use App\Models\PeriodSchedule;
use App\Models\Student;
use App\Models\PresentationVenue;
use App\Models\Lecturer;
use App\Services\PresentationService;
use App\Services\AvailabilityService;
use Carbon\Carbon;

new #[Layout('components.layouts.lecturer')] class extends Component {
    use WithPagination;

    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public string $search = '';
    public string $filterPeriod = '';
    public string $filterType = '';
    public ?string $deletingId = null;

    public ?ThesisPresentation $editing = null;
    public ?string $period_id = null;
    public ?string $venue_id = null;
    public array $student_ids = [];
    public ?string $period_schedule_id = null;
    public string $presentation_date = '';
    public string $time_slot = '';
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


    public ?array $existingPresentation = null;

    public function updatedPeriodId(): void
    {
        $this->reset('venue_id', 'student_ids', 'period_schedule_id', 'presentation_date', 'time_slot', 'lead_examiner_id', 'examiner_ids', 'availableLecturers', 'existingPresentation');
    }

    public function updatedPeriodScheduleId(): void
    {
        $this->reset('presentation_date', 'time_slot', 'lead_examiner_id', 'examiner_ids', 'availableLecturers');
    }

    public function updatedPresentationDate(): void
    {
        $this->reset('time_slot', 'lead_examiner_id', 'examiner_ids', 'availableLecturers');
    }

    public function updatedVenueId(): void
    {
        $this->checkExistingPresentation();
    }

    public function updatedStudentIds(): void
    {
        if ($this->time_slot) {
            $this->loadAvailableLecturers();
        }
    }

    public function updatedTimeSlot(): void
    {
        $this->loadAvailableLecturers();
        $this->checkExistingPresentation();
    }

    public function checkExistingPresentation(): void
    {
        if (!$this->venue_id || !$this->presentation_date || !$this->time_slot) {
            $this->existingPresentation = null;
            return;
        }

        [$startTime, $endTime] = explode('-', $this->time_slot);
        $existing = $this->presentationService->findExistingPresentation(
            $this->venue_id,
            $this->presentation_date,
            $startTime,
            $endTime
        );

        if ($existing) {
            $this->existingPresentation = [
                'venue' => $existing['venue'],
                'date' => $existing['date'],
                'time' => $existing['time'],
            ];
            $this->lead_examiner_id = $existing['lead_examiner_id'];
            $this->examiner_ids = $existing['examiner_ids'];
        } else {
            $this->existingPresentation = null;
        }
    }

    public function loadAvailableLecturers(): void
    {
        if ($this->period_schedule_id && $this->presentation_date && $this->time_slot) {
            $excludeId = $this->editing && $this->editing->exists ? $this->editing->id : null;
            [$startTime, $endTime] = explode('-', $this->time_slot);

            $this->availableLecturers = $this->presentationService->getAvailableLecturers(
                $this->period_schedule_id,
                $this->presentation_date,
                $startTime,
                $endTime,
                $this->student_ids,
                $excludeId
            );
        } else {
            $this->availableLecturers = [];
        }
    }

    public function with(): array
    {
        $query = ThesisPresentation::with(['student', 'periodSchedule.period', 'venue', 'examiners.lecturer', 'leadExaminer.lecturer']);

        if ($this->search) {
            $query->whereHas('student', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterPeriod) {
            $query->whereHas('periodSchedule', function ($q) {
                $q->where('period_id', $this->filterPeriod);
            });
        }

        if ($this->filterType) {
            $query->whereHas('periodSchedule', function ($q) {
                $q->where('type', $this->filterType === 'proposal' ? 'proposal_hearing' : 'thesis_defense');
            });
        }

        $scheduledStudentIds = ThesisPresentation::when($this->editing && $this->editing->exists, function ($q) {
            $q->where('id', '!=', $this->editing->id);
        })
            ->when($this->period_id, function ($q) {
                $q->whereHas('periodSchedule', function ($query) {
                    $query->where('period_id', $this->period_id);
                });
            })
            ->pluck('student_id')
            ->toArray();

        return [
            'presentations' => $query->orderBy('presentation_date', 'desc')->paginate(15),
            'periods' => Period::notArchived()->where('start_date', '<=', Carbon::now())->where('end_date', '>=', Carbon::now())->orderBy('start_date', 'desc')->get(),
            'students' => $this->getAvailableStudents($scheduledStudentIds),
            'venues' => PresentationVenue::orderBy('name')->get(),
            'periodSchedules' => $this->getAvailablePeriodSchedules(),
            'timeSlots' => $this->getTimeSlots(),
            'minDate' => $this->getMinDate(),
            'maxDate' => $this->getMaxDate(),
        ];
    }

    private function getAvailableStudents(array $scheduledStudentIds): array
    {
        if (!$this->period_id) {
            return [];
        }

        return Student::where('status', 3)
            ->whereHas('periods', function ($q) {
                $q->where('periods.id', $this->period_id);
            })
            ->when(!empty($scheduledStudentIds), function ($q) use ($scheduledStudentIds) {
                $q->whereNotIn('id', $scheduledStudentIds);
            })
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    private function getAvailablePeriodSchedules(): array
    {
        if (!$this->period_id) {
            return [];
        }

        $now = Carbon::now();
        $schedules = PeriodSchedule::where('period_id', $this->period_id)->where('end_date', '>=', $now)->orderBy('start_date')->get();

        $result = [];
        $typeCount = ['proposal_hearing' => 0, 'thesis_defense' => 0];

        foreach ($schedules as $schedule) {
            $typeCount[$schedule->type]++;
            $label = $schedule->type === 'proposal_hearing' ? "Proposal Hearing {$typeCount[$schedule->type]}" : "Thesis Defense {$typeCount[$schedule->type]}";

            $result[] = [
                'id' => $schedule->id,
                'label' => $label,
                'type' => $schedule->type,
                'start_date' => $schedule->start_date,
                'end_date' => $schedule->end_date,
            ];
        }

        return $result;
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
        $this->period_id = $presentation->periodSchedule->period_id;
        $this->venue_id = $presentation->venue_id;
        $this->student_ids = [$presentation->student_id];
        $this->period_schedule_id = $presentation->period_schedule_id;
        $this->presentation_date = Carbon::parse($presentation->presentation_date)->format('Y-m-d');
        $this->time_slot = substr($presentation->start_time, 0, 5) . '-' . substr($presentation->end_time, 0, 5);
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
            'venue_id' => 'required|uuid|exists:presentation_venues,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'uuid|exists:students,id',
            'period_schedule_id' => 'required|uuid|exists:period_schedules,id',
            'presentation_date' => 'required|date',
            'time_slot' => 'required|string',
            'lead_examiner_id' => 'required|uuid|exists:lecturers,id',
            'examiner_ids' => 'nullable|array',
            'examiner_ids.*' => 'uuid|exists:lecturers,id',
        ]);

        [$startTime, $endTime] = explode('-', $this->time_slot);

        if ($this->editing && $this->editing->exists) {
            $data = [
                'period_schedule_id' => $this->period_schedule_id,
                'venue_id' => $this->venue_id,
                'student_id' => $this->student_ids[0],
                'presentation_date' => $this->presentation_date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'notes' => $this->notes,
                'lead_examiner_id' => $this->lead_examiner_id,
                'examiner_ids' => $this->examiner_ids,
            ];
            $this->presentationService->updatePresentation($this->editing, $data);
            session()->flash('success', 'Presentation updated successfully.');
        } else {
            foreach ($this->student_ids as $studentId) {
                $data = [
                    'period_schedule_id' => $this->period_schedule_id,
                    'venue_id' => $this->venue_id,
                    'student_id' => $studentId,
                    'presentation_date' => $this->presentation_date,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'notes' => $this->notes,
                    'lead_examiner_id' => $this->lead_examiner_id,
                    'examiner_ids' => $this->examiner_ids,
                ];
                $this->presentationService->createPresentation($data);
            }
            $count = count($this->student_ids);
            session()->flash('success', "{$count} presentation(s) scheduled successfully.");
        }
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
        $this->reset('period_id', 'venue_id', 'student_ids', 'period_schedule_id', 'presentation_date', 'time_slot', 'notes', 'lead_examiner_id', 'examiner_ids', 'availableLecturers', 'existingPresentation');
    }

    private function getTimeSlots(): array
    {
        if (!$this->period_id || !$this->period_schedule_id) {
            return [];
        }

        $period = Period::find($this->period_id);
        $schedule = PeriodSchedule::find($this->period_schedule_id);

        if (!$period || !$schedule) {
            return [];
        }

        // Generate time slots based on schedule type
        $type = $schedule->type === 'proposal_hearing' ? 'proposal_hearing' : 'thesis';
        return app(AvailabilityService::class)->generateSimpleTimeSlots($period, $type);
    }

    private function getMinDate(): ?string
    {
        if (!$this->period_schedule_id) {
            return null;
        }

        $schedule = PeriodSchedule::find($this->period_schedule_id);
        return $schedule ? Carbon::parse($schedule->start_date)->format('Y-m-d') : null;
    }

    private function getMaxDate(): ?string
    {
        if (!$this->period_schedule_id) {
            return null;
        }

        $schedule = PeriodSchedule::find($this->period_schedule_id);
        return $schedule ? Carbon::parse($schedule->end_date)->format('Y-m-d') : null;
    }
};

?>

<div>
    <section class="w-full">
        <div
            class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-xl mb-10 p-6 sm:p-8">
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
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by student name..."
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
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Student</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Date & Time</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Venue</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Type</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Examiners</th>
                            <th class="px-6 py-3 relative"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($presentations as $presentation)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-6 py-4 text-sm">
                                    <div class="font-medium text-zinc-900 dark:text-white">
                                        {{ $presentation->student->name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $presentation->periodSchedule->period->name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    <div>{{ Carbon::parse($presentation->presentation_date)->format('d M Y') }}
                                    </div>
                                    <div class="text-xs">{{ substr($presentation->start_time, 0, 5) }} -
                                        {{ substr($presentation->end_time, 0, 5) }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    <div>{{ $presentation->venue->name }}</div>
                                    <div class="text-xs">{{ $presentation->venue->location }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-xs font-medium capitalize {{ $presentation->periodSchedule->type === 'proposal_hearing' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' }}">
                                        {{ $presentation->periodSchedule->type === 'proposal_hearing' ? 'Proposal' : 'Thesis' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if ($presentation->leadExaminer)
                                        <div class="text-zinc-900 dark:text-white font-medium">⭐
                                            {{ $presentation->leadExaminer->lecturer->name }}</div>
                                    @endif
                                    @foreach ($presentation->examiners()->where('is_lead_examiner', false)->get() as $examiner)
                                        <div class="text-zinc-500 dark:text-zinc-400 text-xs">
                                            {{ $examiner->lecturer->name }}</div>
                                    @endforeach
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end items-center gap-2">
                                        <flux:button wire:click="edit('{{ $presentation->id }}')" variant="ghost"
                                            size="sm" class="cursor-pointer">
                                            Edit
                                        </flux:button>
                                        <flux:button wire:click="confirmDelete('{{ $presentation->id }}')"
                                            variant="danger" size="sm" class="cursor-pointer">
                                            Delete
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6"
                                    class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
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

    <flux:modal name="presentation-modal" wire:model.live="showModal" class="max-w-2xl">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">
                {{ $editing && $editing->exists ? 'Edit Presentation' : 'Schedule New Presentation' }}</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:select wire:model.live="period_id" label="Period" required>
                    <option value="">Select Period</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}">{{ $period->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="venue_id" label="Venue" required :disabled="!$period_id">
                    <option value="">Select Venue</option>
                    @foreach ($venues as $venue)
                        <option value="{{ $venue->id }}">{{ $venue->name }} - {{ $venue->location }}</option>
                    @endforeach
                </flux:select>

                <div class="md:col-span-2">
                    @if($existingPresentation)
                        <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                <strong>📍 Existing presentation found:</strong><br>
                                {{ $existingPresentation['venue'] }} • {{ $existingPresentation['date'] }} • {{ $existingPresentation['time'] }}<br>
                                <span class="text-xs">Examiners have been auto-filled. Select students to join this session.</span>
                            </p>
                        </div>
                    @endif

                    <div x-data="{ open: false, search: '' }" @click.away="open = false" class="relative">
                        <flux:label>Students ({{ count($student_ids) }} selected)</flux:label>
                        <input type="text" x-model="search" @focus="open = true" @click="open = true" 
                            placeholder="Search students..." 
                            class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" 
                            {{ !$period_id ? 'disabled' : '' }}>
                        <div x-show="open" x-transition class="absolute z-50 w-full mt-1 max-h-60 overflow-y-auto border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 shadow-lg">
                            <div class="p-2 space-y-1">
                                @forelse($students as $student)
                                    <label x-show="'{{ strtolower($student['name']) }}'.includes(search.toLowerCase())" 
                                        class="flex items-center gap-2 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700 p-2 rounded">
                                        <input type="checkbox" wire:model.live="student_ids" value="{{ $student['id'] }}" class="rounded cursor-pointer">
                                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $student['name'] }}</span>
                                    </label>
                                @empty
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400 p-2">No students available</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <flux:select wire:model.live="period_schedule_id" label="Type" required :disabled="!$period_id">
                        <option value="">Select Type</option>
                        @foreach ($periodSchedules as $schedule)
                            <option value="{{ $schedule['id'] }}">{{ $schedule['label'] }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:input wire:model.live="presentation_date" type="date" label="Presentation Date" required
                    :disabled="!$period_schedule_id" min="{{ $minDate }}" max="{{ $maxDate }}" />

                <div>
                    <flux:label>Time Slot</flux:label>
                    <select wire:model.live="time_slot" required @disabled(!$presentation_date)
                        class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Time Slot</option>
                        @foreach ($timeSlots as $slot)
                            <option value="{{ $slot }}">{{ $slot }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <flux:select wire:model.live="lead_examiner_id" label="Lead Examiner (⭐)" required
                        :disabled="!$time_slot">
                        <option value="">Select Lead Examiner</option>
                        @foreach ($availableLecturers as $lecturer)
                            <option value="{{ $lecturer['id'] }}">{{ $lecturer['name'] }}</option>
                        @endforeach
                    </flux:select>
                    @if (empty($availableLecturers) && $time_slot)
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">No available lecturers for this time
                            slot</p>
                    @elseif(!$time_slot)
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Select time slot first</p>
                    @endif
                </div>

                <div class="md:col-span-2">
                    <flux:label>Examiners</flux:label>
                    <div
                        class="mt-2 space-y-2 max-h-48 overflow-y-auto border border-zinc-300 dark:border-zinc-600 rounded-lg p-3">
                        @forelse ($availableLecturers as $lecturer)
                            @if ($lecturer['id'] !== $lead_examiner_id)
                                <label
                                    class="flex items-center gap-2 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 p-2 rounded">
                                    <input type="checkbox" wire:model="examiner_ids" value="{{ $lecturer['id'] }}"
                                        class="rounded cursor-pointer">
                                    <span
                                        class="text-sm text-zinc-700 dark:text-zinc-300">{{ $lecturer['name'] }}</span>
                                </label>
                            @endif
                        @empty
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">No available lecturers. Select time
                                slot first.</p>
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
                <flux:button type="button" variant="ghost" wire:click="$set('showModal', false)">Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="delete-modal" wire:model="showDeleteModal">
        <div class="space-y-6">
            <flux:heading size="lg">Confirm Deletion</flux:heading>
            <p class="text-zinc-600 dark:text-zinc-400">Are you sure you want to delete this presentation? This action
                cannot be undone.</p>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:button type="button" variant="ghost" wire:click="$set('showDeleteModal', false)">Cancel
                </flux:button>
                <flux:button wire:click="deletePresentation" variant="danger">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
