<?php

namespace Database\Seeders\Talents;

use App\Models\Talents\Referentiel;
use Illuminate\Database\Seeder;

/**
 * Listes de valeurs du suivi des talents (onglet « 6_Dictionnaire_&_Listes » du canevas Excel).
 * Idempotent et sans données de fédération : peut être lancé sur la base de production.
 * Les disciplines, elles, sont gérées par chaque fédération dans le portail.
 */
class ReferentielSeeder extends Seeder
{
    public function run(): void
    {
        $listes = [
            'type_licence' => ['Nationale', 'Internationale', 'Double Licence', 'Ancienne Lic.'],
            'statut_carriere' => ['Actif', 'En pause', 'Retraité', 'Reconverti'],
            'niveau_competition' => ['Régional', 'National', 'Sous-régional', 'Continental', 'International', 'Mondial', 'Olympique'],
            'distinction' => ['Médaille d\'Or', 'Médaille d\'Argent', 'Médaille de Bronze', 'MVP du Match', 'Clean Sheet', 'Record National', 'Meilleur Joueur'],
            'statut_medical' => ['Aptitude Totale', 'Aptitude avec réserve', 'En Soins', 'Inapte temporaire', 'Inapte définitif'],
            'statut_contractuel' => ['Amateur', 'Amateur Espoir', 'Professionnel', 'Boursier Universitaire', 'Stagiaire Haute Performance', 'Sans contrat'],
            'type_bourse' => ['Bourse Olympique Nationale', 'Bourse Haute Performance', 'Bourse Détection Jeunes', 'Prise en charge Ministère', 'Bourse Solidarité Olympique (CIO)', 'Fonds d\'Insertion'],
            'motif_retraite' => ['Limite d\'âge', 'Blessure récurrente', 'Choix Personnel', 'Opportunité Professionnelle', 'Études / Reconversion'],
            'domaine_reconversion' => ['Encadrement Technique', 'Gestion & Administration', 'Entrepreneuriat', 'Secteur Privé / Autre', 'Médias & Communication', 'Formation de Jeunes'],
            'statut_accompagnement' => ['En attente', 'En cours de formation', 'Dossier Soumis', 'Reconverti', 'Autonome'],
            'region' => ['Boucle du Mouhoun', 'Cascades', 'Centre', 'Centre-Est', 'Centre-Nord', 'Centre-Ouest', 'Centre-Sud',
                'Est', 'Hauts-Bassins', 'Nord', 'Plateau-Central', 'Sahel', 'Sud-Ouest', 'Diaspora'],
        ];

        foreach ($listes as $type => $valeurs) {
            foreach ($valeurs as $ordre => $valeur) {
                Referentiel::firstOrCreate(['type' => $type, 'valeur' => $valeur], ['ordre' => $ordre]);
            }
        }
    }
}
