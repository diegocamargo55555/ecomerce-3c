<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data, Request $request): User
    {
        abort_unless($request->hasSession(), 419, 'Inicie uma sessão pela loja antes de continuar.');
        $user = User::create($data);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return $user;
    }

    public function login(array $credentials, Request $request): User
    {
        abort_unless($request->hasSession(), 419, 'Inicie uma sessão pela loja antes de continuar.');
        if (! Auth::guard('web')->attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha inválidos.'],
            ]);
        }

        $request->session()->regenerate();

        return Auth::guard('web')->user();
    }

    public function logout(Request $request): void
    {
        abort_unless($request->hasSession(), 419, 'Inicie uma sessão pela loja antes de continuar.');
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
