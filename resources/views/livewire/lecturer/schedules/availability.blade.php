<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Services\AvailabilityService;
use App\Services\PeriodService;
use Carbon\Carbon;

new #[Layout('components.layouts.lecturer')] 
class extends Component {
    public ?string $selectedPeriod = null;
    public string $selectedType = 'proposal_hearing';
    public array $availability = [];
    public array $dates = [];
    public array $timeSlots = [];
    public bool $hasUnsavedChanges = false;

    public function mount(): void
    {
        $this->generateTimeSlots();
    }

    public function generateTimeSlots(): void
    {
        if (!$this->selectedPeriod || !$this->selectedType) {
            $this->timeSlots = [];
            return;
        }

        $period = app(PeriodService::class)->findPeriod($this->selectedPeriod);
        $schedule = \App\Models\PeriodSchedule::find($this->selectedType);
        if (!$period || !$schedule) {
            $this->timeSlots = [];
            return;
        }

        $this->timeSlots = app(AvailabilityService::class)->generateSimpleTimeSlots($period, $schedule->type);
    }

    public function updatedSelectedPeriod(): void
    {
        $this->selectedType = '';
        $this->dates = [];
        $this->availability = [];
        $this->timeSlots = [];
    }

    public function updatedSelectedType(): void
    {
        // Force reset
        $this->reset(['dates', 'availability', 'timeSlots']);
        
        if ($this->selectedType) {
            $this->generateTimeSlots();
            $this->loadAvailability();
        }
    }

    public function loadAvailability(): void
    {
        if (!$this->selectedPeriod || !$this->selectedType) {
            $this->dates = [];
            $this->availability = [];
            return;
        }

        $service = app(AvailabilityService::class);
        $schedule = \App\Models\PeriodSchedule::find($this->selectedType);
        if (!$schedule) return;

        $this->dates = $service->getDateRangeForSchedule($schedule);
        
        if (empty($this->dates)) {
            $this->availability = [];
            return;
        }

        $this->availability = $service->loadAvailability(
            auth()->id(),
            $this->selectedType,
            $this->dates,
            $this->timeSlots
        );
    }

    public function getLockedSlots(): array
    {
        if (!$this->selectedType) return [];

        $presentations = \App\Models\ThesisPresentation::where('period_schedule_id', $this->selectedType)
            ->whereHas('examiners', fn($q) => $q->where('lecturer_id', auth()->id()))
            ->with('student')
            ->get();

        $locked = [];
        foreach ($presentations as $p) {
            $date = Carbon::parse($p->presentation_date)->format('Y-m-d');
            $timeSlot = substr($p->start_time, 0, 5) . '-' . substr($p->end_time, 0, 5);
            $key = $date . '_' . $timeSlot;
            
            if (!isset($locked[$key])) {
                $locked[$key] = [];
            }
            $locked[$key][] = $p->student->name;
        }
        
        return $locked;
    }



    public function saveChanges(array $availability): void
    {
        $schedule = \App\Models\PeriodSchedule::find($this->selectedType);
        if (!$schedule) return;

        app(AvailabilityService::class)->saveAvailability(
            auth()->id(),
            $this->selectedType,
            $schedule->type,
            $availability
        );

        $this->availability = $availability;
        $this->dispatch('open-modal', 'save-success-modal');
    }

    public function with(): array
    {
        return [
            'periods' => app(AvailabilityService::class)->getPeriodsWithSchedules(),
            'availableTypes' => $this->getAvailableTypes(),
            'lockedSlots' => $this->getLockedSlots(),
        ];
    }

