<?php

use App\Services\Auth\PasswordResetService;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Locked]
    public string $token;

    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->string('email', '');
    }

    public function resetPassword(PasswordResetService $passwordResetService): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = $passwordResetService->resetPassword($this->only('email', 'password', 'password_confirmation', 'token'));

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('status', __($status));
            $this->redirectRoute('login', navigate: true);
            return;
        }

        $this->addError('email', __($status));
    }
}; ?>

{{-- ======= TEMPLATE BARU ======= --}}
<div class="flex flex-col lg:flex-row min-h-screen text-black">
    
    {{-- 1. Form Reset Password --}}
    <div id="login-container-mobile" class="w-full lg:w-1/3 bg-white min-h-screen flex items-center justify-center p-8 lg:p-5">
        <div class="w-full max-w-sm">
            <img class="pb-6" src="{{ asset('assets/logopcubiru.png') }}" alt="logopcubiru">
            <h2 class="text-xl text-gray-500 mb-2">Pendaftaran & Penjadwalan Proposal Skripsi</h2>
            <h3 class="text-2xl font-semibold text-gray-900 mb-3">Reset Password</h3>

            <form wire:submit.prevent="resetPassword" class="flex flex-col gap-4">
                
                {{-- Email --}}
                <div>
                    <input wire:model="email" type="email" placeholder="Email"
                        class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-lg 
                               focus:outline-none focus:ring-2 focus:ring-gray-700" required autocomplete="email">
                    @error('email')
                        <span class="text-sm text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Password Baru --}}
                <div>
                    <input wire:model="password" type="password" placeholder="New password"
                        class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-lg 
                               focus:outline-none focus:ring-2 focus:ring-gray-700" required autocomplete="new-password">
                    @error('password')
                        <span class="text-sm text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Konfirmasi Password --}}
                <div>
                    <input wire:model="password_confirmation" type="password" placeholder="Confirm password"
                        class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-lg 
                               focus:outline-none focus:ring-2 focus:ring-gray-700" required autocomplete="new-password">
                </div>

                {{-- Tombol Reset --}}
                <div>
                    <button type="submit" 
                        class="w-full bg-gray-700 text-white font-medium py-3 px-4 rounded-lg 
                               hover:bg-gray-800 transition-colors duration-300"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ __('RESET') }}</span>
                        <span wire:loading>{{ __('Resetting...') }}</span>
                    </button>
                </div>

                {{-- Status Message --}}
                @if (session('status'))
                    <div class="text-green-600 text-sm mt-2 text-center">
                        {{ session('status') }}
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- 2. Background Gambar (Desktop Only) --}}
    <div class="hidden lg:block lg:w-2/3 bg-cover bg-center" 
         style="background-image: url('https://images.unsplash.com/photo-1521737711867-e3b97375f902?q=80&w=2574&auto=format&fit=crop');">
    </div>
</div>
