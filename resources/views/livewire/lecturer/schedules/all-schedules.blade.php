<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\ThesisPresentation;
use App\Models\Period;
use App\Models\PeriodSchedule;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

new #[Layout('components.layouts.lecturer')] 
class extends Component {
    public ?string $selectedPeriod = null;
    public string $selectedType = '';
    public array $dates = [];
    public array $timeSlots = [];
    public array $scheduleData = [];
    public ?string $selectedSlot = null;
    public array $slotPresentations = [];

    public function updatedSelectedPeriod(): void
    {
        $this->reset('selectedType', 'dates', 'timeSlots', 'scheduleData', 'selectedSlot', 'slotPresentations');
    }

    public function updatedSelectedType(): void
    {
        $this->reset('dates', 'timeSlots', 'scheduleData', 'selectedSlot', 'slotPresentations');
        if ($this->selectedType) {
            $this->loadSchedule();
        }
    }

    public function loadSchedule(): void
    {
        $schedule = PeriodSchedule::find($this->selectedType);
        if (!$schedule) return;

        $period = Period::find($this->selectedPeriod);
        if (!$period) return;

        $this->dates = app(AvailabilityService::class)->getDateRangeForSchedule($schedule);
        $this->timeSlots = app(AvailabilityService::class)->generateSimpleTimeSlots($period, $schedule->type);

        $presentations = ThesisPresentation::with(['student', 'venue', 'leadExaminer.lecturer', 'examiners.lecturer'])
            ->where('period_schedule_id', $this->selectedType)
            ->get();

        foreach ($presentations as $p) {
            $date = Carbon::parse($p->presentation_date)->format('Y-m-d');
            $timeSlot = substr($p->start_time, 0, 5) . '-' . substr($p->end_time, 0, 5);
            $key = $date . '_' . $timeSlot;
            
            if (!isset($this->scheduleData[$key])) {
                $this->scheduleData[$key] = [];
            }
            $this->scheduleData[$key][] = $p;
        }
    }

    public function openSlot(string $key): void
    {
        $this->selectedSlot = $key;
        $this->slotPresentations = $this->scheduleData[$key] ?? [];
    }

    public function with(): array
    {
        return [
            'periods' => app(AvailabilityService::class)->getPeriodsWithSchedules(),
            'availableTypes' => $this->getAvailableTypes(),
        ];
    }

    private function getAvailableTypes(): array
    {
        if (!$this->selectedPeriod) return [];

        $period = Period::find($this->selectedPeriod);
        if (!$period) return [];

        $today = now()->format('Y-m-d');
        $types = [];
        
        $proposalSchedules = $period->schedules()->where('type', 'proposal_hearing')->orderBy('start_date')->get();
        $thesisSchedules = $period->schedules()->where('type', 'thesis_defense')->orderBy('start_date')->get();
        
        foreach ($proposalSchedules as $index => $schedule) {
            $label = 'Proposal Hearing ' . ($index + 1) . ' (' . 
                    Carbon::parse($schedule->start_date)->format('d M') . ' - ' . 
                    Carbon::parse($schedule->end_date)->format('d M') . ')';
            $types[$schedule->id] = $label;
        }
        
        foreach ($thesisSchedules as $index => $schedule) {
            $label = 'Thesis Defense ' . ($index + 1) . ' (' . 
                    Carbon::parse($schedule->start_date)->format('d M') . ' - ' . 
                    Carbon::parse($schedule->end_date)->format('d M') . ')';
            $types[$schedule->id] = $label;
        }
        
        return $types;
    }
};

?>

