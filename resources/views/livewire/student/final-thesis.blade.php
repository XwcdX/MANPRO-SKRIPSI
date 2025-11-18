<?php

use App\Traits\WithAuthUser;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Services\SubmissionService;
use Livewire\WithFileUploads;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

new class extends Component {
    use WithAuthUser, WithFileUploads;

    public $thesis_file;
    public ?string $existing_file_url = null;

    public function mount()
    {
        if ($this->user) {
            $this->user->refresh();
            if ($this->user->final_thesis_path) {
                $this->existing_file_url = Storage::url($this->user->final_thesis_path);
            }
        }
    }

    public function submit(SubmissionService $service)
    {
        if (! $this->user) {
            $this->dispatch('notify', type: 'error', message: 'User tidak ditemukan.');
            return;
        }

        // validasi input
        try {
            $this->validate([
                'thesis_file' => 'required|file|mimes:pdf,doc,docx|max:2048',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validasi gagal.', ['errors' => $e->errors()]);
            $this->dispatch('notify', type: 'error', message: 'Validasi gagal, periksa input Anda.');
            return;
        }

        // pastikan file berupa UploadedFile
        if (! $this->thesis_file instanceof TemporaryUploadedFile) {
            Log::error('Objek file tidak valid.', ['type' => get_class($this->thesis_file)]);
            $this->dispatch('notify', type: 'error', message: 'File tidak valid.');
            return;
        }

        try {
            // simpan submission
            $response = $service->submitFinalthesis(
                $this->user,
                $this->thesis_file,
            );

            if ($response['success']) {
                Log::info('Thesis berhasil disubmit.', ['user_id' => $this->user->id]);
                $this->dispatch('notify', type: 'success', message: $response['message']);
                $this->reset(['thesis_file']);
                $this->mount();
            } else {
                Log::warning('Submit thesis gagal disimpan di service.', ['user_id' => $this->user->id]);
                $this->dispatch('notify', type: 'error', message: $response['message']);
            }

        } catch (\Throwable $e) {
            Log::error('Terjadi kesalahan saat submit thesis.', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->dispatch('notify', type: 'error', message: 'Terjadi kesalahan saat mengirim thesis.');
        }
    }
};
?>

<div>
    <h4 class="font-medium text-gray-600 mb-4 text-sm sm:text-base">
        Submission for Final Skripsi
    </h4>

    <form wire:submit.prevent="submit" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4">
            <label for="file_input_desktop" class="w-full sm:w-32 text-sm font-medium text-gray-700 mb-2 sm:mb-0">
                Submit Skripsi
            </label>

            <div class="flex flex-col sm:flex-row sm:items-center w-full sm:space-x-3 space-y-3 sm:space-y-0">
                <input wire:model="thesis_file"
                    class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-200 file:text-gray-700 hover:file:bg-gray-300"
                    id="file_input_desktop" name="thesis_file" type="file">

                <!-- Tombol pindah ke bawah di mobile -->
                <button type="submit"
                    wire:loading.attr="disabled"
                    wire:target="thesis_file,submit"
                    class="w-full sm:w-auto px-8 py-2 bg-gray-700 text-white font-medium rounded-lg hover:bg-gray-800 transition duration-200 disabled:opacity-60 disabled:cursor-not-allowed">
                    <!-- Normal (idle) -->
                    <span>
                        Submit
                    </span>
                </button>
            </div>
        </div>
    </form>
    @if ($existing_file_url)
    <p class="text-sm text-gray-600 mt-4">
        File saat ini:
        <a href="{{ $existing_file_url }}" target="_blank" class="text-blue-600 hover:text-blue-800">
            Lihat File
        </a>
    </p>
@endif
</div>