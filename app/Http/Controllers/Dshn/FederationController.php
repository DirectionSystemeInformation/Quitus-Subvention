<?php

namespace App\Http\Controllers\Dshn;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\OuvertureSaisie;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class FederationController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr(trim((string) $request->query('q')), 0, 150);
        $status = in_array($request->query('statut'), ['pending', 'active', 'rejected'], true) ? $request->query('statut') : 'tous';
        $query = User::where('role', 'federation')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('federation_name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('arrete_numero', 'like', '%'.$search.'%')));
        $counts = (clone $query)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->all();
        $counts = array_replace(['pending' => 0, 'active' => 0, 'rejected' => 0], $counts);
        $counts['total'] = array_sum($counts);
        $federations = $query
            ->withCount(['reports' => fn ($q) => $q->where('status', '!=', 'brouillon')])
            ->when($status !== 'tous', fn ($q) => $q->where('status', $status))
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'active' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('dshn.federations', compact('federations', 'search', 'status', 'counts'));
    }

    public function show(User $federation)
    {
        abort_unless($federation->role === 'federation', 404);

        $reports = $federation->reports()->where('status', '!=', 'brouillon')->orderByDesc('year')->orderBy('type')->get();

        $stats = [
            'total' => $reports->count(),
            'valide' => $reports->where('status', 'valide')->count(),
            'soumis' => $reports->where('status', 'soumis')->count(),
            'rejete' => $reports->where('status', 'rejete')->count(),
        ];

        $logs = ActivityLog::where('subject_id', $federation->id)
            ->where('subject_name', $federation->federation_name)->latest()->take(20)->get();

        // Ouvertures exceptionnelles de saisie, gérées par l'administration.
        $ouvertures = OuvertureSaisie::where('user_id', $federation->id)->with('grantedBy')
            ->orderByDesc('year')->orderBy('type')->get();

        return view('dshn.federation-show', compact('federation', 'reports', 'stats', 'logs', 'ouvertures'));
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

        return redirect()->to(role_route('federations.index'))->with('status', "Le compte de {$name} a été supprimé.");
    }
}
