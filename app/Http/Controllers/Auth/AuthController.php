<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => "Ces identifiants ne correspondent à aucun compte.",
            ]);
        }

        if ($user->role === 'federation' && $user->status === 'pending') {
            throw ValidationException::withMessages([
                'email' => "Votre compte est en attente de validation par la DSHN. Vous serez notifié une fois votre compte activé.",
            ]);
        }

        if ($user->role === 'federation' && $user->status === 'rejected') {
            throw ValidationException::withMessages([
                'email' => "Votre demande de compte a été rejetée. Veuillez contacter la Direction du Sport de Haut Niveau.",
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(
            $user->role === 'dshn' ? route('dshn.federations.index') : route('dashboard')
        );
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'federation_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        User::create([
            'name' => $data['name'],
            'federation_name' => $data['federation_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'federation',
            'status' => 'pending',
        ]);

        return redirect()->route('login')->with(
            'status',
            "Votre demande de compte a été enregistrée. Elle sera examinée par la DSHN avant activation."
        );
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
