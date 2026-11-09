<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(private CartService $cartService) {}

    public function register(array $data, Request $request): array
    {
        $result = DB::transaction(function () use ($data, $request) {
            $user = User::create($data);

            return ['user' => $user, 'warnings' => $this->cartService->merge($user, $request->session()->get('cart_token'))];
        });

        Auth::guard('web')->login($result['user']);
        $request->session()->forget('cart_token');
        $request->session()->regenerate(true);

        return $result;
    }

    public function login(array $credentials, Request $request): array
    {
        if (! Auth::guard('web')->validate($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha inválidos.'],
            ]);
        }

        $user = Auth::guard('web')->getLastAttempted();
        $warnings = $this->cartService->merge($user, $request->session()->get('cart_token'));
        Auth::guard('web')->getProvider()->rehashPasswordIfRequired($user, $credentials);
        Auth::guard('web')->login($user);
        $request->session()->forget('cart_token');
        $request->session()->regenerate(true);

        return ['user' => $user, 'warnings' => $warnings];
    }

    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
