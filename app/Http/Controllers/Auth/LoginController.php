<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

// TODO: Refactor to AuthController and remove other scaffold controllers
// Token expiration is configured globally in config/sanctum.php (expiration: 10080 = 7 days).
class LoginController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): array
    {
        $request->authenticate();

        $user = $request->user();

        $token = $user->createToken('HSMS_auth_token')->plainTextToken;

        return [
            'user' => [
                'id' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
            ],
            'token' => $token
        ];
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        $user = $request->user();

        $user->currentAccessToken()->delete();

        return response()->noContent();
    }

    private function roleForUser($user): string
    {
        if (! empty($user->role)) {
            return strtolower((string) $user->role);
        }

        $text = strtolower(($user->name ?? '') . ' ' . ($user->email ?? ''));

        foreach (['principal', 'registrar', 'teacher', 'guidance', 'admin'] as $role) {
            if (str_contains($text, $role)) {
                return $role;
            }
        }

        return 'admin';
    }
}
