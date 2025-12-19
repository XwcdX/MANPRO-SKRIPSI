<?php

use function Livewire\Volt\{state, layout, rules, with, uses};
use Livewire\WithPagination;
use App\Models\Period;
use App\Services\PeriodService;
use Carbon\Carbon;

layout('components.layouts.lecturer');

uses([WithPagination::class]);

state([
    'showModal' => false,
    'search' => '',
    'editing' => null,
    'start_date' => '',
    'end_date' => '',
    'proposal_schedules' => [],
    'thesis_schedules' => [],
    'proposal_schedule_start_time' => '08:00',
    'proposal_schedule_end_time' => '18:00',
    'proposal_slot_duration' => 45,
    'thesis_schedule_start_time' => '08:00',
    'thesis_schedule_end_time' => '18:00',
    'thesis_slot_duration' => 45,
    'break_start_time' => '12:00',
    'break_end_time' => '13:00',
    'default_quota' => 12,
    'showArchiveConfirmModal' => false,
    'archivingPeriodId' => null,
    'showDeleteConfirmModal' => false,
    'deletingPeriodId' => null,
    'serverToday' => fn() => now()->format('Y-m-d'),
]);

rules(
    fn() => [
        'start_date' => 'required|date',
        'end_date' => 'required|date|after:start_date',
        'proposal_schedules.*.start_date' => 'required|date|after_or_equal:start_date|before_or_equal:end_date',
        'proposal_schedules.*.end_date' => 'required|date|after:proposal_schedules.*.start_date|before_or_equal:end_date',
        'proposal_schedules.*.deadline' => 'required|date|before_or_equal:proposal_schedules.*.start_date',
        'thesis_schedules.*.start_date' => 'required|date|after_or_equal:start_date|before_or_equal:end_date',
        'thesis_schedules.*.end_date' => 'required|date|after:thesis_schedules.*.start_date|before_or_equal:end_date',
        'thesis_schedules.*.deadline' => 'required|date|before_or_equal:thesis_schedules.*.start_date',
        'proposal_schedule_start_time' => 'required|date_format:H:i',
        'proposal_schedule_end_time' => 'required|date_format:H:i|after:proposal_schedule_start_time',
        'proposal_slot_duration' => 'required|integer|min:15|max:120',
        'thesis_schedule_start_time' => 'required|date_format:H:i',
        'thesis_schedule_end_time' => 'required|date_format:H:i|after:thesis_schedule_start_time',
        'thesis_slot_duration' => 'required|integer|min:15|max:120',
        'break_start_time' => 'required|date_format:H:i',
        'break_end_time' => 'required|date_format:H:i|after:break_start_time',
        'default_quota' => 'required|integer|min:1|max:50',
    ],
);

$validateScheduleCollision = function () {
    foreach ($this->proposal_schedules as $index => $schedule) {
        if (!empty($schedule['start_date']) && !empty($schedule['end_date'])) {
            if ($this->checkDateCollision($schedule['start_date'], $schedule['end_date'], $index, 'proposal')) {
                $this->addError("proposal_schedules.{$index}.start_date", 'This date range overlaps with another schedule.');
            }
        }
    }
    
    foreach ($this->thesis_schedules as $index => $schedule) {
        if (!empty($schedule['start_date']) && !empty($schedule['end_date'])) {
            if ($this->checkDateCollision($schedule['start_date'], $schedule['end_date'], $index, 'thesis')) {
                $this->addError("thesis_schedules.{$index}.start_date", 'This date range overlaps with another schedule.');
            }
        }
    }
};

with(
    fn() => [
        'periods' => Period::where('name', 'like', '%' . $this->search . '%')
            ->orderBy('start_date', 'desc')
            ->paginate(10),
    ],
);



$updatingSearch = fn() => $this->resetPage();

$confirmArchive = function ($periodId) {
    $this->archivingPeriodId = $periodId;
    $this->showArchiveConfirmModal = true;
};

$archivePeriod = function (PeriodService $service) {
    if ($this->archivingPeriodId) {
        $service->archivePeriod($this->archivingPeriodId);
        session()->flash('success', 'Period has been archived.');
    }
    $this->showArchiveConfirmModal = false;
    $this->resetPage();
};

