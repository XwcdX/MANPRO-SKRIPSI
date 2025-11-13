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
    public ?string $division1 = null;
    public ?string $division2 = null;
    public ?string $reason1 = null;
    public ?string $reason2 = null;


    protected $crud;

    public function mount($user)
    {
        $this->crud = new CrudService();
        $this->user = $user;
        $this->status = $user->status ?? 0;
        $raw = Lecturer::with('divisions')->get();

        $this->lecturers1 = $raw->where('title', '!=', 0)->mapWithKeys(fn ($lecturer) => [
            $lecturer->id => $lecturer->name . ($lecturer->divisions->isNotEmpty() ? ' (' . $lecturer->divisions->pluck('name')->implode(', ') . ')' : '')
        ])->toArray();
        $this->lecturers2 = $raw->mapWithKeys(fn ($lecturer) => [
            $lecturer->id => $lecturer->name . ($lecturer->divisions->isNotEmpty() ? ' (' . $lecturer->divisions->pluck('name')->implode(', ') . ')' : '')
        ])->toArray();
        
        // Store divisions for each lecturer
        $this->lecturerDivisions = $raw->mapWithKeys(fn ($lecturer) => [
            $lecturer->id => $lecturer->divisions->map(fn($div) => ['id' => $div->id, 'name' => $div->name])->toArray()
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
        $divisionId = $dosbing === 0 ? $this->division1 : $this->division2;
        $reason = $dosbing === 0 ? $this->reason1 : $this->reason2;

        $success = $service->assignSupervisor(
            studentId: $this->user->id,
            supervisorId: $lecturerId,
            role: $dosbing,
            note: $reason,
            divisionId: $divisionId
        );

        if ($success) {
            $this->dispatch('notify', type: 'success', message: "Pengajuan Dosen Pembimbing " . ($dosbing + 1) . " berhasil dikirim.");
            if ($dosbing === 0) {
                $this->reset(['dosbing1', 'division1', 'reason1']);
            } else {
                $this->reset(['dosbing2', 'division2', 'reason2']);
            }
        } else {
            $this->dispatch('notify', type: 'error', message: "Gagal mengajukan Dosen Pembimbing " . ($dosbing + 1) . ".");
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
                <label for="dosbing1" class="w-full text-sm font-medium text-gray-700">Dosbing 1</label>

                <select id="dosbing1"
                    wire:model.live="dosbing1"
                    class="bg-gray-100 border border-gray-300 rounded-lg p-2.5 text-sm">
                    <option value="">Pilih dosen...</option>
                    @foreach ($lecturers1 as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>

                @if($dosbing1 && is_string($dosbing1) && isset($lecturerDivisions[$dosbing1]) && count($lecturerDivisions[$dosbing1]) > 0)
                    <select wire:model="division1"
                        class="bg-gray-100 border border-gray-300 rounded-lg p-2.5 text-sm">
                        <option value="">Pilih bidang/divisi...</option>
                        @foreach ($lecturerDivisions[$dosbing1] as $division)
                            <option value="{{ $division['id'] }}">{{ $division['name'] }}</option>
                        @endforeach
                    </select>
                @endif

                <!-- Textbox tambahan -->
                <input type="text"
                    wire:model.defer="reason1"
                    placeholder="Tulis alasan pengajuan dosbing 1..."
                    class="bg-white border border-gray-300 rounded-lg p-2.5 text-sm focus:ring focus:ring-gray-200" />
            </div>

            <button wire:click="submit(0)"
                class="px-3 py-1.5 sm:px-6 sm:py-2.5 bg-gray-700 text-white font-medium rounded-lg text-sm sm:text-base hover:bg-gray-800 transition duration-200 self-end sm:self-center">
                Ajukan
            </button>
        </div>

        <!-- Dosbing 2 -->
        <div class="flex flex-col space-y-3 sm:space-y-2 sm:flex-row sm:items-start sm:space-x-4">
            <div class="flex flex-col flex-grow space-y-2">
                <label for="dosbing2" class="w-full text-sm font-medium text-gray-700">Dosbing 2</label>

                <select id="dosbing2"
                    wire:model.live="dosbing2"
                    class="bg-gray-100 border border-gray-300 rounded-lg p-2.5 text-sm">
                    <option value="">Pilih dosen...</option>
                    @foreach ($lecturers2 as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>

                @if($dosbing2 && is_string($dosbing2) && isset($lecturerDivisions[$dosbing2]) && count($lecturerDivisions[$dosbing2]) > 0)
                    <select wire:model="division2"
                        class="bg-gray-100 border border-gray-300 rounded-lg p-2.5 text-sm">
                        <option value="">Pilih bidang/divisi...</option>
                        @foreach ($lecturerDivisions[$dosbing2] as $division)
                            <option value="{{ $division['id'] }}">{{ $division['name'] }}</option>
                        @endforeach
                    </select>
                @endif

                <!-- Textbox tambahan -->
                <input type="text"
                    wire:model.defer="reason2"
                    placeholder="Tulis alasan pengajuan dosbing 2..."
                    class="bg-white border border-gray-300 rounded-lg p-2.5 text-sm focus:ring focus:ring-gray-200" />
            </div>

            <button wire:click="submit(1)"
                class="px-3 py-1.5 sm:px-6 sm:py-2.5 bg-gray-700 text-white font-medium rounded-lg text-sm sm:text-base hover:bg-gray-800 transition duration-200 self-end sm:self-center">
                Ajukan
            </button>
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
