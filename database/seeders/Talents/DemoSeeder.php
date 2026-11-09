<?php

namespace Database\Seeders\Talents;

use App\Models\Talents\Bourse;
use App\Models\Talents\Competition;
use App\Models\Talents\Discipline;
use App\Models\Talents\Entraineur;
use App\Models\Talents\EvaluationPhysique;
use App\Models\Talents\Federation;
use App\Models\Talents\Licence;
use App\Models\Talents\Parcours;
use App\Models\Talents\Performance;
use App\Models\Talents\Reconversion;
use App\Models\Talents\SuiviMedical;
use App\Models\Talents\Talent;
use App\Models\User;
use App\Services\Talents\AccesTalents;
use Illuminate\Database\Seeder;

/**
 * Jeu de démonstration (onglets 1 à 5 du canevas Excel) utilisé par les tests.
 *
 * Il crée de VRAIS comptes Quitus (fédérations, DSHN, admin) : à ne jamais lancer sur la base de production,
 * les fédérations fictives apparaîtraient dans les campagnes de subvention. Mot de passe des comptes : "password".
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Comptes Quitus : leur rôle talents est déduit de leur rôle Quitus
        foreach ([
            ['admin@talents.bf', 'Administrateur Plateforme', 'admin'],
            ['ministere@talents.bf', 'Agent DSHN', 'dshn'],
            ['fbf@talents.bf', 'Fédération Burkinabè de Football', 'federation'],
            ['fba@talents.bf', 'Fédération Burkinabè d\'Athlétisme', 'federation'],
            ['fbb@talents.bf', 'Fédération Burkinabè de Basketball', 'federation'],
            ['fbc@talents.bf', 'Fédération Burkinabè de Cyclisme', 'federation'],
            ['fbj@talents.bf', 'Fédération Burkinabè de Judo', 'federation'],
            ['fbt@talents.bf', 'Fédération Burkinabè de Taekwondo', 'federation'],
        ] as [$email, $nom, $role]) {
            $user = User::where('email', $email)->first() ?? new User(['email' => $email]);
            $user->forceFill(['name' => $nom, 'password' => 'password', 'role' => $role, 'status' => 'active',
                'federation_name' => $role === 'federation' ? $nom : null])->save();
            AccesTalents::synchroniser($user);
        }

        // Disciplines et spécialités, telles que chaque fédération les aurait saisies
        foreach ([
            'Athlétisme' => ['FBA', false, ['100m / 200m', '400m', '800m / 1500m', 'Saut en longueur', 'Lancer de poids', 'Marathon']],
            'Basketball' => ['FBB', true, ['Meneur (PG)', 'Arrière (SG)', 'Ailier (SF)', 'Ailier fort (PF)', 'Pivot (C)']],
            'Cyclisme' => ['FBC', false, ['Route / Sprinteur', 'Route / Grimpeur', 'Contre-la-montre', 'VTT']],
            'Football' => ['FBF', true, ['Gardien de but', 'Défenseur Central', 'Latéral', 'Milieu de terrain', 'Ailier', 'Attaquant']],
            'Judo' => ['FBJ', false, ['-52 kg', '-57 kg', '-63 kg', '-73 kg', '-81 kg', '-90 kg', '+100 kg']],
            'Taekwondo' => ['FBT', false, ['-58 kg', '-68 kg', '-80 kg', '+80 kg']],
        ] as $nom => [$sigle, $collectif, $specialites]) {
            $discipline = Discipline::firstOrCreate(['nom' => $nom], [
                'federation_id' => Federation::where('sigle', $sigle)->value('id'), 'collectif' => $collectif,
            ]);
            foreach ($specialites as $specialite) {
                $discipline->specialites()->firstOrCreate(['nom' => $specialite]);
            }
        }

        $fed = fn (string $sigle) => Federation::where('sigle', $sigle)->value('id');
        $disc = fn (string $nom) => Discipline::where('nom', $nom)->value('id');

        // Comptes complémentaires du portail talents (sans accès aux subventions)
        foreach ([
            ['dtn.fbf@talents.bf', 'DTN Football', 'Direction Technique (DTN)', 'FBF'],
            ['medecin@talents.bf', 'Dr OUEDRAOGO (Médecin du sport)', 'Médical & préparation physique', null],
            ['observateur@talents.bf', 'CNOSB Observateur', 'Observateur national', null],
        ] as [$email, $nom, $role, $sigle]) {
            $user = User::where('email', $email)->first() ?? new User(['email' => $email]);
            $user->forceFill(['name' => $nom, 'password' => 'password', 'role' => 'talents', 'status' => 'active',
                'talent_federation_id' => $sigle ? $fed($sigle) : null])->save();
            $user->syncRoles([$role]);
        }

        if (Talent::exists()) {
            return;
        }

        $coachs = [];
        foreach ([['KABORE', 'M.', 'Athlétisme', 'FBA', 'RC Bobo-Dioulasso', 'Burkina Faso'],
            ['SMITH', 'J.', 'Basketball', 'FBB', 'Duke University', 'USA'],
            ['LE BRIS', 'R.', 'Football', 'FBF', 'FC Lorient', 'France'],
            ['TRAORE', 'S.', 'Judo', 'FBJ', 'USFA Judo', 'Burkina Faso'],
            ['DUPONT', 'P.', 'Taekwondo', 'FBT', 'INSEP Paris', 'France']] as [$n, $p, $d, $f, $s, $pays]) {
            $coachs[$d] = Entraineur::create(['nom' => $n, 'prenoms' => $p, 'discipline_id' => $disc($d),
                'federation_id' => $fed($f), 'structure' => $s, 'pays' => $pays])->id;
        }

        // Onglet 1 : talents & licences
        $talents = [
            ['SAWADOGO', 'Issa', '2004-05-14', 'Masculin', 'Athlétisme', 'FBA', '100m / 200m', 'Nationale', 'FBA-2026-089', null, null, null, 'Actif', 'Validé', 'Hauts-Bassins'],
            ['OUEDRAOGO', 'Aminata', '2005-11-22', 'Féminin', 'Basketball', 'FBB', 'Meneur (PG)', 'Internationale', 'FBB-2025-102', 'FIBA-USA-88392', 'Duke Univ. (NCAA / USA)', 'USA', 'Actif', 'Validé', 'Diaspora'],
            ['KABORE', 'Yacouba', '2003-08-03', 'Masculin', 'Football', 'FBF', 'Défenseur Central', 'Internationale', 'FBF-2024-441', 'FIFA-ID-99210', 'FC Lorient (France)', 'France', 'Actif', 'Validé', 'Diaspora'],
            ['SANOU', 'Awa', '2007-01-19', 'Féminin', 'Judo', 'FBJ', '-63 kg', 'Nationale', 'FBJ-2026-014', null, null, null, 'Actif', 'Validé', 'Centre'],
            ['ZONGO', 'Moussa', '2006-09-30', 'Masculin', 'Cyclisme', 'FBC', 'Route / Sprinteur', 'Nationale', 'FBC-2026-055', null, null, null, 'Actif', 'En attente API', 'Centre-Est'],
            ['COMPAORE', 'Sibiri', '2002-12-05', 'Masculin', 'Taekwondo', 'FBT', '-80 kg', 'Internationale', 'FBT-2023-011', 'WT-ID-55102', 'INSEP Paris (France)', 'France', 'Actif', 'Validé', 'Diaspora'],
            ['KOUANDA', 'Rachid', '1991-03-18', 'Masculin', 'Football', 'FBF', 'Milieu de terrain', 'Ancienne Lic.', 'FBF-2012-005', 'FIFA-ID-44102', 'Ex-ASFA Yennenga', 'Burkina Faso', 'Retraité', 'Validé', 'Centre'],
            ['DABO', 'Clarisse', '1993-06-02', 'Féminin', 'Basketball', 'FBB', 'Ailier (SF)', 'Ancienne Lic.', 'FBB-2011-031', null, null, null, 'Reconverti', 'Validé', 'Centre'],
            ['TRAORE', 'Bocar', '1990-10-11', 'Masculin', 'Football', 'FBF', 'Attaquant', 'Ancienne Lic.', 'FBF-2010-118', null, null, null, 'Retraité', 'Validé', 'Hauts-Bassins'],
            ['ILBOUDO', 'Fatimata', '2009-04-07', 'Féminin', 'Athlétisme', 'FBA', '800m / 1500m', 'Nationale', 'FBA-2026-112', null, null, null, 'Actif', 'En attente validation', 'Centre-Nord'],
        ];

        $ids = [];
        foreach ($talents as $i => [$nom, $prenoms, $naiss, $genre, $d, $f, $spec, $typeLic, $numNat, $numInt, $club, $paysClub, $carriere, $profil, $region]) {
            $talent = Talent::create([
                'nom' => $nom, 'prenoms' => $prenoms, 'date_naissance' => $naiss, 'genre' => $genre,
                'federation_id' => $fed($f), 'discipline_id' => $disc($d), 'specialite' => $spec,
                'entraineur_id' => $coachs[$d] ?? null, 'statut_carriere' => $carriere, 'statut_profil' => $profil,
                'region' => $region, 'haut_potentiel' => in_array($i, [0, 1, 5]),
            ]);
            $ids[$nom.' '.$prenoms] = $talent->id;

            Licence::create(['talent_id' => $talent->id, 'type_licence' => $typeLic, 'portee' => 'Nationale',
                'numero' => $numNat, 'organisme' => $f, 'date_delivrance' => substr($numNat, -8, 4).'-09-01',
                'date_expiration' => $carriere === 'Actif' ? now()->addMonths($i + 1) : '2025-08-31',
                'statut' => $carriere === 'Actif' ? 'Active' : 'Expirée',
                'verification_api' => $profil === 'En attente API' ? 'Non vérifiée' : 'Vérifiée']);

            if ($numInt) {
                Licence::create(['talent_id' => $talent->id, 'type_licence' => $typeLic, 'portee' => 'Internationale',
                    'numero' => $numInt, 'organisme' => strtok($numInt, '-'), 'club' => $club, 'pays_club' => $paysClub,
                    'statut' => $carriere === 'Actif' ? 'Active' : 'Expirée', 'verification_api' => 'Vérifiée']);
            }
        }

        // Onglet 2 : performances
        foreach ([
            ['SAWADOGO Issa', '2026-03-12', 'Meeting International de Dakar', 'Continental', 'Dakar', 'Sénégal', 'Athlétisme', '100m', '10s 24', '1er', 'Médaille d\'Or', true, 'Minima dépassé pour les Championnats d\'Afrique'],
            ['OUEDRAOGO Aminata', '2026-02-28', 'NCAA Regular Season', 'International', 'Durham', 'USA', 'Basketball', 'Meneur (PG)', '18 pts, 7 ast', 'Victoire', 'MVP du Match', false, 'Excellente vision de jeu sous pression'],
            ['KABORE Yacouba', '2026-04-05', 'Ligue 2 BKT', 'International', 'Lorient', 'France', 'Football', 'Défenseur Central', '90 min jouées', 'Titulaire', 'Clean Sheet', false, 'Solide dans les duels aériens (8/10)'],
            ['SANOU Awa', '2026-01-20', 'Tournoi National Senior', 'National', 'Ouagadougou', 'Burkina Faso', 'Judo', '-63 kg', '3 victoires / 0 déf.', '1ère', 'Médaille d\'Or', false, 'Technique d\'Ippon très affûtée'],
            ['COMPAORE Sibiri', '2026-03-25', 'Open de Paris Taekwondo', 'International', 'Paris', 'France', 'Taekwondo', '-80 kg', '3ème place', '3ème', 'Médaille de Bronze', false, 'Bonne gestion tactique du 3ème round'],
        ] as [$t, $date, $comp, $niveau, $lieu, $pays, $d, $epreuve, $perf, $rang, $dist, $minima, $obs]) {
            $competition = Competition::firstOrCreate(['nom' => $comp], ['niveau' => $niveau, 'lieu' => $lieu,
                'pays' => $pays, 'discipline_id' => $disc($d), 'date_debut' => $date]);
            Performance::create(['talent_id' => $ids[$t], 'competition_id' => $competition->id, 'date_evenement' => $date,
                'epreuve' => $epreuve, 'performance' => $perf, 'rang' => $rang, 'distinction' => $dist,
                'minima_atteint' => $minima, 'observations_dtn' => $obs]);
        }

        // Onglet 3 : évaluations physiques + suivi médical
        foreach ([
            ['SAWADOGO Issa', '2026-01-10', 182, 74, 19.5, 68, 3.82, 'Aptitude Totale', null, 0, 'Condition physique optimale'],
            ['OUEDRAOGO Aminata', '2026-01-15', 175, 65, 17.0, 58, 4.10, 'Aptitude Totale', null, 0, 'Très bonne endurance'],
            ['KABORE Yacouba', '2026-02-01', 188, 81, 16.5, 62, 4.15, 'En Soins', 'Entorse cheville droite', 14, 'Reprise progressive la semaine prochaine'],
            ['SANOU Awa', '2026-01-12', 168, 62, 16.0, 52, 4.25, 'Aptitude Totale', null, 0, 'Gainage et force en hausse'],
            ['COMPAORE Sibiri', '2026-02-10', 185, 78, 18.0, 65, 3.95, 'Aptitude Totale', null, 0, 'Excellente mobilité articulaire'],
        ] as [$t, $date, $taille, $poids, $vma, $detente, $sprint, $statut, $blessure, $jours, $avis]) {
            EvaluationPhysique::create(['talent_id' => $ids[$t], 'date_evaluation' => $date, 'taille_cm' => $taille,
                'poids_kg' => $poids, 'vma_kmh' => $vma, 'detente_cm' => $detente, 'sprint_30m_s' => $sprint,
                'evaluateur' => 'Cellule médicale nationale', 'avis' => $avis]);
            SuiviMedical::create(['talent_id' => $ids[$t], 'date_consultation' => $date, 'statut_medical' => $statut,
                'type_blessure' => $blessure, 'localisation' => $blessure ? 'Cheville droite' : null,
                'date_blessure' => $blessure ? $date : null,
                'date_retour_prevue' => $blessure ? now()->addDays($jours)->toDateString() : null,
                'jours_indisponibilite' => $jours, 'cloture' => ! $blessure, 'avis_medical' => $avis]);
        }

        // Onglet 4 : parcours & bourses
        foreach ([
            ['SAWADOGO Issa', 'RC Bobo-Dioulasso', 'Burkina Faso', 'Amateur Espoir', 'Bourse Olympique Nationale', 'Athlétisme', 'Licence 2 STAPS', 'Projet de stage en Europe en vue', 1500000],
            ['OUEDRAOGO Aminata', 'Duke University', 'USA', 'Boursier Universitaire', 'Prise en charge Ministère', 'Basketball', 'Bachelor Management', 'Excellente progression académique', 3000000],
            ['KABORE Yacouba', 'FC Lorient', 'France', 'Professionnel', null, 'Football', 'Pro', 'Transféré depuis Rahimo FC', null],
            ['SANOU Awa', 'USFA Judo', 'Burkina Faso', 'Amateur', 'Bourse Détection Jeunes', 'Judo', 'Terminale A4', 'Prépa Baccalauréat en parallèle', 600000],
            ['COMPAORE Sibiri', 'INSEP Paris', 'France', 'Stagiaire Haute Performance', 'Bourse Haute Performance', 'Taekwondo', 'Formation Pro', 'Objectif qualification JO', 2500000],
        ] as [$t, $club, $pays, $contrat, $bourse, $d, $scolaire, $comment, $montant]) {
            Parcours::create(['talent_id' => $ids[$t], 'saison' => '2025-2026', 'club' => $club, 'pays' => $pays,
                'statut_contractuel' => $contrat, 'entraineur_id' => $coachs[$d] ?? null,
                'niveau_scolaire' => $scolaire, 'commentaires' => $comment]);
            if ($bourse) {
                Bourse::create(['talent_id' => $ids[$t], 'type_bourse' => $bourse, 'montant' => $montant,
                    'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31', 'statut' => 'En cours']);
            }
        }
        Bourse::create(['talent_id' => $ids['KOUANDA Rachid'], 'type_bourse' => 'Fonds d\'Insertion',
            'montant' => 1200000, 'statut' => 'Demandée', 'observations' => 'Diplôme CAF A']);

        // Onglet 5 : retraite & reconversion
        foreach ([
            ['KOUANDA Rachid', 2025, 'Limite d\'âge', 'Licence en Droit', 'Devenir Entraîneur / Coach Certifié', 'Encadrement Technique', 'En cours de formation', 'Entraîneur Adjoint U17', 'Prise en charge stage DEF Football', 'En attente d\'attribution de Bourse d\'État pour diplôme CAF A'],
            ['DABO Clarisse', 2024, 'Blessure récurrente', 'Master en Marketing', 'Consultante en Marketing Sportif', 'Gestion & Administration', 'Reconverti', 'Membre Commission Marketing FBB', 'Autonome', 'Modèle réussi de reconversion dans l\'écosystème sportif'],
            ['TRAORE Bocar', 2025, 'Choix Personnel', 'BAC +2 Gestion', 'Création d\'Académie de Détection', 'Formation de Jeunes', 'Dossier Soumis', 'Promoteur d\'Académie', 'Appui Ministère (Fonds d\'Insertion)', 'Recherche de partenaires institutionnels et terrains de pratique'],
        ] as [$t, $annee, $motif, $etudes, $projet, $domaine, $statut, $role, $programme, $besoins]) {
            Reconversion::create(['talent_id' => $ids[$t], 'annee_fin_carriere' => $annee, 'motif_retraite' => $motif,
                'niveau_etudes' => $etudes, 'projet' => $projet, 'domaine' => $domaine,
                'statut_accompagnement' => $statut, 'role_actuel' => $role, 'programme_appui' => $programme,
                'besoins' => $besoins]);
        }
    }
}
