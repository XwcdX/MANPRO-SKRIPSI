<?php

use App\Traits\WithAuthUser;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Services\SubmissionService;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

new class extends Component {
    use WithAuthUser, WithFileUploads;

    public string $type = 'proposal'; // proposal | thesis
    public $file;
    public ?int $status = null;

    public ?string $existing_file_url = null;

    public function mount($type = 'proposal')
    {
        $this->type = $type;

        if (! $this->user) {
            return;
        }

        $this->user->refresh();

        if ($this->type === 'proposal') {
            $this->status = $this->user->status ?? 4;
            if($this->user->final_proposal_path){
                $this->existing_file_url = url(Storage::url($this->user->final_proposal_path));
            }
        }

        if ($this->type === 'thesis') {
            $this->status = $this->user->status ?? 7;
            if($this->user->final_thesis_path){
                $this->existing_file_url = url(Storage::url($this->user->final_thesis_path));
            }
        }
    }

    public function submit(SubmissionService $service)
    {
        if (! $this->user) {
            $this->dispatch('notify', type: 'error', message: 'User tidak ditemukan.');
            return;
        }

        $validator = Validator::make(
            [
                'file' => $this->file,
            ],
            [
                'file' => 'required|file|mimes:pdf,doc,docx|max:2048',
            ]
        );

        if ($validator->fails()) {
            $this->dispatch(
                'notify',
                type: 'error',
                message: $validator->errors()->first()
            );

            $this->setErrorBag($validator->errors());

            return;
        }

        if (! $this->file instanceof TemporaryUploadedFile) {
            Log::error('Objek file tidak valid.', ['type' => get_class($this->file)]);
            $this->dispatch('notify', type: 'error', message: 'File tidak valid.');
            return;
        }

        try {
            if (!in_array($this->type, ['proposal', 'thesis'])) {
                throw new \InvalidArgumentException('Tipe tidak valid');
            }
            
            $response = $service->submitFinalFile(
                $this->user,
                $this->file,
                $this->type
            );

            if ($response['success']) {
                Log::info('File berhasil disubmit.', [
                    'user_id' => $this->user->id,
                    'type' => $this->type,
                ]);

                if($this->status == 4){
                    $this->status == 5;
                    $this->dispatch('student-status-updated', status: 5);
                }
                $this->dispatch('notify', type: 'success', message: $response['message']);
                $this->reset(['file']);
                $this->mount($this->type);
            } else {
                Log::warning('Submit gagal di service.', [
                    'user_id' => $this->user->id,
                    'type' => $this->type,
                ]);

                $this->dispatch('notify', type: 'error', message: $response['message']);
            }

        } catch (\Throwable $e) {
            Log::error('Terjadi kesalahan saat submit.', [
                'user_id' => $this->user->id,
                'type' => $this->type,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', type: 'error', message: 'Terjadi kesalahan saat mengirim file.');
        }
    }

    public function getTitleProperty()
    {
        return $this->type === 'proposal'
            ? 'Submission for Final Proposal'
            : 'Submission for Final Skripsi';
    }

    public function getLabelProperty()
    {
        return $this->type === 'proposal'
            ? 'Submit Proposal'
            : 'Submit Skripsi';
    }
};
?>

<div>
    <h4 class="font-medium text-gray-600 mb-4 text-sm sm:text-base">
        {{ $this->title }}
    </h4>

    <form wire:submit.prevent="submit" enctype="multipart/form-data">
        <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4">
            <label class="w-full sm:w-32 text-sm font-medium text-gray-700 mb-2 sm:mb-0">
                {{ $this->label }}
            </label>

            <div class="flex flex-col sm:flex-row sm:items-center w-full sm:space-x-3 space-y-3 sm:space-y-0">
                <input wire:model="file" accept=".docx, .doc, .pdf"
                    type="file"
                    class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50
                        focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0
                        file:text-sm file:font-semibold file:bg-gray-200 file:text-gray-700 hover:file:bg-gray-300">

                <button type="submit"
                    wire:loading.attr="disabled"
                    wire:target="file,submit"
                    class="w-full sm:w-auto px-8 py-2 bg-gray-700 text-white font-medium rounded-lg hover:bg-gray-800 transition duration-200 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                    Submit
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
