<?php

namespace App\Http\Controllers\Dshn;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class FederationController extends Controller
{
    public function index()
    {
        $federations = User::where('role', 'federation')
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'active' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->get();

        return view('dshn.federations', compact('federations'));
    }

    public function validate_(User $federation)
    {
        abort_unless($federation->role === 'federation', 404);

        $federation->update(['status' => 'active', 'rejection_reason' => null]);

        ActivityLog::record(
            'validated',
            "a validé le compte de {$federation->federation_name}",
            $federation->id,
            $federation->federation_name
        );

        return back()->with('status', "Le compte de {$federation->federation_name} a été validé.");
    }

    public function bulkValidate(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $federations = User::where('role', 'federation')
            ->where('status', 'pending')
            ->whereIn('id', $data['ids'])
            ->get();

        foreach ($federations as $federation) {
            $federation->update(['status' => 'active']);

            ActivityLog::record(
                'validated',
                "a validé le compte de {$federation->federation_name}",
                $federation->id,
                $federation->federation_name
            );
        }

        $count = $federations->count();

        return back()->with('status', $count > 1
            ? "{$count} comptes ont été validés."
            : "{$count} compte a été validé.");
    }

    public function reject(Request $request, User $federation)
    {
        abort_unless($federation->role === 'federation', 404);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $federation->update(['status' => 'rejected', 'rejection_reason' => $data['rejection_reason']]);

        ActivityLog::record(
            'rejected',
            "a rejeté le compte de {$federation->federation_name} (motif : {$data['rejection_reason']})",
            $federation->id,
            $federation->federation_name
        );

        return back()->with('status', "Le compte de {$federation->federation_name} a été rejeté.");
    }

    public function update(Request $request, User $federation)
    {
        abort_unless($federation->role === 'federation', 404);

        $data = $request->validate([
            'federation_name' => ['required', 'string', 'max:255'],
            'arrete_numero' => ['required', 'string', 'max:255', Rule::unique('users', 'arrete_numero')->ignore($federation->id)],
            'arrete_date' => ['required', 'date'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($federation->id)],
            'status' => ['required', Rule::in(['pending', 'active', 'rejected'])],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $changes = [];
        if ($federation->federation_name !== $data['federation_name']) $changes[] = 'dénomination de la fédération';
        if ($federation->arrete_numero !== $data['arrete_numero']) $changes[] = "numéro de l'arrêté";
        if (optional($federation->arrete_date)->format('Y-m-d') !== $data['arrete_date']) $changes[] = "date de l'arrêté";
        if ($federation->email !== $data['email']) $changes[] = 'email';
        if ($federation->status !== $data['status']) $changes[] = 'statut';
        if (! empty($data['password'])) $changes[] = 'mot de passe';

        $federation->name = $data['federation_name'];
        $federation->federation_name = $data['federation_name'];
        $federation->arrete_numero = $data['arrete_numero'];
        $federation->arrete_date = $data['arrete_date'];
        $federation->email = $data['email'];
        $federation->status = $data['status'];

        if ($data['status'] !== 'rejected') {
            $federation->rejection_reason = null;
        }

        if (! empty($data['password'])) {
            $federation->password = Hash::make($data['password']);
        }

        $federation->save();

        if (! empty($changes)) {
            ActivityLog::record(
                'updated',
                "a modifié le compte de {$federation->federation_name} (" . implode(', ', $changes) . ')',
                $federation->id,
                $federation->federation_name
            );
        }

        return back()->with('status', "Le compte de {$federation->federation_name} a été mis à jour.");
    }

    public function destroy(User $federation)
    {
        abort_unless($federation->role === 'federation', 404);

        $name = $federation->federation_name;
        $id = $federation->id;
        $federation->delete();

        ActivityLog::record('deleted', "a supprimé le compte de {$name}", $id, $name);

        return back()->with('status', "Le compte de {$name} a été supprimé.");
    }
}
