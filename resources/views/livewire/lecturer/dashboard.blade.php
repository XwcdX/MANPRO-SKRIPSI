<?php

use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Lecturer;

new #[Layout('components.layouts.lecturer')] class extends Component {
    public Lecturer $user;
    public int $supervisionCount = 0;
    public int $pendingTitlesCount = 0;

    public function mount(): void
    {
        $this->user = Auth::user();

        $this->supervisionCount = $this->user->activeSupervisions()->count();

        $this->pendingTitlesCount = Student::where('status', 1)->count();
    }
}; ?>

<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Lecturer Dashboard</h1>
        <p class="mt-1 text-lg text-gray-500 dark:text-gray-400">Welcome back, {{ $user->name }}!</p>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
        <div class="overflow-hidden rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <x-heroicon-o-academic-cap class="h-8 w-8 text-gray-400" />
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Active Supervisions</dt>
                        <dd>
                            <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $supervisionCount }}</div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <x-heroicon-o-document-check class="h-8 w-8 text-gray-400" />
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Pending Title Reviews</dt>
                        <dd>
                            <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $pendingTitlesCount }}</div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>


    </div>

    <div class="mt-10">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200">Quick Actions</h2>
        <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <a href="{{ route('lecturer.schedules.availability') }}" class="block rounded-lg bg-white p-6 shadow transition-transform duration-200 hover:-translate-y-1 hover:shadow-lg dark:bg-gray-800 dark:hover:bg-gray-700">
                <div class="flex items-start">
                    <x-heroicon-s-calendar-days class="h-10 w-10 text-indigo-500" />
                    <div class="ml-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">My Availability</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Set your schedule for hearings and defenses.</p>
                    </div>
                </div>
            </a>

            @can('offer-topics')
                <a href="{{ route('lecturer.topics.index') }}" class="block rounded-lg bg-white p-6 shadow transition-transform duration-200 hover:-translate-y-1 hover:shadow-lg dark:bg-gray-800 dark:hover:bg-gray-700">
                    <div class="flex items-start">
                        <x-heroicon-s-light-bulb class="h-10 w-10 text-indigo-500" />
                        <div class="ml-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">My Topics</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage thesis topics you offer to students.</p>
                        </div>
                    </div>
                </a>
            @endcan
            
            @can('administrate')
                <a href="{{ route('lecturer.periods.index') }}" class="block rounded-lg bg-white p-6 shadow transition-transform duration-200 hover:-translate-y-1 hover:shadow-lg dark:bg-gray-800 dark:hover:bg-gray-700">
                    <div class="flex items-start">
                        <x-heroicon-s-calendar class="h-10 w-10 text-indigo-500" />
                        <div class="ml-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Manage Periods</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Configure academic periods and quotas.</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('lecturer.lecturers.index') }}" class="block rounded-lg bg-white p-6 shadow transition-transform duration-200 hover:-translate-y-1 hover:shadow-lg dark:bg-gray-800 dark:hover:bg-gray-700">
                    <div class="flex items-start">
                        <x-heroicon-s-user-group class="h-10 w-10 text-indigo-500" />
                        <div class="ml-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Manage Lecturers</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage lecturer accounts and roles.</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('lecturer.roles.index') }}" class="block rounded-lg bg-white p-6 shadow transition-transform duration-200 hover:-translate-y-1 hover:shadow-lg dark:bg-gray-800 dark:hover:bg-gray-700">
                    <div class="flex items-start">
                        <x-heroicon-s-shield-check class="h-10 w-10 text-indigo-500" />
                        <div class="ml-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Roles & Permissions</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Configure roles and permissions.</p>
                        </div>
                    </div>
                </a>
            @endcan
        </div>
    </div>
</div>