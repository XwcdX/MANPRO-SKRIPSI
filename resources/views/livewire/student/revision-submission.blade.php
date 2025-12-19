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

    public string $type; // 'proposal' atau 'thesis'
    public string $description = '';
    public $file;
    public bool $showCommentModal = false;
    public string $selectedComment = '';


    protected string $paginationTheme = 'tailwind';

    public function openCommentModal(?string $comment)
    {
        if (! $comment) {
            return;
        }

        $this->selectedComment = $comment;
        $this->showCommentModal = true;
    }

    public function closeCommentModal()
    {
        $this->showCommentModal = false;
        $this->selectedComment = '';
    }


    public function mount(string $type)
    {
        if (!in_array($type, ['proposal', 'thesis'])) {
            abort(404);
        }

        $this->type = $type;
    }

    public function getHistoryProperty()
    {
        $relation = $this->type === 'proposal'
            ? $this->user->history_proposals()
            : $this->user->history_theses();

        return $relation->latest()
            ->paginate(5)
            ->through(function ($h) {
                $h->status_text = match ($h->status) {
                    0 => 'Pending',
                    1 => 'Revision',
                    2 => 'Acc Supervisor',
                    3 => 'Acc Kabid',
                    default => 'Unknown',
                };

                return $h;
            });
    }

    public function submit(SubmissionService $service)
    {
        if (! $this->user) {
            $this->dispatch('notify', type: 'error', message: 'User tidak ditemukan.');
            return;
        }

        $validator = Validator::make(
            [
                'description' => $this->description,
                'file' => $this->file,
            ],
            [
                'description' => 'required|string',
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

        if (! $this->file instanceof UploadedFile) {
            $this->dispatch('notify', type: 'error', message: 'File tidak valid.');
            return;
        }

        if (!in_array($this->type, ['proposal', 'thesis'])) {
            $this->dispatch('notify', type: 'error', message: 'Tipe tidak valid.');
            return;
        }

        try {
            $success = $service->submitRevisionFile(
                $this->user,
                $this->file,
                $this->description,
                $this->type
            );

            if ($success) {
                $this->dispatch('notify',
                    type: 'success',
                    message: ucfirst($this->type).' berhasil disubmit.'
                );

                $this->reset(['description', 'file']);
            } else {
                $this->dispatch('notify',
                    type: 'warning',
                    message: 'Masih ada file '.$this->type.' dengan status pending'
                );
            }
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('notify',
                type: 'error',
                message: $e->getMessage()
            );
        } catch (\Throwable $e) {
            logger()->error('Submit revision error', [
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify',
                type: 'error',
                message: 'Terjadi kesalahan saat menyimpan data'
            );
        }
    }

    public function getTitleProperty()
    {
        return $this->type === 'proposal' ? 'Proposal' : 'Skripsi';
    }
};
?>

<div>
    <h5 class="text-lg font-semibold text-gray-700 mb-2">
        Upload {{ $this->title }}
    </h5>

    <form wire:submit.prevent="submit" enctype="multipart/form-data">
        <div class="space-y-6 max-w-3xl mx-auto pt-8">

            <div class="flex flex-col sm:flex-row sm:items-start sm:space-x-4">
                <label class="w-full sm:w-32 text-sm font-medium text-gray-700 mb-2 sm:pt-2.5">
                    Rincian {{ $this->title }}
                </label>

                <textarea wire:model.defer="description"
                    rows="3"
                    class="flex-grow bg-gray-50 border border-gray-300 text-sm rounded-lg p-2.5 w-full">
                </textarea>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4">
                <label class="w-full sm:w-32 text-sm font-medium text-gray-700 mb-2">
                    Submit {{ $this->title }}
                </label>

                <div class="flex flex-col sm:flex-row sm:items-center w-full sm:space-x-3 space-y-3 sm:space-y-0">
                    <input wire:model="file"
                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-200 file:text-gray-700 hover:file:bg-gray-300"
                        id="file" name="file" type="file">

                    <button type="submit"
                        wire:loading.attr="disabled"
                        wire:target="file,submit"
                        class="w-full sm:w-auto px-8 py-2 bg-gray-700 text-white font-medium rounded-lg hover:bg-gray-800 transition duration-200 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                        <!-- Normal (idle) -->
                        <span>
                            Submit
                        </span>
                    </button>
                </div>
            </div>

        </div>
    </form>

    <div class="mt-12">
        <h3 class="text-sm font-medium text-gray-700 mb-2">
            History {{ $this->title }}
        </h3>

        <table class="w-full text-sm text-left text-gray-600 border overflow-x-auto">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3">Tanggal</th>
                    <th class="px-6 py-3">Deskripsi</th>
                    <th class="px-6 py-3">Dokumen</th>
                    <th class="px-6 py-3">Komentar</th>
                    <th class="px-6 py-3">ACC</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->history as $h)
                <tr class="border-b">
                    <td class="px-6 py-4">{{ $h->created_at }}</td>
                    <td class="px-6 py-4">{{ $h->description }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-blue-600 hover:text-blue-800 cursor-pointer">
                        <a href="{{ url(Storage::url($h->file_path)) }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                            Lihat File
                        </a>
                    </td>
                    <td class="px-6 py-4">
                        @if ($h->comment)
                            <button
                                wire:click="openCommentModal({{ json_encode($h->comment) }})"
                                class="px-3 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700 transition cursor-pointer">
                                Lihat
                            </button>
                        @else
                            <span class="text-gray-400 italic">Tidak ada</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 font-semibold">{{ $h->status_text }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $this->history->links() }}
        </div>
    </div>

    @if ($showCommentModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="bg-white rounded-xl w-full max-w-md mx-4 overflow-hidden border
                shadow-2xl ring-1 ring-black/10 pointer-events-auto">
                
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-700">
                        Komentar
                    </h3>
                </div>

                <div class="px-6 py-4 text-sm text-gray-700 whitespace-pre-line max-h-80 overflow-y-auto">
                    {{ $selectedComment }}
                </div>

                <div class="px-6 py-3 bg-gray-50 text-right">
                    <button
                        wire:click="closeCommentModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition cursor-pointer">
                        Tutup
                    </button>
                </div>

            </div>
        </div>
    @endif

</div>
