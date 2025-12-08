<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\ThesisPresentation;
use App\Models\Student;
use App\Models\StudentStatusHistory;
use Carbon\Carbon;

new #[Layout('components.layouts.lecturer')] 
class extends Component {
    public bool $showModal = false;
    public ?string $selectedPresentation = null;
    public string $decision = '';
    public string $notes = '';

    public function openModal(string $presentationId): void
    {
        $this->selectedPresentation = $presentationId;
        $this->decision = '';
        $this->notes = '';
        $this->showModal = true;
    }

    public function submitDecision(): void
    {
        $presentation = ThesisPresentation::with(['student', 'periodSchedule'])->find($this->selectedPresentation);
        if (!$presentation) return;

        $student = $presentation->student;
        $oldStatus = $student->status;
        
        if ($this->decision === 'pass') {
            $newStatus = 4;
        } else {
            $newStatus = 2;
        }

        $student->update(['status' => $newStatus]);

        StudentStatusHistory::create([
            'student_id' => $student->id,
            'period_id' => $presentation->periodSchedule->period_id,
            'previous_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => auth()->id(),
            'reason' => $this->notes ?: ($this->decision === 'pass' ? 'Passed presentation' : 'Failed presentation'),
        ]);

        $this->showModal = false;
        $this->reset(['selectedPresentation', 'decision', 'notes']);
        session()->flash('success', 'Decision submitted successfully.');
    }



    public function with(): array
    {
        $presentations = ThesisPresentation::with(['student', 'venue', 'periodSchedule.period', 'examiners.lecturer', 'leadExaminer.lecturer'])
            ->whereHas('examiners', fn($q) => $q->where('lecturer_id', auth()->id()))
            ->orderBy('presentation_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        return [
            'presentations' => $presentations,
            'isLeadExaminer' => fn($presentation) => $presentation->leadExaminer?->lecturer_id === auth()->id(),
            'isPastPresentation' => fn($presentation) => Carbon::parse($presentation->presentation_date)->setTimeFromTimeString($presentation->end_time)->isPast(),
        ];
    }
};

?>

<div>
    <section class="w-full">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-xl mb-10 p-6 sm:p-8">
            <div class="mb-6">
                <h1 class="text-3xl text-black dark:text-white font-bold">My Schedules</h1>
                <p class="text-zinc-600 dark:text-zinc-400 mt-1">View all presentation schedules where you are assigned as examiner.</p>
                @if(session('success'))
                    <div class="mt-4 p-4 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif
            </div>

            @if($presentations->isEmpty())
                <div class="text-center py-12 text-zinc-500 dark:text-zinc-400">
                    No presentations assigned to you yet.
                </div>
            @else
                <div class="space-y-4">
                    @foreach($presentations as $presentation)
                        @php
                            $isLead = $isLeadExaminer($presentation);
                            $isPast = $isPastPresentation($presentation);
                            $schedule = $presentation->periodSchedule;
                            $type = $schedule->type === 'proposal_hearing' ? 'Proposal' : 'Thesis Defense';
                        @endphp
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-6 hover:shadow-md transition {{ $isPast && (!$isLead || $presentation->student->status !== 3) ? 'bg-zinc-100 dark:bg-zinc-800/50 opacity-60' : '' }}">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-3">
                                        <h3 class="text-xl font-semibold text-black dark:text-white">{{ $presentation->student->name }}</h3>
                                        <span class="px-3 py-1 text-xs font-medium rounded-full {{ $schedule->type === 'proposal_hearing' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' }}">
                                            {{ $type }}
                                        </span>
                                        @if($isLead)
                                            <span class="px-3 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                                ⭐ Lead Examiner
                                            </span>
                                        @endif
                                        @if($isPast)
                                            <span class="px-3 py-1 text-xs font-medium rounded-full bg-zinc-400 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">
                                                Completed
                                            </span>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <p class="text-zinc-500 dark:text-zinc-400">Period</p>
                                            <p class="text-black dark:text-white font-medium">{{ $presentation->periodSchedule->period->name }}</p>
                                        </div>
                                        <div>
                                            <p class="text-zinc-500 dark:text-zinc-400">Venue</p>
                                            <p class="text-black dark:text-white font-medium">{{ $presentation->venue->name }} - {{ $presentation->venue->location }}</p>
                                        </div>
                                        <div>
                                            <p class="text-zinc-500 dark:text-zinc-400">Date & Time</p>
                                            <p class="text-black dark:text-white font-medium">
                                                {{ Carbon::parse($presentation->presentation_date)->format('d M Y') }} | 
                                                {{ substr($presentation->start_time, 0, 5) }} - {{ substr($presentation->end_time, 0, 5) }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-zinc-500 dark:text-zinc-400">Topic</p>
                                            <p class="text-black dark:text-white font-medium">{{ $presentation->student->thesis_title ?: 'Not set' }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <p class="text-zinc-500 dark:text-zinc-400 text-sm mb-2">Examiners</p>
                                        <div class="flex flex-wrap gap-2">
                                            @if($presentation->leadExaminer)
                                                <span class="px-3 py-1 text-sm bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded-full">
                                                    ⭐ {{ $presentation->leadExaminer->lecturer->name }}
                                                </span>
                                            @endif
                                            @foreach($presentation->examiners()->where('is_lead_examiner', false)->get() as $examiner)
                                                <span class="px-3 py-1 text-sm bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 rounded-full">
                                                    {{ $examiner->lecturer->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>

                                    @if($presentation->notes)
                                        <div class="mt-4">
                                            <p class="text-zinc-500 dark:text-zinc-400 text-sm">Notes</p>
                                            <p class="text-black dark:text-white text-sm">{{ $presentation->notes }}</p>
                                        </div>
                                    @endif
                                </div>

                                @if($isLead && $presentation->student->status === 3)
                                    <div class="ml-4">
                                        <flux:button wire:click="openModal('{{ $presentation->id }}')" variant="primary" size="sm" class="cursor-pointer">
                                            Take Action
                                        </flux:button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <flux:modal name="decision-modal" wire:model="showModal">
        <form wire:submit="submitDecision" class="space-y-6">
            <flux:heading size="lg">Presentation Decision</flux:heading>
            
            <div>
                <flux:label>Decision</flux:label>
                <div class="flex gap-4 mt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" wire:model="decision" value="pass" class="text-green-600">
                        <span class="text-black dark:text-white">Pass</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" wire:model="decision" value="fail" class="text-red-600">
                        <span class="text-black dark:text-white">Fail</span>
                    </label>
                </div>
            </div>

            <div>
                <flux:label>Notes (Optional)</flux:label>
                <textarea wire:model="notes" rows="3" class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button type="button" variant="ghost" wire:click="$set('showModal', false)" class="cursor-pointer">Cancel</flux:button>
                <flux:button type="submit" variant="primary" class="cursor-pointer">Submit</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
