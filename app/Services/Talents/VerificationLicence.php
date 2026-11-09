<?php

namespace App\Services\Talents;

use App\Models\Talents\Federation;
use App\Models\Talents\Licence;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Vérifie une licence auprès de l'API de la fédération émettrice.
 *
 * Contrat attendu : GET {api_url}/licences/{numero} → 200 + {"valide": true, "date_expiration": "AAAA-MM-JJ"}.
 * Sans API configurée, la vérification est enregistrée comme manuelle par l'utilisateur.
 */
class VerificationLicence
{
    /** @return array{0: bool, 1: string} */
    public function verifier(Licence $licence): array
    {
        $federation = Federation::where('sigle', $licence->organisme)->first()
            ?? $licence->talent->federation;

        if (! $federation?->api_url) {
            $this->marquer($licence, 'Vérifiée');

            return [true, 'Aucune API configurée pour '.($federation->sigle ?? $licence->organisme).' : vérification manuelle enregistrée.'];
        }

        try {
            $reponse = Http::withToken((string) $federation->api_token)->timeout(10)->acceptJson()
                ->get(rtrim($federation->api_url, '/').'/licences/'.urlencode($licence->numero));
        } catch (Throwable $e) {
            $this->marquer($licence, 'Échec');

            return [false, 'API de la fédération injoignable : '.$e->getMessage()];
        }

        if ($reponse->successful() && $reponse->json('valide') !== false) {
            if ($expiration = $reponse->json('date_expiration')) {
                $licence->date_expiration = $expiration;
            }
            $this->marquer($licence, 'Vérifiée');

            return [true, "Licence {$licence->numero} confirmée par l'API {$federation->sigle}."];
        }

        $this->marquer($licence, 'Échec');

        return [false, "Licence {$licence->numero} non reconnue par l'API {$federation->sigle} (HTTP {$reponse->status()})."];
    }

    private function marquer(Licence $licence, string $resultat): void
    {
        $licence->forceFill(['verification_api' => $resultat, 'verifie_le' => now()])->save();

        // Un profil bloqué en attente API passe en validation dès que sa licence est confirmée.
        $talent = $licence->talent;
        if ($resultat === 'Vérifiée' && $talent->statut_profil === 'En attente API') {
            $talent->update(['statut_profil' => 'En attente validation']);
        }
    }
}
