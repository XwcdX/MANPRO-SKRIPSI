<?php

use App\Traits\WithAuthUser;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Services\SubmissionService;

new class extends Component {
    use WithAuthUser;

    public string $title = '';
    public string $description = '';
    public ?int $status = null;
    public $user;

    public function mount($user)
    {
        $this->user = $user;
        $this->status = $user->status ?? 0;
        $this->title = $this->status > 0 ? $user->thesis_title : '';
        $this->description = $this->status > 0 ? $user->thesis_description ?? '' : '';
    }

    public function submit(SubmissionService $service)
    {
        // pastikan usernya ada dan guard aktif
        if (! $this->user) {
            $this->dispatch('notify', type: 'error', message: 'User tidak ditemukan.');
            return;
        }

        // panggil service
        $success = $service->submitTitle($this->user->id, $this->title, $this->description);

        if ($success) {
            $this->status = 1;
            $this->dispatch('student-status-updated', status: 1);
            $this->dispatch('notify', type: 'success', message: 'Judul berhasil disubmit.');
        } else {
            $this->dispatch('notify', type: 'error', message: 'Judul tidak valid atau sudah dipakai.');
        }
    }
};
?>

<form wire:submit.prevent="submit" class="space-y-6">
    <h5 class="text-lg font-semibold text-gray-700 mb-2">
        {{ $status > 0 ? 'Ubah Judul Proposal' : 'Input Judul Proposal' }}
    </h5>

    {{-- Input Judul + Search Button --}}
    <div>
        <label for="input-judul" class="block mb-2 text-sm font-medium text-gray-700">
            Input Judul
        </label>

        <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-3 space-y-3 sm:space-y-0">
            <input 
                type="text" 
                id="input-judul" 
                wire:model.defer="title"
                class="placeholder-gray-500 text-gray-700 flex-grow bg-gray-100 border border-gray-300 
                       text-sm rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                placeholder="Masukkan judul proposal..."
            >

            <button 
                type="button"
                class="px-5 py-2.5 bg-gray-700 text-white font-medium rounded-lg hover:bg-gray-800 transition sm:w-auto w-full">
                Search
            </button>
        </div>
    </div>

    {{-- Deskripsi --}}
    <div>
        <label for="deskripsi" class="block mb-2 text-sm font-medium text-gray-700">Deskripsi</label>
        <textarea 
            id="deskripsi" 
            wire:model.defer="description"
            rows="6"
            class="placeholder-gray-500 text-gray-700 bg-gray-100 border border-gray-300 
                   text-sm rounded-lg w-full p-2.5 focus:ring-2 focus:ring-blue-500 focus:outline-none"
            placeholder="Jelaskan deskripsi singkat..."
        ></textarea>
    </div>

    {{-- Tombol Submit --}}
    <div class="flex flex-col sm:flex-row sm:justify-end">
        <button 
            type="submit"  wire:loading.attr="disabled"
            class="px-8 py-2.5 bg-gray-700 text-white font-medium rounded-lg hover:bg-gray-800 
                   transition w-full sm:w-auto">
            {{-- Saat tidak loading --}}
            <span wire:loading.remove>
                {{ $status > 0 ? 'Change' : 'Submit' }}
            </span>

            {{-- Saat loading --}}
            <span wire:loading>
                <svg class="animate-spin h-4 w-4 text-white inline-block mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3.5-3.5L12 0v4a8 8 0 11-8 8h4z"></path>
                </svg>
                Processing...
            </span>
        </button>
    </div>
</form>