    private function getAvailableTypes(): array
    {
        if (!$this->selectedPeriod) {
            return [];
        }

        $period = app(PeriodService::class)->findPeriod($this->selectedPeriod);
        if (!$period) {
            return [];
        }

        $today = now()->format('Y-m-d');
        $types = [];
        
        $proposalSchedules = $period->schedules()
            ->where('type', 'proposal_hearing')
            ->where('start_date', '>=', $today)
            ->orderBy('start_date')
            ->get();
        
        $thesisSchedules = $period->schedules()
            ->where('type', 'thesis_defense')
            ->where('start_date', '>=', $today)
            ->orderBy('start_date')
            ->get();
        
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
                <h1 class="text-3xl text-black dark:text-white font-bold">My Availability</h1>
                <p class="text-zinc-600 dark:text-zinc-400 mt-1">Set your availability for proposal hearings and thesis defenses.</p>
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

            <div wire:key="schedule-{{ $selectedType }}" x-data="{ 
                availability: @js($availability),
                lockedSlots: @js($lockedSlots),
                hasChanges: false,
                toggleSlot(key) {
                    if (this.lockedSlots[key]) return;
                    this.availability[key] = !this.availability[key];
                    this.hasChanges = true;
                },
                saveChanges() {
                    $wire.saveChanges(this.availability);
                    this.hasChanges = false;
                }
            }">
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
                                                @endphp
                                                <td class="px-2 py-2">
                                                    @if($isBreak)
                                                        <div class="w-full h-12 rounded bg-zinc-300 dark:bg-zinc-700" title="Break Time"></div>
                                                    @else
                                                        <div 
                                                            @click="toggleSlot('{{ $key }}')"
                                                            :class="lockedSlots['{{ $key }}'] ? 'bg-yellow-500' : (availability['{{ $key }}'] ? 'bg-green-500 hover:bg-green-600 cursor-pointer' : 'bg-red-500 hover:bg-red-600 cursor-pointer')"
                                                            class="w-full h-12 rounded flex items-center justify-center text-xs text-white font-medium px-1"
                                                            :title="lockedSlots['{{ $key }}'] ? 'Locked: ' + lockedSlots['{{ $key }}'].join(', ') : (availability['{{ $key }}'] ? 'Available' : 'Busy')">
                                                            <span x-show="lockedSlots['{{ $key }}']" x-text="lockedSlots['{{ $key }}'] ? lockedSlots['{{ $key }}'].length + ' student(s)' : ''"></span>
                                                        </div>
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

                <div class="mt-4 flex items-center justify-between">
                    <div class="flex items-center gap-6 text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 bg-green-500 rounded"></div>
                            <span class="text-zinc-600 dark:text-zinc-400">Available</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 bg-red-500 rounded"></div>
                            <span class="text-zinc-600 dark:text-zinc-400">Busy</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 bg-yellow-500 rounded"></div>
                            <span class="text-zinc-600 dark:text-zinc-400">Locked (Assigned)</span>
                        </div>
                    </div>
                    <flux:button x-show="hasChanges" @click="saveChanges()" variant="primary" class="cursor-pointer">Save Changes</flux:button>
                </div>
            @elseif($selectedPeriod)
                <div class="text-center py-12 text-zinc-500 dark:text-zinc-400">
                    No {{ $selectedType === 'proposal_hearing' ? 'proposal hearing' : 'thesis defense' }} dates configured for this period.
                </div>
            @else
                <div class="text-center py-12 text-zinc-500 dark:text-zinc-400">
                    Please select a period to manage your availability.
                </div>
            @endif
            </div>
        </div>
    </section>

    <flux:modal name="save-success-modal" class="max-w-md">
        <div class="space-y-6">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div>
                    <flux:heading size="lg">Success</flux:heading>
                    <p class="text-zinc-600 dark:text-zinc-400 text-sm mt-1">Your availability has been saved successfully.</p>
                </div>
            </div>
            <div class="flex justify-end">
                <flux:button x-on:click="$dispatch('close-modal', 'save-success-modal')" variant="primary" class="cursor-pointer">OK</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="unsaved-changes-modal" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">Unsaved Changes</flux:heading>
            <p class="text-zinc-600 dark:text-zinc-400">You have unsaved changes. Do you want to save before leaving?</p>
            <div class="flex justify-end gap-2">
                <flux:button x-on:click="$dispatch('close-modal', 'unsaved-changes-modal'); window.location.href = window.pendingNavigation;" variant="ghost" class="cursor-pointer">Leave Without Saving</flux:button>
                <flux:button x-on:click="$dispatch('close-modal', 'unsaved-changes-modal'); $el.closest('[x-data]').__x.$data.saveChanges(); setTimeout(() => window.location.href = window.pendingNavigation, 500);" variant="primary" class="cursor-pointer">Save & Leave</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
