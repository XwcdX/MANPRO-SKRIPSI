<?php

use function Livewire\Volt\{state, layout, with, computed, uses, mount};
use Livewire\WithPagination;
use App\Services\StudentService;

layout('components.layouts.lecturer');

uses(WithPagination::class);

state([
    'search' => '',
    'selectedPeriodId' => null,
]);

mount(function (StudentService $service) {
    $activePeriod = $service->getActivePeriod();
    $this->selectedPeriodId = $activePeriod?->id;
});

with(fn(StudentService $service) => [
    'periods' => $service->getStartedPeriods(),
]);

$students = computed(function () {
    if (!$this->selectedPeriodId) {
        return collect();
    }

    return app(StudentService::class)
        ->getStudentsByPeriod($this->selectedPeriodId, $this->search)
        ->paginate(15);
});

$updatingSearch = fn() => $this->resetPage();
$updatingSelectedPeriodId = fn() => $this->resetPage();

?>

<div>
    <section class="w-full">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-xl mb-10 p-6 sm:p-8">
            <div class="mb-6">
                <h1 class="text-3xl text-black dark:text-white font-bold">Students by Period</h1>
                <p class="text-zinc-600 dark:text-zinc-400 mt-1">View all students enrolled in a specific period.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <select wire:model.live="selectedPeriodId" 
                        class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select a period...</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}">{{ $period->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by name or email..."
                        class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <hr class="border-t border-zinc-200 dark:border-zinc-700 mb-6">

            @if ($selectedPeriodId)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Thesis Title</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Supervisors</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($this->students as $student)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-white">
                                        {{ $student->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $student->email }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                        @if ($student->thesis_title)
                                            <span class="line-clamp-2">{{ $student->thesis_title }}</span>
                                        @else
                                            <span class="italic text-zinc-400 dark:text-zinc-500">Not submitted yet</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ $student->status_text }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                        @if ($student->supervisors->count() > 0)
                                            <div class="space-y-1">
                                                @foreach ($student->supervisors as $supervisor)
                                                    <div class="flex items-center gap-1">
                                                        <span class="text-xs px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300">
                                                            {{ $supervisor->pivot->role == 0 ? 'S1' : 'S2' }}
                                                        </span>
                                                        <span>{{ $supervisor->name }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="italic text-zinc-400 dark:text-zinc-500">No supervisor yet</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                        No students found for this period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $this->students->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-zinc-500 dark:text-zinc-400">Please select a period to view students.</p>
                </div>
            @endif
        </div>
    </section>
</div>
