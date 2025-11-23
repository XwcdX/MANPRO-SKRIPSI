<?php

use App\Traits\WithAuthUser;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Services\CrudService;
use App\Models\Lecturer;
use App\Services\SubmissionService;

new class extends Component {
    use WithAuthUser;

    public $user;
    public ?int $status = null;

    public array $lecturers1 = [];
    public array $lecturers2 = [];
    public array $lecturerDivisions = [];
    public ?string $dosbing1 = null;
    public ?string $dosbing2 = null;
    public ?string $reason1 = null;
    public ?string $reason2 = null;
    public ?string $status1 = null;
    public ?string $status2 = null;
    public bool $editMode1 = false;
    public bool $editMode2 = false;


    protected $crud;

    public function mount()
    {
        $this->crud = new CrudService();
        $this->user->load([
            'supervisionApplications' => fn ($q) => $q->where('period_id', $this->user->activePeriod()->id)->whereNot('status', 'declined')->whereNot('status', 'canceled'),
            'supervisors'
        ]);
        // 1. Ambil dosbing resmi dulu dari pivot
        foreach ($this->user->supervisors as $lecturer) {
            if ($lecturer->pivot->role == 0) {
                $this->dosbing1 = $lecturer->id;
                $this->status1 = 'active';
            }

            if ($lecturer->pivot->role == 1) {
                $this->dosbing2 = $lecturer->id;
                $this->status2 = 'active';
            }
        }

        // 2. Ambil yang belum ada dari supervisionApplications
        foreach ($this->user->supervisionApplications as $app) {
            if ($app->proposed_role == 0 && !$this->dosbing1) {
                $this->dosbing1 = $app->lecturer_id;
                $this->reason1 = $app->student_notes;
                $this->status1 = $app->status;
            }

            if ($app->proposed_role == 1 && !$this->dosbing2) {
                $this->dosbing2 = $app->lecturer_id;
                $this->reason2 = $app->student_notes;
                $this->status2 = $app->status;
            }
        }
        $this->status = $user->status ?? 0;
        $raw = Lecturer::with('divisions')->get();

        $this->lecturers1 = $raw->filter(fn ($lecturer) => $lecturer->hasPermissionTo('offer-topics'))->mapWithKeys(fn ($lecturer) => [
            $lecturer->id => $lecturer->name . ($lecturer->divisions->isNotEmpty() ? ' (' . $lecturer->divisions->pluck('name')->implode(', ') . ')' : '')
        ])->toArray();
        $this->lecturers2 = $raw->mapWithKeys(fn ($lecturer) => [
            $lecturer->id => $lecturer->name . ($lecturer->divisions->isNotEmpty() ? ' (' . $lecturer->divisions->pluck('name')->implode(', ') . ')' : '')
        ])->toArray();
    }

    public function submit($dosbing, SubmissionService $service)
    {
        // Validasi input
        if ($dosbing === 0 && !$this->dosbing1) {
            $this->dispatch('notify', type: 'error', message: 'Pilih Dosen Pembimbing 1 terlebih dahulu.');
            return;
        }

        if ($dosbing === 1 && !$this->dosbing2) {
            $this->dispatch('notify', type: 'error', message: 'Pilih Dosen Pembimbing 2 terlebih dahulu.');
            return;
        }

        $lecturerId = $dosbing === 0 ? $this->dosbing1 : $this->dosbing2;
        $reason = $dosbing === 0 ? $this->reason1 : $this->reason2;

        $response = $service->assignSupervisor(
            studentId: $this->user->id,
            supervisorId: $lecturerId,
            role: $dosbing,
            note: $reason,
        );

        if ($response['success']) {
            if ($dosbing === 0) {
                $this->status1 = 'pending';
                $this->editMode1 = false;
            } else {
                $this->status2 = 'pending';
                $this->editMode2 = false;
            }

            $this->dispatch('notify', type: 'success', message: $response['message']);
        } else {
            $this->dispatch('notify', type: 'error', message: $response['message']);
        }
    }
    public function enableEdit($dosbing)
    {
        if ($dosbing === 0) {
            $this->editMode1 = true;
        } else {
            $this->editMode2 = true;
        }
    }

    public function cancel($dosbing, SubmissionService $service)
    {
        // Panggil service cancel jika ada, atau set ulang statusnya saja
        $lecturerId = $dosbing === 0 ? $this->dosbing1 : $this->dosbing2;
        $response = $service->cancelSupervisor(
            studentId: $this->user->id,
            supervisorId: $lecturerId,
            role: $dosbing,
        );

        if ($response['success']) {
            if ($dosbing === 0) {
                $this->status1 = null;
                $this->dosbing1 = null;
                $this->reason1 = null;
            } else {
                $this->status2 = null;
                $this->dosbing2 = null;
                $this->reason2 = null;
            }

            $this->dispatch('notify', type: 'success', message: $response['message']);
        } else {
            $this->dispatch('notify', type: 'error', message: $response['message']);
        }
    }

};
?>

