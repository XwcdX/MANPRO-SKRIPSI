<?php

use App\Traits\WithAuthUser;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new class extends Component {
    use WithAuthUser;

    public int $studentStatus;
    public array $steps = [];

    public function mount()
    {
        $this->studentStatus = $this->user->status;

        $this->steps = [
            [
                'name' => 'Submit Title',
                'description' => 'Submit your thesis title for review.',
                'status_unlocked' => 0,
                'status_active' => [1],
            ],
            [
                'name' => 'Title Approved',
                'description' => 'Your title has been approved by the department.',
                'status_unlocked' => 3,
                'status_active' => [3, 4],
            ],
            [
                'name' => 'Presentation Scheduled',
                'description' => 'Your presentation schedule is set.',
                'status_unlocked' => 5,
                'status_active' => [5],
            ],
            [
                'name' => 'Thesis Accepted',
                'description' => 'Congratulations! Your thesis has been accepted.',
                'status_unlocked' => 7,
                'status_active' => [7],
            ],
            [
                'name' => 'Completed',
                'description' => 'You have completed all requirements.',
                'status_unlocked' => 8,
                'status_active' => [8],
            ],
        ];
    }
}; ?>

<div> {{-- Add a single root div --}}
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium">Welcome, {{ $user->name }}!</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Here is your current thesis progress. Your status is:
                        <strong>{{ $user->status_text ?? 'N/A' }}</strong> {{-- Added a fallback for status_text --}}
                    </p>

                    <div class="mt-8">
                        <nav aria-label="Progress">
                            <ol role="list" class="flex items-center">
                                @foreach ($steps as $stepIdx => $step)
                                    @php
                                        $isCompleted = $studentStatus >= $step['status_unlocked'];
                                        $isCurrent = in_array($studentStatus, $step['status_active']);
                                    @endphp
                                    <li class="relative {{ !$loop->last ? 'pr-8 sm:pr-20' : '' }}">
                                        @if ($isCompleted)
                                            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                                @if (!$loop->last)
                                                    <div class="h-0.5 w-full bg-indigo-600"></div>
                                                @endif
                                            </div>
                                            <a href="#"
                                                class="relative flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 hover:bg-indigo-900">
                                                <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor"
                                                    aria-hidden="true">
                                                    <path fill-rule="evenodd"
                                                        d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.052-.143z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </a>
                                        @elseif ($isCurrent)
                                            {{-- Current Step --}}
                                            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                                @if (!$loop->last)
                                                    <div class="h-0.5 w-full bg-gray-200 dark:bg-gray-700"></div>
                                                @endif
                                            </div>
                                            <a href="#"
                                                class="relative flex h-8 w-8 items-center justify-center rounded-full border-2 border-indigo-600 bg-white dark:bg-gray-800"
                                                aria-current="step">
                                                <span class="h-2.5 w-2.5 rounded-full bg-indigo-600"
                                                    aria-hidden="true"></span>
                                            </a>
                                        @else
                                            {{-- Upcoming/Locked Step --}}
                                            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                                @if (!$loop->last)
                                                    <div class="h-0.5 w-full bg-gray-200 dark:bg-gray-700"></div>
                                                @endif
                                            </div>
                                            <a href="#"
                                                class="group relative flex h-8 w-8 items-center justify-center rounded-full border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:border-gray-400">
                                                {{-- Lock Icon would go here --}}
                                                <span
                                                    class="h-2.5 w-2.5 rounded-full bg-transparent group-hover:bg-gray-300"
                                                    aria-hidden="true"></span>
                                            </a>
                                        @endif
                                        <div class="absolute -bottom-10 w-max text-center">
                                            <p
                                                class="text-xs font-semibold {{ $isCompleted || $isCurrent ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500' }}">
                                                {{ $step['name'] }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
