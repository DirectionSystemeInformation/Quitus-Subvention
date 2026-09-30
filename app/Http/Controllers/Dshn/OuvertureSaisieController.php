<?php

namespace App\Http\Controllers\Dshn;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\OuvertureSaisie;
use App\Models\User;
use App\Support\PeriodeSaisie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Ouvertures exceptionnelles de saisie hors exercice, réservées à
 * l'administration (voir PeriodeSaisie).
 */
class OuvertureSaisieController extends Controller
{
    public function store(Request $request, User $federation)
    {
        abort_unless($federation->role === 'federation', 404);

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(PeriodeSaisie::LIBELLES))],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'expires_on' => ['nullable', 'date', 'after_or_equal:today'],
            'motif' => ['required', 'string', 'max:255'],
        ], [
            'expires_on.after_or_equal' => 'La date limite ne peut pas être passée.',
        ], [
            'type' => 'type de saisie',
            'year' => 'année',
            'expires_on' => 'date limite',
            'motif' => 'motif',
        ]);

        if ((int) $data['year'] === PeriodeSaisie::anneeDeDroit($data['type'])) {
            return back()->withInput()->withErrors(['year' => 'L’année '.$data['year'].' est déjà ouverte de droit pour cette saisie.']);
        }

        OuvertureSaisie::updateOrCreate(
            ['user_id' => $federation->id, 'type' => $data['type'], 'year' => $data['year']],
            ['motif' => $data['motif'], 'expires_on' => $data['expires_on'] ?? null, 'granted_by' => Auth::id()]
        );

        $libelle = PeriodeSaisie::LIBELLES[$data['type']];
        ActivityLog::record(
            'updated',
            "a ouvert exceptionnellement la saisie « {$libelle} » {$data['year']} pour {$federation->federation_name} (motif : {$data['motif']})",
            $federation->id,
            $federation->federation_name
        );

        return redirect()->to(role_route('federations.show', $federation))->withFragment('ouvertures')
            ->with('status', "Saisie « {$libelle} » {$data['year']} ouverte pour {$federation->federation_name}.");
    }

    public function destroy(OuvertureSaisie $ouverture)
    {
        $federation = $ouverture->federation;
        $libelle = PeriodeSaisie::LIBELLES[$ouverture->type] ?? $ouverture->type;
        $ouverture->delete();

        ActivityLog::record(
            'updated',
            "a refermé la saisie « {$libelle} » {$ouverture->year} pour {$federation->federation_name}",
            $federation->id,
            $federation->federation_name
        );

        return redirect()->to(role_route('federations.show', $federation))->withFragment('ouvertures')
            ->with('status', "Saisie « {$libelle} » {$ouverture->year} refermée.");
    }
}
