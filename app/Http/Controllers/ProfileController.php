<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        // Once a federation's account has been validated, its legal identity
        // (the information the DSHN checked to approve it) is locked: only
        // the login email stays self-service from here.
        $identityLocked = $user->isFederation() && $user->status === 'active';

        $data = $request->validate([
            'name' => [$user->isFederation() ? 'nullable' : 'required', 'string', 'max:255'],
            'federation_name' => [$user->isFederation() && ! $identityLocked ? 'required' : 'nullable', 'string', 'max:255'],
            'arrete_numero' => [$user->isFederation() && ! $identityLocked ? 'required' : 'nullable', 'string', 'max:255', Rule::unique('users', 'arrete_numero')->ignore($user->id)],
            'arrete_date' => [$user->isFederation() && ! $identityLocked ? 'required' : 'nullable', 'date'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->email = $data['email'];

        if ($user->isFederation()) {
            if (! $identityLocked) {
                $user->federation_name = $data['federation_name'];
                $user->name = $data['federation_name'];
                $user->arrete_numero = $data['arrete_numero'];
                $user->arrete_date = $data['arrete_date'];
            }
        } else {
            $user->name = $data['name'];
        }

        $user->save();

        ActivityLog::record(
            'updated',
            "{$user->name} a mis à jour ses informations",
            $user->id,
            $user->federation_name ?? $user->name
        );

        return back()->with('status', 'Vos informations ont été enregistrées.');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Le mot de passe actuel est incorrect.',
            ]);
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        ActivityLog::record(
            'updated',
            "{$user->name} a changé son mot de passe",
            $user->id,
            $user->federation_name ?? $user->name
        );

        return back()->with('status', 'Votre mot de passe a été modifié.');
    }
}
