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

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'federation_name' => [$user->isFederation() ? 'required' : 'nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['nullable', 'required_with:password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if (! empty($data['password'])) {
            if (! Hash::check($data['current_password'] ?? '', $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => 'Le mot de passe actuel est incorrect.',
                ]);
            }

            $user->password = Hash::make($data['password']);
        }

        $user->name = $data['name'];
        $user->email = $data['email'];

        if ($user->isFederation()) {
            $user->federation_name = $data['federation_name'];
        }

        $user->save();

        ActivityLog::record(
            'updated',
            "{$user->name} a mis à jour son profil",
            $user->id,
            $user->federation_name ?? $user->name
        );

        return back()->with('status', 'Votre profil a été mis à jour avec succès.');
    }
}