<div>
    <h4 class="font-medium text-gray-600 mb-4 text-sm sm:text-base">Pilih Dosen Pembimbing</h4>

    <div class="space-y-6 max-w-xl mx-auto pt-2 md:pt-8 md:mb-20 mb-4">

        <!-- Dosbing 1 -->
        <div class="flex flex-col space-y-3 sm:space-y-2 sm:flex-row sm:items-start sm:space-x-4">
            <div class="flex flex-col flex-grow space-y-2">
                <label for="dosbing1" class="w-full text-sm font-medium text-gray-700">Dosbing 1 {{ $status1 === 'pending' ? '(Pending)' : ($status1 === 'accepted' ? ($editMode1 ? '(Editing)' : '(Accepted)') : '') }}</label>

                <select id="dosbing1"
                    wire:model.live="dosbing1"
                    @disabled($status1 === 'pending' || $status1 === 'accepted')
                    class="bg-gray-100 border border-gray-300 rounded-lg p-2.5 text-sm">
                    <option value="">Pilih dosen...</option>
                    @foreach ($lecturers1 as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>

                <!-- Textbox tambahan -->
                <input type="text"
                    wire:model.defer="reason1"
                    @disabled($status1 === 'pending' || $status1 === 'accepted' && !$editMode1)
                    placeholder="Tulis alasan pengajuan dosbing 1..."
                    class="bg-white border border-gray-300 rounded-lg p-2.5 text-sm focus:ring focus:ring-gray-200" />
            </div>

            @if ($status1 === 'pending')
                <button
                    wire:click="cancel(0)"
                    class="px-3 py-1.5 sm:px-6 sm:py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg text-sm sm:text-base self-end sm:self-center">
                    Cancel
                </button>

            @elseif ($status1 === 'accepted' && !$editMode1)
                <button
                    wire:click="enableEdit(0)"
                    class="px-3 py-1.5 sm:px-6 sm:py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg text-sm sm:text-base self-end sm:self-center">
                    Change
                </button>

            @else
                <button
                    wire:click="submit(0)"
                    wire:loading.attr="disabled"
                    class="px-3 py-1.5 sm:px-6 sm:py-2.5 bg-gray-700 text-white font-medium rounded-lg text-sm sm:text-base hover:bg-gray-800 self-end sm:self-center">
                    Ajukan
                </button>
            @endif

        </div>

        <!-- Dosbing 2 -->
        <div class="flex flex-col space-y-3 sm:space-y-2 sm:flex-row sm:items-start sm:space-x-4">
            <div class="flex flex-col flex-grow space-y-2">
                <label for="dosbing2" class="w-full text-sm font-medium text-gray-700">Dosbing 2 {{ $status2 === 'pending' ? '(Pending)' : ($status2 === 'accepted' ? ($editMode2 ? '(Editing)' : '(Accepted)') : '') }}</label>

                <select id="dosbing2"
                    wire:model.live="dosbing2"
                    @disabled($status2 === 'pending' || $status2 === 'accepted' && !$editMode2)
                    class="bg-gray-100 border border-gray-300 rounded-lg p-2.5 text-sm">
                    <option value="">Pilih dosen...</option>
                    @foreach ($lecturers2 as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>

                <!-- Textbox tambahan -->
                <input type="text"
                    wire:model.defer="reason2"
                    @disabled($status2 === 'pending' || $status2 === 'accepted' && !$editMode2)
                    placeholder="Tulis alasan pengajuan dosbing 2..."
                    class="bg-white border border-gray-300 rounded-lg p-2.5 text-sm focus:ring focus:ring-gray-200" />
            </div>

            @if ($status2 === 'pending')
                <button
                    wire:click="cancel(1)"
                    class="px-3 py-1.5 sm:px-6 sm:py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg text-sm sm:text-base self-end sm:self-center">
                    Cancel
                </button>

            @elseif ($status2 === 'accepted' && !$editMode2)
                <button
                    wire:click="enableEdit(1)"
                    class="px-3 py-1.5 sm:px-6 sm:py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg text-sm sm:text-base self-end sm:self-center">
                    Change
                </button>

            @else
                <button
                    wire:click="submit(1)"
                    wire:loading.attr="disabled"
                    class="px-3 py-1.5 sm:px-6 sm:py-2.5 bg-gray-700 text-white font-medium rounded-lg text-sm sm:text-base hover:bg-gray-800 self-end sm:self-center">
                    Ajukan
                </button>
            @endif

        </div>

    </div>

    <script>
        document.addEventListener('livewire:load', () => {
            const initSelect = (id) => {
                if (window.TomSelect) {
                    new TomSelect(`#${id}`, { create: false, sortField: { field: "text", direction: "asc" } });
                }
            };
            initSelect('dosbing1');
            initSelect('dosbing2');
        });
    </script>
</div>
