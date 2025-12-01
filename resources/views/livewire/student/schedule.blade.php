<?php

use App\Traits\WithAuthUser;
use Livewire\Volt\Component;

new class extends Component {
    use WithAuthUser;

    public string $type = 'proposal'; // proposal | final

    public $presentation = null;
    public string $tanggal = '';
    public string $jam = '';
    public string $lokasi = '';
    public string $status = '';

    public function mount($type = 'proposal')
    {
        $this->type = $type;

        $relation = $type === 'final'
            ? 'finalPresentations'
            : 'proposalPresentations';

        $this->user->load([
            $relation => function ($q) {
                $periodId = $this->user->activePeriod()?->id;

                $q->when($periodId, fn ($query) =>
                    $query->where('period_id', $periodId)
                )->with(['venue']);
            }
        ]);

        $this->presentation = $this->user->$relation->first();

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
            . ' - ' .
            \Carbon\Carbon::parse($p->end_time)->format('H:i');

        $this->lokasi = $p->venue
            ? $p->venue->name . ' - ' . $p->venue->location
            : '-';

        $this->status = ucfirst($p->status ?? 'Terjadwal');
    }
};
?>

<div class="pt-4 md:pt-8 mb-16 md:mb-20">
    <div class="flex flex-col items-center justify-center p-0 md:p-4">

        <h1 class="text-2xl md:text-4xl font-semibold text-gray-800 mb-8 md:mb-10 text-center">
            Jadwal Sidang {{ $type === 'final' ? 'Skripsi' : 'Proposal' }}
        </h1>

        @if($presentation)
            <div class="w-full max-w-sm sm:max-w-lg bg-gray-100 p-6 md:p-10 rounded-lg shadow-xl relative">
                <div class="text-center space-y-3 md:space-y-4">
                    <p class="text-xl md:text-3xl font-semibold text-gray-800">
                        {{ $tanggal }}
                    </p>

                    <p class="text-2xl md:text-4xl font-bold text-gray-900">
                        Pk: {{ $jam }}
                    </p>

                    <p class="text-lg md:text-xl font-medium text-gray-700">
                        {{ $lokasi }}
                    </p>
                </div>

                <div class="mt-6 md:mt-8 pt-3 md:pt-4 border-t border-gray-300 text-center">
                    <span class="block w-full py-2 text-lg md:text-xl text-white font-medium rounded-lg shadow-lg bg-green-600">
                        {{ $status }}
                    </span>
                </div>
            </div>
        @else
            <div class="w-full max-w-sm sm:max-w-lg bg-gray-100 p-6 md:p-10 rounded-lg shadow-xl text-center">
                <p class="text-xl md:text-2xl font-semibold text-gray-700">
                    Belum dijadwalkan
                </p>
            </div>
        @endif

    </div>
</div>