$create = function () {
    $this->resetErrorBag();
    $this->reset();
    $this->editing = new Period();
    $this->default_quota = 12;
    $this->proposal_schedule_start_time = '08:00';
    $this->proposal_schedule_end_time = '18:00';
    $this->proposal_slot_duration = 45;
    $this->thesis_schedule_start_time = '08:00';
    $this->thesis_schedule_end_time = '18:00';
    $this->thesis_slot_duration = 45;
    $this->break_start_time = '12:00';
    $this->break_end_time = '13:00';
    $this->proposal_schedules = [];
    $this->thesis_schedules = [];
    $this->showModal = true;
};

$edit = function (Period $period) {
    $this->resetErrorBag();
    $this->reset();

    $this->editing = $period;
    $this->start_date = Carbon::parse($period->start_date)->format('Y-m-d');
    $this->end_date = Carbon::parse($period->end_date)->format('Y-m-d');
    
    $this->proposal_schedules = $period->schedules()->where('type', 'proposal_hearing')->get()->map(function($schedule) {
        return [
            'id' => $schedule->id,
            'start_date' => Carbon::parse($schedule->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse($schedule->end_date)->format('Y-m-d'),
            'deadline' => Carbon::parse($schedule->deadline)->format('Y-m-d'),
        ];
    })->toArray();
    
    $this->thesis_schedules = $period->schedules()->where('type', 'thesis_defense')->get()->map(function($schedule) {
        return [
            'id' => $schedule->id,
            'start_date' => Carbon::parse($schedule->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse($schedule->end_date)->format('Y-m-d'),
            'deadline' => Carbon::parse($schedule->deadline)->format('Y-m-d'),
        ];
    })->toArray();
    
    $this->proposal_schedule_start_time = $period->proposal_schedule_start_time ? substr($period->proposal_schedule_start_time, 0, 5) : '08';
    $this->proposal_schedule_end_time = $period->proposal_schedule_end_time ? substr($period->proposal_schedule_end_time, 0, 5) : '18:00';
    $this->proposal_slot_duration = $period->proposal_slot_duration ?? 45;
    $this->thesis_schedule_start_time = $period->thesis_schedule_start_time ? substr($period->thesis_schedule_start_time, 0, 5) : '08';
    $this->thesis_schedule_end_time = $period->thesis_schedule_end_time ? substr($period->thesis_schedule_end_time, 0, 5) : '18:00';
    $this->thesis_slot_duration = $period->thesis_slot_duration ?? 45;
    $this->break_start_time = $period->break_start_time ? substr($period->break_start_time, 0, 5) : '12:00';
    $this->break_end_time = $period->break_end_time ? substr($period->break_end_time, 0, 5) : '13:00';
    $this->default_quota = $period->default_quota;

    $this->showModal = true;
};

$save = function (PeriodService $service) {
    $this->validate();
    $this->validateScheduleCollision();
    
    if ($this->getErrorBag()->any()) {
        return;
    }
    
    $data = $this->only([
        'start_date', 'end_date', 'default_quota',
        'proposal_schedule_start_time', 'proposal_schedule_end_time', 'proposal_slot_duration',
        'thesis_schedule_start_time', 'thesis_schedule_end_time', 'thesis_slot_duration',
        'break_start_time', 'break_end_time'
    ]);
    $data['proposal_schedules'] = $this->proposal_schedules;
    $data['thesis_schedules'] = $this->thesis_schedules;

    try {
        if ($this->editing && $this->editing->exists) {
            $service->updatePeriod($this->editing, $data);
        } else {
            $service->createPeriod($data);
        }
        $this->showModal = false;
        session()->flash('success', 'Period saved successfully.');
        $this->resetPage();
    } catch (\Exception $e) {
        $this->showModal = false;
        if ($e->getMessage() === 'Period already exists') {
            session()->flash('error', 'Period already exists.');
        } else {
            session()->flash('error', 'Failed to save period.');
        }
    }
};

$confirmDelete = function ($periodId) {
    $this->deletingPeriodId = $periodId;
    $this->showDeleteConfirmModal = true;
};

$deletePeriod = function (PeriodService $service) {
    if ($this->deletingPeriodId) {
        $service->deletePeriod($this->deletingPeriodId);
        session()->flash('success', 'Period deleted successfully.');
    }
    $this->showDeleteConfirmModal = false;
    $this->resetPage();
};

$addProposalSchedule = function () {
    $this->proposal_schedules[] = ['start_date' => '', 'end_date' => '', 'deadline' => ''];
};

$removeProposalSchedule = function ($index) {
    unset($this->proposal_schedules[$index]);
    $this->proposal_schedules = array_values($this->proposal_schedules);
};

$addThesisSchedule = function () {
    $this->thesis_schedules[] = ['start_date' => '', 'end_date' => '', 'deadline' => ''];
};

$removeThesisSchedule = function ($index) {
    unset($this->thesis_schedules[$index]);
    $this->thesis_schedules = array_values($this->thesis_schedules);
};

$getCanFillOtherFields = function () {
    if (empty($this->start_date) || empty($this->end_date)) {
        return false;
    }
    return strtotime($this->end_date) > strtotime($this->start_date);
};

$getUsedDateRanges = function () {
    $ranges = [];
    
    foreach ($this->proposal_schedules as $schedule) {
        if (!empty($schedule['start_date']) && !empty($schedule['end_date'])) {
            $ranges[] = [
                'start' => $schedule['start_date'],
                'end' => $schedule['end_date'],
                'type' => 'proposal'
            ];
        }
    }
    
    foreach ($this->thesis_schedules as $schedule) {
        if (!empty($schedule['start_date']) && !empty($schedule['end_date'])) {
            $ranges[] = [
                'start' => $schedule['start_date'],
                'end' => $schedule['end_date'],
                'type' => 'thesis'
            ];
        }
    }
    
    return $ranges;
};

$checkDateCollision = function ($startDate, $endDate, $excludeIndex = null, $excludeType = null) {
    if (empty($startDate) || empty($endDate)) {
        return false;
    }
    
    $ranges = $this->getUsedDateRanges();
    
    foreach ($ranges as $index => $range) {
        if ($excludeIndex !== null && $excludeType !== null) {
            $currentSchedules = $excludeType === 'proposal' ? $this->proposal_schedules : $this->thesis_schedules;
            if (isset($currentSchedules[$excludeIndex])) {
                $currentRange = $currentSchedules[$excludeIndex];
                if ($range['start'] === $currentRange['start_date'] && $range['end'] === $currentRange['end_date']) {
                    continue;
                }
            }
        }
        
        if (($startDate >= $range['start'] && $startDate <= $range['end']) ||
            ($endDate >= $range['start'] && $endDate <= $range['end']) ||
            ($startDate <= $range['start'] && $endDate >= $range['end'])) {
            return true;
        }
    }
    
    return false;
};

?>

<div>
    <section class="w-full">
        <div
            class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-xl mb-10 p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-3xl text-black dark:text-white font-bold">Manage Academic Periods</h1>
                    <p class="text-zinc-600 dark:text-zinc-400 mt-1">Create, edit, and activate registration periods.</p>
                </div>
                <div class="flex-shrink-0">
                    <flux:button wire:click="create" variant="primary" class="cursor-pointer">Create New Period
                    </flux:button>
                </div>
            </div>

            <div class="mb-6">
                <div class="w-full sm:w-1/3">
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by period name..."
                        class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <hr class="border-t border-zinc-200 dark:border-zinc-700 mb-6">

            @if (session('error'))
                <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-200 text-sm sm:text-base">
                    {{ session('error') }}
                </div>
            @endif

            @include('partials.session-messages')

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Name</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Period Dates</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Status</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Active</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($periods as $period)
                            <tr class="hover:bg-zinc-600 dark:hover:bg-zinc-800">
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $period->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ Carbon::parse($period->start_date)->format('d M Y') }} -
                                    {{ Carbon::parse($period->end_date)->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-xs font-medium capitalize 
                                        {{ $period->status == 'completed' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' : '' }}
                                        {{ $period->status == 'in_progress' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                        {{ $period->status == 'registration_open' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200' : '' }}
                                        {{ $period->status == 'upcoming' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' }}
                                    ">{{ str_replace('_', ' ', $period->status) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($period->is_active)
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>
                                    @else
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end items-center gap-2">
                                        @if ($period->status === 'archived')
                                            <span class="text-xs italic text-zinc-500">Archived</span>
                                        @else
                                            @if ($period->status === 'completed')
                                                <flux:button wire:click="confirmArchive('{{ $period->id }}')"
                                                    variant="outline" size="sm" class="cursor-pointer">Archive
                                                </flux:button>
                                            @endif

                                            <flux:button wire:click="edit('{{ $period->id }}')" variant="ghost"
                                                size="sm" class="cursor-pointer">Edit</flux:button>

                                            <flux:button href="{{ route('lecturer.periods.manage-quotas', $period) }}"
                                                variant="outline" size="sm" class="cursor-pointer">Manage Quotas
                                            </flux:button>

                                            <flux:button wire:click="confirmDelete('{{ $period->id }}')"
                                                variant="danger" size="sm" class="cursor-pointer">Delete
                                            </flux:button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5"
                                    class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">No periods
                                    found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $periods->links() }}
            </div>
        </div>
    </section>

    @if ($showModal)
        <flux:modal name="period-modal" wire:model.live="showModal" class="max-w-2xl">
            <form wire:submit.prevent="save" class="space-y-6">
                <flux:heading size="lg">{{ $editing && $editing->exists ? 'Edit Period' : 'Create New Period' }}
                </flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-data="{ errors: @js($errors->any()) }">
                    <x-date-input wire-model="start_date" label="Period Start Date" required />
                    @if(empty($start_date))
                        <x-date-input wire-model="end_date" label="Period End Date" required disabled />
                    @else
                        <x-date-input wire-model="end_date" label="Period End Date" required :min="date('Y-m-d', strtotime($start_date . ' +1 day'))" />
                    @endif
                    
                    <flux:input wire:model="default_quota" type="number" label="Default Lecturer Quota" required placeholder="12" />
                    <div></div>
                    
                    <div class="md:col-span-2">
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-xs sm:text-sm text-amber-900 dark:text-amber-200">
                            <p class="font-semibold mb-1">📝 Automatic Features:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li><strong>Period Name:</strong> Auto-generated based on end date (Gasal/Genap)</li>
                                <li><strong>Registration Close:</strong> Auto-calculated (1 day before earliest proposal hearing)</li>
                            </ul>
                        </div>
                    </div>

                    <div class="md:col-span-2 border-t border-zinc-200 dark:border-zinc-700 pt-4 mt-2">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Proposal Hearing Periods (Optional)</h3>
                            @if($this->getCanFillOtherFields())
                                <flux:button type="button" wire:click="addProposalSchedule" variant="outline" size="sm" class="cursor-pointer">
                                    Add Period
                                </flux:button>
                            @else
                                <flux:button type="button" variant="outline" size="sm" disabled>
                                    Add Period
                                </flux:button>
                            @endif
                        </div>
                        @if(!$this->getCanFillOtherFields())
                            <div class="p-3 bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg text-sm text-zinc-500 dark:text-zinc-400 italic">
                                Please fill in Period Start Date and End Date first to add proposal hearing periods.
                            </div>
                        @else
                            @foreach($proposal_schedules as $index => $schedule)
                                @php
                                    $isOngoingOrPast = !empty($schedule['start_date']) && $schedule['start_date'] <= now()->format('Y-m-d');
                                    $isLast = $index === count($proposal_schedules) - 1;
                                @endphp
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-3 p-3 border border-zinc-200 dark:border-zinc-600 rounded-lg {{ $isOngoingOrPast ? 'bg-zinc-50 dark:bg-zinc-800/50' : '' }}">
                                    @if($isLast)
                                        <x-date-input wire-model="proposal_schedules.{{ $index }}.deadline" label="Deadline" :min="$start_date" :max="$end_date" />
                                        @if(empty($schedule['deadline']))
                                            <x-date-input wire-model="proposal_schedules.{{ $index }}.start_date" label="Start Date" disabled />
                                        @else
                                            <x-date-input wire-model="proposal_schedules.{{ $index }}.start_date" label="Start Date" :min="$schedule['deadline']" :max="$end_date" />
                                        @endif
                                        @if(empty($schedule['start_date']))
                                            <x-date-input wire-model="proposal_schedules.{{ $index }}.end_date" label="End Date" disabled />
                                        @else
                                            <x-date-input wire-model="proposal_schedules.{{ $index }}.end_date" label="End Date" :min="date('Y-m-d', strtotime($schedule['start_date'] . ' +1 day'))" :max="$end_date" />
                                        @endif
                                    @else
                                        <x-date-input wire-model="proposal_schedules.{{ $index }}.deadline" label="Deadline" disabled />
                                        <x-date-input wire-model="proposal_schedules.{{ $index }}.start_date" label="Start Date" disabled />
                                        <x-date-input wire-model="proposal_schedules.{{ $index }}.end_date" label="End Date" disabled />
                                    @endif
                                    <div class="col-span-1 sm:col-span-3 flex justify-end">
                                        @if(!$isOngoingOrPast)
                                            <flux:button type="button" wire:click="removeProposalSchedule({{ $index }})" variant="danger" size="sm" class="cursor-pointer">
                                                Remove
                                            </flux:button>
                                        @else
                                            <span class="text-xs text-zinc-500 italic">Ongoing/Completed - Cannot be removed</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            @if(empty($proposal_schedules))
                                <p class="text-sm text-zinc-500 dark:text-zinc-400 italic">No proposal hearing periods added yet.</p>
                            @endif
                        @endif
                    </div>

                    <div class="md:col-span-2 border-t border-zinc-200 dark:border-zinc-700 pt-4 mt-2">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Thesis Defense Periods (Optional)</h3>
                            @if($this->getCanFillOtherFields())
                                <flux:button type="button" wire:click="addThesisSchedule" variant="outline" size="sm" class="cursor-pointer">
                                    Add Period
                                </flux:button>
                            @else
                                <flux:button type="button" variant="outline" size="sm" disabled>
                                    Add Period
                                </flux:button>
                            @endif
                        </div>
                        @if(!$this->getCanFillOtherFields())
                            <div class="p-3 bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg text-sm text-zinc-500 dark:text-zinc-400 italic">
                                Please fill in Period Start Date and End Date first to add thesis defense periods.
                            </div>
                        @else
                            @foreach($thesis_schedules as $index => $schedule)
                                @php
                                    $isOngoingOrPast = !empty($schedule['start_date']) && $schedule['start_date'] <= now()->format('Y-m-d');
                                    $isLast = $index === count($thesis_schedules) - 1;
                                @endphp
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-3 p-3 border border-zinc-200 dark:border-zinc-600 rounded-lg {{ $isOngoingOrPast ? 'bg-zinc-50 dark:bg-zinc-800/50' : '' }}">
                                    @if($isLast)
                                        <x-date-input wire-model="thesis_schedules.{{ $index }}.deadline" label="Deadline" :min="$start_date" :max="$end_date" />
                                        @if(empty($schedule['deadline']))
                                            <x-date-input wire-model="thesis_schedules.{{ $index }}.start_date" label="Start Date" disabled />
                                        @else
                                            <x-date-input wire-model="thesis_schedules.{{ $index }}.start_date" label="Start Date" :min="$schedule['deadline']" :max="$end_date" />
                                        @endif
                                        @if(empty($schedule['start_date']))
                                            <x-date-input wire-model="thesis_schedules.{{ $index }}.end_date" label="End Date" disabled />
                                        @else
                                            <x-date-input wire-model="thesis_schedules.{{ $index }}.end_date" label="End Date" :min="date('Y-m-d', strtotime($schedule['start_date'] . ' +1 day'))" :max="$end_date" />
                                        @endif
                                    @else
                                        <x-date-input wire-model="thesis_schedules.{{ $index }}.deadline" label="Deadline" disabled />
                                        <x-date-input wire-model="thesis_schedules.{{ $index }}.start_date" label="Start Date" disabled />
                                        <x-date-input wire-model="thesis_schedules.{{ $index }}.end_date" label="End Date" disabled />
                                    @endif
                                    <div class="col-span-1 sm:col-span-3 flex justify-end">
                                        @if(!$isOngoingOrPast)
                                            <flux:button type="button" wire:click="removeThesisSchedule({{ $index }})" variant="danger" size="sm" class="cursor-pointer">
                                                Remove
                                            </flux:button>
                                        @else
                                            <span class="text-xs text-zinc-500 italic">Ongoing/Completed - Cannot be removed</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            @if(empty($thesis_schedules))
                                <p class="text-sm text-zinc-500 dark:text-zinc-400 italic">No thesis defense periods added yet.</p>
                            @endif
                        @endif
                    </div>

                    <div class="md:col-span-2 border-t border-zinc-200 dark:border-zinc-700 pt-4 mt-2">
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">Break Time Configuration</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <flux:input wire:model="break_start_time" type="time" label="Break Start Time" 
                                :disabled="!$this->getCanFillOtherFields()" />
                            <flux:input wire:model="break_end_time" type="time" label="Break End Time" 
                                :disabled="!$this->getCanFillOtherFields()" />
                        </div>
                        <p class="text-xs text-zinc-500 mt-2">Break time will be automatically excluded from all scheduling.</p>
                    </div>
                    
                    <div class="md:col-span-2 border-t border-zinc-200 dark:border-zinc-700 pt-4 mt-2">
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">Proposal Hearing Schedule Configuration</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <flux:input wire:model="proposal_schedule_start_time" type="time" label="Start Time" 
                                :disabled="!$this->getCanFillOtherFields()" />
                            <flux:input wire:model="proposal_schedule_end_time" type="time" label="End Time" 
                                :disabled="!$this->getCanFillOtherFields()" />
                            <flux:input wire:model="proposal_slot_duration" type="number" label="Slot Duration (minutes)" placeholder="45" 
                                :disabled="!$this->getCanFillOtherFields()" />
                        </div>
                    </div>
                    
                    <div class="md:col-span-2 border-t border-zinc-200 dark:border-zinc-700 pt-4 mt-2">
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">Thesis Defense Schedule Configuration</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <flux:input wire:model="thesis_schedule_start_time" type="time" label="Start Time" 
                                :disabled="!$this->getCanFillOtherFields()" />
                            <flux:input wire:model="thesis_schedule_end_time" type="time" label="End Time" 
                                :disabled="!$this->getCanFillOtherFields()" />
                            <flux:input wire:model="thesis_slot_duration" type="number" label="Slot Duration (minutes)" placeholder="45" 
                                :disabled="!$this->getCanFillOtherFields()" />
                        </div>
                        <p class="text-xs text-zinc-500 mt-2">These settings define the available time slots for each type of presentation.</p>
                    </div>

                    <div class="md:col-span-2">
                        <div class="p-3 sm:p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg text-xs sm:text-sm text-blue-900 dark:text-blue-200">
                            <p class="font-semibold mb-1">📊 Period Status Flow:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Before start date: <strong>Upcoming</strong></li>
                                <li>Start date to registration close: <strong>Registration Open</strong></li>
                                <li>After registration close to end date: <strong>In Progress</strong></li>
                                <li>After end date: <strong>Completed</strong> (can be archived)</li>
                            </ul>
                        </div>
                        <p class="text-xs text-zinc-500 mt-2">💡 The default quota applies to all lecturers. Individual quotas can be adjusted after creating the period.</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="$set('showModal', false)"
                        class="cursor-pointer">Cancel</flux:button>
                    <flux:button type="submit" variant="primary" class="cursor-pointer">Save Period</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    @if ($showArchiveConfirmModal)
        <flux:modal name="archive-confirm-modal" wire:model.live="showArchiveConfirmModal" class="max-w-md">
            <div class="space-y-6">
                <flux:heading size="lg">Confirm Archival</flux:heading>

                <p class="text-zinc-600 dark:text-zinc-400">
                    Are you sure you want to archive this period? The status cannot be changed afterwards.
                </p>

                <div class="flex justify-end gap-2">
                    <flux:button type="button" variant="ghost" wire:click="$set('showArchiveConfirmModal', false)">
                        Cancel
                    </flux:button>
                    <flux:button type="button" variant="danger" wire:click="archivePeriod"
                        wire:loading.attr="disabled">
                        Confirm Archive
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    @if ($showDeleteConfirmModal)
        <flux:modal name="delete-confirm-modal" wire:model.live="showDeleteConfirmModal" class="max-w-md">
            <div class="space-y-6">
                <flux:heading size="lg">Confirm Deletion</flux:heading>

                <p class="text-zinc-600 dark:text-zinc-400">
                    Are you sure you want to permanently delete this period and all its data? This action cannot be undone.
                </p>

                <div class="flex justify-end gap-2">
                    <flux:button type="button" variant="ghost" wire:click="$set('showDeleteConfirmModal', false)">
                        Cancel
                    </flux:button>
                    <flux:button type="button" variant="danger" wire:click="deletePeriod"
                        wire:loading.attr="disabled">
                        Confirm Delete
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
