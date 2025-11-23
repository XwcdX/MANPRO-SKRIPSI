<?php

use App\Traits\WithAuthUser;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Services\SubmissionService;
use App\Services\CrudService;
use Livewire\WithFileUploads;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithAuthUser, WithFileUploads, WithPagination;

    public string $description = '';
    public $thesis_file;

    public function getHistoryProperty()
    {
        return $this->user->history_theses()
        ->latest()
        ->paginate(5)
        ->through(function ($h) {
            $statusText = match ($h->status) {
                0 => 'Pending',
                1 => 'Revision',
                2 => 'Acc Supervisor',
                3 => 'Acc Kabid',
                default => 'Unknown',
            };

            $h->status_text = $statusText;
            return $h;
        });
    }

    public function submit(SubmissionService $service)
    {

        // pastikan user terautentikasi
        if (! $this->user) {
            Log::warning('Submit skripsi gagal: user tidak ditemukan.');
            $this->dispatch('notify', type: 'error', message: 'User tidak ditemukan.');
            return;
        }

        // validasi input
        try {
            $this->validate([
                'description' => 'required|string',
                'thesis_file' => 'required|file|mimes:pdf,doc,docx|max:2048',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validasi gagal.', ['errors' => $e->errors()]);
            $this->dispatch('notify', type: 'error', message: 'Validasi gagal, periksa input Anda.');
            return;
        }

        // pastikan file berupa UploadedFile
        if (! $this->thesis_file instanceof UploadedFile) {
            Log::error('Objek file tidak valid.', ['type' => gettype($this->thesis_file)]);
            $this->dispatch('notify', type: 'error', message: 'File tidak valid.');
            return;
        }

        try {
            // simpan submission
            $success = $service->submitThesis(
                $this->user,
                $this->thesis_file,
                $this->description,
            );

            if ($success) {
                $this->dispatch('notify', type: 'success', message: 'Skripsi berhasil disubmit.');
                $this->reset(['description', 'thesis_file']);
                $this->mount();
            } else {
                Log::warning('Submit skripsi gagal disimpan di service.', ['user_id' => $this->user->id]);
                $this->dispatch('notify', type: 'error', message: 'Gagal menyimpan skripsi.');
            }

        } catch (\Throwable $e) {
            Log::error('Terjadi kesalahan saat submit skripsi.', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->dispatch('notify', type: 'error', message: 'Terjadi kesalahan saat mengirim skripsi.');
        }
    }
};
?>

<div>
    <h5 class="text-lg font-semibold text-gray-700 mb-2">
        Upload Skripsi
    </h5>

    <form wire:submit.prevent="submit" enctype="multipart/form-data">
        
        @csrf

        <div class="space-y-6 max-w-3xl mx-auto pt-8">

            <!-- Rincian Skripsi -->
            <div class="flex flex-col sm:flex-row sm:items-start sm:space-x-4">
                <label for="jurusan_desktop" class="w-full sm:w-32 text-sm font-medium text-gray-700 mb-2 sm:mb-0 sm:pt-2.5">
                    Rincian Skripsi
                </label>
                <textarea wire:model.defer="description" id="jurusan_desktop" name="jurusan" rows="3"
                    class="flex-grow bg-gray-50 border border-gray-300 text-sm rounded-lg p-2.5 w-full"
                    placeholder="latar belakang, perumusan masalah, tujuan, ruang lingkup, teori"></textarea>
            </div>

            <!-- Upload Skripsi -->
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

        </div>
    </form>

    {{-- HISTORY Skripsi --}}
    <div class="mt-12">
        <h3 class="text-sm font-medium text-gray-700 mb-2">History Skripsi</h3>
        <div class="relative overflow-x-auto border sm:rounded-lg">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                    <tr>
                        <th scope="col" class="px-6 py-3">Tanggal</th>
                        <th scope="col" class="px-6 py-3">Deskripsi</th>
                        <th scope="col" class="px-6 py-3">Dokumen</th>
                        <th scope="col" class="px-6 py-3">Komentar</th>
                        <th scope="col" class="px-6 py-3">ACC</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->history as $h)
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">{{ $h->created_at->format('Y-m-d H:i:s') }}</td>
                            <td class="px-6 py-4">{{ $h->description }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-blue-600 hover:text-blue-800 cursor-pointer">
                                <a href="{{ Storage::url($h->file_path) }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                    Lihat File
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $h->comment }}</td>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold">{{ $h->status_text }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
