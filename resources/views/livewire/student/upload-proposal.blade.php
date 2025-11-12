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
        $this->description = $this->status > 0 ? $user->thesis_description : '';
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

$steps = ['Judul', 'Pilih Dosbing', 'Upload Proposal', 'Sidang Proposal', 'Final Proposal', 'Upload Skripsi', 'Sidang Skripsi', 'Final Skripsi'];
    $currentStepIndex = 2; // Tahap aktif: 'Upload Proposal'
    
    $history = [
        ['tanggal' => '1 Agustus 2024', 'revisi' => 1, 'penjelasan' => 'kurang rinci di bagian latar belakang', 'dokumen' => 1, 'acc' => 'Dosbing Kabid'],
        ['tanggal' => '8 Agustus 2024', 'revisi' => 2, 'penjelasan' => 'sumber kurang terpercaya', 'dokumen' => 2, 'acc' => 'Dosbing Kabid'],
        ['tanggal' => '15 Agustus 2024', 'revisi' => 3, 'penjelasan' => 'banyaknya typo', 'dokumen' => 3, 'acc' => 'Dosbing Kabid'],
        ['tanggal' => '22 Agustus 2024', 'revisi' => 4, 'penjelasan' => 'dalam analisis kurang jelas', 'dokumen' => 4, 'acc' => 'Dosbing Kabid'],
        ['tanggal' => '29 Agustus 2024', 'revisi' => 5, 'penjelasan' => 'metode yang digunakan kurang efisien', 'dokumen' => 5, 'acc' => 'Dosbing Kabid'],
    ];
    // TIDAK PERLU Carbon::setLocale('id') lagi
?>

<div>
    <h5 class="text-lg font-semibold text-gray-700 mb-2">
        Upload Proposal
    </h5>

    <form action="action_url" method="POST" enctype="multipart/form-data">
        @php
            $steps = ['Judul', 'Pilih Dosbing', 'Upload Proposal', 'Sidang Proposal', 'Final Proposal', 'Upload Skripsi', 'Sidang Skripsi', 'Final Skripsi'];
            $currentStepIndex = 2;

            $history = [
                ['tanggal' => '1 Agustus 2024', 'revisi' => 1, 'penjelasan' => 'kurang rinci di bagian latar belakang', 'dokumen' => 1, 'acc' => 'Dosbing Kabid'],
                ['tanggal' => '8 Agustus 2024', 'revisi' => 2, 'penjelasan' => 'sumber kurang terpercaya', 'dokumen' => 2, 'acc' => 'Dosbing Kabid'],
                ['tanggal' => '15 Agustus 2024', 'revisi' => 3, 'penjelasan' => 'banyaknya typo', 'dokumen' => 3, 'acc' => 'Dosbing Kabid'],
                ['tanggal' => '22 Agustus 2024', 'revisi' => 4, 'penjelasan' => 'dalam analisis kurang jelas', 'dokumen' => 4, 'acc' => 'Dosbing Kabid'],
                ['tanggal' => '29 Agustus 2024', 'revisi' => 5, 'penjelasan' => 'metode yang digunakan kurang efisien', 'dokumen' => 5, 'acc' => 'Dosbing Kabid'],
            ];
        @endphp
        @csrf

        <div class="space-y-6 max-w-3xl mx-auto pt-8">

            <!-- Rincian Proposal -->
            <div class="flex flex-col sm:flex-row sm:items-start sm:space-x-4">
                <label for="jurusan_desktop" class="w-full sm:w-32 text-sm font-medium text-gray-700 mb-2 sm:mb-0 sm:pt-2.5">
                    Rincian Proposal
                </label>
                <textarea id="jurusan_desktop" name="jurusan" rows="3"
                    class="flex-grow bg-gray-50 border border-gray-300 text-sm rounded-lg p-2.5 w-full"
                    placeholder="latar belakang, perumusan masalah, tujuan, ruang lingkup, teori"></textarea>
            </div>

            <!-- Upload Proposal -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4">
                <label for="file_input_desktop" class="w-full sm:w-32 text-sm font-medium text-gray-700 mb-2 sm:mb-0">
                    Submit Proposal
                </label>

                <div class="flex flex-col sm:flex-row sm:items-center w-full sm:space-x-3 space-y-3 sm:space-y-0">
                    <input
                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-200 file:text-gray-700 hover:file:bg-gray-300"
                        id="file_input_desktop" name="proposal_file" type="file">

                    <!-- Tombol pindah ke bawah di mobile -->
                    <button type="submit"
                        class="w-full sm:w-auto px-8 py-2 bg-gray-700 text-white font-medium rounded-lg hover:bg-gray-800 transition duration-200">
                        Submit
                    </button>
                </div>
            </div>

        </div>
    </form>

    {{-- HISTORY PROPOSAL --}}
    <div class="mt-12">
        <h3 class="text-sm font-medium text-gray-700 mb-2">History Proposal</h3>
        <div class="relative overflow-x-auto border sm:rounded-lg">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                    <tr>
                        <th scope="col" class="px-6 py-3">Tanggal</th>
                        <th scope="col" class="px-6 py-3">Revisi ke-</th>
                        <th scope="col" class="px-6 py-3">Penjelasan</th>
                        <th scope="col" class="px-6 py-3">Dokumen</th>
                        <th scope="col" class="px-6 py-3">ACC</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($history as $item)
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">{{ $item['tanggal'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $item['revisi'] }}</td>
                            <td class="px-6 py-4">{{ $item['penjelasan'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-blue-600 hover:text-blue-800 cursor-pointer">
                                {{ $item['dokumen'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold">{{ $item['acc'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
