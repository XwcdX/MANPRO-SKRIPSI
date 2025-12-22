<?php

use App\Models\PeriodSchedule;
use App\Traits\WithAuthUser;
use Livewire\Volt\Component;
use App\Services\ScheduleSelectionService;

new class extends Component {
    use WithAuthUser;

    public string $type = 'proposal';

    public $presentation = null;

    public array $schedules = [];
    public ?string $selectedScheduleId = null;

    public ?string $tanggal = '';
    public ?string $jam = '';
    public ?string $lokasi = '';
    public ?string $status = '';

    public ?PeriodSchedule $selectedSchedule = null; // WAJIB

    private function scheduleColumn()
    {
        return $this->type === 'final'
            ? 'final_schedule_id'
            : 'proposal_schedule_id';
    }

    private function scheduleType()
    {
        return $this->type === 'final'
            ? 'thesis_defense'
            : 'proposal_hearing';
    }

    public function mount($type = 'proposal')
    {
        $this->type = $type;

        $period = $this->user->activePeriod();
        if (!$period) return;

        $periodId = $period->id;

        $scheduleType = $this->scheduleType();
        $column = $this->scheduleColumn();

        $this->schedules = PeriodSchedule::where('period_id', $periodId)
            ->where('type', $scheduleType)
            ->orderBy('start_date', 'asc')
            ->get()
            ->toArray();

        $this->selectedScheduleId = $this->user->$column;

        if ($this->selectedScheduleId) {
            $this->selectedSchedule = PeriodSchedule::find($this->selectedScheduleId);

            if ($this->selectedSchedule) {
                $deadline = \Carbon\Carbon::parse($this->selectedSchedule->deadline);

                if (now()->greaterThan($deadline)) {
                    $this->loadPresentationDetail();
                    return;
                }
            }
        }
    }

    private function loadPresentationDetail()
    {
        $relation = $this->type === 'final'
            ? 'finalPresentations'
            : 'proposalPresentations';

        $column = $this->scheduleColumn();

        $this->user->load([
            $relation => fn ($q) => $q
                ->when($this->user->$column, fn($query) =>
                    $query->where('period_schedule_id', $this->user->$column)
                )
                ->with(['venue'])
        ]);

        $this->presentation = $this->user->$relation->first();

        if (!$this->presentation && $this->selectedScheduleId) {
            $this->tanggal = null;
            $this->jam = null;
            $this->lokasi = null;
            $this->status = 'Waiting';
            return;
        }

        if ($this->presentation) {
            $this->setupPresentationData();
        }
    }

    private function setupPresentationData()
    {
        $p = $this->presentation;

        $this->tanggal = \Carbon\Carbon::parse($p->presentation_date)
            ->translatedFormat('d F Y');

        $this->jam = \Carbon\Carbon::parse($p->start_time)->format('H:i')
            . ' - ' . \Carbon\Carbon::parse($p->end_time)->format('H:i');

        $this->lokasi = $p->venue
            ? $p->venue->name . ' - ' . $p->venue->location
            : '-';

        $this->status = ucfirst($p->status ?? 'Terjadwal');
    }

    public function chooseSchedule($id)
    {
        try {
            app(ScheduleSelectionService::class)
                ->choose($this->user, $id, $this->type);

            $this->user->refresh();
            $this->selectedScheduleId = $id;

            // Dispatch ke listener
            $this->dispatch('notify', 'success', 'Berhasil memilih jadwal!');

        } catch (\Exception $e) {
            $this->dispatch('notify', 'error', $e->getMessage());
        }
    }

    public function cancelSchedule()
    {
        try {
            app(ScheduleSelectionService::class)
                ->cancel($this->user, $this->type);

            $this->user->refresh();
            $this->selectedScheduleId = null;

            $this->dispatch('notify', 'success', 'Jadwal berhasil dibatalkan!');

        } catch (\Exception $e) {
            $this->dispatch('notify', 'error', $e->getMessage());
        }
    }

    #[On('notify')]
    public function showSweetAlert($type, $message)
    {
        $this->js("
            Swal.fire({
                toast: true,
                icon: '{$type}',
                title: '{$message}',
                position: 'top-end',
                timer: 3000,
                showConfirmButton: false,
                width: '500px'
            });
        ");
    }

    public function getSelectedScheduleProperty()
    {
        return $this->selectedScheduleId
            ? PeriodSchedule::find($this->selectedScheduleId)
            : null;
    }

    public function confirmChoose($id)
    {
        $this->js("
            Swal.fire({
                title: 'Daftar Jadwal?',
                text: 'Apakah kamu yakin ingin mendaftar jadwal ini?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, daftar!'
            }).then((result) => {
                if (result.isConfirmed) {
                    \$wire.chooseSchedule('{$id}');
                } else {
                    if(btn) btn.disabled = false;
                }
            });
        ");
    }

    public function confirmCancel()
    {
        $this->js("
            Swal.fire({
                title: 'Batalkan Jadwal?',
                text: 'Apakah kamu yakin ingin membatalkan jadwal ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, batalkan!'
            }).then((result) => {
                if (result.isConfirmed) {
                    \$wire.cancelSchedule();
                }
            });
        ");
    }

};
?>
<div class="pt-4 md:pt-8 mb-16 md:mb-20">
    <div class="flex flex-col items-center justify-center p-0 md:p-4">

        <h1 class="text-2xl md:text-4xl font-semibold text-gray-800 mb-8 md:mb-10 text-center">
            Jadwal Sidang {{ $type === 'final' ? 'Skripsi' : 'Proposal' }}
        </h1>

        @if($selectedScheduleId && $this->selectedSchedule && now()->greaterThan($this->selectedSchedule->deadline))
            <div class="w-full max-w-sm sm:max-w-lg bg-gray-100 p-6 md:p-10 rounded-lg shadow-xl relative">
                @if($lokasi && $tanggal && $jam)
                    <div class="text-center space-y-3 md:space-y-4">
                        <p class="text-xl md:text-3xl font-semibold text-gray-800">{{ $tanggal }}</p>
                        <p class="text-2xl md:text-4xl font-bold text-gray-900">Pk: {{ $jam }}</p>
                        <p class="text-lg md:text-xl font-medium text-gray-700">{{ $lokasi }}</p>
                    </div>
                @else
                    <span class="block text-center w-full py-2 text-lg md:text-xl text-white font-medium rounded-lg shadow-lg bg-green-600">
                        {{ $status }}
                    </span>
                @endif
            </div>
        @else

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 w-full">

                @foreach($schedules as $i => $s)
                    @php
                        $i++;
                        $start = $s['start_date']->locale('id');
                        $end = $s['end_date']->locale('id');
                        $deadline = $s['deadline']->locale('id');
                        $now = now();
                        $canAction = $now->lessThan($deadline);
                    @endphp

                    <div class="bg-gray-100 p-4 rounded-lg shadow">

                        <h2 class="text-lg font-semibold text-gray-800 mb-2">
                            Sidang {{ $type == 'proposal' ? 'Proposal' : 'Skripsi' }} {{ $i }}
                        </h2>

                        <p class="text-sm text-gray-700">Akhir Pendaftaran: {{ $deadline->translatedFormat('d F Y') }}</p>
                        <p class="text-sm text-gray-700">Sidang Mulai: {{ $start->translatedFormat('d F Y') }}</p>
                        <p class="text-sm text-gray-700 mb-4">Sidang Berakhir: {{ $end->translatedFormat('d F Y') }}</p>

                        @if($canAction)
                            @if($selectedScheduleId == $s['id'])
                                <button
                                    wire:click="confirmCancel"
                                    class="w-full bg-red-600 text-white py-2 rounded cursor-pointer">
                                    Cancel
                                </button>
                            @else
                                <button
                                    id="btn-choose-{{ $s['id'] }}"
                                    wire:click="confirmChoose('{{ $s['id'] }}')"
                                    class="w-full bg-blue-600 text-white py-2 rounded cursor-pointer">
                                    Daftar
                                </button>
                            @endif
                        @else
                            <div class="text-center py-2 text-gray-500">Tidak tersedia</div>
                        @endif
                    </div>
                @endforeach

            </div>
        @endif

    </div>

    <script>
    window.addEventListener('toast', e => {
        Swal.fire({
            toast: true,
            icon: e.detail.type,
            title: e.detail.message,
            position: 'top-end',
            timer: 3000,
            showConfirmButton: false,
        });
    });
    </script>

</div>
