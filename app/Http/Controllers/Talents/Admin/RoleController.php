<?php

namespace App\Http\Controllers\Talents\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Matrice rôles × permissions : chaque acteur (Ministère, fédération, DTN, médecin...) est paramétrable.
 */
class RoleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:roles.gerer')];
    }

    public function index(Request $request)
    {
        $roles = Role::with('permissions')->withCount('users')->orderBy('id')->get();

        return view('suivi.admin.roles', [
            'roles' => $roles,
            'role' => $roles->firstWhere('id', $request->integer('role')) ?? $roles->first(),
            'modules' => config('talents.modules'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'max:100', 'unique:roles,name'], 'copie_de' => ['nullable', 'exists:roles,id']]);
        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        if (! empty($data['copie_de'])) {
            $role->syncPermissions(Role::findById($data['copie_de'])->permissions);
        }

        return redirect()->route('suivi.roles.index', ['role' => $role->id])->with('succes', 'Rôle créé.');
    }

    public function update(Request $request, Role $role)
    {
        abort_if($role->name === 'Super administrateur', 422, 'Le rôle Super administrateur conserve toutes les permissions.');
        $permissions = Permission::whereIn('name', $request->input('permissions', []))->get();
        $role->syncPermissions($permissions);

        return back()->with('succes', "Permissions du rôle « {$role->name} » enregistrées.");
    }

    public function destroy(Role $role)
    {
        abort_if($role->name === 'Super administrateur' || $role->users()->exists(), 422, 'Rôle protégé ou encore attribué.');
        $role->delete();

        return redirect()->route('suivi.roles.index')->with('succes', 'Rôle supprimé.');
    }
}
