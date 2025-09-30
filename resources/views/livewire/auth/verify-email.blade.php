<?php

use App\Services\Auth\VerificationService;
use App\Traits\WithAuthUser;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    use WithAuthUser;

    public function sendVerification(VerificationService $verificationService): void
    {
        if ($verificationService->sendVerificationLink($this->user)) {
            Session::flash('status', 'verification-link-sent');
        }
    }
}; ?>

<div class="flex flex-col gap-6 p-6">
    <x-auth-header :title="__('Verify Your Email Address')" :description="__(
        'Please click the verification link sent to your email. If you didn\'t receive it, we can send another.',
    )" />

    @if (session('status') == 'verification-link-sent')
        <div class="p-4 text-sm text-green-700 bg-green-100 rounded-lg dark:bg-green-200 dark:text-green-800"
            role="alert">
            {{ __('A new verification link has been sent to your email address.') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-4">
        <flux:button wire:click="sendVerification" variant="primary" class="w-full">
            <span wire:loading.remove wire:target="sendVerification">{{ __('Resend Verification Email') }}</span>
            <span wire:loading wire:target="sendVerification">{{ __('Sending...') }}</span>
        </flux:button>

        <form method="POST" action="{{ route($activeGuard . '.logout') }}" class="w-full">
            @csrf
            <flux:button type="submit" variant="secondary" class="w-full">
                {{ __('Log Out') }}
            </flux:button>
        </form>
    </div>
</div>
