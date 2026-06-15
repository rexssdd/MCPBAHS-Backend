<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Accept either an "email" field or a "username" field.
     * Both are treated as the login identifier and looked up
     * against the users.email column (the canonical login field).
     */
    public function rules(): array
    {
        return [
            'email'    => ['nullable', 'required_without:username', 'string'],
            'username' => ['nullable', 'required_without:email',    'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * FIX: The frontend sends the field as "username" (not "email"),
     * so we read whichever field is present. We then look up the user
     * by exact match on the email column OR the name column so that
     * both email addresses and plain usernames work.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Accept either field name the client sends.
        $login    = trim((string) ($this->input('email') ?: $this->input('username')));
        $password = (string) $this->input('password');

        if ($login === '') {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Look up by email first; fall back to exact name match so
        // accounts created with a plain "username" value still work.
        $user = User::query()
            ->where('email', $login)
            ->orWhere('name', $login)
            ->first();

        if (! $user || ! Hash::check($password, (string) $user->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        Auth::login($user, $this->boolean('remember'));
        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        $login = $this->input('email') ?: $this->input('username');

        return Str::transliterate(Str::lower((string) $login) . '|' . $this->ip());
    }
}