<?php

use App\Services\Auth\PasswordResetService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $email = '';

    public function sendResetLink(PasswordResetService $passwordResetService): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = $passwordResetService->sendResetLink($this->email);
        session()->flash('status', __($status));
        $this->reset('email');
    }
}; ?>

{{-- ======= TEMPLATE BARU (SAMA DENGAN RESET PASSWORD) ======= --}}
<div class="flex flex-col lg:flex-row min-h-screen text-black">

    {{-- 1. Form Lupa Password --}}
    <div id="login-container-mobile" class="w-full lg:w-1/3 bg-white min-h-screen flex items-center justify-center p-8 lg:p-5">
        <div class="w-full max-w-sm">
            <img class="pb-6" src="{{ asset('assets/logopcubiru.png') }}" alt="logopcubiru">
            <h2 class="text-xl text-gray-500 mb-2">Pendaftaran & Penjadwalan Proposal Skripsi</h2>
            <h3 class="text-2xl font-semibold text-gray-900 mb-3">Lupa Password</h3>
            <p class="text-gray-600 text-sm mb-6">Masukkan email Anda untuk menerima tautan reset password.</p>

            {{-- Alert sukses --}}
            @if (session('status'))
                <div class="p-4 text-sm text-green-700 bg-green-100 rounded-lg mb-4">
                    {{ session('status') }}
                </div>
            @endif

            <form wire:submit.prevent="sendResetLink" class="flex flex-col gap-4">
                {{-- Email --}}
                <div>
                    <input wire:model="email" type="email" placeholder="c14230001@john.petra.ac.id"
                        class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-lg 
                               focus:outline-none focus:ring-2 focus:ring-gray-700"
                        required autofocus>
                    @error('email')
                        <span class="text-sm text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Tombol Kirim --}}
                <div>
                    <button type="submit" 
                        class="w-full bg-gray-700 text-white font-medium py-3 px-4 rounded-lg 
                               hover:bg-gray-800 transition-colors duration-300"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ __('Kirim Tautan Reset Password') }}</span>
                        <span wire:loading>{{ __('Mengirim...') }}</span>
                    </button>
                </div>

                {{-- Link ke login --}}
                <div class="text-center text-sm mt-3">
                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-800 transition-colors duration-200" wire:navigate>
                        {{ __('Kembali ke Halaman Login') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- 2. Background Gambar (Desktop Only) --}}
    <div class="hidden lg:block lg:w-2/3 bg-cover bg-center" 
         style="background-image: url('https://images.unsplash.com/photo-1521737711867-e3b97375f902?q=80&w=2574&auto=format&fit=crop');">
    </div>
</div>
