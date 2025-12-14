<?php

use App\Traits\WithAuthUser;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Services\CrudService;
use App\Services\PeriodService;
use App\Models\Period;

new #[Layout('components.layouts.auth')] class extends Component {
    use WithAuthUser;
    public $period;

    public function mount(CrudService $crud)
    {
        $this->period = Period::whereNull('archived_at')
            ->where('start_date', '<=', now())
            ->whereHas('proposalHearings', function ($q) {
                $q->where('deadline', '>=', now());
            })
            ->first();
    }
    public function register(PeriodService $periodService)
    {
        $status = $periodService->registerPeriod($this->user, $this->period);
        if($status){
            $this->redirectRoute('student.dashboard', navigate: true);
        }
        else{
            $this->dispatch('notify', type: 'error', message: 'Gagal register pada periode ini.');
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
                showConfirmButton: false
            });
        ");
    }
}; ?>
<div class="p-6 text-center min-h-screen flex flex-col justify-center items-center">
    @if ($period)
        <h2 class="text-2xl font-semibold mb-2">
            {{ $period->name }} is Open
        </h2>
        <p class="mb-4 text-white">Register Now</p>
        <button wire:click="register" 
            wire:loading.attr="disabled" 
            wire:target="register"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex items-center justify-center cursor-pointer">
        
            {{-- Saat tidak loading --}}
            <span wire:loading.remove wire:target="register">
                Register
            </span>

            {{-- Saat loading --}}
            <span wire:loading wire:target="register" class="flex items-center space-x-2">
                <svg class="animate-spin h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3.5-3.5L12 0v4a8 8 0 11-8 8h4z"></path>
                </svg>
                <span>Processing...</span>
            </span>
        </button>
    @else
        <p class="text-2xl text-white mb-5">No registration period is currently open.</p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="flex items-center justify-center w-full px-4 py-2.5 
                    bg-red-600 hover:bg-red-700 text-white font-semibold 
                    rounded-lg transition duration-200 shadow-sm hover:shadow-md cursor-pointer">
                Log out
            </button>
        </form>
    @endif
</div>