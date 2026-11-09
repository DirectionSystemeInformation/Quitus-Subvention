<?php

namespace App\Http\Controllers\Talents\Admin;

use App\Http\Controllers\Controller;
use App\Models\Talents\Federation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

/**
 * Comptes du portail talents.
 *  - Comptes complémentaires (DTN, médecin, entraîneur...) : users.role = talents, gérés ici, sans accès à Quitus.
 *  - Comptes Quitus (fédérations, DSHN, cabinet...) : affichés en lecture seule, leur rôle talents est automatique.
 */
class UtilisateurController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:utilisateurs.gerer')];
    }

    public function index(Request $request)
    {
        $origine = $request->query('origine') === 'quitus' ? 'quitus' : 'talents';

        return view('suivi.admin.utilisateurs.index', [
            'origine' => $origine,
            'utilisateurs' => User::with('roles')
                ->when($origine === 'talents', fn ($q) => $q->where('role', 'talents'))
                ->when($origine === 'quitus', fn ($q) => $q->where('role', '!=', 'talents')->whereHas('roles'))
                ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($q) => $q->where('name', 'like', "%$v%")
                    ->orWhere('email', 'like', "%$v%")->orWhere('federation_name', 'like', "%$v%")))
                ->when($request->query('role'), fn ($q, $v) => $q->role($v))
                ->when($request->query('federation_id'), fn ($q, $v) => $q->where('talent_federation_id', $v))
                ->orderBy('name')->paginate(20)->withQueryString(),
            'roles' => Role::orderBy('name')->pluck('name'),
            'federations' => Federation::orderBy('sigle')->get(),
        ]);
    }

    public function create()
    {
        return $this->formulaire(new User(['status' => 'active']));
    }

    public function store(Request $request)
    {
        $donnees = $this->valider($request);
        $user = new User(collect($donnees)->except('password')->all());
        $user->forceFill(['role' => 'talents', 'status' => $donnees['status'], 'password' => Hash::make($donnees['password'])])->save();
        $user->syncRoles($request->input('roles', []));

        return redirect()->route('suivi.utilisateurs.index')->with('succes', "Compte {$user->email} créé.");
    }

    public function edit(User $utilisateur)
    {
        return $this->formulaire($this->compteTalents($utilisateur));
    }

    public function update(Request $request, User $utilisateur)
    {
        $utilisateur = $this->compteTalents($utilisateur);
        $donnees = $this->valider($request, $utilisateur);
        $motDePasse = $donnees['password'] ?? null;
        unset($donnees['password']);
        $utilisateur->fill($donnees)->forceFill(['status' => $donnees['status']]);
        if ($motDePasse) {
            $utilisateur->password = Hash::make($motDePasse);
        }
        $utilisateur->save();
        $utilisateur->syncRoles($request->input('roles', []));

        return redirect()->route('suivi.utilisateurs.index')->with('succes', 'Compte mis à jour.');
    }

    public function destroy(Request $request, User $utilisateur)
    {
        $utilisateur = $this->compteTalents($utilisateur);
        $utilisateur->delete();

        return back()->with('succes', 'Compte supprimé.');
    }

    /** Les comptes Quitus se gèrent dans Quitus : ici, seuls les comptes complémentaires sont modifiables. */
    private function compteTalents(User $user): User
    {
        abort_unless($user->isComptesTalents(), 404);

        return $user;
    }

    private function formulaire(User $user)
    {
        return view('suivi.admin.utilisateurs.form', [
            'u' => $user,
            'roles' => Role::where('name', '!=', 'Super administrateur')->orderBy('name')->pluck('name'),
            'federations' => Federation::orderBy('sigle')->get(),
        ]);
    }

    private function valider(Request $request, ?User $user = null): array
    {
        $donnees = $request->validate([
            'name' => ['required', 'max:150'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user?->id)],
            'telephone' => ['nullable', 'max:30'],
            'fonction' => ['nullable', 'max:150'],
            'talent_federation_id' => ['nullable', Rule::exists('talent_federations', 'id')],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            'roles' => ['array'],
            'roles.*' => [Rule::exists('talent_roles', 'name'), Rule::notIn(['Super administrateur'])],
        ]);
        unset($donnees['roles']);

        return [...$donnees, 'status' => $request->boolean('actif') ? 'active' : 'pending'];
    }
}
