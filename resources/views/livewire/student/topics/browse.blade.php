<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\LecturerTopic;
use App\Models\TopicApplication;
use App\Models\Period;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterDivision = '';
    public ?LecturerTopic $applying = null;
    public bool $showModal = false;
    public string $student_notes = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDivision(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $student = auth()->user();
        $activePeriod = $student->activePeriod();
        
        $hasSupervisor = DB::table('student_lecturers')
            ->where('student_id', $student->id)
            ->where('role', 0)
            ->where('status', 'active')
            ->exists();
            
        $hasAcceptedTopic = false;
        $existingApplication = null;
        if ($activePeriod) {
            $hasAcceptedTopic = TopicApplication::where('student_id', $student->id)
                ->where('status', 'accepted')
                ->exists();
                
            $existingApplication = TopicApplication::where('student_id', $student->id)
                ->whereIn('status', ['pending'])
                ->first();
        }

        $query = LecturerTopic::with(['lecturer.divisions'])
            ->where('lecturer_topics.is_available', true)
            ->where('lecturer_topics.student_quota', '>', 0)
            ->leftJoin('topic_applications as ta', function ($join) use ($student, $activePeriod) {
                $join->on('lecturer_topics.id', '=', 'ta.topic_id')
                    ->where('ta.student_id', $student->id)
                    ->where('ta.period_id', $activePeriod->id);
            })
            ->select(
                'lecturer_topics.*',
                'ta.status as application_status'
            );

        if ($this->search) {
            $query->where(function($q) {
                $q->where('topic', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhereHas('lecturer', function($q2) {
                      $q2->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->filterDivision) {
            $query->whereHas('lecturer.divisions', function($q) {
                $q->where('divisions.id', $this->filterDivision);
            });
        }

        return [
            'topics' => $query->latest()->paginate(15),
            'divisions' => DB::table('divisions')->orderBy('name')->get(),
            'activePeriod' => $activePeriod,
            'existingApplication' => $existingApplication,
            'hasAcceptedTopic' => $hasAcceptedTopic,
            'hasSupervisor' => $hasSupervisor,
        ];
    }

    public function openApplyModal(LecturerTopic $topic): void
    {
        $student = auth()->user();
        $activePeriod = $student->activePeriod();
        
        if (!$activePeriod) {
            session()->flash('error', 'No active period available.');
            return;
        }

        $existingApplication = TopicApplication::where('student_id', auth()->id())
            ->whereIn('status', ['pending'])
            ->first();

        if ($existingApplication) {
            session()->flash('error', 'You already have a pending application. Please wait for the response.');
            return;
        }

        $this->applying = $topic;
        $this->student_notes = '';
        $this->showModal = true;
    }

    public function submitApplication(): void
    {
        $this->validate([
            'student_notes' => 'nullable|string|max:1000',
        ]);

        if (!$this->applying) {
            return;
        }

        $student = auth()->user();
        $activePeriod = $student->activePeriod();
        
        if (!$activePeriod) {
            session()->flash('error', 'No active period available.');
            return;
        }

        $existingApplication = TopicApplication::where('student_id', auth()->id())
            ->whereIn('status', ['pending'])
            ->first();

        if ($existingApplication) {
            session()->flash('error', 'You already have a pending application.');
            $this->showModal = false;
            return;
        }

        TopicApplication::updateOrCreate(
        [
            'student_id' => auth()->id(),
            'period_id' => $activePeriod->id,
        ],
        [
            'student_id' => auth()->id(),
            'topic_id' => $this->applying->id,
            'lecturer_id' => $this->applying->lecturer_id,
            'period_id' => $activePeriod->id,
            'student_notes' => $this->student_notes,
            'status' => 'pending',
        ]);

        session()->flash('success', 'Application submitted successfully. Please wait for lecturer response.');
        $this->showModal = false;
        $this->reset('applying', 'student_notes');
    }
};

?>

<div>
    <div class="bg-white rounded-lg shadow-lg p-6 text-black">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Browse Lecturer Topics</h1>
            <p class="text-gray-600 mt-1">Find and apply for thesis topics offered by lecturers.</p>
        </div>

        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <input wire:model.live.debounce.300ms="search" type="text"
                placeholder="Search topics or lecturers..."
                class="px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
            
            <select wire:model.live="filterDivision"
                class="px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Divisions</option>
                @foreach ($divisions as $division)
                    <option value="{{ $division->id }}">{{ $division->name }}</option>
                @endforeach
            </select>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        @if($hasSupervisor)
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">
                ✓ You already have a supervisor assigned. You can browse but cannot apply for topics.
            </div>
        @elseif($existingApplication)
            <div class="mb-4 p-4 bg-yellow-100 text-yellow-800 rounded-lg">
                You have a pending application for: <strong>{{ $existingApplication->topic->topic }}</strong>
            </div>
        @endif

        <div class="space-y-4">
            @forelse ($topics as $topic)
                <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h3 class="text-xl font-semibold text-gray-800">{{ $topic->topic }}</h3>
                            <p class="text-sm text-gray-600 mt-1">
                                By: <span class="font-medium">{{ $topic->lecturer->name }}</span>
                                @if($topic->lecturer->divisions->isNotEmpty())
                                    <span class="text-gray-400">•</span>
                                    @foreach($topic->lecturer->divisions as $division)
                                        <span class="text-blue-600">{{ $division->name }}</span>@if(!$loop->last), @endif
                                    @endforeach
                                @endif
                            </p>
                            @if($topic->description)
                                <p class="text-gray-700 mt-3">{{ $topic->description }}</p>
                            @endif
                            <div class="flex gap-4 mt-3 text-sm">
                                <span class="text-gray-600">
                                    <strong>Capacity:</strong> {{ $topic->student_quota }} student(s)
                                </span>
                            </div>
                            @if($topic->application_status && $topic->application_status == 'declined')
                                <p class="text-red-500 mt-3">{{ ucfirst($topic->application_status) }}</p>
                            @endif
                        </div>
                        <div class="ml-4">
                            @if($hasSupervisor || $existingApplication || ($topic->application_status && $topic->application_status == 'declined'))
                                <button disabled class="px-4 py-2 bg-gray-300 text-gray-500 rounded-lg cursor-not-allowed">
                                    Apply
                                </button>
                            @else
                                <button wire:click="openApplyModal('{{ $topic->id }}')" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 cursor-pointer">
                                    Apply
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12 text-gray-500">
                    No topics available at the moment.
                </div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $topics->links() }}
        </div>
    </div>

    @if($showModal && $applying)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Apply for Topic</h2>
                
                <div class="space-y-4 mb-6">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Topic</label>
                        <p class="text-gray-900">{{ $applying->topic }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Lecturer</label>
                        <p class="text-gray-900">{{ $applying->lecturer->name }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Your Notes (Optional)</label>
                        <textarea wire:model="student_notes" rows="4"
                            placeholder="Explain your interest in this topic and why you're a good fit..."
                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        @error('student_notes')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="flex gap-2 justify-end">
                    <button wire:click="$set('showModal', false)" 
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 cursor-pointer">
                        Cancel
                    </button>
                    <button wire:click="submitApplication" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 cursor-pointer">
                        Submit Application
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
