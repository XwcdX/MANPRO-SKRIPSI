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

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Forgot Password')" :description="__('Enter your email to receive a password reset link')" />

    @if (session('status'))
        <div class="p-4 text-sm text-green-700 bg-green-100 rounded-lg dark:bg-green-200 dark:text-green-800"
            role="alert">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" wire:submit="sendResetLink" class="flex flex-col gap-6">
        <flux:input wire:model="email" :label="__('Full Email Address')" type="email" required autofocus
            placeholder="c14230001@john.petra.ac.id" />

        <flux:button variant="primary" type="submit" class="w-full">
            <span wire:loading.remove>{{ __('Email Password Reset Link') }}</span>
            <span wire:loading>{{ __('Sending...') }}</span>
        </flux:button>
    </form>

    <div class="text-center text-sm">
        <flux:link :href="route('login')" wire:navigate>{{ __('Back to log in') }}</flux:link>
    </div>
</div>
