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
    private const ROLES = ['dshn', 'admin', 'dg', 'comite_arbitrage', 'ministre', 'dgf'];

    private const ROLE_LABELS = [
        'dshn' => 'agent DSHN',
        'admin' => 'administrateur',
        'dg' => 'Directeur Général',
        'comite_arbitrage' => "Comité d'arbitrage budgétaire",
        'ministre' => 'Ministre',
        'dgf' => 'DGF',
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

        // Chaque ligne du tableau "Comptes" est visible et modifiable en même temps ; les
        // champs sont donc suffixés par l'id du compte (name_12, email_12, ...) pour que
        // les erreurs de validation et les anciennes valeurs (old()) après un échec ne
        // s'appliquent qu'à la bonne ligne, jamais à la première ligne du tableau.
        $id = $user->id;

        $data = $request->validate([
            "name_$id" => ['required', 'string', 'max:255'],
            "email_$id" => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            "role_$id" => ['required', Rule::in(self::ROLES)],
            "password_$id" => ['nullable', 'confirmed', Password::defaults()],
        ], [], [
            "name_$id" => 'nom',
            "email_$id" => 'email',
            "role_$id" => 'rôle',
            "password_$id" => 'mot de passe',
        ]);

        $name = $data["name_$id"];
        $email = $data["email_$id"];
        $role = $data["role_$id"];
        $password = $data["password_$id"] ?? null;

        if ($user->id === Auth::id() && $role !== 'admin') {
            return back()->withErrors(["role_$id" => 'Vous ne pouvez pas retirer vos propres droits administrateur.'])->withInput();
        }

        $changes = [];
        if ($user->name !== $name) $changes[] = 'nom';
        if ($user->email !== $email) $changes[] = 'email';
        if ($user->role !== $role) $changes[] = 'rôle';
        if (! empty($password)) $changes[] = 'mot de passe';

        $user->name = $name;
        $user->email = $email;
        $user->role = $role;

        if (! empty($password)) {
            $user->password = Hash::make($password);
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
