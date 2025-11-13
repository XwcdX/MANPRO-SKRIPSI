<?php

use function Livewire\Volt\{state, layout, with, uses};
use Livewire\WithPagination;
use App\Services\SupervisionApplicationService;
use App\Services\PeriodService;

layout('components.layouts.lecturer');

uses(WithPagination::class);

state([
    'search' => '',
    'statusFilter' => 'pending',
]);

with(fn() => [
    'applications' => app(SupervisionApplicationService::class)
        ->getApplicationsForLecturer(auth()->id(), $this->statusFilter, $this->search)
        ->paginate(15),
    'currentQuota' => auth()->user()->getAvailableCapacityForPeriod(app(PeriodService::class)->getActivePeriod()?->id ?? null),
    'activePeriod' => app(PeriodService::class)->getActivePeriod(),
]);

$accept = function ($applicationId, SupervisionApplicationService $service) {
    $service->acceptApplication($applicationId, auth()->id());
    session()->flash('success', 'Application accepted successfully.');
    $this->resetPage();
};

$decline = function ($applicationId, SupervisionApplicationService $service) {
    $service->declineApplication($applicationId);
    session()->flash('success', 'Application declined.');
    $this->resetPage();
};

$updatingSearch = fn() => $this->resetPage();
$updatingStatusFilter = fn() => $this->resetPage();

$getActivePeriod = fn(PeriodService $service) => $service->getActivePeriod();

?>

<div>
    <section class="w-full">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-xl mb-10 p-6 sm:p-8">
            <div class="mb-6">
                <h1 class="text-3xl text-black dark:text-white font-bold">Student Applications</h1>
                <p class="text-zinc-600 dark:text-zinc-400 mt-1">Review and manage student supervision requests.</p>
                @if ($activePeriod)
                    <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <span class="text-sm text-blue-900 dark:text-blue-200">
                            Available Quota: <strong>{{ $currentQuota }}</strong> students
                        </span>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <select wire:model.live="statusFilter" 
                        class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="pending">Pending</option>
                        <option value="accepted">Accepted</option>
                        <option value="declined">Declined</option>
                    </select>
                </div>
                <div>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by student name or email..."
                        class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <hr class="border-t border-zinc-200 dark:border-zinc-700 mb-6">

            @include('partials.session-messages')

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Student</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Period</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Role</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Thesis Title</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Applied</th>
                            @if ($statusFilter === 'pending')
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($applications as $application)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $application->student->name }}</div>
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $application->student->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $application->period->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                        {{ $application->proposed_role == 0 ? 'Supervisor 1' : 'Supervisor 2' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    @if ($application->student->thesis_title)
                                        <span class="line-clamp-2">{{ $application->student->thesis_title }}</span>
                                    @else
                                        <span class="italic text-zinc-400 dark:text-zinc-500">Not submitted yet</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $application->created_at->diffForHumans() }}
                                </td>
                                @if ($statusFilter === 'pending')
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end items-center gap-2">
                                            <flux:button 
                                                wire:click="accept('{{ $application->id }}')"
                                                wire:confirm="Are you sure you want to accept this student? This action cannot be undone and will update the student's status."
                                                variant="primary" 
                                                size="sm" 
                                                class="cursor-pointer">
                                                Accept
                                            </flux:button>
                                            <flux:button 
                                                wire:click="decline('{{ $application->id }}')"
                                                wire:confirm="Are you sure you want to decline this application?"
                                                variant="danger" 
                                                size="sm" 
                                                class="cursor-pointer">
                                                Decline
                                            </flux:button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $statusFilter === 'pending' ? '6' : '5' }}" class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    No {{ $statusFilter }} applications found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $applications->links() }}
            </div>
        </div>
    </section>
</div>
