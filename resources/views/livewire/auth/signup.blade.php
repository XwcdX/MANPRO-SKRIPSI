<?php

use App\Services\Auth\AuthService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use App\Models\Student;
use App\Models\Lecturer;

new #[Layout('components.layouts.auth')] class extends Component {

    #[Validate('required|string')]
    public string $name = '';

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string|min:8')]
    public string $password = '';

    #[Validate('required|string|same:password')]
    public string $password_confirmation = '';

    public function signup(AuthService $authService): void
    {
        try {
            $this->validate();

            if (str_contains($this->email, config('domains.student'))) {

                $role = 'student';

                $user = Student::create([
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => bcrypt($this->password),
                ]);

            } elseif (str_contains($this->email, 'petra.ac.id')) {

                $role = 'lecturer';

                $user = Lecturer::create([
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => bcrypt($this->password),
                ]);

            } else {
                throw new \Exception("Email tidak menggunakan domain kampus yang valid.");
            }

            Auth::guard($role)->login($user);

            $redirectRoute = $role === 'student'
                ? 'student.dashboard'
                : 'lecturer.dashboard';

            $this->redirect(route($redirectRoute), navigate: false);

        } catch (\Throwable $e) {

            $msg = addslashes($e->getMessage());

            $this->js("
                Swal.fire({
                    icon: 'error',
                    title: 'Signup gagal',
                    text: '$msg',
                    showCloseButton: true,
                    showConfirmButton: true,
                    confirmButtonText: 'Tutup'
                });
            ");
        }
    }
};

?>
<div class="flex flex-col lg:flex-row min-h-screen text-black">

    <div id="login-container-mobile"
         class="w-full lg:w-1/3 bg-white flex items-center justify-center min-h-screen p-8">

        <div class="w-full max-w-sm">

            <div class="flex justify-center">
                <img class="pb-6 w-64" src="{{ asset('assets/logopcubiru.png') }}" alt="logopcubiru">
            </div>

            <h2 class="text-lg font-medium text-gray-700 mb-4">
                Pendaftaran & Penjadwalan Proposal Skripsi
            </h2>

            {{-- SIGNUP FORM --}}
            <form wire:submit="signup" class="flex flex-col gap-2">

                {{-- NRP / USERNAME --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Name
                    </label>

                    <div class="mb-4">
                        <input id="name" type="name" wire:model="name" required autofocus
                            placeholder="Your name"
                            class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-lg 
                                focus:outline-none focus:ring-2 focus:ring-gray-700">
                    </div>

                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email
                    </label>

                    <div class="mb-4">
                        <input id="email" type="email" wire:model="email" required autofocus
                            placeholder="Your Email"
                            class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-lg 
                                focus:outline-none focus:ring-2 focus:ring-gray-700">
                    </div>

                    @error('email')
                        <span class="text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                {{-- PASSWORD --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input id="password" type="password" wire:model="password" required
                        class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-700"
                        placeholder="Password">

                    @error('password')
                        <span class="text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                {{-- CONFIRM PASSWORD --}}
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <input id="password_confirmation" type="password" wire:model="password_confirmation" required
                        class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-700"
                        placeholder="Confirm Password">

                    @error('password_confirmation')
                        <span class="text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                {{-- SIGNUP BUTTON --}}
                <button type="submit"
                    class="w-full mt-4 bg-gray-700 text-white font-medium py-3 px-4 rounded-lg hover:bg-gray-800 transition-colors duration-300">
                    <span wire:loading.remove>SIGN UP</span>
                    <span wire:loading>Processing...</span>
                </button>

                <p class="text-xs text-center text-gray-600">
                    Sudah punya akun?
                    <a href="{{ route('login') }}" class="text-gray-900 font-medium hover:underline">
                        Login di sini
                    </a>.
                </p>

            </form>
        </div>
    </div>

    <div class="hidden lg:block lg:w-2/3 bg-cover bg-center"
         style="background-image: url('https://images.unsplash.com/photo-1521737711867-e3b97375f902?q=80&w=2574&auto=format&fit=crop');">
    </div>
</div>
