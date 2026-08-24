<?php

namespace App\Http\Controllers\Dshn;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    private const ROLES = ['dshn', 'admin', 'dg', 'comite_arbitrage', 'ministre'];

    private const ROLE_LABELS = [
        'dshn' => 'agent DSHN',
        'admin' => 'administrateur',
        'dg' => 'Directeur Général',
        'comite_arbitrage' => "Comité d'arbitrage budgétaire",
        'ministre' => 'Ministre',
    ];

    public function index()
    {
        $users = User::whereIn('role', self::ROLES)->orderBy('name')->get();

        return view('dshn.users', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(self::ROLES)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'status' => 'active',
        ]);

        ActivityLog::record(
            'created',
            "a créé le compte {$user->name} (".self::ROLE_LABELS[$data['role']].')',
            $user->id,
            $user->name
        );

        return back()->with('status', 'Compte créé.');
    }

    public function update(Request $request, User $user)
    {
        abort_unless(in_array($user->role, self::ROLES, true), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(self::ROLES)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if ($user->id === Auth::id() && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Vous ne pouvez pas retirer vos propres droits administrateur.']);
        }

        $changes = [];
        if ($user->name !== $data['name']) $changes[] = 'nom';
        if ($user->email !== $data['email']) $changes[] = 'email';
        if ($user->role !== $data['role']) $changes[] = 'rôle';
        if (! empty($data['password'])) $changes[] = 'mot de passe';

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        if (! empty($changes)) {
            ActivityLog::record(
                'updated',
                "a modifié le compte {$user->name} (" . implode(', ', $changes) . ')',
                $user->id,
                $user->name
            );
        }

        return back()->with('status', 'Compte mis à jour.');
    }

    public function destroy(User $user)
    {
        abort_unless(in_array($user->role, self::ROLES, true), 404);

        if ($user->id === Auth::id()) {
            return back()->withErrors(['user' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }

        $name = $user->name;
        $id = $user->id;
        $user->delete();

        ActivityLog::record('deleted', "a supprimé le compte {$name}", $id, $name);

        return back()->with('status', 'Compte supprimé.');
    }
}
