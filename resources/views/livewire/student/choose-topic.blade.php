<?php

use App\Traits\WithAuthUser;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On; 
use App\Services\CrudService;
use App\Models\LecturerTopic;

new class extends Component {
    use WithAuthUser;

    public $topikData;

    public function mount()
    {
        $this->studentStatus = $this->user->status;
        $this->topikData = LecturerTopic::with(['lecturer'])
        ->where('period_id', $this->user->activePeriod()->id)
        ->where('is_available', true)
        ->get()
        ->toArray();
    }

    public function submit($id)
    {
        $topik = LecturerTopic::find($id);

        if (!$topik) {
            $this->showSweetAlert('error', 'Topik tidak ditemukan');
            return;
        }

        // Validasi kuota
        if ($topik->student_quota <= 0) {
            $this->showSweetAlert('warning', 'Kuota topik sudah habis');
            return;
        }

        //Kasih yang manggil service buat anu application
        $this->showSweetAlert('success', 'Topik berhasil direquest');
    }

    public function showSweetAlert($type, $message)
    {
        $this->js("
            Swal.fire({
                toast: true,
                icon: '{$type}',
                title: '{$message}',
                position: 'top-end',
                timer: 3000,
                showConfirmButton: false
            });
        ");
    }
}; ?>

    
<div class="max-w-5xl mx-auto space-y-6">
    
    @if(count($topikData) == 0)

        <div class="flex items-center justify-center h-[70vh]">
            <h1 class="text-white text-2xl font-semibold">
                Tidak ada topik tersedia
            </h1>
        </div>

    @else
        @foreach($topikData as $index => $topik)
        <div class="flex flex-col md:flex-row bg-white rounded-xl shadow-lg overflow-hidden">

            <!-- Bagian Kiri -->
            <div class="flex-1 p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">
                    {{ $topik['topic'] }}
                </h2>

                <p class="text-gray-600 mb-4">
                    {{ $topik['description'] }}
                </p>

                <div class="space-y-1 text-sm text-gray-700">
                    <p><span class="font-medium">Dosen Pengampu:</span> {{ $topik['lecturer']['name'] }}</p>
                    <p><span class="font-medium">Kuota:</span> {{ $topik['student_quota'] }} Orang</p>
                </div>
            </div>

            <!-- Bagian Kanan -->
            <div class="flex items-center justify-center px-6 py-4 bg-gray-50 md:w-auto w-full">
                <button type="submit" wire:click="submit({{ $topik['id'] }})"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition w-full md:w-auto">
                    Ambil Topik
                </button>
            </div>

        </div>
        @endforeach
    @endif

</div>