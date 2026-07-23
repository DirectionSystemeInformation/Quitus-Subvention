<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    protected function statusMessage(string $status): string
    {
        return match ($status) {
            Password::RESET_LINK_SENT => "Si cette adresse correspond à un compte, un lien de réinitialisation vient de lui être envoyé.",
            Password::INVALID_USER => "Aucun compte ne correspond à cette adresse e-mail.",
            Password::INVALID_TOKEN => "Ce lien de réinitialisation est invalide ou a expiré. Veuillez en demander un nouveau.",
            Password::RESET_THROTTLED => "Veuillez patienter avant de redemander un lien de réinitialisation.",
            Password::PASSWORD_RESET => "Votre mot de passe a été réinitialisé. Vous pouvez maintenant vous connecter.",
            default => "Une erreur est survenue. Veuillez réessayer.",
        };
    }

    public function showRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT || $status === Password::INVALID_USER) {
            return back()->with('status', $this->statusMessage(Password::RESET_LINK_SENT));
        }

        return back()->withErrors(['email' => $this->statusMessage($status)]);
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $data,
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();

                ActivityLog::record(
                    'updated',
                    "{$user->name} a réinitialisé son mot de passe via le lien de récupération",
                    $user->id,
                    $user->federation_name ?? $user->name
                );
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', $this->statusMessage($status));
        }

        return back()->withErrors(['email' => $this->statusMessage($status)]);
    }
}
