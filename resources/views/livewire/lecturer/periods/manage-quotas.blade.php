<?php

use function Livewire\Volt\{state, layout, with, mount, uses};
use Livewire\WithPagination;
use App\Models\Period;
use App\Models\Lecturer;
use App\Models\LecturerPeriodQuota;

layout('components.layouts.lecturer');

uses(WithPagination::class);

state([
    'period' => null,
    'search' => '',
    'editingLecturerId' => null,
    'customQuota' => null,
    'showResetModal' => false,
    'resettingLecturerId' => null,
]);

mount(function (Period $period) {
    $this->period = $period;
});

with(fn() => [
    'lecturers' => Lecturer::with(['lecturerQuotas' => fn($q) => $q->where('period_id', $this->period->id)])
        ->where('is_active', true)
        ->where(function($query) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
        })
        ->orderBy('name')
        ->paginate(15),
]);

$updatingSearch = function() {
    $this->resetPage();
};

$editQuota = function ($lecturerId) {
    $this->editingLecturerId = $lecturerId;
    $customQuota = LecturerPeriodQuota::where('lecturer_id', $lecturerId)
        ->where('period_id', $this->period->id)
        ->first();
    $this->customQuota = $customQuota ? $customQuota->max_students : $this->period->default_quota;
};

$saveQuota = function () {
    $this->validate(['customQuota' => 'required|integer|min:0|max:50']);
    
    LecturerPeriodQuota::updateOrCreate(
        [
            'lecturer_id' => $this->editingLecturerId,
            'period_id' => $this->period->id,
        ],
        ['max_students' => $this->customQuota]
    );
    
    $this->editingLecturerId = null;
    $this->customQuota = null;
    session()->flash('success', 'Lecturer quota updated successfully.');
};

$confirmReset = function ($lecturerId) {
    $this->resettingLecturerId = $lecturerId;
    $this->showResetModal = true;
};

$resetToDefault = function () {
    if ($this->resettingLecturerId) {
        LecturerPeriodQuota::where('lecturer_id', $this->resettingLecturerId)
            ->where('period_id', $this->period->id)
            ->delete();
        
        session()->flash('success', 'Lecturer quota reset to default.');
    }
    $this->showResetModal = false;
    $this->resettingLecturerId = null;
};

$cancelEdit = function () {
    $this->editingLecturerId = null;
    $this->customQuota = null;
};

?>

<div>
    <section class="w-full">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-xl mb-10 p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-3xl text-black dark:text-white font-bold">Manage Lecturer Quotas</h1>
                    <p class="text-zinc-600 dark:text-zinc-400 mt-1">Period: {{ $period->name }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Default Quota: <span class="font-semibold">{{ $period->default_quota }}</span> students per lecturer</p>
                </div>
                <div class="flex-shrink-0">
                    <flux:button href="{{ route('lecturer.periods.index') }}" variant="outline" class="cursor-pointer">
                        Back to Periods
                    </flux:button>
                </div>
            </div>

            <div class="mb-6">
                <div class="w-full sm:w-1/3">
                    <input wire:model.live.debounce.300ms="search" type="text"
                        placeholder="Search by name or email..."
                        class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <hr class="border-t border-zinc-200 dark:border-zinc-700 mb-6">

            @include('partials.session-messages')

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Lecturer Name
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Email
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Current Quota
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($lecturers as $lecturer)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $lecturer->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $lecturer->email }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                    @if ($editingLecturerId === $lecturer->id)
                                        <div class="flex items-center gap-2">
                                            <input type="number" wire:model="customQuota" min="0" max="50"
                                                class="w-20 px-2 py-1 rounded border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                                                wire:keydown.enter="saveQuota">
                                            <button wire:click="saveQuota" class="text-green-600 hover:text-green-700 cursor-pointer" title="Save">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                            <button wire:click="cancelEdit" class="text-zinc-500 hover:text-red-600 cursor-pointer" title="Cancel">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                        @error('customQuota')
                                            <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    @else
                                        @php
                                            $customQuota = $lecturer->lecturerQuotas->first();
                                            $currentQuota = $customQuota ? $customQuota->max_students : $period->default_quota;
                                            $isCustom = $customQuota !== null;
                                        @endphp
                                        <span class="font-semibold">{{ $currentQuota }}</span>
                                        @if ($isCustom)
                                            <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Custom</span>
                                        @else
                                            <span class="ml-2 text-xs text-zinc-400">(Default)</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if ($editingLecturerId !== $lecturer->id)
                                        <div class="flex justify-end items-center gap-2">
                                            <flux:button wire:click="editQuota('{{ $lecturer->id }}')" variant="ghost" size="sm" class="cursor-pointer">
                                                Edit Quota
                                            </flux:button>
                                            @if ($lecturer->lecturerQuotas->first())
                                                <flux:button wire:click="confirmReset('{{ $lecturer->id }}')" 
                                                    variant="outline" size="sm" class="cursor-pointer">
                                                    Reset to Default
                                                </flux:button>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    No lecturers found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $lecturers->links() }}
            </div>
        </div>
    </section>

    @if ($showResetModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            x-data x-show="true" x-transition>
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-8 w-full max-w-md"
                @click.away="$wire.showResetModal = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100">
                <h2 class="text-2xl font-bold text-zinc-800 dark:text-zinc-200 mb-4">Confirm Reset</h2>
                <p class="text-zinc-600 dark:text-zinc-400 mb-6">
                    Are you sure you want to reset this lecturer's quota to the default (<strong>{{ $period->default_quota }}</strong> students)?
                </p>
                <div class="flex justify-end gap-4">
                    <button type="button" wire:click="$set('showResetModal', false)"
                        class="px-4 py-2 bg-zinc-200 dark:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-md hover:bg-zinc-300 dark:hover:bg-zinc-500 cursor-pointer transition-colors">
                        Cancel
                    </button>
                    <button type="button" wire:click="resetToDefault"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer transition-colors"
                        wire:loading.attr="disabled" wire:target="resetToDefault">
                        <span wire:loading.remove wire:target="resetToDefault">Reset to Default</span>
                        <span wire:loading wire:target="resetToDefault">Resetting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
