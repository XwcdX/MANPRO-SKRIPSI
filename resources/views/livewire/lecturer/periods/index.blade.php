<?php

use function Livewire\Volt\{state, layout, rules, with, uses};
use Livewire\WithPagination;
use App\Models\Period;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

layout('components.layouts.lecturer');

uses([WithPagination::class]);

state([
    'showModal' => false,
    'search' => '',
    'editing' => null,
    'name' => '',
    'start_date' => '',
    'end_date' => '',
    'registration_start' => '',
    'registration_end' => '',
    'supervision_selection_deadline' => null,
    'title_submission_deadline' => null,
    'is_active' => false,
    'status' => 'upcoming',
    'max_students' => null,
    'showArchiveConfirmModal' => false,
    'archivingPeriodId' => null,
]);

rules(
    fn() => [
        'name' => ['required', 'string', 'max:255', Rule::unique('periods')->ignore($this->editing?->id)],
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'registration_start' => 'required|date',
        'registration_end' => 'required|date|after_or_equal:registration_start',
        'supervision_selection_deadline' => 'nullable|date|after_or_equal:registration_end',
        'title_submission_deadline' => 'nullable|date|after_or_equal:supervision_selection_deadline',
        'is_active' => 'required|boolean',
        'status' => 'required|in:upcoming,registration_open,in_progress,completed,archived',
        'max_students' => 'nullable|integer|min:1',
    ],
);

with(
    fn() => [
        'periods' => Period::where('name', 'like', '%' . $this->search . '%')
            ->orderBy('start_date', 'desc')
            ->paginate(10),
    ],
);

$archivePeriod = function (Period $period) {
    $period->is_active = false;
    $period->status = 'archived';
    $period->save();

    session()->flash('success', "Period '{$period->name}' has been archived.");
    $this->resetPage();
};

$updatingSearch = fn() => $this->resetPage();

$confirmArchive = function ($periodId) {
    $this->archivingPeriodId = $periodId;
    $this->showArchiveConfirmModal = true;
};

$archivePeriod = function () {
    if ($this->archivingPeriodId) {
        $period = Period::find($this->archivingPeriodId);
        if ($period) {
            $period->is_active = false;
            $period->status = 'archived';
            $period->save();
            session()->flash('success', "Period '{$period->name}' has been archived.");
        }
    }
    $this->showArchiveConfirmModal = false;
    $this->resetPage();
};

$create = function () {
    $this->resetErrorBag();
    $this->reset('name', 'start_date', 'end_date', 'registration_start', 'registration_end', 'supervision_selection_deadline', 'title_submission_deadline', 'is_active', 'status', 'max_students', 'editing');
    $this->editing = new Period();
    $this->is_active = false;
    $this->status = 'upcoming';
    $this->showModal = true;
};

$edit = function (Period $period) {
    $this->resetErrorBag();
    $this->reset();

    $this->editing = $period;
    $this->name = $period->name;

    $this->start_date = Carbon::parse($period->start_date)->format('Y-m-d');
    $this->end_date = Carbon::parse($period->end_date)->format('Y-m-d');
    $this->registration_start = Carbon::parse($period->registration_start)->format('Y-m-d');
    $this->registration_end = Carbon::parse($period->registration_end)->format('Y-m-d');

    $this->supervision_selection_deadline = $period->supervision_selection_deadline ? Carbon::parse($period->supervision_selection_deadline)->format('Y-m-d') : null;
    $this->title_submission_deadline = $period->title_submission_deadline ? Carbon::parse($period->title_submission_deadline)->format('Y-m-d') : null;

    $this->is_active = $period->is_active;
    $this->status = $period->status;
    $this->max_students = $period->max_students;

    $this->showModal = true;
};

$save = function () {
    $this->validate();
    if (!$this->editing) {
        $this->editing = new Period();
    }
    DB::transaction(function () {
        if ($this->is_active) {
            Period::where('is_active', true)->update(['is_active' => false]);
        }
        $this->editing->fill($this->only(['name', 'start_date', 'end_date', 'registration_start', 'registration_end', 'supervision_selection_deadline', 'title_submission_deadline', 'is_active', 'status', 'max_students']));
        $this->editing->save();
    });
    session()->flash('success', 'Period saved successfully.');
    $this->showModal = false;
    $this->resetPage();
};

$activatePeriod = function (Period $period) {
    DB::transaction(function () use ($period) {
        Period::where('is_active', true)->update(['is_active' => false]);
        $period->update(['is_active' => true, 'status' => 'registration_open']);
    });
    session()->flash('success', "Period '{$period->name}' has been activated.");
    $this->resetPage();
};

$deletePeriod = function (Period $period) {
    $period->delete();
    session()->flash('success', 'Period deleted successfully.');
};

?>

<div>
    {{-- This is the main page content --}}
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
                                Registration Dates</th>
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
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $period->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ Carbon::parse($period->registration_start)->format('d M Y') }} -
                                    {{ Carbon::parse($period->registration_end)->format('d M Y') }}
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
                                            @if (!$period->is_active && $period->status !== 'archived')
                                                <flux:button wire:click="activatePeriod('{{ $period->id }}')"
                                                    variant="outline" size="sm" class="cursor-pointer">Activate
                                                </flux:button>
                                            @endif

                                            @if ($period->status === 'completed')
                                                <flux:button wire:click="confirmArchive('{{ $period->id }}')"
                                                    variant="outline" size="sm" class="cursor-pointer">Archive
                                                </flux:button>
                                            @endif

                                            <flux:button wire:click="edit('{{ $period->id }}')" variant="ghost"
                                                size="sm" class="cursor-pointer">Edit</flux:button>

                                            <flux:button wire:click="deletePeriod('{{ $period->id }}')"
                                                wire:confirm="Are you sure you want to permanently delete this period and all its data?"
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <flux:input wire:model="name" label="Period Name" placeholder="e.g., Odd 2025/2026" required />
                    </div>

                    <flux:input wire:model="start_date" type="date" label="Period Start Date" required />
                    <flux:input wire:model="end_date" type="date" label="Period End Date" required />

                    <flux:input wire:model="registration_start" type="date" label="Registration Start" required />
                    <flux:input wire:model="registration_end" type="date" label="Registration End" required />

                    <flux:input wire:model="supervision_selection_deadline" type="date"
                        label="Supervisor Selection Deadline" />
                    <flux:input wire:model="title_submission_deadline" type="date"
                        label="Title Submission Deadline" />

                    <flux:select wire:model="status" label="Status" required>
                        <option value="upcoming">Upcoming</option>
                        <option value="registration_open">Registration Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="archived">Archived</option>
                    </flux:select>
                    <flux:input wire:model="max_students" type="number" label="Max Students (optional)" />

                    <div class="md:col-span-2">
                        <flux:checkbox wire:model="is_active" label="Set as Active Registration Period">
                            <p class="text-xs text-zinc-500 mt-1">Note: Activating this will deactivate any other
                                currently active period.</p>
                        </flux:checkbox>
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
</div>
