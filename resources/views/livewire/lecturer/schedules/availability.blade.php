<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Period;
use App\Models\LecturerAvailability;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

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
        if (!$this->selectedPeriod) {
            $this->timeSlots = [];
            return;
        }

        $period = Period::find($this->selectedPeriod);
        if (!$period) {
            $this->timeSlots = [];
            return;
        }

        $this->timeSlots = [];
        $start = Carbon::parse($period->schedule_start_time ?? '07:30');
        $end = Carbon::parse($period->schedule_end_time ?? '18:00');
        
        while ($start->lt($end)) {
            $this->timeSlots[] = $start->format('H:i');
            $start->addMinutes(30);
        }
    }

    public function updatedSelectedPeriod(): void
    {
        $this->generateTimeSlots();
        $this->loadAvailability();
    }

    public function updatedSelectedType(): void
    {
        $this->loadAvailability();
    }

    public function loadAvailability(): void
    {
        if (!$this->selectedPeriod) {
            $this->dates = [];
            $this->availability = [];
            return;
        }

        $period = Period::find($this->selectedPeriod);
        if (!$period) return;

        if ($this->selectedType === 'proposal_hearing') {
            $startDate = $period->proposal_hearing_start;
            $endDate = $period->proposal_hearing_end;
        } else {
            $startDate = $period->thesis_start;
            $endDate = $period->thesis_end;
        }

        if (!$startDate || !$endDate) {
            $this->dates = [];
            $this->availability = [];
            return;
        }

        $this->dates = [];
        $dateRange = CarbonPeriod::create($startDate, $endDate);
        foreach ($dateRange as $date) {
            if ($date->dayOfWeek !== 0) {
                $this->dates[] = $date->format('Y-m-d');
            }
        }

        $existing = LecturerAvailability::where('lecturer_id', auth()->id())
            ->where('period_id', $this->selectedPeriod)
            ->where('type', $this->selectedType)
            ->get();

        $this->availability = [];
        foreach ($existing as $item) {
            $key = Carbon::parse($item->date)->format('Y-m-d') . '_' . $item->time_slot;
            $this->availability[$key] = $item->is_available;
        }

        foreach ($this->dates as $date) {
            foreach ($this->timeSlots as $time) {
                $key = $date . '_' . $time;
                if (!isset($this->availability[$key])) {
                    $this->availability[$key] = true;
                }
            }
        }
    }



    public function saveChanges(array $availability): void
    {
        foreach ($availability as $key => $isAvailable) {
            [$date, $time] = explode('_', $key);
            
            LecturerAvailability::updateOrCreate(
                [
                    'lecturer_id' => auth()->id(),
                    'period_id' => $this->selectedPeriod,
                    'type' => $this->selectedType,
                    'date' => $date,
                    'time_slot' => $time,
                ],
                [
                    'is_available' => $isAvailable,
                ]
            );
        }

        $this->availability = $availability;
        $this->dispatch('open-modal', 'save-success-modal');
    }

    public function with(): array
    {
        return [
            'periods' => Period::notArchived()
                ->whereNotNull('proposal_hearing_start')
                ->orWhereNotNull('thesis_start')
                ->orderBy('start_date', 'desc')
                ->get(),
        ];
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

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Schedule Type</label>
                    <select wire:model.live="selectedType" class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white">
                        <option value="proposal_hearing">Proposal Hearing</option>
                        <option value="thesis">Thesis Defense</option>
                    </select>
                </div>
            </div>

            <div x-data="{ 
                availability: @js($availability),
                hasChanges: false,
                toggleSlot(key) {
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
                                                    $isAvailable = $availability[$key] ?? false;
                                                @endphp
                                                <td class="px-2 py-2">
                                                    @php $key = $date . '_' . $time; @endphp
                                                    <div 
                                                        @click="toggleSlot('{{ $key }}')"
                                                        :class="availability['{{ $key }}'] ? 'bg-green-500 hover:bg-green-600' : 'bg-red-500 hover:bg-red-600'"
                                                        class="w-full h-12 rounded cursor-pointer"
                                                        :title="availability['{{ $key }}'] ? 'Available' : 'Busy'">
                                                    </div>
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