<div>
    <section class="w-full">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-xl mb-10 p-6 sm:p-8">
            <div class="mb-6">
                <h1 class="text-3xl text-black dark:text-white font-bold">All Schedules</h1>
                <p class="text-zinc-600 dark:text-zinc-400 mt-1">View all presentation schedules in calendar format.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Select Period</label>
                    <select wire:model.live="selectedPeriod" class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white">
                        <option value="">Choose a period</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}">{{ $period->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if($selectedPeriod)
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Schedule Type</label>
                        <select wire:model.live="selectedType" class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white">
                            <option value="">Select Type</option>
                            @foreach($availableTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            @if($selectedPeriod && count($dates) > 0)
                <div class="overflow-x-auto">
                    <div class="inline-block min-w-full align-middle">
                        <div class="overflow-hidden border border-zinc-200 dark:border-zinc-700 rounded-lg">
                            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                                <thead class="bg-zinc-50 dark:bg-zinc-800">
                                    <tr>
                                        <th class="sticky left-0 z-10 bg-zinc-50 dark:bg-zinc-800 px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase">Time</th>
                                        @foreach($dates as $date)
                                            <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase whitespace-nowrap">
                                                {{ Carbon::parse($date)->format('D') }}<br>
                                                {{ Carbon::parse($date)->format('d M') }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @foreach($timeSlots as $time)
                                        <tr>
                                            <td class="sticky left-0 z-10 bg-white dark:bg-zinc-900 px-4 py-2 text-sm font-medium text-zinc-900 dark:text-white whitespace-nowrap border-r border-zinc-200 dark:border-zinc-700">
                                                {{ $time }}
                                            </td>
                                            @foreach($dates as $date)
                                                @php
                                                    $key = $date . '_' . $time;
                                                    $isBreak = str_contains($time, 'Break');
                                                    $hasSchedule = isset($scheduleData[$key]);
                                                    $hasPendingAction = false;
                                                    if ($hasSchedule) {
                                                        foreach ($scheduleData[$key] as $p) {
                                                            $presentationDateTime = Carbon::parse($p->presentation_date)->setTimeFromTimeString($p->end_time);
                                                            if ($presentationDateTime->isPast() && $p->student->status === 3) {
                                                                $hasPendingAction = true;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                <td class="px-2 py-2">
                                                    @if($isBreak)
                                                        <div class="w-full h-12 rounded bg-zinc-300 dark:bg-zinc-700" title="Break Time"></div>
                                                    @elseif($hasSchedule)
                                                        <div 
                                                            wire:click="openSlot('{{ $key }}')"
                                                            class="w-full h-12 rounded cursor-pointer flex items-center justify-center text-xs text-white font-medium {{ $hasPendingAction ? 'bg-red-500 hover:bg-red-600' : 'bg-yellow-500 hover:bg-yellow-600' }}"
                                                            title="{{ count($scheduleData[$key]) }} presentation(s)">
                                                            {{ count($scheduleData[$key]) }}
                                                            @if($hasPendingAction)
                                                                <span class="ml-1">⚠️</span>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <div class="w-full h-12 rounded bg-zinc-200 dark:bg-zinc-800"></div>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-6 text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 bg-zinc-200 dark:bg-zinc-800 rounded"></div>
                        <span class="text-zinc-600 dark:text-zinc-400">No Schedule</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 bg-yellow-500 rounded"></div>
                        <span class="text-zinc-600 dark:text-zinc-400">Has Schedule</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 bg-red-500 rounded"></div>
                        <span class="text-zinc-600 dark:text-zinc-400">Pending Action ⚠️</span>
                    </div>
                </div>
            @elseif($selectedPeriod)
                <div class="text-center py-12 text-zinc-500 dark:text-zinc-400">
                    No schedule configured for this period.
                </div>
            @else
                <div class="text-center py-12 text-zinc-500 dark:text-zinc-400">
                    Please select a period to view schedules.
                </div>
            @endif
        </div>
    </section>

    <flux:modal name="slot-modal" wire:model="selectedSlot" class="max-w-4xl">
        <div class="space-y-6">
            <flux:heading size="lg">Presentations at {{ $selectedSlot }}</flux:heading>
            
            @if(count($slotPresentations) > 0)
                <div class="space-y-4">
                    @foreach($slotPresentations as $p)
                        @php
                            $presentationDateTime = Carbon::parse($p->presentation_date)->setTimeFromTimeString($p->end_time);
                            $isPastAndPending = $presentationDateTime->isPast() && $p->student->status === 3;
                        @endphp
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 {{ $isPastAndPending ? 'border-red-500 dark:border-red-500' : '' }}">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h4 class="font-semibold text-black dark:text-white">{{ $p->student->name }}</h4>
                                        @if($isPastAndPending)
                                            <span class="px-2 py-1 text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-full">
                                                ⚠️ Pending Decision
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400"><strong>Venue:</strong> {{ $p->venue->name }} - {{ $p->venue->location }}</p>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400"><strong>Topic:</strong> {{ $p->student->thesis_title ?: 'Not set' }}</p>
                                    <div class="mt-2">
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-1"><strong>Examiners:</strong></p>
                                        <div class="flex flex-wrap gap-2">
                                            @if($p->leadExaminer)
                                                <span class="px-2 py-1 text-xs bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded-full">
                                                    ⭐ {{ $p->leadExaminer->lecturer->name }}
                                                </span>
                                            @endif
                                            @foreach($p->examiners()->where('is_lead_examiner', false)->get() as $examiner)
                                                <span class="px-2 py-1 text-xs bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 rounded-full">
                                                    {{ $examiner->lecturer->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="flex justify-end">
                <flux:button x-on:click="$wire.selectedSlot = null" variant="primary" class="cursor-pointer">Close</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
