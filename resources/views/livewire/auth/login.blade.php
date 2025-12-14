<?php

use App\Services\Auth\AuthService;
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

    public bool $remember = false;

    public function login(AuthService $authService): void
    {
        \Log::info('masuk login');
        $this->validate();

        \Log::info('Validate done');

        $guard = $this->role;
        $domain = $guard === 'student' ? config('domains.student') : config('domains.lecturer');
        $fullEmail = $this->nrp . $domain;

        $user = $authService->attemptLogin(['email' => $fullEmail, 'password' => $this->password], $guard, $this->remember);

        \Log::info('Attempt login done', ['user' => $user]);
        if (!$user) {
            throw ValidationException::withMessages([
                'nrp' => __('auth.failed'),
            ]);
        }

        $redirectRoute = $guard === 'student' ? 'student.dashboard' : 'lecturer.dashboard';
        \Log::info('About to redirect', [
            Auth::user(),
            session()->all(),
            'user' => $user,
            'route' => $redirectRoute,
            'url' => route($redirectRoute),
            'auth_check' => auth('student')->check(),
        ]);
        $this->redirect(route($redirectRoute), navigate: false);
    }
};
?>

<div class="flex flex-col lg:flex-row min-h-screen text-black">

    <div id="login-container-mobile"
         class="w-full lg:w-1/3 bg-white flex items-center justify-center min-h-screen p-8">

        <div class="w-full max-w-sm">
            {{-- Header --}}
            <div class="flex justify-center"><img class="pb-6 w-64" src="{{ asset('assets/logopcubiru.png') }}" alt="logopcubiru"></div>
            <h2 class="text-lg font-medium text-gray-700 mb-4">
                Pendaftaran & Penjadwalan Proposal Skripsi
            </h2>

            {{-- Form Livewire --}}
            <form wire:submit="login" class="flex flex-col gap-2">
                <div>
                    <label for="nrp" class="block text-sm font-medium text-gray-700">
                        NRP / Username
                    </label>

                    <div class="mb-4">
                        <input id="nrp" type="text" wire:model="nrp" required autofocus
                            placeholder="c14230001"
                            class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-700">
                    </div>

                    <div class="mt-1 flex">
                        <span class="inline-flex items-center px-3 text-sm text-gray-900 bg-gray-100 border border-r-0 border-gray-200 rounded-l-lg">
                            @
                        </span>
                        <select wire:model="role"
                            class="flex-1 px-4 py-3 bg-gray-100 border border-l-0 border-gray-200 rounded-r-lg focus:outline-none focus:ring-2 focus:ring-gray-700 text-sm">
                            <option value="student">{{ ltrim(config('domains.student'), '@') }}</option>
                            <option value="lecturer">{{ ltrim(config('domains.lecturer'), '@') }}</option>
                        </select>
                    </div>

                    @if ($nrp)
                        <p class="mt-2 text-xs text-gray-500">
                            Your full email is:
                            <span class="font-medium text-gray-700">
                                {{ $nrp }}{{ $role === 'student' ? config('domains.student') : config('domains.lecturer') }}
                            </span>
                        </p>
                    @endif

                    @error('nrp')
                        <span class="text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password
                    </label>
                    <input id="password" type="password" wire:model="password" required
                        class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-700"
                        placeholder="Password">

                    @error('password')
                        <span class="text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>
                <div class="flex flex-col gap-2">
                    {{-- Remember & Lupa Password --}}
                    <div class="flex items-center justify-between">
                        <label class="flex items-center text-sm">
                            <input type="checkbox" wire:model="remember" class="mr-2">
                            Remember me
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm text-gray-700 hover:text-gray-900">
                                Forgot password?
                            </a>
                        @endif
                    </div>

                    {{-- Tombol Login --}}
                    <button type="submit"
                        class="w-full bg-gray-700 text-white font-medium py-3 px-4 rounded-lg hover:bg-gray-800 transition-colors duration-300 cursor-pointer">
                        <span wire:loading.remove>LOG IN</span>
                        <span wire:loading>Processing...</span>
                    </button>

                    <a href="{{ route('google.redirect') }}"
                    class="w-full flex items-center justify-center gap-2 border border-gray-300 py-3 px-4 rounded-lg hover:bg-gray-100 transition-colors duration-300">
                        <img src="{{ asset('assets/logo/google.svg') }}" alt="Google" class="w-5 h-5">
                        Login with Google
                    </a>

                    <p class="text-xs text-center text-gray-600">
                        Belum punya akun? Login dengan Google atau
                        <a href="{{ route('signup') }}" class="text-gray-900 font-medium hover:underline">
                            daftar di sini
                        </a>.
                    </p>

                    {{-- Info tambahan --}}
                    <p class="text-xs sm:text-sm mt-3 text-gray-600">
                        Keterangan pengisian username: <br>
                        - Mahasiswa : sesuai email di <strong>john.petra.ac.id</strong> <br>
                        - Dosen : sesuai email di <strong>peter.petra.ac.id</strong>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <div class="hidden lg:block lg:w-2/3 bg-cover bg-center"
         style="background-image: url('https://images.unsplash.com/photo-1521737711867-e3b97375f902?q=80&w=2574&auto=format&fit=crop');">
    </div>
</div>
