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

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Reset Password')" :description="__('Please enter your new password below')" />

    <form method="POST" wire:submit="resetPassword" class="flex flex-col gap-6">
        <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />
        <flux:input wire:model="password" :label="__('Password')" type="password" required autocomplete="new-password"
            placeholder="New Password" viewable />
        <flux:input wire:model="password_confirmation" :label="__('Confirm Password')" type="password" required
            autocomplete="new-password" placeholder="Confirm Password" viewable />

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full">
                <span wire:loading.remove>{{ __('Reset Password') }}</span>
                <span wire:loading>{{ __('Resetting...') }}</span>
            </flux:button>
        </div>
    </form>
</div>
