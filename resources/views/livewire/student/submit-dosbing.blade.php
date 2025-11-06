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
    public ?string $dosbing1 = null;
    public ?string $dosbing2 = null;

    protected $crud;

    public function mount($user)
    {
        $this->crud = new CrudService();
        $this->user = $user;
        $this->status = $user->status ?? 0;
        $raw = $this->crud->setModel(new Lecturer())->all();

        $this->lecturers1 = $raw->where('title', '!=', 0)->mapWithKeys(fn ($lecturer) => [
            $lecturer->id => $lecturer->name
        ])->toArray();
        $this->lecturers2 = $raw->mapWithKeys(fn ($lecturer) => [
            $lecturer->id => $lecturer->name
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

        // Jalankan layanan pengajuan dosen pembimbing
        $success = $service->assignSupervisor(
            studentId: $this->user->id,
            supervisorId: $lecturerId,
            role: $dosbing
        );

        if ($success) {
            $this->dispatch('notify', type: 'success', message: "Pengajuan Dosen Pembimbing " . ($dosbing + 1) . " berhasil dikirim.");
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
        <div class="flex flex-col space-y-2 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-4">
            <label for="dosbing1" class="w-28 text-sm font-medium text-gray-700">Dosbing 1</label>

            <select id="dosbing1"
                wire:model="dosbing1"
                class="flex-grow bg-gray-100 border border-gray-300 rounded-lg p-2.5 text-sm">
                <option value="">Pilih dosen...</option>
                @foreach ($lecturers1 as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>

            <button wire:click="submit(0)"
                class="px-3 py-1.5 sm:px-6 sm:py-2.5 bg-gray-700 text-white font-medium rounded-lg text-sm sm:text-base hover:bg-gray-800 transition duration-200">
                Ajukan
            </button>
        </div>

        <!-- Dosbing 2 -->
        <div class="flex flex-col space-y-2 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-4">
            <label for="dosbing2" class="w-28 text-sm font-medium text-gray-700">Dosbing 2</label>

            <select id="dosbing2"
                wire:model="dosbing2"
                class="flex-grow bg-gray-100 border border-gray-300 rounded-lg p-2.5 text-sm">
                <option value="">Pilih dosen...</option>
                @foreach ($lecturers2 as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>

            <button wire:click="submit(1)"
                class="px-3 py-1.5 sm:px-6 sm:py-2.5 bg-gray-700 text-white font-medium rounded-lg text-sm sm:text-base hover:bg-gray-800 transition duration-200">
                Ajukan
            </button>
        </div>

    </div>

    {{-- Tambahkan Tom Select agar searchable --}}
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