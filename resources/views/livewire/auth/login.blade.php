<?php

use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Validate('required|string|in:student,lecturer')]
    public string $role = 'student';

    #[Validate('required|string')]
    public string $nrp = '';

    #[Validate('required|string')]
    public string $password = '';

    public string $secret_login = '';

    public bool $remember = false;

    public function mount(): void
    {
        $this->secret_login = env('SECRET_LOGIN');
    }

    public function login(AuthService $authService): void
    {
        $this->validate();

        $guard = $this->role;
        $domain = $guard === 'student' ? config('domains.student') : config('domains.lecturer');
        $fullEmail = $this->nrp . $domain;

        $user = $authService->attemptLogin(['email' => $fullEmail, 'password' => $this->password], $guard, $this->remember);

        if (!$user) {
            throw ValidationException::withMessages([
                'nrp' => __('auth.failed'),
            ]);
        }

        Session::regenerate();

        $redirectRoute = $guard === 'student' ? 'student.dashboard' : 'lecturer.dashboard';
        $this->redirect(url: route($redirectRoute, absolute: false), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Log in to your account')" :description="__('Enter your credentials below to log in')" />

    <form method="POST" wire:submit="login" class="flex flex-col gap-6">
        <div>
            <label for="nrp"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('NRP / Username') }}</label>
            <div class="mt-1 flex">
                <input id="nrp" type="text" wire:model.live="nrp" required autofocus autocomplete="nrp"
                    placeholder="c14230001"
                    class="w-full border rounded-lg block disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 h-10 leading-[1.375rem] ps-3 pe-10 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500 shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5">

                <select wire:model.live="role" id="role"
                    class="relative -ml-px block w-auto rounded-l-none rounded-r-md border-0 bg-transparent py-2.5 pl-3 pr-9 text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 focus:z-10 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:text-white dark:ring-zinc-700 dark:focus:ring-indigo-500 sm:text-sm sm:leading-6">
                    <option value="student">{{ config('domains.student') }}</option>
                    <option value="lecturer">{{ config('domains.lecturer') }}</option>
                </select>
            </div>

            @if ($nrp)
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Your full email is: <span
                        class="font-medium text-gray-700 dark:text-gray-200">{{ $nrp }}{{ $role === 'student' ? config('domains.student') : config('domains.lecturer') }}</span>
                </p>
            @endif
            @error('nrp')
                <span class="text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <flux:input wire:model="password" :label="__('Password')" type="password" required
                autocomplete="current-password" :placeholder="__('Password')" viewable />
        </div>

        <div class="flex items-center justify-between">
            <flux:checkbox wire:model="remember" :label="__('Remember me')" />

            @if (Route::has('password.request'))
                <flux:link class="text-sm" :href="route('password.request')" wire:navigate>
                    {{ __('Forgot your password?') }}
                </flux:link>
            @endif
        </div>

        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" class="w-full">
                <span wire:loading.remove>{{ __('Log in') }}</span>
                <span wire:loading>{{ __('Processing...') }}</span>
            </flux:button>
        </div>
    </form>
</div>
