<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
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
            'identifiant' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['identifiant'])
            ->orWhere('arrete_numero', $credentials['identifiant'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'identifiant' => "Ces identifiants ne correspondent à aucun compte.",
            ]);
        }

        if ($user->role === 'federation' && $user->status === 'pending') {
            throw ValidationException::withMessages([
                'identifiant' => "Votre compte est en attente de validation par la DSHN. Vous serez notifié une fois votre compte activé.",
            ]);
        }

        if ($user->role === 'federation' && $user->status === 'rejected') {
            $reason = $user->rejection_reason
                ? " Motif : {$user->rejection_reason}."
                : '';

            throw ValidationException::withMessages([
                'identifiant' => "Votre demande de compte a été rejetée.{$reason} Veuillez contacter la Direction du Sport de Haut Niveau.",
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(
            $user->isAdmin() ? route('admin.dashboard') : ($user->isDshn() ? route('dshn.dashboard') : route('dashboard'))
        );
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'federation_name' => ['required', 'string', 'max:255'],
            'arrete_numero' => ['required', 'string', 'max:255', 'unique:users,arrete_numero'],
            'arrete_date' => ['required', 'date'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $data['federation_name'],
            'federation_name' => $data['federation_name'],
            'arrete_numero' => $data['arrete_numero'],
            'arrete_date' => $data['arrete_date'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'federation',
            'status' => 'pending',
        ]);

        ActivityLog::record(
            'created',
            "La fédération {$user->federation_name} a demandé la création de son compte",
            $user->id,
            $user->federation_name
        );

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
