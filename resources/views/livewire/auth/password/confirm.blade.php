<?php
# FOR LATER IF NEEDED DON'T DELETE - T
use App\Traits\WithAuthUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    use WithAuthUser;

    public string $password = '';

    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (
            !Auth::guard($this->activeGuard)->validate([
                'email' => $this->user->email,
                'password' => $this->password,
            ])
        ) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $redirectRoute = $this->activeGuard === 'student' ? 'student.dashboard' : 'lecturer.dashboard';
        $this->redirectIntended(default: route($redirectRoute, absolute: false), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    ...
</div>
