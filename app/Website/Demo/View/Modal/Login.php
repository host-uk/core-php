<?php

declare(strict_types=1);

namespace Website\Demo\View\Modal;

use Core\Helpers\LoginRateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Login Page Component.
 *
 * Handles user authentication with rate limiting.
 */
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    /**
     * Attempt to authenticate the user.
     */
    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $limiter = app(LoginRateLimiter::class);

        if ($limiter->tooManyAttempts(request())) {
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', [
                    'seconds' => $limiter->availableIn(request()),
                ]),
            ]);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $limiter->increment(request());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $limiter->clear(request());

        session()->regenerate();

        $this->redirect('/hub', navigate: true);
    }

    #[Layout('demo::layouts.app', ['title' => 'Sign In'])]
    public function render()
    {
        return view('demo::web.login');
    }
}
